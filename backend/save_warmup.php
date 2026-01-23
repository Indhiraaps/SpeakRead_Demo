<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

try {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['sid']) || !isset($data['reading_practice_mistakes'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
    
    $sid = (int)$data['sid'];
    $mistakes = $data['reading_practice_mistakes'];
    
    // Clear previous warmup words for this student
    $deleteStmt = $pdo->prepare("DELETE FROM Warmup WHERE SID = ?");
    $deleteStmt->execute([$sid]);
    
    // Insert new mistakes that need practice
    if (!empty($mistakes)) {
        $insertStmt = $pdo->prepare("INSERT INTO Warmup (SID, IncorrectWord) VALUES (?, ?)");
        
        $inserted = 0;
        foreach ($mistakes as $word) {
            if (!empty(trim($word))) {
                $insertStmt->execute([$sid, trim($word)]);
                $inserted++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Warmup saved',
            'words_saved' => $inserted
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'No mistakes to save - all words mastered!',
            'words_saved' => 0
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>