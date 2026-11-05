<?php
// save_recipe.php
require_once 'database.php';

// Ensure the page is accessed via a POST form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index");
    exit;
}

// 1. Capture and sanitize basic text inputs
$title = trim($_POST['title'] ?? '');
$image_url = trim($_POST['image_url'] ?? null);
$source_url = trim($_POST['source_url'] ?? null);

// Convert empty strings to null values for optional columns
$image_url = ($image_url === '') ? null : $image_url;
$source_url = ($source_url === '') ? null : $source_url;

// Capture ID selections from dropdown elements
$course_id = $_POST['course_id'] ?? null;
$cuisine_id = $_POST['cuisine_id'] ?? null;
$folder_id = $_POST['folder_id'] ?? null; // Can be null if unassigned
$folder_id = ($folder_id === '') ? null : $folder_id;

// Handle temporary hardcoded sub-category ID until Phase 6 automation is live
// (We will use ID 1 for now—make sure a placeholder row exists or your DB allows defaults)
$sub_category_id = 1; 

// 2. Explode textarea blocks into neat individual array lines
$raw_ingredients = explode("\n", $_POST['ingredients'] ?? '');
$raw_steps = explode("\n", $_POST['steps'] ?? '');

// Server-side strict string validation
if (empty($title) || empty($course_id) || empty($cuisine_id) || empty($raw_ingredients) || empty($raw_steps)) {
    die("Error: Please fill out all required fields.");
}

try {
    // 3. Begin an isolated MySQL transaction block
    $pdo->beginTransaction();

    // 4. Insert the core recipe record into the main recipes table
    $recipe_sql = "INSERT INTO recipes (title, source_url, image_url, course_id, cuisine_id, sub_category_id) 
                   VALUES (:title, :source_url, :image_url, :course_id, :cuisine_id, :sub_category_id)";
    
    $recipe_stmt = $pdo->prepare($recipe_sql);
    $recipe_stmt->execute([
        ':title'           => $title,
        ':source_url'      => $source_url,
        ':image_url'       => $image_url,
        ':course_id'       => $course_id,
        ':cuisine_id'      => $cuisine_id,
        ':sub_category_id' => $sub_category_id
    ]);
    
    // Snatch the freshly generated primary recipe ID
    $recipe_id = $pdo->lastInsertId();

    // 5. Link the recipe to a custom folder inside the junction table (if selected)
    if ($folder_id !== null) {
        $folder_sql = "INSERT INTO recipe_folders (recipe_id, folder_id) VALUES (:recipe_id, :folder_id)";
        $folder_stmt = $pdo->prepare($folder_sql);
        $folder_stmt->execute([
            ':recipe_id' => $recipe_id,
            ':folder_id' => $folder_id
        ]);
    }

    // 6. Loop and insert structured rows into recipe_ingredients
    $ing_sql = "INSERT INTO recipe_ingredients (recipe_id, ingredient_text, display_order) VALUES (:recipe_id, :text, :order)";
    $ing_stmt = $pdo->prepare($ing_sql);
    
    $order = 0;
    foreach ($raw_ingredients as $line) {
        $clean_line = trim($line);
        if ($clean_line !== '') { // Skip accidental empty carriage returns
            $ing_stmt->execute([
                ':recipe_id' => $recipe_id,
                ':text'      => $clean_line,
                ':order'     => $order
            ]);
            $order++;
        }
    }

    // 7. Loop and insert sequential instruction steps into recipe_steps
    $step_sql = "INSERT INTO recipe_steps (recipe_id, step_number, instruction_text) VALUES (:recipe_id, :num, :text)";
    $step_stmt = $pdo->prepare($step_sql);
    
    $step_number = 1;
    foreach ($raw_steps as $line) {
        $clean_line = trim($line);
        if ($clean_line !== '') {
            $step_stmt->execute([
                ':recipe_id' => $recipe_id,
                ':num'       => $step_number,
                ':text'      => $clean_line
            ]);
            $step_number++;
        }
    }

    // 8. Commit everything permanently to the live database together
    $pdo->commit();

    // Redirect the user back to the homepage with a confirmation alert parameter
    header("Location: index?success=1");
    exit;

} catch (Exception $e) {
    // If anything goes wrong, rollback the data database changes instantly to keep things clean
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Database transaction execution error: " . $e->getMessage());
}
