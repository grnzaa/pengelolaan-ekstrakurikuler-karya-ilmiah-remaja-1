<?php
// Database Configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "aplikasi_pengelolaan_ekstrakurikuler_kir_esensial";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Koneksi ke database gagal!: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");
?>
