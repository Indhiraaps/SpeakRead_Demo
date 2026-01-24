<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$sid = isset($_GET['sid']) ? (int)$_GET['sid'] : 0;

if ($sid <= 0) {
    echo json_encode(['error' => 'Invalid student ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM Warmup WHERE SID = ?");
    $stmt->execute([$sid]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        echo json_encode([
            'success' => true,
            'sid' => $data['SID'],
            'homework_mistakes' => $data['homework_mistakes'],
            'reading_practice_mistakes' => $data['reading_practice_mistakes'],
            'date_added' => $data['DateAdded']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No warmup record found for Student ID: ' . $sid
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>