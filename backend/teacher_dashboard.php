<?php
session_start();

// Check if user is logged in as teacher
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../frontend/login.html");
    exit();
}

// Check if user_id exists in session
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    session_destroy();
    echo "<script>alert('Session expired. Please login again.'); window.location.href='../frontend/login.html';</script>";
    exit();
}

// Database Configuration
$host = 'localhost'; 
$db = 'speakread_db'; 
$user = 'root'; 
$pass = '12345678';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT DISTINCT Grade FROM Students WHERE TID = ? AND Grade IS NOT NULL AND Grade != '' ORDER BY Grade");
    $stmt->execute([$_SESSION['user_id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) { 
    die("Database Error: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SpeakRead</title>
    <style>
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { margin: 0; background: #f8fafc; display: flex; }
        .sidebar { width: 260px; background: #ffffff; height: 100vh; border-right: 1px solid #e2e8f0; padding: 24px; position: fixed; }
        .logo { font-size: 22px; font-weight: 800; color: #2563eb; margin-bottom: 40px; }
        .nav-item { padding: 12px; margin-bottom: 8px; border-radius: 8px; cursor: pointer; color: #64748b; text-decoration: none; display: block; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { background: #eff6ff; color: #2563eb; font-weight: 600; }
        .main-content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .header h1 { margin: 0 0 5px 0; font-size: 28px; }
        .header p { margin: 0; color: #64748b; font-size: 14px; }
        .class-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 24px; }
        .class-card { background: white; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; cursor: pointer; transition: 0.2s; }
        .class-card:hover { transform: translateY(-5px); border-color: #2563eb; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .class-card h3 { margin: 0 0 10px 0; color: #1e293b; font-size: 20px; }
        .class-card p { margin: 0; color: #64748b; font-size: 14px; }
        .btn { background: #2563eb; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.2s; }
        .btn:hover { background: #1d4ed8; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
        .empty-state-icon { font-size: 64px; margin-bottom: 20px; opacity: 0.5; }
        .empty-state h2 { color: #1e293b; margin-bottom: 10px; }
        .empty-state p { font-size: 16px; }
        
        #classModal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index: 1000; }
        .modal-content { background:white; padding:30px; border-radius:12px; width:450px; max-height: 90vh; overflow-y: auto; }
        .modal-content h2 { margin-top: 0; color: #1e293b; }
        .modal-content label { display:block; margin-bottom:8px; font-size:14px; font-weight: 600; color: #475569; }
        .modal-content input[type="text"],
        .modal-content input[type="file"] { width:100%; padding:10px; margin-bottom:20px; border:1px solid #cbd5e1; border-radius:6px; font-size: 14px; }
        .modal-content input[type="file"] { padding: 8px; }
        
        .format-box { background: #f1f5f9; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 12px; border: 1px solid #e2e8f0; }
        .format-box strong { color: #475569; display: block; margin-bottom: 8px; }
        .format-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        .format-table th { text-align: left; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 5px; font-weight: 600; }
        .format-table td { padding: 5px; color: #94a3b8; }
        .format-box p { margin: 8px 0 0 0; color: #64748b; }
        
        .btn-group { display:flex; gap:10px; }
        .btn-secondary { background:#94a3b8; }
        .btn-secondary:hover { background: #64748b; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">SpeakRead</div>
    <a href="#" class="nav-item active">📊 Dashboard</a>
    <a href="logout.php" class="nav-item" style="margin-top: 50px; color: #ef4444;">🚪 Logout</a>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h1>My Classes</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['teacher_name'] ?? 'Teacher'); ?>!</p>
        </div>
        <button class="btn" onclick="document.getElementById('classModal').style.display='flex'">+ Create New Class</button>
    </div>

    <?php if (empty($classes)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📚</div>
            <h2>No Classes Yet</h2>
            <p>Click "Create New Class" above to get started with your first class</p>
        </div>
    <?php else: ?>
        <div class="class-grid">
            <?php foreach ($classes as $class): ?>
                <div class="class-card" onclick="window.location.href='grade_management.php?grade=<?php echo urlencode($class['Grade']); ?>'">
                    <h3><?php echo htmlspecialchars($class['Grade']); ?></h3>
                    <p>Click to manage this class</p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="classModal">
    <div class="modal-content">
        <h2>Create New Class</h2>
        <form action="upload_students.php" method="POST" enctype="multipart/form-data">
            <label>Class/Grade Name *</label>
            <input type="text" name="grade_name" placeholder="e.g. Grade 5-A" required>
            
            <label>Upload Student List (Optional)</label>
            <input type="file" name="student_excel" accept=".csv, .xlsx">
            
            <div class="format-box">
                <strong>📋 REQUIRED CSV FORMAT:</strong>
                <table class="format-table">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Password</th>
                    </tr>
                    <tr>
                        <td>John Doe</td>
                        <td>john@test.com</td>
                        <td>password123</td>
                    </tr>
                </table>
                <p>⚠️ First row must be headers (Name, Email, Password). No extra columns.</p>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn" style="flex:1;">Create Class</button>
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="document.getElementById('classModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('classModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('classModal').style.display = 'none';
    }
});
</script>

</body>
</html>