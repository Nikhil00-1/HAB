<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

require __DIR__ . "/config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");

if (!$email || !$password) {
    echo json_encode(["success" => false, "error" => "Missing credentials"]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, password FROM doctors WHERE email = ?");
$stmt->execute([$email]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doctor || !password_verify($password, $doctor["password"])) {
    echo json_encode(["success" => false, "error" => "Invalid email or password"]);
    exit;
}

echo json_encode([
    "success" => true,
    "doctor_id" => $doctor["id"],
    "doctor_name" => $doctor["name"]
]);
