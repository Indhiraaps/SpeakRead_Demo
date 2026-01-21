<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../frontend/login.html");
    exit();
}
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT DISTINCT Grade FROM Students WHERE TID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
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
        .nav-item { padding: 12px; margin-bottom: 8px; border-radius: 8px; cursor: pointer; color: #64748b; text-decoration: none; display: block; }
        .nav-item:hover, .nav-item.active { background: #eff6ff; color: #2563eb; font-weight: 600; }
        .main-content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .class-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 24px; }
        .class-card { background: white; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; cursor: pointer; transition: 0.2s; }
        .class-card:hover { transform: translateY(-5px); border-color: #2563eb; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .btn { background: #2563eb; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; }
        #classModal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:1000; }
        .modal-content { background:white; padding:30px; border-radius:12px; width:450px; }
        .format-box { background: #f1f5f9; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 11px; }
        .format-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .format-table th { text-align: left; color: #475569; border-bottom: 1px solid #cbd5e1; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="logo">SpeakRead</div>
    <a href="#" class="nav-item active">Dashboard</a>
    <a href="logout.php" class="nav-item" style="margin-top: 50px; color: #ef4444;">Logout</a>
</div>

<div class="main-content">
    <div class="header">
        <h1>My Classes</h1>
        <button class="btn" onclick="document.getElementById('classModal').style.display='flex'">+ Create New Class</button>
    </div>

    <div class="class-grid">
        <?php foreach ($classes as $class): ?>
            <div class="class-card" onclick="window.location.href='lessons.php?grade=<?php echo urlencode($class['Grade']); ?>'">
                <h3><?php echo htmlspecialchars($class['Grade']); ?></h3>
                <p>Click to manage homework & lessons</p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="classModal">
    <div class="modal-content">
        <h2>Create New Class</h2>
        <form action="upload_students.php" method="POST" enctype="multipart/form-data">
            <label style="display:block; margin-bottom:8px; font-size:14px;">Class/Grade Name</label>
            <input type="text" name="grade_name" placeholder="e.g. Grade 5-A" required style="width:100%; padding:10px; margin-bottom:20px; border:1px solid #ddd; border-radius:6px;">
            
            <label style="display:block; margin-bottom:8px; font-size:14px;">Upload Student List (Optional)</label>
            <input type="file" name="student_excel" accept=".csv, .xlsx" style="margin-bottom:15px;">
            
            <div class="format-box">
                <strong style="color: #475569;">REQUIRED FORMAT:</strong>
                <table class="format-table">
                    <tr><th>Name</th><th>Email</th><th>Password</th></tr>
                    
                </table>
            </div>
            
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn" style="flex:1;">Create Class</button>
                <button type="button" class="btn" style="flex:1; background:#94a3b8;" onclick="document.getElementById('classModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>