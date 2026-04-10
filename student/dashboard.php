<?php
/**
 * student/dashboard.php
 * Student's personal dashboard: summary stats + quick attendance overview.
 */
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php"); exit();
}

$studentId = $_SESSION['student_id'] ?? 0;
$name = $_SESSION['student_name'] ?? 'Student';

// Get student info
$student = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$student->execute([$studentId]);
$student = $student->fetch();

// Attendance stats
$stats = $pdo->prepare("
    SELECT
        COUNT(*)                         AS total,
        SUM(status = 'Present')          AS present,
        SUM(status = 'Absent')           AS absent,
        ROUND(SUM(status='Present') * 100.0 / NULLIF(COUNT(*), 0), 1) AS percentage
    FROM attendance WHERE student_id = ?
");
$stats->execute([$studentId]);
$stats = $stats->fetch();
$total    = $stats['total']   ?? 0;
$present  = $stats['present'] ?? 0;
$absent   = $stats['absent']  ?? 0;
$pct      = $stats['percentage'] ?? 0;

// Last 5 attendance records
$recent = $pdo->prepare("
    SELECT date, status FROM attendance WHERE student_id = ? ORDER BY date DESC LIMIT 5
");
$recent->execute([$studentId]);
$recent = $recent->fetchAll();

// Today's status
$todayStatus = $pdo->prepare("SELECT status FROM attendance WHERE student_id = ? AND date = ?");
$todayStatus->execute([$studentId, date('Y-m-d')]);
$todayStatus = $todayStatus->fetchColumn();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard — AttendEase</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>

    :root{--navy:#0f1c2e;--navy2:#162235;--blue:#1a6cf6;--blue-lt:#3d85f7;--gold:#f5a623;--mint:#00c9a7;--red:#e74c3c;--green:#27ae60;--text:#e8edf5;--muted:#8a9bb8;--card:#1b2d47;--border:rgba(255,255,255,0.07);--sidebar:200px}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'DM Sans',sans-serif;background:var(--navy);color:var(--text);min-height:100vh;display:flex}
    .sidebar{width:var(--sidebar);background:var(--navy2);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;padding:24px 0}
    .sidebar-brand{display:flex;align-items:center;gap:10px;padding:0 20px 24px;border-bottom:1px solid var(--border);margin-bottom:16px}
    .sidebar-brand .icon{width:36px;height:36px;background:linear-gradient(135deg,var(--mint),#00a381);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px}
    .sidebar-brand span{font-family:'Sora',sans-serif;font-size:.9rem;font-weight:700;color:var(--text)}
    .sidebar-nav{flex:1;padding:0 12px}
    .nav-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:var(--muted);text-decoration:none;font-size:.85rem;font-weight:500;transition:all .2s;margin-bottom:4px}
    .nav-link:hover{color:var(--text);background:rgba(255,255,255,.05)}
    .nav-link.active{color:var(--mint);background:rgba(0,201,167,.1)}
    .nav-link i{width:16px;text-align:center;font-size:.85rem}
    .sidebar-footer{padding:0 12px}
    .btn-logout{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:rgba(231,76,60,.7);text-decoration:none;font-size:.85rem;font-weight:500;transition:all .2s;width:100%;border:none;background:none;cursor:pointer}
    .btn-logout:hover{color:var(--red);background:rgba(231,76,60,.08)}
    .main{margin-left:var(--sidebar);flex:1;padding:32px;min-height:100vh}
    .page-header{margin-bottom:28px}
    .page-header h1{font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:700;color:var(--text)}
    .page-header p{color:var(--muted);font-size:.875rem;margin-top:4px}
    .card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px;margin-bottom:20px}
    .card-title{font-family:'Sora',sans-serif;font-size:1rem;font-weight:600;color:var(--text);margin-bottom:16px}
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-bottom:28px}
    .stat-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px;display:flex;align-items:center;gap:16px}
    .stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
    .stat-icon.blue{background:rgba(26,108,246,.15);color:var(--blue-lt)}
    .stat-icon.green{background:rgba(39,174,96,.15);color:var(--green)}
    .stat-icon.gold{background:rgba(245,166,35,.15);color:var(--gold)}
    .stat-icon.mint{background:rgba(0,201,167,.15);color:var(--mint)}
    .stat-icon.red{background:rgba(231,76,60,.15);color:var(--red)}
    .stat-val{font-family:'Sora',sans-serif;font-size:1.6rem;font-weight:700;color:var(--text)}
    .stat-lbl{color:var(--muted);font-size:.78rem;margin-top:2px}
    .table-wrap{overflow-x:auto}
    table{width:100%;border-collapse:collapse}
    th{color:var(--muted);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;padding:10px 14px;border-bottom:1px solid var(--border);text-align:left}
    td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:.88rem;color:var(--text)}
    tr:last-child td{border-bottom:none}
    tr:hover td{background:rgba(255,255,255,.02)}
    .badge{padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:600}
    .badge-present{background:rgba(39,174,96,.15);color:#5ce68a}
    .badge-absent{background:rgba(231,76,60,.15);color:#ff8080}
    .progress-bar-wrap{background:rgba(255,255,255,.06);border-radius:999px;height:8px;overflow:hidden}
    .progress-bar-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--mint),var(--blue));transition:width 1s}
    .progress-bar-fill.low{background:linear-gradient(90deg,#e74c3c,#f5a623)}
    @media(max-width:768px){.sidebar{width:60px}.sidebar-brand span,.nav-link span,.btn-logout span{display:none}.sidebar-brand{justify-content:center;padding:0 0 24px}.nav-link{justify-content:center;padding:10px}.btn-logout{justify-content:center}.main{margin-left:60px;padding:20px}}

    .welcome-card{
        background:linear-gradient(135deg,rgba(0,201,167,.15),rgba(26,108,246,.1));
        border:1px solid rgba(0,201,167,.2);border-radius:16px;padding:28px;
        margin-bottom:24px;display:flex;align-items:center;gap:20px;
    }
    .welcome-avatar{
        width:56px;height:56px;background:linear-gradient(135deg,var(--mint),var(--blue));
        border-radius:50%;display:flex;align-items:center;justify-content:center;
        font-size:22px;font-family:'Sora',sans-serif;font-weight:700;color:#fff;flex-shrink:0;
    }
    .big-pct{font-family:'Sora',sans-serif;font-size:3.5rem;font-weight:800;line-height:1}
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-brand"><div class="icon"><i class="fas fa-user-graduate"></i></div><span>AttendEase</span></div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link active"><i class="fas fa-home"></i><span>Dashboard</span></a>
        <a href="attendance.php" class="nav-link"><i class="fas fa-calendar-check"></i><span>My Attendance</span></a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
</aside>

<main class="main">
    <!-- Welcome -->
    <div class="welcome-card">
        <div class="welcome-avatar"><?php echo strtoupper(substr($name, 0, 1)); ?></div>
        <div>
            <div style="font-family:'Sora',sans-serif;font-size:1.3rem;font-weight:700">
                Welcome, <?= htmlspecialchars($name) ?>!
            </div>
            <div style="color:var(--muted);font-size:.85rem;margin-top:4px">
                Roll: <strong style="color:var(--mint)"><?= htmlspecialchars($student['roll'] ?? '') ?></strong>
                &nbsp;&bull;&nbsp; <?= date('l, F j, Y') ?>
            </div>
        </div>
        <?php if ($todayStatus): ?>
        <div style="margin-left:auto">
            <span class="badge <?= $todayStatus === 'Present' ? 'badge-present' : 'badge-absent' ?>" style="font-size:.85rem;padding:6px 16px">
                Today: <?= $todayStatus ?>
            </span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-calendar-alt"></i></div>
            <div><div class="stat-val"><?= $total ?></div><div class="stat-lbl">Total Days</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div><div class="stat-val"><?= $present ?></div><div class="stat-lbl">Days Present</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
            <div><div class="stat-val"><?= $absent ?></div><div class="stat-lbl">Days Absent</div></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
        <!-- Attendance percentage -->
        <div class="card" style="text-align:center">
            <div class="card-title">Overall Attendance</div>
            <div class="big-pct" style="color:<?= $pct < 75 ? 'var(--gold)' : 'var(--mint)' ?>">
                <?= $pct ?>%
            </div>
            <div class="progress-bar-wrap" style="margin:16px 0 8px">
                <div class="progress-bar-fill <?= $pct < 75 ? 'low' : '' ?>" style="width:<?= $pct ?>%"></div>
            </div>
            <?php if ($pct < 75 && $total > 0): ?>
            <small style="color:var(--gold)"><i class="fas fa-exclamation-triangle me-1"></i>Below 75% threshold</small>
            <?php elseif ($total === 0): ?>
            <small style="color:var(--muted)">No records yet</small>
            <?php else: ?>
            <small style="color:var(--mint)"><i class="fas fa-check me-1"></i>Good standing</small>
            <?php endif; ?>
        </div>

        <!-- Recent records -->
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <div class="card-title" style="margin-bottom:0">Recent Records</div>
                <a href="attendance.php" style="color:var(--mint);font-size:.8rem;text-decoration:none">View all →</a>
            </div>
            <?php if (empty($recent)): ?>
            <p style="color:var(--muted);text-align:center;padding:16px">No records yet.</p>
            <?php else: ?>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $r): ?>
                <tr>
                    <td style="color:var(--muted)"><?= date('d M Y', strtotime($r['date'])) ?></td>
                    <td><span class="badge <?= $r['status'] === 'Present' ? 'badge-present' : 'badge-absent' ?>"><?= $r['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
