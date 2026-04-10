<?php
/**
 * teacher/report.php
 * Full attendance report: date-wise records + per-student % calculations.
 */
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php"); exit();
}

// Per-student attendance percentage
$summary = $pdo->query("
    SELECT
        s.id, s.name, s.roll,
        COUNT(a.id)                         AS total_days,
        SUM(a.status = 'Present')           AS present_days,
        SUM(a.status = 'Absent')            AS absent_days,
        ROUND(SUM(a.status='Present') * 100.0 / NULLIF(COUNT(a.id),0), 1) AS percentage
    FROM students s
    LEFT JOIN attendance a ON a.student_id = s.id
    GROUP BY s.id
    ORDER BY s.roll
")->fetchAll();

// Date-wise attendance (latest 20 dates)
$dates = $pdo->query("
    SELECT DISTINCT date FROM attendance ORDER BY date DESC LIMIT 20
")->fetchAll(PDO::FETCH_COLUMN);

// For each date: all students' status
$byDate = [];
foreach ($dates as $date) {
    $stmt = $pdo->prepare("
        SELECT s.name, s.roll, a.status
        FROM students s
        LEFT JOIN attendance a ON a.student_id = s.id AND a.date = ?
        ORDER BY s.roll
    ");
    $stmt->execute([$date]);
    $byDate[$date] = $stmt->fetchAll();
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report — AttendEase</title>
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
    .btn{padding:10px 20px;border-radius:10px;font-family:'Sora',sans-serif;font-size:.875rem;font-weight:600;cursor:pointer;border:none;transition:all .2s}
    .btn-primary{background:linear-gradient(135deg,var(--blue),#0f57d4);color:#fff;box-shadow:0 4px 16px rgba(26,108,246,.3)}
    .btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(26,108,246,.4)}
    .btn-sm{padding:6px 14px;font-size:.78rem}
    .form-label{color:var(--muted);font-size:.8rem;font-weight:500;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;display:block}
    .form-control{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:10px;padding:11px 14px;color:var(--text);font-size:.9rem;font-family:'DM Sans',sans-serif;outline:none;transition:all .2s}
    .form-control:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(26,108,246,.15);background:rgba(26,108,246,.05)}
    .progress-bar-wrap{background:rgba(255,255,255,.06);border-radius:999px;height:6px;overflow:hidden}
    .progress-bar-fill{height:100%;border-radius:999px;background:linear-gradient(90deg,var(--blue),var(--mint));transition:width .6s}
    .progress-bar-fill.low{background:linear-gradient(90deg,#e74c3c,#f5a623)}
    @media(max-width:768px){.sidebar{width:60px}.sidebar-brand span,.nav-link span,.btn-logout span{display:none}.sidebar-brand{justify-content:center;padding:0 0 24px}.nav-link{justify-content:center;padding:10px}.btn-logout{justify-content:center}.main{margin-left:60px;padding:20px}}

    .tab-bar{display:flex;gap:4px;margin-bottom:20px;background:var(--navy2);border-radius:12px;padding:4px}
    .tab-btn{flex:1;padding:10px;border:none;border-radius:10px;background:none;color:var(--muted);font-family:'Sora',sans-serif;font-size:.85rem;font-weight:600;cursor:pointer;transition:all .2s}
    .tab-btn.active{background:var(--card);color:var(--blue-lt);box-shadow:0 2px 8px rgba(0,0,0,.2)}
    .tab-content{display:none} .tab-content.active{display:block}
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-brand"><div class="icon"><i class="fas fa-graduation-cap"></i></div><span>AttendEase</span></div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i><span>Dashboard</span></a>
        <a href="add_student.php" class="nav-link"><i class="fas fa-user-plus"></i><span>Add Student</span></a>
        <a href="mark_attendance.php" class="nav-link"><i class="fas fa-clipboard-check"></i><span>Mark Attendance</span></a>
        <a href="report.php" class="nav-link active"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
    </nav>
    <div class="sidebar-footer"><a href="../logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a></div>
</aside>

<main class="main">
    <div class="page-header">
        <h1>Attendance Reports</h1>
        <p>View per-student percentages and date-wise records</p>
    </div>

    <div class="tab-bar">
        <button class="tab-btn active" onclick="showTab('summary')"><i class="fas fa-chart-pie me-2"></i>Summary</button>
        <button class="tab-btn" onclick="showTab('datewise')"><i class="fas fa-calendar-alt me-2"></i>Date-wise</button>
    </div>

    <!-- SUMMARY TAB -->
    <div id="tab-summary" class="tab-content active">
        <div class="card">
            <div class="card-title">Student Attendance Summary</div>
            <?php if (empty($summary)): ?>
            <p style="color:var(--muted);text-align:center;padding:24px">No data yet.</p>
            <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Name</th><th>Roll</th><th>Total Days</th><th>Present</th><th>Absent</th><th>Percentage</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($summary as $s):
                        $pct = $s['percentage'] ?? 0;
                        $lowAtt = $pct < 75;
                    ?>
                    <tr>
                        <td><i class="fas fa-user-graduate" style="color:var(--blue-lt);margin-right:8px"></i><?= htmlspecialchars($s['name']) ?></td>
                        <td><span style="font-family:monospace;color:var(--muted)"><?= htmlspecialchars($s['roll']) ?></span></td>
                        <td style="color:var(--muted)"><?= $s['total_days'] ?></td>
                        <td><span style="color:#5ce68a;font-weight:600"><?= $s['present_days'] ?? 0 ?></span></td>
                        <td><span style="color:#ff8080;font-weight:600"><?= $s['absent_days'] ?? 0 ?></span></td>
                        <td style="min-width:140px">
                            <div style="display:flex;align-items:center;gap:10px">
                                <div class="progress-bar-wrap" style="flex:1">
                                    <div class="progress-bar-fill <?= $lowAtt ? 'low' : '' ?>"
                                         style="width:<?= $pct ?>%"></div>
                                </div>
                                <span style="font-family:'Sora',sans-serif;font-size:.85rem;font-weight:700;
                                      color:<?= $lowAtt ? 'var(--gold)' : 'var(--mint)' ?>;min-width:42px">
                                    <?= $pct ?>%
                                </span>
                            </div>
                            <?php if ($lowAtt && $s['total_days'] > 0): ?>
                            <small style="color:var(--gold);font-size:.72rem"><i class="fas fa-exclamation-triangle me-1"></i>Low attendance</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- DATE-WISE TAB -->
    <div id="tab-datewise" class="tab-content">
        <?php if (empty($dates)): ?>
        <div class="card"><p style="color:var(--muted);text-align:center;padding:24px">No attendance records yet.</p></div>
        <?php else:
            foreach ($dates as $date):
                $presentCount = array_reduce($byDate[$date], fn($c,$r) => $c + ($r['status']==='Present'?1:0), 0);
                $total = count($byDate[$date]);
        ?>
        <div class="card" style="margin-bottom:16px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px">
                <div class="card-title" style="margin-bottom:0">
                    <i class="fas fa-calendar-day me-2" style="color:var(--blue-lt)"></i>
                    <?= date('l, d F Y', strtotime($date)) ?>
                </div>
                <span style="color:var(--muted);font-size:.82rem">
                    <?= $presentCount ?>/<?= $total ?> present
                </span>
            </div>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Name</th><th>Roll</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($byDate[$date] as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td><span style="font-family:monospace;color:var(--muted)"><?= htmlspecialchars($r['roll']) ?></span></td>
                    <td>
                        <span class="badge <?= ($r['status']==='Present') ? 'badge-present' : 'badge-absent' ?>">
                            <?= $r['status'] ?? 'Not Marked' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</main>

<script>
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.target.classList.add('active');
}
</script>
</body>
</html>
