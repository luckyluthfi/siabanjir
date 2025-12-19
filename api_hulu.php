<?php
require("./test/database.php");
$conn = Database::connect();

$stmt = $conn->prepare("SELECT * FROM tbl_hulu ORDER BY id DESC LIMIT 1");
$stmt->execute();
$data = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($data);
