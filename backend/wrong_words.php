<?php
session_start();

// Database configuration
$host = 'localhost';
$db = 'speakread_db';
$user = 'root';
$pass = '12345678'; // ⚠️ CHANGE TO YOUR PASSWORD

// Set header for JSON response
header('Content-Type: application/json');

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
    $source = isset($data['source']) ? $data['source'] : 'homework'; // Default to homework
    
    // If there are no wrong words, just return success
    if (empty($wrong_words)) {
        echo json_encode(['success' => true, 'message' => 'No wrong words to save']);
        exit;
    }
    
    // Remove duplicates and prepare comma-separated list
    $unique_words = array_unique(array_filter(array_map('trim', $wrong_words)));
    $words_string = implode(', ', $unique_words);
    
    // Determine which column to update based on source
    if ($source === 'reading_practice') {
        $column = 'reading_practice_mistakes';
    } else {
        $column = 'homework_mistakes';
    }
    
    // Check if student already has a warmup record
    $checkStmt = $pdo->prepare("SELECT WID, {$column} FROM Warmup WHERE SID = ?");
    $checkStmt->execute([$sid]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing record - append new words
        $current_words = $existing[$column] ?? '';
        
        if (!empty($current_words)) {
            // Merge with existing words and remove duplicates
            $current_array = array_map('trim', explode(',', $current_words));
            $merged = array_unique(array_merge($current_array, $unique_words));
            $words_string = implode(', ', $merged);
        }
        
        $updateStmt = $pdo->prepare("UPDATE Warmup SET {$column} = ? WHERE SID = ?");
        $updateStmt->execute([$words_string, $sid]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Wrong words updated successfully',
            'words_saved' => count($unique_words),
            'column' => $column,
            'action' => 'updated'
        ]);
        
    } else {
        // Insert new record
        if ($source === 'reading_practice') {
            $insertStmt = $pdo->prepare("INSERT INTO Warmup (SID, reading_practice_mistakes) VALUES (?, ?)");
        } else {
            $insertStmt = $pdo->prepare("INSERT INTO Warmup (SID, homework_mistakes) VALUES (?, ?)");
        }
        
        $insertStmt->execute([$sid, $words_string]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Wrong words saved successfully',
            'words_saved' => count($unique_words),
            'column' => $column,
            'action' => 'inserted'
        ]);
    }
    
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