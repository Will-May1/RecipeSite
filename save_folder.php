<?php
// save_folder.php
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $folder_name = trim($_POST['folder_name'] ?? '');

    if (!empty($folder_name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO folders 
            (name, parent_id) 
            VALUES 
            (:name, 8)");
            $stmt->execute([':name' => $folder_name]);
            
            header("Location: index");
            exit;
        } catch (PDOException $e) {
            die("Error saving folder category: " . $e->getMessage());
        }
    }
}
header("Location: index");
exit;
