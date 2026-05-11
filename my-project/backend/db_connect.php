<?php
$host = "localhost";
$user = "root";
$password = "";
$database = "documanager";
$port = 3307;

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

// Timezone Fix
date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");

$conn->set_charset("utf8mb4");

// --- HEARTBEAT GOES HERE ---
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    @$conn->query("UPDATE users SET last_login = NOW() WHERE id = $uid");
}