<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

require __DIR__ . "/config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data["name"] ?? "");
$email = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");
$specialization = trim($data["specialization"] ?? "");

if (!$name || !$email || !$password || !$specialization) {
  echo json_encode(["success" => false, "error" => "All fields are required"]);
  exit;
}

/* HASH PASSWORD */
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

/* CHECK EMAIL EXISTS */
$check = $pdo->prepare("SELECT id FROM doctors WHERE email = ?");
$check->execute([$email]);

if ($check->fetch()) {
  echo json_encode(["success" => false, "error" => "Email already registered"]);
  exit;
}

/* INSERT INTO DOCTORS TABLE */
$stmt = $pdo->prepare("
  INSERT INTO doctors (name, email, password, specialization)
  VALUES (?, ?, ?, ?)
");

$stmt->execute([$name, $email, $hashedPassword, $specialization]);

echo json_encode(["success" => true]);
