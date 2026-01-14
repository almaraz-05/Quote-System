<?php
include("secrets.php");

try {
    $dsn = "mysql:host=courses;dbname=$username";
    $pdo = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
?>

