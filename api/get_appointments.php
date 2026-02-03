<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require __DIR__ . "/config/db.php";

/* ================= INPUT ================= */
$status   = $_GET["status"] ?? "upcoming";
$doctorId = $_GET["doctor_id"] ?? null;

if (!$doctorId) {
  echo json_encode([
    "success" => false,
    "error" => "Doctor ID missing"
  ]);
  exit;
}

/* ================= QUERY ================= */
$stmt = $pdo->prepare("
  SELECT
    id,
    patient_name,
    patient_email,
    appointment_date,
    appointment_time,
    session_type
  FROM appointments
  WHERE status = ?
    AND doctor_id = ?
  ORDER BY appointment_date, appointment_time
");

$stmt->execute([$status, $doctorId]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
