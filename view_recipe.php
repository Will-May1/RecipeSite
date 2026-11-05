<?php
// view_recipe.php
require_once 'database.php';

// 1. Snatch the target Recipe ID from the URL parameters
$recipe_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$recipe_id) {
    header("Location: index");
    exit;
}

try {
    // 2. Fetch primary recipe data along with taxonomy strings
    $recipe_sql = "SELECT r.*, c.name AS course_name, cu.name AS cuisine_name, s.name AS sub_category_name
                   FROM recipes r
                   LEFT JOIN courses c ON r.course_id = c.id
                   LEFT JOIN cuisines cu ON r.cuisine_id = cu.id
                   LEFT JOIN sub_categories s ON r.sub_category_id = s.id
                   WHERE r.id = :id";
    $recipe_stmt = $pdo->prepare($recipe_sql);
    $recipe_stmt->execute([':id' => $recipe_id]);
    $recipe = $recipe_stmt->fetch();

    if (!$recipe) {
        die("Error: Recipe not found.");
    }

    // 3. Fetch matching ingredients structured rows
    $ing_stmt = $pdo->prepare("SELECT ingredient_text FROM recipe_ingredients WHERE recipe_id = :id ORDER BY display_order ASC");
    $ing_stmt->execute([':id' => $recipe_id]);
    $ingredients = $ing_stmt->fetchAll();

    // 4. Fetch sequential cooking instructions steps
    $step_stmt = $pdo->prepare("SELECT step_number, instruction_text FROM recipe_steps WHERE recipe_id = :id ORDER BY step_number ASC");
    $step_stmt->execute([':id' => $recipe_id]);
    $steps = $step_stmt->fetchAll();

} catch (PDOException $e) {
    die("ChefIt Viewer Extraction Error: " . $e->getMessage());
}

// Inject global header layout
include 'header.php';
?>

<div class="container my-4">
    <!-- Breadcrumb Back button tracking navigation -->
    <div class="mb-4">
        <a href="index" class="btn btn-sm btn-outline-secondary border-0 ps-0 text-dark"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
    </div>

    <!-- Main Recipe Profile Card Panel -->
    <div class="card border-0 shadow-sm bg-white rounded-3 overflow-hidden mb-4">
        <div class="row g-0">
            <!-- Left Grid: Visual representation panel layout -->
            <div class="col-md-5">
                <?php if (!empty($recipe['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($recipe['image_url']); ?>" class="w-100 h-100 object-fit-cover" style="min-height: 250px;" alt="Recipe food profile photo">
                <?php else: ?>
                    <div class="bg-secondary-subtle d-flex flex-column justify-content-center align-items-center w-100 h-100" style="min-height: 250px;">
                        <i class="bi bi-egg-fried text-muted display-2"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Grid: Metadata track definitions summary -->
            <div class="col-md-7 p-4 d-flex flex-column justify-content-center">
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill"><?php echo htmlspecialchars($recipe['course_name'] ?? 'Unassigned'); ?></span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill"><i class="bi bi-globe2 me-1"></i><?php echo htmlspecialchars($recipe['cuisine_name'] ?? 'Generic'); ?></span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill"><i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($recipe['sub_category_name'] ?? 'Placeholder'); ?></span>
                </div>
                
                <h1 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($recipe['title']); ?></h1>
                
                <?php if (!empty($recipe['source_url'])): ?>
                        <a href="<?php echo htmlspecialchars($recipe['source_url']); ?>" target="_blank" class="btn btn-sm btn-light border rounded-pill"><i class="bi bi-link-45deg me-1"></i> View Original Recipe Source</a>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" data-bs-toggle="modal" data-bs-target="#deleteRecipeModal">
                        <i class="bi bi-trash3 me-1"></i> Delete Recipe
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dual Split Content Processing Workspace Grid Layer -->
    <div class="row g-4">
        <!-- Ingredients panel column layer -->
        <div class="col-lg-4 col-md-5">
            <div class="card border-0 shadow-sm bg-white p-4 rounded-3 h-100">
                <h4 class="fw-bold mb-3 border-bottom pb-2 text-dark"><i class="bi bi-list-check text-success me-2"></i>Ingredients</h4>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($ingredients as $ing): ?>
                        <?php 
                        $text = $ing['ingredient_text'];
                        
                        // Check if the current row item text represents an automated section header marker
                        if (substr($text, 0, 3) === "== " && substr($text, -3) === " =="): 
                            $clean_heading = trim(str_replace("==", "", $text));
                        ?>
                            <!-- Renders an elegant, non-clickable visual category banner layout block -->
                            <li class="pt-3 pb-1 fw-bold text-success border-bottom-0 text-uppercase small style-heading" style="letter-spacing: 0.05em;">
                                <i class="bi bi-tag-fill me-1 small"></i> <?php echo htmlspecialchars($clean_heading); ?>
                            </li>
                        <?php else: ?>
                            <!-- Renders standard functional checkbox rows for individual food elements -->
                            <li class="py-2 border-bottom border-light d-flex align-items-start">
                                <input class="form-check-input flex-shrink-0 mt-1 me-3 border-success" type="checkbox" id="ingCheck-<?php echo bin2hex(random_bytes(4)); ?>">
                                <label class="text-dark small" style="cursor: pointer; user-select: none;"><?php echo htmlspecialchars($text); ?></label>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Instructions pipeline column layer -->
        <div class="col-lg-8 col-md-7">
            <div class="card border-0 shadow-sm bg-white p-4 rounded-3 h-100">
                <h4 class="fw-bold mb-3 border-bottom pb-2 text-dark"><i class="bi bi-journal-text text-success me-2"></i>Instructions / Steps</h4>
                <ol class="list-unstyled mb-0">
                    <?php foreach ($steps as $step): ?>
                        <li class="mb-4 d-flex align-items-start">
                            <span class="badge bg-dark rounded-circle d-flex align-items-center justify-content-center p-0 me-3 mt-1 flex-shrink-0" style="width: 28px; height: 28px; font-size: 14px;">
                                <?php echo $step['step_number']; ?>
                            </span>
                            <div class="text-dark pt-1">
                                <?php echo htmlspecialchars($step['instruction_text']); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     DELETE CONFIRMATION MODAL POPUP COMPONENT (ADDED HERE)
     ========================================================================= -->
<div class="modal fade" id="deleteRecipeModal" tabindex="-1" aria-labelledby="deleteRecipeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-danger" id="deleteRecipeModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Recipe
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-0 text-dark">Are you absolutely sure you want to delete <strong><?php echo htmlspecialchars($recipe['title']); ?></strong>? This action cannot be undone and will erase all ingredients and cooking steps.</p>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <form action="delete_recipe" method="POST" class="w-100 d-flex gap-2 justify-content-end">
                    <!-- Hidden field to pass the specific recipe ID securely -->
                    <input type="hidden" name="id" value="<?php echo $recipe['id']; ?>">
                    <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3">Confirm Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Inject global layout closure template
include 'footer.php';
?>
