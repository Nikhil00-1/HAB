<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require __DIR__ . "/config/db.php";

/* ================= READ INPUT ================= */
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
  echo json_encode([
    "success" => false,
    "error" => "Invalid request"
  ]);
  exit;
}

/* ================= REQUIRED FIELDS ================= */
$required = [
  "patientName",
  "patientEmail",
  "patientPhone",
  "doctorId",
  "appointmentDate",
  "appointmentTime"
];

foreach ($required as $field) {
  if (!isset($data[$field]) || trim($data[$field]) === "") {
    echo json_encode([
      "success" => false,
      "error" => "Missing required fields"
    ]);
    exit;
  }
}

/* ================= SANITIZE ================= */
$patientName  = trim($data["patientName"]);
$patientEmail = trim($data["patientEmail"]);
$patientPhone = trim($data["patientPhone"]);
$doctorId     = (int)$data["doctorId"];
$date         = trim($data["appointmentDate"]);
$time         = trim($data["appointmentTime"]);
$sessionType  = trim($data["sessionType"] ?? "");

/* ================= SLOT CHECK (PER DOCTOR) ================= */
$check = $pdo->prepare("
  SELECT COUNT(*) 
  FROM appointments
  WHERE appointment_date = ?
    AND appointment_time = ?
    AND doctor_id = ?
    AND status = 'upcoming'
");

$check->execute([$date, $time, $doctorId]);

if ($check->fetchColumn() > 0) {
  echo json_encode([
    "success" => false,
    "error" => "This time slot is already booked"
  ]);
  exit;
}

/* ================= INSERT APPOINTMENT ================= */
$stmt = $pdo->prepare("
  INSERT INTO appointments
  (
    doctor_id,
    patient_name,
    patient_email,
    patient_phone,
    appointment_date,
    appointment_time,
    session_type,
    status
  )
  VALUES (?, ?, ?, ?, ?, ?, ?, 'upcoming')
");

$stmt->execute([
  $doctorId,
  $patientName,
  $patientEmail,
  $patientPhone,
  $date,
  $time,
  $sessionType
]);

echo json_encode([
  "success" => true
]);
