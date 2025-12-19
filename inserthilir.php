<?php
require("./test/database.php");
$conn = Database::connect();

header("Content-Type: application/json");

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(["success" => false, "msg" => "Invalid JSON"]);
    exit;
}

// Ambil data dari JSON
$distance  = isset($data['distance']) ? intval($data['distance']) : null;
$status    = isset($data['status']) ? $data['status'] : null;
$timestamp = isset($data['timestamp']) ? $data['timestamp'] : date("Y-m-d H:i:s");

if ($distance === null || $status === null) {
    echo json_encode(["success" => false, "msg" => "Missing parameter"]);
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO tbl_hilir (ket_hilir, sta_hilir, waktu)
        VALUES (:ket_hilir, :sta_hilir, :waktu)
    ");

    $stmt->execute([
        ":ket_hilir" => $distance,
        ":sta_hilir" => $status,
        ":waktu"     => $timestamp
    ]);

    echo json_encode(["success" => true, "msg" => "Data inserted"]);
}
catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "msg" => "DB Error",
        "error" => $e->getMessage()
    ]);
}
