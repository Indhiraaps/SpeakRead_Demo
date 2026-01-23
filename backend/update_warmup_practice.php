<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $sid = (int)$input['sid'];
    $wordType = $input['word_type']; // 'reading' or 'homework'
    $masteredWords = $input['mastered_words'];
    
    // Validate input
    if ($sid <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
        exit;
    }
    
    // Convert type to database format
    $dbType = $wordType === 'reading' ? 'reading_practice' : 'homework';
    
    if (empty($masteredWords)) {
        echo json_encode(['success' => true, 'message' => 'No words mastered', 'deleted' => 0]);
        exit;
    }
    
    // DELETE ONLY the mastered words for this student and type
    $placeholders = str_repeat('?,', count($masteredWords) - 1) . '?';
    $deleteStmt = $pdo->prepare("
        DELETE FROM Warmup 
        WHERE SID = ? 
        AND WordType = ? 
        AND IncorrectWord IN ($placeholders)
    ");
    
    $params = array_merge([$sid, $dbType], $masteredWords);
    $deleteStmt->execute($params);
    
    $deleted = $deleteStmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => 'Warmup updated successfully',
        'deleted' => $deleted,
        'mastered_words' => $masteredWords,
        'student_id' => $sid,
        'word_type' => $dbType
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