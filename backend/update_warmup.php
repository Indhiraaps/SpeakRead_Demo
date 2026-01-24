<?php
session_start();
require_once '../config/db.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/warmup_update_errors.log');

header('Content-Type: application/json');

// Log file for debugging
$logFile = __DIR__ . '/warmup_update_log.txt';

try {
    $input = file_get_contents('php://input');
    file_put_contents($logFile, "\n" . str_repeat("=", 50) . "\n", FILE_APPEND);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - New Request\n", FILE_APPEND);
    file_put_contents($logFile, "Raw Input: " . $input . "\n", FILE_APPEND);
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON decode error: ' . json_last_error_msg());
    }
    
    file_put_contents($logFile, "Decoded Data: " . print_r($data, true) . "\n", FILE_APPEND);
    
    if (!isset($data['sid']) || !isset($data['word_type']) || !isset($data['mastered_words'])) {
        $error = ['success' => false, 'message' => 'Missing required data'];
        file_put_contents($logFile, "ERROR: " . json_encode($error) . "\n", FILE_APPEND);
        echo json_encode($error);
        exit;
    }
    
    $sid = (int)$data['sid'];
    $wordType = $data['word_type']; // 'reading' or 'homework'
    $masteredWords = $data['mastered_words']; // Words to REMOVE from database
    
    file_put_contents($logFile, "Student ID: $sid\n", FILE_APPEND);
    file_put_contents($logFile, "Word Type: $wordType\n", FILE_APPEND);
    file_put_contents($logFile, "Mastered Words: " . implode(', ', $masteredWords) . "\n", FILE_APPEND);
    
    // Determine which column to update
    $column = ($wordType === 'reading') ? 'reading_practice_mistakes' : 'homework_mistakes';
    file_put_contents($logFile, "Column to update: $column\n", FILE_APPEND);
    
    // Get current words from database
    $stmt = $pdo->prepare("SELECT {$column} FROM Warmup WHERE SID = ?");
    $stmt->execute([$sid]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current) {
        $error = ['success' => false, 'message' => 'No warmup record found for this student'];
        file_put_contents($logFile, "ERROR: No warmup record for SID $sid\n", FILE_APPEND);
        echo json_encode($error);
        exit;
    }
    
    file_put_contents($logFile, "Current DB value: " . ($current[$column] ?? 'NULL') . "\n", FILE_APPEND);
    
    // Parse current words
    $currentWords = [];
    if (!empty($current[$column])) {
        $currentWords = array_filter(array_map('trim', explode(',', $current[$column])));
    }
    
    file_put_contents($logFile, "Current Words Array: " . print_r($currentWords, true) . "\n", FILE_APPEND);
    file_put_contents($logFile, "Words Count Before: " . count($currentWords) . "\n", FILE_APPEND);
    
    // Remove mastered words (keep only words that are NOT in mastered list)
    $remainingWords = array_diff($currentWords, $masteredWords);
    
    file_put_contents($logFile, "Remaining Words Array: " . print_r($remainingWords, true) . "\n", FILE_APPEND);
    file_put_contents($logFile, "Words Count After: " . count($remainingWords) . "\n", FILE_APPEND);
    
    // Update database with remaining words
    $newValue = !empty($remainingWords) ? implode(', ', array_values($remainingWords)) : NULL;
    
    file_put_contents($logFile, "New DB Value: " . ($newValue ?? 'NULL') . "\n", FILE_APPEND);
    
    $updateStmt = $pdo->prepare("UPDATE Warmup SET {$column} = ? WHERE SID = ?");
    $success = $updateStmt->execute([$newValue, $sid]);
    
    file_put_contents($logFile, "Update Success: " . ($success ? 'YES' : 'NO') . "\n", FILE_APPEND);
    file_put_contents($logFile, "Rows Affected: " . $updateStmt->rowCount() . "\n", FILE_APPEND);
    
    // Verify the update
    $verifyStmt = $pdo->prepare("SELECT {$column} FROM Warmup WHERE SID = ?");
    $verifyStmt->execute([$sid]);
    $verified = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    file_put_contents($logFile, "Verified DB Value After Update: " . ($verified[$column] ?? 'NULL') . "\n", FILE_APPEND);
    
    $response = [
        'success' => true,
        'message' => 'Warmup updated successfully',
        'column' => $column,
        'words_before' => count($currentWords),
        'words_mastered' => count($masteredWords),
        'words_remaining' => count($remainingWords),
        'mastered_words' => $masteredWords,
        'remaining_words' => array_values($remainingWords),
        'new_db_value' => $newValue
    ];
    
    file_put_contents($logFile, "SUCCESS Response: " . json_encode($response) . "\n", FILE_APPEND);
    echo json_encode($response);
    
} catch (PDOException $e) {
    $error = [
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ];
    file_put_contents($logFile, "DATABASE ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode($error);
    
} catch (Exception $e) {
    $error = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
    file_put_contents($logFile, "GENERAL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode($error);
}
?>