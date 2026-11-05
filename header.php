<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefIt - Smart Recipe Organizer</title>
    
    <!-- 1. Correct Link for Bootstrap 5.3.8 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- 2. Bootstrap Icons v1.11.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- 3. Google Fonts CDN Combination Setup -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- 4. Flat Custom Typography Layout Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Responsive Navigation Core -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <!-- Brand Logo utilizing a Bootstrap Icon -->
            <a class="navbar-brand fw-bold text-success" href="index">
                <i class="bi bi-egg-fried me-2"></i>ChefIt
            </a>
            
            <!-- Mobile Toggle Menu Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#chefItNav" aria-controls="chefItNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Action Links -->
            <div class="collapse navbar-collapse" id="chefItNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index"><i class="bi bi-columns-gap me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="add_recipe"><i class="bi bi-plus-circle-fill me-1"></i> Add Recipe</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main workspace wrapper begins here (Closes in footer.php) -->
    <main class="py-4 flex-grow-1">
