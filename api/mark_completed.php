<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require __DIR__ . "/config/db.php";

$status   = $_GET["status"] ?? "upcoming";
$doctorId = $_GET["doctor_id"] ?? null;

if (!$doctorId) {
  echo json_encode([]);
  exit;
}

if ($status === "completed") {
  $query = "
    SELECT *
    FROM appointments
    WHERE doctor_id = ?
    AND status = 'completed'
    ORDER BY appointment_date, appointment_time
  ";
} else {
  $query = "
    SELECT *
    FROM appointments
    WHERE doctor_id = ?
    AND status = 'upcoming'
    ORDER BY appointment_date, appointment_time
  ";
}

$stmt = $pdo->prepare($query);
$stmt->execute([$doctorId]);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
