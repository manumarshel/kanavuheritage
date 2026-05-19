<?php
// C:\xampp\htdocs\kanav\dashboard\includes\db_connect.php

// ---- EDIT THESE 4 VALUES IF NEEDED ----
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'kanav';   // <- make sure this DB exists

// Create mysqli connection
$conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Fatal if connection fails
if ($conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}

// Ensure UTF-8
$conn->set_charset('utf8mb4');
