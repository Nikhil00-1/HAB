<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require __DIR__ . "/config/db.php";

/* ================= LOAD PHPMailer ================= */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/PHPMailer/src/Exception.php";
require __DIR__ . "/PHPMailer/src/PHPMailer.php";
require __DIR__ . "/PHPMailer/src/SMTP.php";

/* ================= READ INPUT ================= */
$data = $_POST;

if (!$data) {
    echo json_encode(["success" => false, "error" => "Invalid request"]);
    exit;
}

/* ================= REQUIRED FIELDS ================= */
$required = ["name","email","phone","doctor_id","date","time"];

foreach ($required as $field) {
    if (!isset($data[$field]) || trim($data[$field]) === "") {
        echo json_encode(["success"=>false,"error"=>"Missing required fields"]);
        exit;
    }
}

/* ================= SANITIZE ================= */
$patientName  = trim($data["name"]);
$patientEmail = trim($data["email"]);
$patientPhone = trim($data["phone"]);
$doctorId     = (int)$data["doctor_id"];
$date         = trim($data["date"]);
$time         = trim($data["time"]);
$sessionType  = trim($data["session"] ?? "");
$txnId        = trim($data["transactionId"] ?? "");
$reportPath   = "";

/* ================= FILE UPLOAD ================= */
if (isset($_FILES['medical_report']) && $_FILES['medical_report']['error'] == 0) {

    $allowedTypes = ['pdf','jpg','jpeg','png'];
    $fileExt = strtolower(pathinfo($_FILES["medical_report"]["name"], PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedTypes)) {
        echo json_encode(["success"=>false,"error"=>"Invalid file type"]);
        exit;
    }

    if ($_FILES['medical_report']['size'] > 5*1024*1024) {
        echo json_encode(["success"=>false,"error"=>"File too large (Max 5MB)"]);
        exit;
    }

    $uploadDir = __DIR__ . "/../uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES["medical_report"]["name"]);
    $targetFile = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES["medical_report"]["tmp_name"], $targetFile)) {
        echo json_encode(["success"=>false,"error"=>"File upload failed"]);
        exit;
    }

    $reportPath = "uploads/" . $fileName;
}

/* ================= SLOT CHECK ================= */
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
    echo json_encode(["success"=>false,"error"=>"This time slot is already booked"]);
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
        transaction_id,
        medical_report,
        status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming')
");

$stmt->execute([
    $doctorId,
    $patientName,
    $patientEmail,
    $patientPhone,
    $date,
    $time,
    $sessionType,
    $txnId,
    $reportPath
]);

/* ================= SEND EMAIL ================= */

/* ================= GET BOOKING ID ================= */
$bookingId = $pdo->lastInsertId();

/* ================= GET DOCTOR NAME ================= */
$docStmt = $pdo->prepare("SELECT name FROM doctors WHERE id = ?");
$docStmt->execute([$doctorId]);
$doctorName = $docStmt->fetchColumn();

/* ================= SEND EMAIL ================= */

$mail = new PHPMailer(true);

try {

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'stakethirteen44@gmail.com';
    $mail->Password   = ''; // App password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom('stakethirteen44@gmail.com', 'HAB Clinic');
    $mail->addAddress($patientEmail);
    $mail->addAddress('stakethirteen44@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = "Appointment Confirmation - HAB Clinic (Booking #$bookingId)";

    $mail->Body = "
    <!DOCTYPE html>
    <html>
    <body style='font-family:Arial;background:#f4f6f8;padding:20px;'>

    <div style='max-width:600px;margin:auto;background:#fff;border-radius:10px;overflow:hidden;'>

      <div style='background:#0d9488;color:white;padding:20px;text-align:center;'>
        <h2>HAB Clinic</h2>
        <p>Appointment Confirmation</p>
      </div>

      <div style='padding:25px;'>

        <p>Dear <strong>$patientName</strong>,</p>

        <p>Your appointment has been successfully booked.</p>

        <table width='100%' cellpadding='8' style='border-collapse:collapse;margin-top:15px;'>

          <tr style='background:#f1f5f9;'>
            <td><strong>Booking ID</strong></td>
            <td>#{$bookingId}</td>
          </tr>

          <tr>
            <td><strong>Doctor</strong></td>
            <td>Dr. {$doctorName}</td>
          </tr>

          <tr style='background:#f1f5f9;'>
            <td><strong>Session</strong></td>
            <td>{$sessionType}</td>
          </tr>

          <tr>
            <td><strong>Date</strong></td>
            <td>{$date}</td>
          </tr>

          <tr style='background:#f1f5f9;'>
            <td><strong>Time</strong></td>
            <td>{$time}</td>
          </tr>

          <tr>
            <td><strong>Transaction ID</strong></td>
            <td>{$txnId}</td>
          </tr>

        </table>

        <p style='margin-top:20px;'>Thank you for choosing HAB Clinic.</p>

      </div>

      <div style='background:#f8fafc;padding:15px;text-align:center;font-size:12px;color:#64748b;'>
        © 2026 HAB Clinic <br>
        This is an automated email.
      </div>

    </div>

    </body>
    </html>
    ";

    $mail->AltBody = "
    HAB Clinic - Appointment Confirmation

    Booking ID: #$bookingId
    Doctor: Dr. $doctorName
    Session: $sessionType
    Date: $date
    Time: $time
    Transaction ID: $txnId
    ";

    $mail->send();

} catch (Exception $e) {
    echo json_encode([
        "success"=>false,
        "error"=>"Booking saved but email failed: ".$mail->ErrorInfo
    ]);
    exit;
}

/* ================= SUCCESS RESPONSE ================= */
echo json_encode(["success" => true]);
