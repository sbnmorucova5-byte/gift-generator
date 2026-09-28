<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "gift_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Verilənlər bazasına qoşulma alınmadı: " . $conn->connect_error);
}

// Şrift kodlamasını UTF-8 edirik (emojilər və azərbaycan hərfləri üçün)
$conn->set_charset("utf8mb4");
?>