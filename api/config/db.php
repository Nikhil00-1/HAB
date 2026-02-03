<?php
$DB_HOST = "localhost";
$DB_NAME = "u558197017_hab_appointmen";   // your DB name
$DB_USER = "u558197017_hab1";        // replace
$DB_PASS = "Shreyas@1243";        // replace

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}
