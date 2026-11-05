<?php
// add_recipe.php
require_once 'database.php';

// 1. Fetch available options from your database tables to populate dropdowns
try {
    // Fetch courses (Pre-seeded: Starters, Mains, etc.)
    $courses_stmt = $pdo->query("SELECT id, name FROM courses ORDER BY id ASC");
    $courses = $courses_stmt->fetchAll();

    // Fetch existing cuisines
    $cuisines_stmt = $pdo->query("SELECT id, name FROM cuisines ORDER BY name ASC");
    $cuisines = $cuisines_stmt->fetchAll();

    // Fetch existing folders
    $folders_stmt = $pdo->query("SELECT id, name 
                                FROM folders 
                                WHERE parent_id IS NOT NULL
                                ORDER BY name ASC");
    $folders = $folders_stmt->fetchAll();

} catch (PDOException $e) {
    die("Error fetching form options: " . $e->getMessage());
}

// Inject the global header template
include 'header.php';
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 bg-white p-4">
                <h2 class="fw-bold text-dark mb-4"><i class="bi bi-file-earmark-medical-fill text-success me-2"></i>Add Manual Recipe</h2>
                
                <!-- Recipe Submission Form -->
                <form action="save_recipe" method="POST">
                    
                    <!-- Title Row -->
                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold">Recipe Title</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g., Creamy Butter Chicken" required>
                    </div>

                    <!-- Image & Source URL Rows -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="image_url" class="form-label fw-bold">Image URL</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" placeholder="https://example.com">
                        </div>
                        <div class="col-md-6">
                            <label for="source_url" class="form-label fw-bold">Original Source URL (Optional)</label>
                            <input type="url" class="form-control" id="source_url" name="source_url" placeholder="https://blog.com">
                        </div>
                    </div>

                    <!-- Taxonomies & Dynamic Dropdowns -->
                    <div class="row g-3 mb-4">
                        <!-- Course Selection (Dynamic) -->
                        <div class="col-md-4">
                            <label for="course_id" class="form-label fw-bold">Course Category</label>
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option value="" selected disabled>Choose Course...</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Cuisine Selection (Dynamic) -->
                        <div class="col-md-4">
                            <label for="cuisine_id" class="form-label fw-bold">Cuisine Type</label>
                            <select class="form-select" id="cuisine_id" name="cuisine_id" required>
                                <option value="" selected disabled>Choose Cuisine...</option>
                                <?php foreach ($cuisines as $cuisine): ?>
                                    <option value="<?php echo $cuisine['id']; ?>"><?php echo htmlspecialchars($cuisine['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Missing one? It auto-creates later!</small>
                        </div>

                        <!-- Folder Destination (Dynamic) -->
                        <div class="col-md-4">
                            <label for="folder_id" class="form-label fw-bold">Save into Folder</label>
                            <select class="form-select" id="folder_id" name="folder_id">
                                <option value="" selected>No Folder (Unassigned)</option>
                                <?php foreach ($folders as $folder): ?>
                                    <option value="<?php echo $folder['id']; ?>"><?php echo htmlspecialchars($folder['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Raw Ingredients Input Block -->
                    <div class="mb-3">
                        <label for="ingredients" class="form-label fw-bold">Ingredients</label>
                        <textarea class="form-control" id="ingredients" name="ingredients" rows="5" placeholder="Enter each ingredient on a new line...&#10;2 tbsp Olive Oil&#10;500g Diced Chicken" required></textarea>
                    </div>

                    <!-- Raw Cooking Steps Input Block -->
                    <div class="mb-4">
                        <label for="steps" class="form-label fw-bold">Instructions / Cooking Steps</label>
                        <textarea class="form-control" id="steps" name="steps" rows="6" placeholder="Enter each cooking step on a new line...&#10;Heat oil in a pan over medium heat.&#10;Add the chicken pieces and brown all over." required></textarea>
                    </div>

                    <!-- Submission Panel Buttons -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="index" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-success px-4"><i class="fas fa-save me-2"></i>Save Recipe</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php 
// Inject the global footer template
include 'footer.php'; 
?>
