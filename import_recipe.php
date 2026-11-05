<?php
// =========================================================================
// SECTION 1: INBOUND REQUEST HANDLING & HTML DOM EXTRACTION
// =========================================================================
require_once 'database.php';

// System Keys & Configuration Constants
define('OPENROUTER_API_KEY', 'ALERT ENTER OPENROUTER API KEY HERE');
define('LLM_MODEL_SLUG', 'google/gemini-2.5-flash'); 

$url = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['url'])) {
        $url = trim($_POST['url']);
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $url = trim($input['url'] ?? '');
    }
}

if (empty($url)) {
    echo json_encode(['success' => false, 'error' => 'No target URL address provided.']);
    exit;
}

try {
    // 1. Direct Web Scraping Engine via Native PHP cURL (Section 32)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Mandatory fallback configuration for InfinityFree
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    
    $raw_html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Isolate plain text walls to stay safely within model token limits (Section 34)
    if ($http_code === 200 && !empty($raw_html)) {
        $clean_text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $raw_html);
        $clean_text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $clean_text);
        $clean_text = strip_tags($clean_text);
        $clean_text = preg_replace('/\s+/', ' ', $clean_text); 
        $clean_text = substr($clean_text, 0, 15000); // Guard text budget bounds
    } else {
        $clean_text = "The server could not read raw HTML from this URL source path due to host restrictions. Source URL: " . $url;
    }
        // =========================================================================
    // SECTION 2: OPENROUTER INTELLECTUAL RECIPE PARSER & SCHEMATIC EXTRACTION
    // =========================================================================
    // Developer prompt forcing precise structural schema fields output
    $system_instruction = "You are a professional recipe processing automation engine. Analyze the raw webpage dump content or URL source provided and return a completely structured recipe strictly matching this precise JSON scheme format configuration:\n"
                        . "{\n"
                        . "  \"title\": \"Clear item recipe title name\",\n"
                        . "  \"course\": \"Starters, Mains, Desserts, or Sauces\",\n"
                        . "  \"cuisine\": \"Cultural food origin, e.g. Italian, Indian, Japanese\",\n"
                        . "  \"sub_category\": \"Dish grouping classification type, e.g. Pasta, Curries, Soups\",\n"
                        . "  \"ingredients\": {\n"
                        . "     \"Main Component\": [\"ingredient line 1\", \"ingredient line 2\"],\n"
                        . "     \"Sauce or Marinade\": [\"ingredient line 3\"]\n"
                        . "  },\n"
                        . "  \"steps\": [\"step processing item step 1\", \"step line 2\"]\n"
                        . "  }\n"
                        . "Rules: Rely only on information implicitly or explicitly mentioned. The 'ingredients' field MUST be an object grouping items under clear subheadings (e.g., 'For the Chicken', 'For the Slaw', 'For the Sauce'). If a dish has only one component, use 'General Ingredients' as the sole object key. The 'course' key field item string value MUST EXACTLY be one of these choices: Starters, Mains, Desserts, or Sauces. Do not include conversational markdown commentary outside of the JSON block.";

    $openrouter_url = "https://openrouter.ai/api/v1/chat/completions";
    $payload = [
        'model' => LLM_MODEL_SLUG,
        'max_tokens' => 2000,
        'response_format' => ['type' => 'json_object'],
        'messages' => [
            ['role' => 'system', 'content' => $system_instruction],
            ['role' => 'user', 'content' => "Target URL Link Reference: " . $url . "\nScraped Source Content Material Text Context:\n" . $clean_text]
        ]
    ];

    $ch_ai = curl_init();
    curl_setopt($ch_ai, CURLOPT_URL, $openrouter_url);
    curl_setopt($ch_ai, CURLOPT_POST, true); // <--- CRUCIAL FIX: Explicitly forces a POST request method
    curl_setopt($ch_ai, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_ai, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch_ai, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch_ai, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . OPENROUTER_API_KEY,
        "Content-Type: application/json"
    ]);

    $ai_response = curl_exec($ch_ai);
    $ai_http_code = curl_getinfo($ch_ai, CURLINFO_HTTP_CODE);
    curl_close($ch_ai);

    if ($ai_http_code !== 200 || empty($ai_response)) {
        throw new Exception("OpenRouter AI communication interface drop out failure (HTTP: $ai_http_code). Raw response: " . $ai_response);
    }

    $ai_data = json_decode($ai_response, true);
    $recipe_json_str = trim($ai_data['choices'][0]['message']['content'] ?? '{}'); // <--- STRUCTURAL FIX: OpenRouter returns choices array containing message nodes

    // Robust Sanitization: Strip any decorative markdown wrappers (```json ... ```) that the LLM might include
    if (preg_match('/^```json\s*(.*?)\s*```$/is', $recipe_json_str, $matches)) {
        $recipe_json_str = trim($matches[1]);
    }

    $recipe_data = json_decode($recipe_json_str, true);

    // Validation Guard Check
    if (empty($recipe_data) || !isset($recipe_data['title']) || !isset($recipe_data['ingredients'])) {
        throw new Exception("LLM returned incomplete text structure parameters mapping. Raw Output captured: " . substr($recipe_json_str, 0, 150));
    }

    // Capture social Open Graph preview images if visible inside raw HTML markup headers
    if (preg_match('/<meta\b[^>]*property=["\']og:image["\']\b[^>]*content=["\'](.*?)["\']/is', $raw_html, $img_matches)) {
        $recipe_data['image_url'] = $img_matches[1];
    } else {
        $recipe_data['image_url'] = null;
    }
    $recipe_data['source_url'] = $url;

    // =========================================================================
    // SECTION 3: AUTOMATED TAXONOMY LOOKUPS & TRANSACTIONAL DATABASE SEEDER
    // =========================================================================
    $pdo->beginTransaction();

    // 1. Course ID Lookup Verification Match (Section 16 / Phase 6 Lookups)
    $course_name = $recipe_data['course'] ?? 'Mains';
    $course_stmt = $pdo->prepare("SELECT id FROM courses WHERE name LIKE :name LIMIT 1");
    $course_stmt->execute([':name' => '%' . trim($course_name) . '%']);
    $course_id = $course_stmt->fetchColumn() ?: 2; // Default fallback to Mains (ID 2)

    // 2. Section 39 & 40: On-the-fly Cuisine Engine Creation
    $cuisine_name = !empty($recipe_data['cuisine']) ? trim($recipe_data['cuisine']) : "International";
    $cuisine_stmt = $pdo->prepare("SELECT id FROM cuisines WHERE name = :name LIMIT 1");
    $cuisine_stmt->execute([':name' => $cuisine_name]);
    $cuisine_id = $cuisine_stmt->fetchColumn();

    if (!$cuisine_id) {
        $ins_cuisine = $pdo->prepare("INSERT INTO cuisines (name) VALUES (:name)");
        $ins_cuisine->execute([':name' => $cuisine_name]);
        $cuisine_id = $pdo->lastInsertId();
    }

    // 3. Section 39 & 40: On-the-fly Sub-category Engine Creation
    $sub_cat_name = !empty($recipe_data['sub_category']) ? trim($recipe_data['sub_category']) : "General Dish";
    $sub_cat_stmt = $pdo->prepare("SELECT id FROM sub_categories WHERE name = :name LIMIT 1");
    $sub_cat_stmt->execute([':name' => $sub_cat_name]);
    $sub_category_id = $sub_cat_stmt->fetchColumn();

    if (!$sub_category_id) {
        $ins_sub = $pdo->prepare("INSERT INTO sub_categories (name) VALUES (:name)");
        $ins_sub->execute([':name' => $sub_cat_name]);
        $sub_category_id = $pdo->lastInsertId();
    }

        // =========================================================================
    // SECTION 41 REWRITE: TWO-TIER PARENT-CHILD AUTOMATED FOLDER MAPPER
    // =========================================================================
    
    // Step A: Resolve or auto-generate the root parent folder segment (The Course name, e.g., "Mains")
    $parent_folder_name = trim($course_name);
    $parent_check_stmt = $pdo->prepare("SELECT id FROM folders WHERE name = :name AND parent_id IS NULL LIMIT 1");
    $parent_check_stmt->execute([':name' => $parent_folder_name]);
    $parent_folder_id = $parent_check_stmt->fetchColumn();

    if (!$parent_folder_id) {
        $ins_parent = $pdo->prepare("INSERT INTO folders (name, parent_id) VALUES (:name, NULL)");
        $ins_parent->execute([':name' => $parent_folder_name]);
        $parent_folder_id = $pdo->lastInsertId();
    }

    // Step B: Resolve or auto-generate the nested sub-folder segment linked directly to the parent (e.g., "Italian")
    $child_folder_name = trim($cuisine_name);
    $child_check_stmt = $pdo->prepare("SELECT id FROM folders WHERE name = :name AND parent_id = :parent_id LIMIT 1");
    $child_check_stmt->execute([
        ':name'      => $child_folder_name,
        ':parent_id' => $parent_folder_id
    ]);
    $child_folder_id = $child_check_stmt->fetchColumn();

    if (!$child_folder_id) {
        $ins_child = $pdo->prepare("INSERT INTO folders (name, parent_id) VALUES (:name, :parent_id)");
        $ins_child->execute([
            ':name'      => $child_folder_name,
            ':parent_id' => $parent_folder_id
        ]);
        $child_folder_id = $pdo->lastInsertId();
    }

    // Target the deeply nested child folder as our destination mapping point for recipe association
    $folder_id = $child_folder_id;

    // 5. Insert Primary Recipe Row Record (Section 6 / 21)
    $recipe_sql = "INSERT INTO recipes (title, source_url, image_url, course_id, cuisine_id, sub_category_id) 
                   VALUES (:title, :source_url, :image_url, :course_id, :cuisine_id, :sub_category_id)";
    $recipe_stmt = $pdo->prepare($recipe_sql);
    $recipe_stmt->execute([
        ':title'           => substr($recipe_data['title'], 0, 255),
        ':source_url'      => $recipe_data['source_url'] ?? null,
        ':image_url'       => !empty($recipe_data['image_url']) ? $recipe_data['image_url'] : null,
        ':course_id'       => $course_id,
        ':cuisine_id'      => $cuisine_id,
        ':sub_category_id' => $sub_category_id
    ]);
    $recipe_id = $pdo->lastInsertId();

    // 6. Connect Junction Relational Entry Row References
    $folder_link_sql = "INSERT INTO recipe_folders (recipe_id, folder_id) VALUES (:recipe_id, :folder_id)";
    $folder_link_stmt = $pdo->prepare($folder_link_sql);
    $folder_link_stmt->execute([
        ':recipe_id' => $recipe_id,
        ':folder_id' => $folder_id
    ]);

        // MODIFIED SEEDER LOOP: Iterates through each component heading group (Chicken, Slaw, Sauce)
    $ingredients_payload = $recipe_data['ingredients'] ?? [];
    $ing_stmt = $pdo->prepare("INSERT INTO recipe_ingredients (recipe_id, ingredient_text, display_order) VALUES (:recipe_id, :text, :order)");
    
    $order = 0;
    foreach ($ingredients_payload as $section_heading => $items) {
        if (!is_array($items)) continue;
        
        // If there's an explicit section name provided, inject it into the database as a visual header row
        if (trim($section_heading) !== "General Ingredients" && count($ingredients_payload) > 1) {
            $ing_stmt->execute([
                ':recipe_id' => $recipe_id,
                ':text'      => "== " . trim($section_heading) . " ==", // Appends clear markdown style markers to differentiate headers in view_recipe.php
                ':order'     => $order
            ]);
            $order++;
        }
        
        // Loop and add each individual item text string to the database
        foreach ($items as $ing_text) {
            $ing_stmt->execute([
                ':recipe_id' => $recipe_id,
                ':text'      => trim($ing_text),
                ':order'     => $order
            ]);
            $order++;
        }
    }


    // 8. Loop and populate cooking structural step instructions (Section 7)
    $steps = $recipe_data['steps'] ?? [];
    $step_stmt = $pdo->prepare("INSERT INTO recipe_steps (recipe_id, step_number, instruction_text) VALUES (:recipe_id, :num, :text)");
    foreach ($steps as $index => $step_text) {
        $step_stmt->execute([
            ':recipe_id' => $recipe_id,
            ':num'       => ($index + 1),
            ':text'      => trim($step_text)
        ]);
    }

    $pdo->commit();

    // Context execution control routing responses (Bridges Section 30 AJAX and Native HTML forms)
    if (isset($_POST['url'])) {
        header("Location: index?success=1");
        exit;
    } else {
        echo json_encode(['success' => true]);
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    if (isset($_POST['url'])) {
        die("ChefIt Seeder Script Exception Break: " . $e->getMessage());
    } else {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
