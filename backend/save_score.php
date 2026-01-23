<?php
session_start();
require_once '../config/db.php';

// Set header for JSON response
header('Content-Type: application/json');

try {
    // Get POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate required data
    if (!isset($data['sid']) || !isset($data['hid']) || !isset($data['accuracy'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
    
    $sid = (int)$data['sid'];
    $hid = (int)$data['hid'];
    $accuracy = (float)$data['accuracy'];
    
    // Insert score into Scores table
    $stmt = $pdo->prepare("INSERT INTO Scores (SID, HID, Accuracy) VALUES (?, ?, ?)");
    $stmt->execute([$sid, $hid, $accuracy]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Score saved successfully',
        'score_id' => $pdo->lastInsertId()
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>