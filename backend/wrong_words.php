<?php
session_start();
require_once '../config/db.php';

// Set header for JSON response
header('Content-Type: application/json');

try {
    // Get POST data (JSON sent from reading_practice.php or homework.php)
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate required data
    if (!isset($data['sid']) || !isset($data['wrong_words'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
    
    // Extract data
    $sid = (int)$data['sid'];
    $wrong_words = $data['wrong_words'];
    $source = $data['source'] ?? 'homework'; // 'homework' or 'reading_practice'
    
    // If there are no wrong words, just return success
    if (empty($wrong_words)) {
        echo json_encode(['success' => true, 'message' => 'No wrong words to save']);
        exit;
    }
    
    // Convert array to comma-separated string
    $words_string = implode(',', array_map('trim', $wrong_words));
    
    // Determine which column to update based on source
    $column = ($source === 'homework') ? 'homework_mistakes' : 'reading_practice_mistakes';
    
    // Check if student already has a warmup record
    $checkStmt = $pdo->prepare("SELECT WID, $column FROM Warmup WHERE SID = ?");
    $checkStmt->execute([$sid]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        // Append new mistakes to existing ones
        $existing_words = $existing[$column] ? explode(',', $existing[$column]) : [];
        $all_words = array_unique(array_merge($existing_words, $wrong_words));
        $words_string = implode(',', array_map('trim', $all_words));
        
        // Update existing record
        $updateStmt = $pdo->prepare("UPDATE Warmup SET $column = ? WHERE SID = ?");
        $updateStmt->execute([$words_string, $sid]);
        $message = 'Wrong words updated successfully';
    } else {
        // Insert new record
        $insertStmt = $pdo->prepare("INSERT INTO Warmup (SID, $column) VALUES (?, ?)");
        $insertStmt->execute([$sid, $words_string]);
        $message = 'Wrong words saved successfully';
    }
    
    // Send success response
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'words_saved' => count($wrong_words)
    ]);
    
} catch (PDOException $e) {
    // Database error
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // General error
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>