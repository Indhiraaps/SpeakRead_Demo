<?php
session_start();

// Database Configuration - ADJUST THIS TO MATCH YOUR SETUP
$host = 'mysql-19588968-speakread000.g.aivencloud.com';
$dbname = 'defaultdb';
$port = '25249';
$user = 'avnadmin';
$pass = 'AVNS_-hJYen-fDyBu9ApXbxH'; // Change to YOUR password

header('Content-Type: application/json');

// Check if teacher is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode([
        'success' => false, 
        'message' => 'Not authorized. Please login as teacher.',
        'session_role' => $_SESSION['role'] ?? 'none'
    ]);
    exit();
}

try {
    // Connect to database
    // Change this line:
$pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    
    if (!isset($data['grade'])) {
        throw new Exception('Grade is required');
    }
    
    $grade = trim($data['grade']);
    $mon = isset($data['Mon']) ? (int)$data['Mon'] : 0;
    $tue = isset($data['Tue']) ? (int)$data['Tue'] : 0;
    $wed = isset($data['Wed']) ? (int)$data['Wed'] : 0;
    $thu = isset($data['Thu']) ? (int)$data['Thu'] : 0;
    $fri = isset($data['Fri']) ? (int)$data['Fri'] : 0;
    $sat = isset($data['Sat']) ? (int)$data['Sat'] : 0;
    $sun = isset($data['Sun']) ? (int)$data['Sun'] : 0;
    
    // Check if schedule exists
    $checkStmt = $pdo->prepare("SELECT ID FROM HomeworkSchedule WHERE Grade = ?");
    $checkStmt->execute([$grade]);
    $exists = $checkStmt->fetch();
    
    if ($exists) {
        // UPDATE existing schedule
        $updateStmt = $pdo->prepare("
            UPDATE HomeworkSchedule 
            SET Mon = ?, Tue = ?, Wed = ?, Thu = ?, Fri = ?, Sat = ?, Sun = ?
            WHERE Grade = ?
        ");
        $updateStmt->execute([$mon, $tue, $wed, $thu, $fri, $sat, $sun, $grade]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'action' => 'UPDATE',
            'grade' => $grade,
            'days' => [
                'Mon' => $mon,
                'Tue' => $tue,
                'Wed' => $wed,
                'Thu' => $thu,
                'Fri' => $fri,
                'Sat' => $sat,
                'Sun' => $sun
            ]
        ]);
    } else {
        // INSERT new schedule
        $insertStmt = $pdo->prepare("
            INSERT INTO HomeworkSchedule (Grade, Mon, Tue, Wed, Thu, Fri, Sat, Sun) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$grade, $mon, $tue, $wed, $thu, $fri, $sat, $sun]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Schedule created successfully',
            'action' => 'INSERT',
            'grade' => $grade,
            'days' => [
                'Mon' => $mon,
                'Tue' => $tue,
                'Wed' => $wed,
                'Thu' => $thu,
                'Fri' => $fri,
                'Sat' => $sat,
                'Sun' => $sun
            ]
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>