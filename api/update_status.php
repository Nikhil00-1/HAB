<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require __DIR__ . "/config/db.php";

$id     = $_GET['id'] ?? null;
$status = $_GET['status'] ?? null;

if (!$id || !$status) {
  echo json_encode(["success" => false, "error" => "Missing data"]);
  exit;
}

$stmt = $pdo->prepare("UPDATE appointments SET status=? WHERE id=?");
$stmt->execute([$status, $id]);

echo json_encode(["success" => true]);


