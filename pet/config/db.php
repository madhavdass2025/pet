<?php
$host = 'localhost';
$db   = 'pet_clinic';
$user = 'root';
$pass = ''; // Default for many environments, adjust if needed
$charset = 'utf8mb4';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset($charset);
?>
