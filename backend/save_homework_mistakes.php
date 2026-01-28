<?php
session_start();
require_once '../config/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Log file for debugging
$logFile = __DIR__ . '/homework_mistakes_log.txt';

try {
    file_put_contents($logFile, "\n" . str_repeat("=", 50) . "\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - New Request\n", FILE_APPEND);
    
    $input = file_get_contents('php://input');
    file_put_contents($logFile, "Raw Input: " . $input . "\n", FILE_APPEND);
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON decode error: ' . json_last_error_msg());
    }
    
    file_put_contents($logFile, "Decoded Data: " . print_r($data, true) . "\n", FILE_APPEND);
    
    if (!isset($data['sid'])) {
        throw new Exception('Missing student ID (sid)');
    }
    
    if (!isset($data['wrong_words'])) {
        throw new Exception('Missing wrong_words array');
    }
    
    $sid = (int)$data['sid'];
    $wrongWords = $data['wrong_words'];
    
    file_put_contents($logFile, "Student ID: $sid\n", FILE_APPEND);
    file_put_contents($logFile, "Wrong Words Count: " . count($wrongWords) . "\n", FILE_APPEND);
    
    if ($sid <= 0) {
        throw new Exception('Invalid student ID: ' . $sid);
    }
    
    // Check if student exists
    $checkStudent = $pdo->prepare("SELECT SID, Name FROM Students WHERE SID = ?");
    $checkStudent->execute([$sid]);
    $student = $checkStudent->fetch();
    
    if (!$student) {
        throw new Exception('Student not found with ID: ' . $sid);
    }
    
    file_put_contents($logFile, "Student Found: " . $student['Name'] . "\n", FILE_APPEND);
    
    if (empty($wrongWords)) {
        $response = [
            'success' => true,
            'message' => 'No mistakes to save',
            'words_saved' => 0
        ];
        file_put_contents($logFile, "Response: No words to save\n", FILE_APPEND);
        echo json_encode($response);
        exit;
    }
    
    // Clean and filter words
    $wrongWords = array_values(array_unique(array_filter(array_map('trim', $wrongWords))));
    
    if (empty($wrongWords)) {
        $response = [
            'success' => true,
            'message' => 'No valid words after filtering',
            'words_saved' => 0
        ];
        echo json_encode($response);
        exit;
    }
    
    file_put_contents($logFile, "Filtered Words: " . implode(', ', $wrongWords) . "\n", FILE_APPEND);
    
    // Check if student has existing warmup record
    $checkStmt = $pdo->prepare("SELECT WID, homework_mistakes FROM Warmup WHERE SID = ?");
    $checkStmt->execute([$sid]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // UPDATE existing record (REMOVED DateAdded)
        file_put_contents($logFile, "Existing record found (WID: {$existing['WID']})\n", FILE_APPEND);
        
        $currentWords = [];
        if (!empty($existing['homework_mistakes'])) {
            $currentWords = array_map('trim', explode(',', $existing['homework_mistakes']));
        }
        
        $merged = array_values(array_unique(array_merge($currentWords, $wrongWords)));
        $wordsString = implode(', ', $merged);
        
        file_put_contents($logFile, "Merged words: $wordsString\n", FILE_APPEND);
        
        // FIXED: Removed DateAdded from UPDATE query
        $updateStmt = $pdo->prepare("UPDATE Warmup SET homework_mistakes = ? WHERE SID = ?");
        $success = $updateStmt->execute([$wordsString, $sid]);
        
        file_put_contents($logFile, "Update success: " . ($success ? 'YES' : 'NO') . "\n", FILE_APPEND);
        file_put_contents($logFile, "Rows affected: " . $updateStmt->rowCount() . "\n", FILE_APPEND);
        
        $response = [
            'success' => true,
            'message' => 'Homework mistakes updated successfully',
            'action' => 'UPDATE',
            'words_saved' => count($wrongWords),
            'total_words' => count($merged),
            'student_id' => $sid,
            'student_name' => $student['Name']
        ];
        
    } else {
        // INSERT new record
        file_put_contents($logFile, "No existing record - inserting new\n", FILE_APPEND);
        
        $wordsString = implode(', ', $wrongWords);
        
        $insertStmt = $pdo->prepare("INSERT INTO Warmup (SID, homework_mistakes) VALUES (?, ?)");
        $success = $insertStmt->execute([$sid, $wordsString]);
        
        file_put_contents($logFile, "Insert success: " . ($success ? 'YES' : 'NO') . "\n", FILE_APPEND);
        file_put_contents($logFile, "New WID: " . $pdo->lastInsertId() . "\n", FILE_APPEND);
        
        $response = [
            'success' => true,
            'message' => 'Homework mistakes saved successfully',
            'action' => 'INSERT',
            'words_saved' => count($wrongWords),
            'student_id' => $sid,
            'student_name' => $student['Name']
        ];
    }
    
    file_put_contents($logFile, "SUCCESS - Response: " . json_encode($response) . "\n", FILE_APPEND);
    echo json_encode($response);
    
} catch (PDOException $e) {
    $error = [
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ];
    file_put_contents($logFile, "DATABASE ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode($error);
    
} catch (Exception $e) {
    $error = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    file_put_contents($logFile, "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode($error);
}
?>