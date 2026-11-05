<?php
// delete_recipe.php
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index");
    exit;
}

$recipe_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$recipe_id) {
    header("Location: index");
    exit;
}

try {
    // Begin an isolated transaction block to safely cascade deletions across your child tables
    $pdo->beginTransaction();

    // 1. Clear junction mappings inside the recipe_folders table
    $del_folders = $pdo->prepare("DELETE FROM recipe_folders WHERE recipe_id = :id");
    $del_folders->execute([':id' => $recipe_id]);

    // 2. Clear all structural rows from your child table recipe_ingredients
    $del_ingredients = $pdo->prepare("DELETE FROM recipe_ingredients WHERE recipe_id = :id");
    $del_ingredients->execute([':id' => $recipe_id]);

    // 3. Clear all sequential rows from your child table recipe_steps
    $del_steps = $pdo->prepare("DELETE FROM recipe_steps WHERE recipe_id = :id");
    $del_steps->execute([':id' => $recipe_id]);

    // 4. Wipe the primary recipe master record row from the recipes table
    $del_recipe = $pdo->prepare("DELETE FROM recipes WHERE id = :id");
    $del_recipe->execute([':id' => $recipe_id]);

    $pdo->commit();

    // Route smoothly back to dashboard with a distinct deletion parameter toast flag
    header("Location: index?deleted=1");
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Database removal process execution error: " . $e->getMessage());
}
