<?php
header("Content-Type: application/json");
require __DIR__ . "/config/db.php";

try {

    $specialization = $_GET['specialization'] ?? '';

    if ($specialization) {
        // 🔍 FILTER MODE
        $stmt = $pdo->prepare("
            SELECT id, name 
            FROM doctors
            WHERE TRIM(LOWER(specialization)) = TRIM(LOWER(?))
        ");
        $stmt->execute([$specialization]);
    } else {
        // 🔥 RETURN ALL DOCTORS
        $stmt = $pdo->query("
            SELECT id, name 
            FROM doctors
        ");
    }

    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($doctors);

} catch (Exception $e) {
    echo json_encode([]);
}
