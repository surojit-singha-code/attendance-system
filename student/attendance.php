<?php
/**
 * student/attendance.php
 * Shows full date-wise attendance for the logged-in student + percentage.
 */
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php"); exit();
}

$studentId = $_SESSION['student_id'] ?? 0;

// All attendance records, newest first
$records = $pdo->prepare("
    SELECT date, status FROM attendance WHERE student_id = ? ORDER BY date DESC
");
$records->execute([$studentId]);
$records = $records->fetchAll();

// Stats
$total   = count($records);
$present = count(array_filter($records, fn($r) => $r['status'] === 'Present'));
$absent  = $total - $present;
$pct     = $total > 0 ? round($present * 100 / $total, 1) : 0;

// Month-wise grouping
$byMonth = [];
foreach ($records as $r) {
    $monthKey = date('F Y', strtotime($r['date']));
    $byMonth[$monthKey][] = $r;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance — AttendEase</title>
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

    .month-header{
        display:flex;justify-content:space-between;align-items:center;
        padding:10px 14px;background:rgba(255,255,255,.03);
        border-bottom:1px solid var(--border);border-radius:8px 8px 0 0;margin-bottom:0;
    }
    .month-title{font-family:'Sora',sans-serif;font-size:.9rem;font-weight:600;color:var(--text)}
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-brand"><div class="icon"><i class="fas fa-user-graduate"></i></div><span>AttendEase</span></div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i><span>Dashboard</span></a>
        <a href="attendance.php" class="nav-link active"><i class="fas fa-calendar-check"></i><span>My Attendance</span></a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
</aside>

<main class="main">
    <div class="page-header">
        <h1>My Attendance</h1>
        <p>Complete attendance history — <?= htmlspecialchars($_SESSION['student_name'] ?? '') ?></p>
    </div>

    <!-- Summary row -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:16px;margin-bottom:24px">
        <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px;display:flex;align-items:center;gap:14px">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(26,108,246,.15);color:var(--blue-lt);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0"><i class="fas fa-calendar-alt"></i></div>
            <div><div style="font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:700"><?= $total ?></div><div style="color:var(--muted);font-size:.78rem">Total Days</div></div>
        </div>
        <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px;display:flex;align-items:center;gap:14px">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(39,174,96,.15);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0"><i class="fas fa-check-circle"></i></div>
            <div><div style="font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:700"><?= $present ?></div><div style="color:var(--muted);font-size:.78rem">Present</div></div>
        </div>
        <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px;display:flex;align-items:center;gap:14px">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(231,76,60,.15);color:var(--red);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0"><i class="fas fa-times-circle"></i></div>
            <div><div style="font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:700"><?= $absent ?></div><div style="color:var(--muted);font-size:.78rem">Absent</div></div>
        </div>
        <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:20px">
            <div style="color:var(--muted);font-size:.78rem;margin-bottom:8px">Overall %</div>
            <div style="font-family:'Sora',sans-serif;font-size:1.5rem;font-weight:700;color:<?= $pct<75?'var(--gold)':'var(--mint)' ?>"><?= $pct ?>%</div>
            <div class="progress-bar-wrap" style="margin-top:8px">
                <div class="progress-bar-fill <?= $pct<75?'low':'' ?>" style="width:<?= $pct ?>%"></div>
            </div>
        </div>
    </div>

    <?php if (empty($records)): ?>
    <div class="card" style="text-align:center;padding:40px;color:var(--muted)">
        <i class="fas fa-calendar-times" style="font-size:2rem;margin-bottom:12px;display:block;opacity:.4"></i>
        No attendance records found yet.
    </div>
    <?php else: ?>

    <!-- Month-wise grouped records -->
    <?php foreach ($byMonth as $month => $rows):
        $mPresent = count(array_filter($rows, fn($r) => $r['status'] === 'Present'));
        $mTotal   = count($rows);
    ?>
    <div class="card" style="padding:0;overflow:hidden">
        <div class="month-header">
            <div class="month-title"><i class="fas fa-calendar-alt me-2" style="color:var(--mint)"></i><?= $month ?></div>
            <span style="color:var(--muted);font-size:.8rem">
                <?= $mPresent ?>/<?= $mTotal ?> present
                &nbsp;&bull;&nbsp;
                <strong style="color:<?= ($mPresent/$mTotal*100)<75?'var(--gold)':'var(--mint)' ?>">
                    <?= $mTotal > 0 ? round($mPresent/$mTotal*100, 1) : 0 ?>%
                </strong>
            </span>
        </div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Date</th><th>Day</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= date('d M Y', strtotime($r['date'])) ?></td>
                <td style="color:var(--muted)"><?= date('l', strtotime($r['date'])) ?></td>
                <td><span class="badge <?= $r['status']==='Present'?'badge-present':'badge-absent' ?>"><?= $r['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</main>
</body>
</html>
