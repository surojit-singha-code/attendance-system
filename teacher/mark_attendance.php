<?php
/**
 * teacher/mark_attendance.php
 * Mark or UPDATE attendance (Present/Absent) for any date.
 * Uses INSERT ... ON DUPLICATE KEY UPDATE so the same date can
 * be corrected any number of times without errors.
 */
session_start();
require_once '../db.php';

// Guard: teachers only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php"); exit();
}

$today   = date('Y-m-d');
$selDate = $_GET['date'] ?? $today;

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selDate)) {
    $selDate = $today;
}

$msg   = '';
$error = '';

// ── Handle form submission ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read the posted date (comes from the hidden <input name="date">)
    $postDate = trim($_POST['date'] ?? $today);

    // Validate posted date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $postDate)) {
        $postDate = $today;
    }

    // status[] array: keys = student_id, values = 'Present' | 'Absent'
    $statuses = $_POST['status'] ?? [];

    if (empty($statuses)) {
        $error = 'No students found. Please add students first.';
    } else {
        /*
         * INSERT ... ON DUPLICATE KEY UPDATE
         * The UNIQUE KEY on (student_id, date) means:
         *   - First time for this date  → INSERT a new row
         *   - Already exists            → UPDATE the status in place
         * This lets teachers correct mistakes at any time.
         */
        $stmt = $pdo->prepare("
            INSERT INTO attendance (student_id, date, status)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");

        $count = 0;
        foreach ($statuses as $studentId => $status) {
            // Only accept valid values — ignore anything else
            if (!in_array($status, ['Present', 'Absent'])) continue;
            $stmt->execute([intval($studentId), $postDate, $status]);
            $count++;
        }

        $msg     = "Attendance saved / updated for {$count} student(s) on {$postDate}.";
        $selDate = $postDate; // keep the form showing the same date after save
    }
}

// ── Load students with their current status for $selDate ───────────────────
$studentsStmt = $pdo->prepare("
    SELECT s.id, s.name, s.roll, a.status
    FROM   students s
    LEFT JOIN attendance a
           ON a.student_id = s.id AND a.date = ?
    ORDER  BY s.roll
");
$studentsStmt->execute([$selDate]);
$students = $studentsStmt->fetchAll();

// Flag: has this date been marked before? (used for the info banner only)
$alreadyMarked = false;
foreach ($students as $s) {
    if ($s['status'] !== null) { $alreadyMarked = true; break; }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance — AttendEase</title>
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
    .btn-success{background:rgba(39,174,96,.15);color:var(--green);border:1px solid rgba(39,174,96,.2)}
    .btn-success:hover{background:var(--green);color:#fff}
    .btn-sm{padding:6px 14px;font-size:.78rem}
    .alert{padding:14px 16px;border-radius:10px;margin-bottom:20px;font-size:.875rem;display:flex;align-items:center;gap:10px}
    .alert-success{background:rgba(39,174,96,.12);border:1px solid rgba(39,174,96,.3);color:#5ce68a}
    .alert-danger{background:rgba(231,76,60,.12);border:1px solid rgba(231,76,60,.3);color:#ff8080}
    .alert-warning{background:rgba(245,166,35,.12);border:1px solid rgba(245,166,35,.3);color:var(--gold)}
    .alert-info{background:rgba(26,108,246,.12);border:1px solid rgba(26,108,246,.3);color:var(--blue-lt)}
    @media(max-width:768px){.sidebar{width:60px}.sidebar-brand span,.nav-link span,.btn-logout span{display:none}.sidebar-brand{justify-content:center;padding:0 0 24px}.nav-link{justify-content:center;padding:10px}.btn-logout{justify-content:center}.main{margin-left:60px;padding:20px}}

    /* Radio toggle buttons */
    .toggle-wrap{display:flex;gap:8px}
    .radio-btn{display:none}
    .radio-label{
        padding:7px 16px;border-radius:8px;cursor:pointer;font-size:.82rem;font-weight:600;
        border:1px solid var(--border);color:var(--muted);transition:all .2s;user-select:none;
    }
    .radio-label:hover{color:var(--text);border-color:rgba(255,255,255,.15)}
    .radio-btn[value="Present"]:checked + .radio-label{background:rgba(39,174,96,.2);color:#5ce68a;border-color:rgba(39,174,96,.4)}
    .radio-btn[value="Absent"]:checked  + .radio-label{background:rgba(231,76,60,.2);color:#ff8080;border-color:rgba(231,76,60,.4)}
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
        <a href="dashboard.php"       class="nav-link"><i class="fas fa-home"></i><span>Dashboard</span></a>
        <a href="add_student.php"     class="nav-link"><i class="fas fa-user-plus"></i><span>Add Student</span></a>
        <a href="mark_attendance.php" class="nav-link active"><i class="fas fa-clipboard-check"></i><span>Mark Attendance</span></a>
        <a href="report.php"          class="nav-link"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
    </nav>
    <div class="sidebar-footer">
        <a href="../logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="main">
    <div class="page-header">
        <h1>Mark Attendance</h1>
        <p>Select a date and mark or update each student's attendance</p>
    </div>

    <!-- Success message -->
    <?php if (!empty($msg)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <!-- Error message -->
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-times-circle"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- ── Date picker ── -->
    <form method="GET" action="mark_attendance.php"
          class="card" style="margin-bottom:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <div>
            <label class="form-label" style="margin-bottom:0;margin-right:8px">Select Date</label>
            <input type="date" name="date" class="form-control"
                   style="width:auto;display:inline-block"
                   value="<?= htmlspecialchars($selDate) ?>"
                   max="<?= $today ?>">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search me-2"></i>Load Students
        </button>
    </form>

    <!-- ── No students yet ── -->
    <?php if (empty($students)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-info-circle"></i>
        No students found.
        <a href="add_student.php" style="color:inherit;font-weight:600;margin-left:4px">Add students first →</a>
    </div>

    <?php else: ?>

    <!-- Info banner when this date already has records -->
    <?php if ($alreadyMarked): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        Attendance for <strong><?= htmlspecialchars($selDate) ?></strong> was already marked.
        Current values are pre-selected below — change any and click <strong>Save / Update</strong>.
    </div>
    <?php endif; ?>

    <!-- ── Attendance form ── -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <div class="card-title" style="margin-bottom:0">
                Students &mdash; <?= date('D, d M Y', strtotime($selDate)) ?>
            </div>
            <!-- Bulk action buttons -->
            <div>
                <button type="button" onclick="markAll('Present')" class="btn btn-success btn-sm me-1">
                    <i class="fas fa-check me-1"></i>All Present
                </button>
                <button type="button" onclick="markAll('Absent')" class="btn btn-danger btn-sm">
                    <i class="fas fa-times me-1"></i>All Absent
                </button>
            </div>
        </div>

        <form method="POST" action="mark_attendance.php">
            <!-- Pass the currently viewed date to PHP -->
            <input type="hidden" name="date" value="<?= htmlspecialchars($selDate) ?>">

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Roll No.</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($students as $i => $s): ?>
                    <tr>
                        <td style="color:var(--muted)"><?= $i + 1 ?></td>

                        <td>
                            <i class="fas fa-user-graduate"
                               style="color:var(--blue-lt);margin-right:8px"></i>
                            <?= htmlspecialchars($s['name']) ?>
                        </td>

                        <td>
                            <span style="font-family:monospace;color:var(--muted)">
                                <?= htmlspecialchars($s['roll']) ?>
                            </span>
                        </td>

                        <td>
                            <!--
                                Radio buttons are ALWAYS shown (never read-only).
                                If a record already exists the saved value is pre-selected,
                                otherwise Present is selected by default.
                            -->
                            <div class="toggle-wrap">
                                <input
                                    type="radio"
                                    name="status[<?= $s['id'] ?>]"
                                    id="p_<?= $s['id'] ?>"
                                    class="radio-btn"
                                    value="Present"
                                    <?= ($s['status'] !== 'Absent') ? 'checked' : '' ?>>
                                <label for="p_<?= $s['id'] ?>" class="radio-label">Present</label>

                                <input
                                    type="radio"
                                    name="status[<?= $s['id'] ?>]"
                                    id="a_<?= $s['id'] ?>"
                                    class="radio-btn"
                                    value="Absent"
                                    <?= ($s['status'] === 'Absent') ? 'checked' : '' ?>>
                                <label for="a_<?= $s['id'] ?>" class="radio-label">Absent</label>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div><!-- /.table-wrap -->

            <!-- Save button is ALWAYS visible — works for both new and existing records -->
            <div style="margin-top:20px;text-align:right">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save / Update Attendance
                </button>
            </div>
        </form>
    </div><!-- /.card -->

    <?php endif; ?>
</main>

<script>
/**
 * markAll(status)
 * Selects all radio buttons of the given value in one click.
 */
function markAll(status) {
    document.querySelectorAll('.radio-btn[value="' + status + '"]')
            .forEach(function(r){ r.checked = true; });
}
</script>
</body>
</html>
