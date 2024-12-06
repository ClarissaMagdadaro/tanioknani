<?php
$host = 'localhost';
$dbname = 'arte_db'; // Your database name
$username = 'root';  // Use 'root' if no custom user is created
$password = '';      // Leave empty if using root without a password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Removed "Connection successful!" message
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
