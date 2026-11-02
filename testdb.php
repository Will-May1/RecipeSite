<?php
require_once 'database.php'; // Directly referencing the same directory

if (isset($pdo)) {
    echo "<h1>ChefIt Connected Successfully!</h1>";
    
    // Pull the courses table we populated in Phase 1
    $stmt = $pdo->query("SELECT name FROM courses");
    echo "<h3>Seeded Course Categories:</h3><ul>";
    while ($row = $stmt->fetch()) {
        echo "<li>" . htmlspecialchars($row['name']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "Database object execution error.";
}
