<?php
require_once '../config/db.php';

// Test with your actual student ID
$sid = 6;
$wordType = 'reading';
$masteredWords = ['purple', 'green'];

$column = 'reading_practice_mistakes';

// Get current
$stmt = $pdo->prepare("SELECT {$column} FROM Warmup WHERE SID = ?");
$stmt->execute([$sid]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

echo "BEFORE: " . $current[$column] . "\n\n";

// Parse
$currentWords = array_filter(array_map('trim', explode(',', $current[$column])));
echo "Current words array: " . print_r($currentWords, true) . "\n";

// Remove
$remainingWords = array_diff($currentWords, $masteredWords);
echo "After removing: " . print_r($remainingWords, true) . "\n";

// Update
$newValue = !empty($remainingWords) ? implode(', ', array_values($remainingWords)) : NULL;
echo "New value: " . ($newValue ?? 'NULL') . "\n\n";

$updateStmt = $pdo->prepare("UPDATE Warmup SET {$column} = ? WHERE SID = ?");
$updateStmt->execute([$newValue, $sid]);

echo "Rows updated: " . $updateStmt->rowCount() . "\n";

// Verify
$stmt->execute([$sid]);
$after = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nAFTER: " . ($after[$column] ?? 'NULL') . "\n";
?>