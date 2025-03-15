<?php
$host = 'localhost';
$dbname = 'ikariam_quiz'; // Your database name
$username = 'root'; // Default username for XAMPP MySQL
$password = ''; // Default password for XAMPP MySQL (empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
