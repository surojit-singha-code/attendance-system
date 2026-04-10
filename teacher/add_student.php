<?php
/**
 * teacher/add_student.php
 * Add a new student with login credentials. Also shows full student list.
 */
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php"); exit();
}

$msg = $error = '';

// Handle deletion message from delete_student.php redirect
if (isset($_GET['deleted'])) {
    $msg = 'Student "' . htmlspecialchars($_GET['deleted']) . '" was deleted successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $roll     = trim($_POST['roll'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($roll) || empty($username) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            $pdo->beginTransaction();
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
            $stmt->execute([$username, $hash]);
            $userId = $pdo->lastInsertId();
            $stmt2  = $pdo->prepare("INSERT INTO students (name, roll, user_id) VALUES (?, ?, ?)");
            $stmt2->execute([$name, $roll, $userId]);
            $pdo->commit();
            $msg = "Student \"$name\" added. Login: $username / $password";
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = 'Username or Roll Number already exists.';
            } else {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

$students = $pdo->query("
    SELECT s.*, u.username FROM students s
    JOIN users u ON s.user_id = u.id
    ORDER BY s.created_at DESC
")->fetchAll();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student — AttendEase</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
    :root{--navy:#0f1c2e;--navy2:#162235;--blue:#1a6cf6;--blue-lt:#3d85f7;--gold:#f5a623;--mint:#00c9a7;--red:#e74c3c;--green:#27ae60;--text:#e8edf5;--muted:#8a9bb8;--card:#1b2d47;--border:rgba(255,255,255,0.07);--sidebar:180px}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'DM Sans',sans-serif;background:var(--navy);color:var(--text);min-height:100vh;display:flex}
    .sidebar{width:var(--sidebar);background:var(--navy2);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;padding:24px 0}
    .sidebar-brand{display:flex;align-items:center;gap:10px;padding:0 20px 24px;border-bottom:1px solid var(--border);margin-bottom:16px}
    .sidebar-brand .icon{width:36px;height:36px;background:linear-gradient(135deg,var(--blue),var(--mint));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px}
    .sidebar-brand span{font-family:'Sora',sans-serif;font-size:.9rem;font-weight:700;color:var(--text)}
    .sidebar-nav{flex:1;padding:0 12px}
    .nav-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:var(--muted);text-decoration:none;font-size:.85rem;font-weight:500;transition:all .2s;margin-bottom:4px}
    .nav-link:hover{color:var(--text);background:rgba(255,255,255,.05)}
    .nav-link.active{color:var(--blue-lt);background:rgba(26,108,246,.1)}
    .nav-link i{width:16px;text-align:center;font-size:.85rem}
    .sidebar-footer{padding:0 12px}
    .btn-logout{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:rgba(231,76,60,.7);text-decoration:none;font-size:.85rem;font-weight:500;transition:all .2s;width:100%;border:none;background:none;cursor:pointer}
    .btn-logout:hover{color:var(--red);background:rgba(231,76,60,.08)}
    .main{margin-left:var(--sidebar);flex:1;padding:32px;min-height:100vh}
    .page-header{margin-bottom:28px}
    .page-header h1{font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:700;color:var(--text)}
    .page-header p{color:var(--muted);font-size:.875rem;margin-top:4px}
    .card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px}
    .card-title{font-family:'Sora',sans-serif;font-size:1rem;font-weight:600;color:var(--text);margin-bottom:16px}
    .table-wrap{overflow-x:auto}
    table{width:100%;border-collapse:collapse}
    th{color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;padding:10px 14px;border-bottom:1px solid var(--border);text-align:left}
    td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:.88rem;color:var(--text)}
    tr:last-child td{border-bottom:none}
    tr:hover td{background:rgba(255,255,255,.02)}
    .badge{padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:600}
    .badge-present{background:rgba(39,174,96,.15);color:#5ce68a}
    .badge-absent{background:rgba(231,76,60,.15);color:#ff8080}
    .form-label{color:var(--muted);font-size:.8rem;font-weight:500;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;display:block}
    .form-control{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;padding:11px 14px;color:var(--text);font-size:.9rem;font-family:'DM Sans',sans-serif;outline:none;transition:all .2s}
    .form-control::placeholder{color:rgba(138,155,184,.5)}
    .form-control:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(26,108,246,.15);background:rgba(26,108,246,.05)}
    .form-group{margin-bottom:18px}
    .btn{padding:10px 20px;border-radius:10px;font-family:'Sora',sans-serif;font-size:.875rem;font-weight:600;cursor:pointer;border:none;transition:all .2s}
    .btn-primary{background:linear-gradient(135deg,var(--blue),#0f57d4);color:#fff;box-shadow:0 4px 16px rgba(26,108,246,.3)}
    .btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(26,108,246,.4)}
    .btn-danger{background:rgba(231,76,60,.15);color:var(--red);border:1px solid rgba(231,76,60,.2)}
    .btn-danger:hover{background:var(--red);color:#fff}
    .btn-sm{padding:6px 14px;font-size:.78rem}
    .alert{padding:14px 16px;border-radius:10px;margin-bottom:20px;font-size:.875rem;display:flex;align-items:center;gap:10px}
    .alert-success{background:rgba(39,174,96,.12);border:1px solid rgba(39,174,96,.3);color:#5ce68a}
    .alert-danger{background:rgba(231,76,60,.12);border:1px solid rgba(231,76,60,.3);color:#ff8080}
    @media(max-width:768px){.sidebar{width:60px}.sidebar-brand span,.nav-link span,.btn-logout span{display:none}.sidebar-brand{justify-content:center;padding:0 0 24px}.nav-link{justify-content:center;padding:10px}.btn-logout{justify-content:center}.main{margin-left:60px;padding:20px}}
</style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-brand"><div class="icon"><i class="fas fa-graduation-cap"></i></div><span>AttendEase</span></div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i><span>Dashboard</span></a>
        <a href="add_student.php" class="nav-link active"><i class="fas fa-user-plus"></i><span>Add Student</span></a>
        <a href="mark_attendance.php" class="nav-link"><i class="fas fa-clipboard-check"></i><span>Mark Attendance</span></a>
        <a href="report.php" class="nav-link"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
</aside>

<main class="main">
    <div class="page-header">
        <h1>Manage Students</h1>
        <p>Add new students and manage existing ones</p>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><?= $msg ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-times-circle"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:320px 1fr;gap:24px;align-items:start;flex-wrap:wrap">
        <!-- Add form -->
        <div class="card">
            <div class="card-title"><i class="fas fa-user-plus me-2" style="color:var(--blue-lt)"></i>New Student</div>
            <form method="POST" action="add_student.php">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Jane Smith"
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required maxlength="100">
                </div>
                <div class="form-group">
                    <label class="form-label">Roll Number</label>
                    <input type="text" name="roll" class="form-control" placeholder="e.g. CS2024001"
                        value="<?= htmlspecialchars($_POST['roll'] ?? '') ?>" required maxlength="20">
                </div>
                <div class="form-group">
                    <label class="form-label">Login Username</label>
                    <input type="text" name="username" class="form-control" placeholder="e.g. jane_smith"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required maxlength="50">
                </div>
                <div class="form-group">
                    <label class="form-label">Login Password</label>
                    <input type="text" name="password" class="form-control" placeholder="Min. 6 characters"
                        value="<?= htmlspecialchars($_POST['password'] ?? '') ?>" required minlength="6">
                    <small style="color:var(--muted);font-size:.75rem;margin-top:4px;display:block">
                        Share these credentials with the student.
                    </small>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;padding:12px">
                    <i class="fas fa-user-plus me-2"></i>Add Student
                </button>
            </form>
        </div>

        <!-- Student list -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-users me-2" style="color:var(--blue-lt)"></i>
                All Students (<?= count($students) ?>)
            </div>
            <div class="table-wrap">
                <?php if (empty($students)): ?>
                <p style="color:var(--muted);text-align:center;padding:32px">No students yet. Add one!</p>
                <?php else: ?>
                <table>
                    <thead><tr><th>#</th><th>Name</th><th>Roll</th><th>Username</th><th>Joined</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($students as $i => $s): ?>
                    <tr>
                        <td style="color:var(--muted)"><?= $i + 1 ?></td>
                        <td><i class="fas fa-user" style="color:var(--blue-lt);margin-right:8px;font-size:.75rem"></i><?= htmlspecialchars($s['name']) ?></td>
                        <td><span style="font-family:monospace;color:var(--muted)"><?= htmlspecialchars($s['roll']) ?></span></td>
                        <td><span style="color:var(--blue-lt)"><?= htmlspecialchars($s['username']) ?></span></td>
                        <td style="color:var(--muted)"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                        <td>
                            <a href="delete_student.php?id=<?= $s['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete <?= htmlspecialchars(addslashes($s['name'])) ?>?\\nAll attendance data will be permanently lost.')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
</body>
</html>
