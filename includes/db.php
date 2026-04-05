<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'testadmin');
define('DB_PASS', 'testadminpass');
define('DB_NAME', 'qcsim_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
