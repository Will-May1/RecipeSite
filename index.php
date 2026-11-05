<?php
// index.php
require_once 'database.php';

// 1. Grab any operational message indicators from the URL parameters
// 1. Grab operational indicators and active category navigation filter variables from the URL query string
$success = isset($_GET['success']);
$filter_folder = filter_input(INPUT_GET, 'folder_id', FILTER_VALIDATE_INT);
$filter_course = filter_input(INPUT_GET, 'course_id', FILTER_VALIDATE_INT);
$filter_cuisine = filter_input(INPUT_GET, 'cuisine_id', FILTER_VALIDATE_INT);

try {
    // 2. Query all active folders including parent data to build the sidebar tree structure
    $folders_stmt = $pdo->query("SELECT id, name, parent_id FROM folders ORDER BY name ASC");
    $all_folders = $folders_stmt->fetchAll();

    // Reconstruct folders into an active parent-child tree mapping index arrays
    $root_folders = [];
    $sub_folders = [];
    foreach ($all_folders as $f) {
        if ($f['parent_id'] === null) {
            $root_folders[] = $f;
        } else {
            $sub_folders[$f['parent_id']][] = $f;
        }
    }

    // SECTION 25 & 26 ADDITION: Fetch all categories to populate dashboard filter pill menus
    $courses_menu_stmt = $pdo->query("SELECT id, name FROM courses ORDER BY id ASC");
    $all_courses = $courses_menu_stmt->fetchAll();

    $cuisines_menu_stmt = $pdo->query("SELECT id, name FROM cuisines ORDER BY name ASC");
    $all_cuisines = $cuisines_menu_stmt->fetchAll();

    // Dynamic Filter Architecture: Start with a baseline master SQL statement structure
    $recipes_sql = "SELECT DISTINCT r.id, r.title, r.image_url, c.name AS course_name, cu.name AS cuisine_name 
                    FROM recipes r
                    LEFT JOIN courses c ON r.course_id = c.id
                    LEFT JOIN cuisines cu ON r.cuisine_id = cu.id
                    LEFT JOIN recipe_folders rf ON r.id = rf.recipe_id
                    WHERE 1=1"; // 1=1 allows us to cleanly append dynamic AND queries below
    
    $params = [];

    // Section 24: If a sidebar folder is selected, append the filtering limit clause
    if ($filter_folder) {
        $recipes_sql .= " AND rf.folder_id = :folder_id";
        $params[':folder_id'] = $filter_folder;
    }

    // Section 25/26: If a top-level course classification is chosen
    if ($filter_course) {
        $recipes_sql .= " AND r.course_id = :course_id";
        $params[':course_id'] = $filter_course;
    }

    // Section 26: If a structural cuisine type filter is active
    if ($filter_cuisine) {
        $recipes_sql .= " AND r.cuisine_id = :cuisine_id";
        $params[':cuisine_id'] = $filter_cuisine;
    }

    $recipes_sql .= " ORDER BY r.created_at DESC";
    
    $recipes_stmt = $pdo->prepare($recipes_sql);
    $recipes_stmt->execute($params);
    $recipes = $recipes_stmt->fetchAll();

} catch (PDOException $e) {
    die("ChefIt Workspace aggregation crash: " . $e->getMessage());
}


// Inject the global head framework template
include 'header.php';
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Section 24 & 25: Restructured Native Form (Bypasses InfinityFree Restrictions) -->
<div class="card border-0 shadow-sm p-4 mb-4 bg-white rounded-3">
    <h5 class="fw-bold text-dark mb-2"><i class="bi bi-robot text-success me-2"></i>Import with ChefIt</h5>
    <p class="text-muted small mb-3">Paste any food blog URL or Instagram Reel link below to extract and auto-sort the recipe instantly.</p>
    
    <!-- Using a standard HTML form POST to communicate with your backend directly -->
    <form action="import_recipe" method="POST">
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-link-45deg"></i></span>
            <input type="url" name="url" class="form-control border-start-0 ps-0" placeholder="https://instagram.com... or ://foodblog.com" required>
            
            <button class="btn btn-success px-4" type="submit">
                <i class="bi bi-cpu me-1"></i>Extract Recipe
            </button>
        </div>
    </form>
</div>



<div class="container-fluid px-4">
    
    <!-- Success Toast Alert Dismissible Notification -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                <div><strong>Success!</strong> Your recipe has been safely preserved and automatically categorized.</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Recipe Wiped Dismissible Alert Banner (Section 23 Alert Add-on) -->
    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-trash3-fill fs-4 me-2 text-danger"></i>
                <div class="text-dark"><strong>Recipe Removed!</strong> The recipe has been deleted.</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>


    <!-- Phase 4: Split Layout Grid (Sidebar tray + Active Workspace) -->
    <div class="row g-4">
        
        <!-- Sidebar Navigation Drawer (Left Panel Column) -->
        <div class="col-lg-3 col-md-4">
            <div class="card border-0 shadow-sm p-3 mb-4 bg-white rounded-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-folder2-open text-warning me-2"></i>My Folders</h5>
                    <!-- Button placeholder triggering folder modal generation tool -->
                    <button class="btn btn-sm btn-outline-success border-0 py-0" data-bs-toggle="modal" data-bs-target="#addFolderModal">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                
                <div class="list-group list-group-flush">
                    <!-- Dynamically toggles active visual highlighting states based on active filters -->
                    <a href="index#recipedash" class="list-group-item list-group-item-action border-0 rounded-2 mb-1 px-3 py-2 <?php echo (!$filter_folder && !$filter_course && !$filter_cuisine) ? 'active bg-success text-white' : ''; ?>">
                        <i class="bi bi-grid-1x2-fill me-2"></i>All Recipes
                    </a>
                    <?php if (empty($root_folders)): ?>
                        <p class="text-muted small px-3 py-2 mb-0">No custom folders created yet.</p>
                    <?php else: ?>
                        <?php foreach ($root_folders as $root): ?>
                            <?php 
                            // Determine if any child within this specific parent root is currently active
                            $is_child_active = false;
                            if (isset($sub_folders[$root['id']])) {
                                foreach ($sub_folders[$root['id']] as $child) {
                                    if ($filter_folder == $child['id']) {
                                        $is_child_active = true;
                                        break;
                                    }
                                }
                            }
                            ?>
                            <!-- Collapsible Dropdown parent item toggle button (Does not reload page) -->
                            <button class="list-group-item list-group-item-action border-0 rounded-2 mb-1 px-3 py-2 fw-bold d-flex align-items-center justify-content-between" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#collapseFolder-<?php echo $root['id']; ?>" 
                                    aria-expanded="<?php echo $is_child_active ? 'true' : 'false'; ?>" 
                                    style="font-size: 0.95rem; background: transparent;">
                                <span>
                                    <i class="bi bi-folder2-open text-warning me-2"></i><?php echo htmlspecialchars($root['name']); ?>
                                </span>
                                <i class="bi bi-chevron-down small text-muted toggle-arrow"></i>
                            </button>
                            
                            <!-- Indented collapsible subfolder container panel row -->
                            <?php if (isset($sub_folders[$root['id']])): ?>
                                <div class="collapse ms-3 ps-2 border-start border-secondary-subtle mb-2 <?php echo $is_child_active ? 'show' : ''; ?>" 
                                     id="collapseFolder-<?php echo $root['id']; ?>">
                                    <?php foreach ($sub_folders[$root['id']] as $child): ?>
                                        <a href="index?folder_id=<?php echo $child['id']; ?>#recipedash"
                                           class="list-group-item list-group-item-action border-0 rounded-2 mb-1 py-1 px-2 text-muted small <?php echo ($filter_folder == $child['id']) ? 'active bg-success text-white fw-bold' : ''; ?>">
                                            <i class="bi bi-folder-fill text-warning opacity-75 me-2"></i><?php echo htmlspecialchars($child['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>


                </div>
            </div>
        </div>

        <!-- Main Content Stream Area (Right Panel Column) -->
        <div class="col-lg-9 col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0 text-dark" id="recipedash">Recipe Dashboard</h2>
                    <small class="text-muted">Manage your collection of organized culinary metrics</small>
                </div>
                <a href="add_recipe" class="btn btn-success px-3 fs-6 d-flex align-items-center rounded-pill shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i>New Entry
                </a>
            </div>

            <!-- SECTION 25 & 26: DYNAMIC WORKSPACE CATEGORY CHIP REGION -->
            <div class="card border-0 shadow-sm p-3 mb-4 bg-white rounded-3">
            <!-- Course Categories Filter Row (Section 25) -->
                <div class="mb-2 d-flex align-items-center flex-wrap gap-2">
                    <span class="text-muted small fw-bold me-2"><i class="bi bi-layers-half text-success me-1"></i>Courses:</span>
                    <!-- Clear course selection but preserve folder and cuisine filters -->
                    <a href="index?<?php echo http_build_query(array_merge($_GET, ['course_id' => ''])); ?>#recipedash" class="btn btn-sm rounded-pill <?php echo !$filter_course ? 'btn-success text-white' : 'btn-light border'; ?>">All</a>
                    <?php foreach ($all_courses as $c): ?>
                        <?php 
                        // Merges the current active URL params with this specific course loop item selection
                        $course_url_params = http_build_query(array_merge($_GET, ['course_id' => $c['id']])); 
                        ?>
                        <a href="index?<?php echo $course_url_params; ?>#recipedash" class="btn btn-sm rounded-pill <?php echo ($filter_course == $c['id']) ? 'btn-success text-white' : 'btn-light border'; ?>">
                            <?php echo htmlspecialchars($c['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>


                <!-- Secondary Cuisine Filter Row (Section 26) -->
                <?php if (!empty($all_cuisines)): ?>
                <div class="d-flex align-items-center flex-wrap gap-2 pt-2 border-top border-light">
                    <span class="text-muted small fw-bold me-2"><i class="bi bi-globe2 text-primary me-1"></i>Cuisines:</span>
                    <!-- Clear cuisine selection but preserve folder and course filters -->
                    <a href="index?<?php echo http_build_query(array_merge($_GET, ['cuisine_id' => ''])); ?>#recipedash" class="btn btn-sm rounded-pill <?php echo !$filter_cuisine ? 'btn-primary text-white' : 'btn-light border'; ?>">All</a>
                    <?php foreach ($all_cuisines as $cu): ?>
                        <?php 
                        // Merges the current active URL params with this specific cuisine loop item selection
                        $cuisine_url_params = http_build_query(array_merge($_GET, ['cuisine_id' => $cu['id']])); 
                        ?>
                        <a href="index?<?php echo $cuisine_url_params; ?>#recipedash" class="btn btn-sm rounded-pill <?php echo ($filter_cuisine == $cu['id']) ? 'btn-primary text-white' : 'btn-light border'; ?>">
                            <?php echo htmlspecialchars($cu['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Grid Displays of Saved Recipe Records -->
            <?php if (empty($recipes)): ?>
                <div class="card text-center p-5 border-0 shadow-sm bg-white rounded-3">
                    <div class="py-4">
                        <i class="bi bi-journal-x text-muted display-1"></i>
                        <h4 class="fw-bold mt-3">Your Recipe Book is Empty</h4>
                        <p class="text-muted">Get started by entering a recipe manually or pasting a URL link block.</p>
                        <a href="add_recipe" class="btn btn-outline-success mt-2">Add Your First Recipe</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                    <?php foreach ($recipes as $recipe): ?>
                        <div class="col">
                            <div class="card h-100 border-0 shadow-sm bg-white rounded-3 overflow-hidden recipe-card">
                                <!-- Fallback conditional image panel element -->
                                <?php if (!empty($recipe['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($recipe['image_url']); ?>" class="card-img-top object-fit-cover" style="height: 180px;" alt="Food Image">
                                <?php else: ?>
                                    <div class="bg-secondary-subtle d-flex flex-column justify-content-center align-items-center" style="height: 180px;">
                                        <i class="bi bi-egg-fried text-muted display-4"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body p-3 d-flex flex-column">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small rounded-pill align-self-start mb-2">
                                        <?php echo htmlspecialchars($recipe['course_name'] ?? 'Unassigned'); ?>
                                    </span>
                                    <h5 class="card-title fw-bold text-dark mb-1 text-truncate"><?php echo htmlspecialchars($recipe['title']); ?></h5>
                                    <p class="card-text text-muted small mb-3"><i class="bi bi-globe2 text-secondary me-1"></i>Cuisine: <?php echo htmlspecialchars($recipe['cuisine_name'] ?? 'Generic'); ?></p>
                                    
                                    <!-- Anchor link routing block targeting Section 23b viewer -->
                                    <a href="view_recipe?id=<?php echo $recipe['id']; ?>" class="btn btn-outline-dark btn-sm mt-auto w-100 rounded-pill">
                                        <i class="bi bi-eye me-1"></i>View Recipe
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Section 19: Lightweight Folder Creation Modal Popup Component -->
<div class="modal fade" id="addFolderModal" tabindex="-1" aria-labelledby="addFolderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="addFolderModalLabel"><i class="bi bi-folder-plus text-success me-2"></i>Create New Folder</h5>
                <button type="button" class="btn-close" data-bs-close="modal" aria-label="Close"></button>
            </div>
            <form action="save_folder" method="POST">
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label for="folder_name" class="form-label fw-bold">Folder Name</label>
                        <input type="text" class="form-control" id="folder_name" name="folder_name" placeholder="e.g., Sunday Roast Ideas" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-3">Create Folder</button>
                </div>
            </form>
        </div>
    </div>
</div> 

    </main> <!-- Closes the main tag opened in header.php -->

    <!-- Sticky Bottom Footer Layout Component -->
    <footer class="bg-white border-top py-3 mt-auto shadow-sm">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <span class="fw-bold text-success">ChefIt</span>. Powered by AI Automation</p>
        </div>
    </footer>

    
    <!-- Flat Custom JavaScript Async File -->
    <script src="main.js"></script>
</body>
</html>

