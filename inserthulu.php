<?php
require("./test/database.php");
$conn = Database::connect();

header("Content-Type: application/json");

// Baca JSON dari ESP32
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(["success" => false, "msg" => "JSON ERROR"]);
    exit;
}

$distance  = $data['distance'] ?? null;
$status    = $data['status'] ?? null;
$timestamp = $data['timestamp'] ?? date("Y-m-d H:i:s");

// Validasi data
if ($distance === null || $status === null || $cuaca === null) {
    echo json_encode(["success" => false, "msg" => "INCOMPLETE DATA"]);
    exit;
}

// Submit ke database
$stmt = $conn->prepare("
    INSERT INTO tbl_hulu (ket_hulu, sta_hulu, waktu)
    VALUES (:ket, :sta, :waktu)
");

$stmt->bindParam(':ket',   $distance);
$stmt->bindParam(':sta',   $status);
$stmt->bindParam(':waktu', $timestamp);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "msg" => "OK"]);
} else {
    echo json_encode([
        "success" => false,
        "msg" => "DB ERROR",
        "error" => $stmt->errorInfo()
    ]);
}
