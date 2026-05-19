<?php
require 'includes/connect.php';
$res = $conn->query("SELECT * FROM bookings ORDER BY id DESC LIMIT 5");
echo json_encode($res->fetch_all(MYSQLI_ASSOC));
