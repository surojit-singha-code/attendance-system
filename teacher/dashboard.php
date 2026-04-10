<?php
/**
 * teacher/dashboard.php - Teacher Dashboard
 * Shows summary stats: total students, today's attendance count.
 */
session_start();
require_once '../db.php';

// Guard: only teachers allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php"); exit();
}

$today = date('Y-m-d');

// Total students
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();

// Today's attendance counts
$todayStats = $pdo->prepare("
    SELECT
        SUM(status='Present') AS present,
        SUM(status='Absent')  AS absent
    FROM attendance WHERE date = ?
");
$todayStats->execute([$today]);
$stats = $todayStats->fetch();
$presentToday = $stats['present'] ?? 0;
$absentToday  = $stats['absent']  ?? 0;
$markedToday  = $presentToday + $absentToday;

// Recent 5 students
$recentStudents = $pdo->query("SELECT * FROM students ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Check if today's attendance is marked
$attendanceMarked = ($markedToday > 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard — AttendEase</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>

    :root{
        --navy:#0f1c2e;--navy2:#162235;--blue:#1a6cf6;--blue-lt:#3d85f7;
        --gold:#f5a623;--mint:#00c9a7;--red:#e74c3c;--green:#27ae60;
        --text:#e8edf5;--muted:#8a9bb8;--card:#1b2d47;--border:rgba(255,255,255,0.07);
        --sidebar:180px;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'DM Sans',sans-serif;background:var(--navy);color:var(--text);min-height:100vh;display:flex}

    /* SIDEBAR */
    .sidebar{
        width:var(--sidebar);background:var(--navy2);border-right:1px solid var(--border);
        display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;
        padding:24px 0;
    }
    .sidebar-brand{
        display:flex;align-items:center;gap:10px;padding:0 20px 24px;
        border-bottom:1px solid var(--border);margin-bottom:16px;
    }
    .sidebar-brand .icon{
        width:36px;height:36px;background:linear-gradient(135deg,var(--blue),var(--mint));
        border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;
    }
    .sidebar-brand span{font-family:'Sora',sans-serif;font-size:.9rem;font-weight:700;color:var(--text)}
    .sidebar-nav{flex:1;padding:0 12px}
    .nav-link{
        display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;
        color:var(--muted);text-decoration:none;font-size:.85rem;font-weight:500;
        transition:all .2s;margin-bottom:4px;
    }
    .nav-link:hover{color:var(--text);background:rgba(255,255,255,.05)}
    .nav-link.active{color:var(--blue-lt);background:rgba(26,108,246,.1)}
    .nav-link i{width:16px;text-align:center;font-size:.85rem}
    .sidebar-footer{padding:0 12px}
    .btn-logout{
        display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;
        color:rgba(231,76,60,.7);text-decoration:none;font-size:.85rem;font-weight:500;
        transition:all .2s;width:100%;border:none;background:none;cursor:pointer;
    }
    .btn-logout:hover{color:var(--red);background:rgba(231,76,60,.08)}

    /* MAIN CONTENT */
    .main{margin-left:var(--sidebar);flex:1;padding:32px;min-height:100vh}
    .page-header{margin-bottom:28px}
    .page-header h1{font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:700;color:var(--text)}
    .page-header p{color:var(--muted);font-size:.875rem;margin-top:4px}

    /* CARDS */
    .card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px}
    .card-title{font-family:'Sora',sans-serif;font-size:1rem;font-weight:600;color:var(--text);margin-bottom:16px}

    /* STAT CARDS */
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:28px}
    .stat-card{
        background:var(--card);border:1px solid var(--border);border-radius:16px;
        padding:20px;display:flex;align-items:center;gap:16px;
    }
    .stat-icon{
        width:48px;height:48px;border-radius:12px;display:flex;align-items:center;
        justify-content:center;font-size:20px;flex-shrink:0;
    }
    .stat-icon.blue{background:rgba(26,108,246,.15);color:var(--blue-lt)}
    .stat-icon.green{background:rgba(39,174,96,.15);color:var(--green)}
    .stat-icon.gold{background:rgba(245,166,35,.15);color:var(--gold)}
    .stat-icon.mint{background:rgba(0,201,167,.15);color:var(--mint)}
    .stat-icon.red{background:rgba(231,76,60,.15);color:var(--red)}
    .stat-val{font-family:'Sora',sans-serif;font-size:1.6rem;font-weight:700;color:var(--text)}
    .stat-lbl{color:var(--muted);font-size:.78rem;margin-top:2px}

    /* TABLE */
    .table-wrap{overflow-x:auto}
    table{width:100%;border-collapse:collapse}
    th{color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;
       letter-spacing:.5px;padding:10px 14px;border-bottom:1px solid var(--border);text-align:left}
    td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:.88rem;color:var(--text)}
    tr:last-child td{border-bottom:none}
    tr:hover td{background:rgba(255,255,255,.02)}

    /* BADGES */
    .badge{padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:600}
    .badge-present{background:rgba(39,174,96,.15);color:#5ce68a}
    .badge-absent{background:rgba(231,76,60,.15);color:#ff8080}
    .badge-teacher{background:rgba(245,166,35,.15);color:var(--gold)}
    .badge-student{background:rgba(0,201,167,.15);color:var(--mint)}

    /* FORMS */
    .form-label{color:var(--muted);font-size:.8rem;font-weight:500;text-transform:uppercase;
                letter-spacing:.5px;margin-bottom:8px;display:block}
    .form-control{
        width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);
        border-radius:10px;padding:11px 14px;color:var(--text);font-size:.9rem;
        font-family:'DM Sans',sans-serif;outline:none;transition:all .2s;
    }
    .form-control::placeholder{color:rgba(138,155,184,.5)}
    .form-control:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(26,108,246,.15);background:rgba(26,108,246,.05)}
    .form-group{margin-bottom:18px}
    .btn{
        padding:10px 20px;border-radius:10px;font-family:'Sora',sans-serif;
        font-size:.875rem;font-weight:600;cursor:pointer;border:none;transition:all .2s;
    }
    .btn-primary{background:linear-gradient(135deg,var(--blue),#0f57d4);color:#fff;box-shadow:0 4px 16px rgba(26,108,246,.3)}
    .btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(26,108,246,.4)}
    .btn-danger{background:rgba(231,76,60,.15);color:var(--red);border:1px solid rgba(231,76,60,.2)}
    .btn-danger:hover{background:var(--red);color:#fff}
    .btn-success{background:rgba(39,174,96,.15);color:var(--green);border:1px solid rgba(39,174,96,.2)}
    .btn-success:hover{background:var(--green);color:#fff}
    .btn-sm{padding:6px 14px;font-size:.78rem}

    /* ALERT */
    .alert{padding:14px 16px;border-radius:10px;margin-bottom:20px;font-size:.875rem;display:flex;align-items:center;gap:10px}
    .alert-success{background:rgba(39,174,96,.12);border:1px solid rgba(39,174,96,.3);color:#5ce68a}
    .alert-danger{background:rgba(231,76,60,.12);border:1px solid rgba(231,76,60,.3);color:#ff8080}
    .alert-warning{background:rgba(245,166,35,.12);border:1px solid rgba(245,166,35,.3);color:var(--gold)}
    .alert-info{background:rgba(26,108,246,.12);border:1px solid rgba(26,108,246,.3);color:var(--blue-lt)}

    /* PROGRESS */
    .progress-bar-wrap{background:rgba(255,255,255,.06);border-radius:999px;height:6px;overflow:hidden}
    .progress-bar-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--blue),var(--mint));transition:width .6s}
    .progress-bar-fill.low{background:linear-gradient(90deg,#e74c3c,#f5a623)}

    /* RESPONSIVE */
    @media(max-width:768px){
        .sidebar{width:60px}
        .sidebar-brand span,.nav-link span,.btn-logout span{display:none}
        .sidebar-brand{justify-content:center;padding:0 0 24px}
        .nav-link{justify-content:center;padding:10px}
        .btn-logout{justify-content:center}
        .main{margin-left:60px;padding:20px}
    }

    </style>
</head>
<body>
<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="icon"><i class="fas fa-graduation-cap"></i></div>
        <span>AttendEase</span>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link active"><i class="fas fa-home"></i><span>Dashboard</span></a>
        <a href="add_student.php" class="nav-link"><i class="fas fa-user-plus"></i><span>Add Student</span></a>
        <a href="mark_attendance.php" class="nav-link"><i class="fas fa-clipboard-check"></i><span>Mark Attendance</span></a>
        <a href="report.php" class="nav-link"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</aside>

<!-- MAIN -->
<main class="main">
    <div class="page-header">
        <h1>Teacher Dashboard</h1>
        <p>Welcome back, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> &mdash; <?= date('l, F j, Y') ?></p>
    </div>

    <!-- Attendance alert -->
    <?php if (!$attendanceMarked && $totalStudents > 0): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        Today's attendance hasn't been marked yet.
        <a href="mark_attendance.php" style="color:inherit;font-weight:600;margin-left:4px">Mark now →</a>
    </div>
    <?php elseif ($attendanceMarked): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        Today's attendance has been marked — <?= $presentToday ?> present, <?= $absentToday ?> absent.
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <div><div class="stat-val"><?= $totalStudents ?></div><div class="stat-lbl">Total Students</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-user-check"></i></div>
            <div><div class="stat-val"><?= $presentToday ?></div><div class="stat-lbl">Present Today</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-user-times"></i></div>
            <div><div class="stat-val"><?= $absentToday ?></div><div class="stat-lbl">Absent Today</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon gold"><i class="fas fa-calendar-day"></i></div>
            <div><div class="stat-val"><?= $markedToday ?></div><div class="stat-lbl">Marked Today</div></div>
        </div>
    </div>

    <!-- Quick actions -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:28px">
        <a href="mark_attendance.php" class="btn btn-primary" style="text-decoration:none;text-align:center;padding:14px">
            <i class="fas fa-clipboard-check me-2"></i>Mark Today's Attendance
        </a>
        <a href="report.php" class="btn btn-success" style="text-decoration:none;text-align:center;padding:14px">
            <i class="fas fa-chart-bar me-2"></i>View Full Report
        </a>
    </div>

    <!-- Recent students table -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <div class="card-title" style="margin-bottom:0">Recently Added Students</div>
            <a href="add_student.php" class="btn btn-primary btn-sm" style="text-decoration:none">
                <i class="fas fa-plus me-1"></i>Add Student
            </a>
        </div>
        <div class="table-wrap">
            <?php if (empty($recentStudents)): ?>
                <p style="color:var(--muted);text-align:center;padding:24px">No students added yet.</p>
            <?php else: ?>
            <table>
                <thead>
                    <tr><th>Name</th><th>Roll No.</th><th>Joined</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recentStudents as $s): ?>
                    <tr>
                        <td><i class="fas fa-user-graduate" style="color:var(--blue-lt);margin-right:8px"></i><?= htmlspecialchars($s['name']) ?></td>
                        <td><span style="font-family:monospace;color:var(--muted)"><?= htmlspecialchars($s['roll']) ?></span></td>
                        <td style="color:var(--muted)"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
                        <td>
                            <a href="delete_student.php?id=<?= $s['id'] ?>" class="btn btn-danger btn-sm"
                               onclick="return confirm('Delete this student? This action cannot be undone.')">
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
</main>
</body>
</html>
