<?php
require("./test/database.php");
$conn = Database::connect();

$q = $conn->query("SELECT * FROM tbl_hulu ORDER BY id DESC LIMIT 1");
$data = $q->fetch(PDO::FETCH_ASSOC);

echo json_encode($data);
?>
