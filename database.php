<?php
$host     = 'sql202.infinityfree.com';
$db       = 'if0_43187131_chefit';
$user     = 'if0_43187131';            
$pass     = 'hHimykmJ2nY';   
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
    PDO::ATTR_EMULATE_PREPARES   => false,                  
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("ChefIt Database connection failed: " . $e->getMessage());
}
