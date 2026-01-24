<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $sid = (int)$input['sid'];
    $wordType = $input['word_type']; // 'reading' or 'homework'
    $masteredWords = $input['mastered_words'] ?? [];
    $failedWords = $input['failed_words'] ?? [];
    
    // Determine which column to update
    $column = ($wordType === 'homework') ? 'homework_mistakes' : 'reading_practice_mistakes';
    
    // Get current words from database
    $stmt = $pdo->prepare("SELECT $column FROM Warmup WHERE SID = ?");
    $stmt->execute([$sid]);
    $result = $stmt->fetch();
    
    if ($result && !empty($result[$column])) {
        // Parse existing words
        $currentWords = array_filter(array_map('trim', explode(',', $result[$column])));
        
        // Remove mastered words, keep failed words
        $remainingWords = array_diff($currentWords, $masteredWords);
        
        // Ensure all failed words are in the list (in case of new failures)
        $remainingWords = array_unique(array_merge($remainingWords, $failedWords));
        
        if (empty($remainingWords)) {
            // No words left - set column to NULL
            $updateStmt = $pdo->prepare("UPDATE Warmup SET $column = NULL WHERE SID = ?");
            $updateStmt->execute([$sid]);
            
            echo json_encode([
                'success' => true,
                'message' => 'All words mastered! Column cleared.',
                'remaining_words' => 0
            ]);
        } else {
            // Update with remaining words
            $wordsString = implode(',', array_values($remainingWords));
            $updateStmt = $pdo->prepare("UPDATE Warmup SET $column = ? WHERE SID = ?");
            $updateStmt->execute([$wordsString, $sid]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Warmup updated successfully',
                'mastered_count' => count($masteredWords),
                'remaining_words' => count($remainingWords),
                'remaining_list' => array_values($remainingWords)
            ]);
        }
    } else {
        // No existing record or column is empty
        if (!empty($failedWords)) {
            // Create/update record with failed words only
            $wordsString = implode(',', $failedWords);
            
            $checkStmt = $pdo->prepare("SELECT WID FROM Warmup WHERE SID = ?");
            $checkStmt->execute([$sid]);
            
            if ($checkStmt->fetch()) {
                // Update existing record
                $updateStmt = $pdo->prepare("UPDATE Warmup SET $column = ? WHERE SID = ?");
                $updateStmt->execute([$wordsString, $sid]);
            } else {
                // Insert new record
                $insertStmt = $pdo->prepare("INSERT INTO Warmup (SID, $column) VALUES (?, ?)");
                $insertStmt->execute([$sid, $wordsString]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Failed words saved for practice',
                'remaining_words' => count($failedWords)
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'No words to update',
                'remaining_words' => 0
            ]);
        }
    }
    
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