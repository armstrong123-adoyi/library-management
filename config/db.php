<?php
// config/db.php
// Procedural MySQLi database connection

$db_host = "localhost";
$db_user = "root";      // default XAMPP username
$db_pass = "";          // default XAMPP password is empty
$db_name = "library_db";

// Create connection
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set charset to avoid encoding issues
mysqli_set_charset($conn, "utf8mb4");
?>
