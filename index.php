<?php
session_start();
// Already logged in? Go to dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'teacher') header("Location: teacher/dashboard.php");
    else header("Location: student/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendEase — Choose Your Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root{--bg:#080e1a;--blue:#1a6cf6;--mint:#00c9a7;--text:#e8edf5;--muted:#7a8faa;--border:rgba(255,255,255,0.06)}
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'DM Sans',sans-serif;background:var(--bg);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;position:relative;padding:20px}
        /* Background orbs */
        body::before{content:'';position:fixed;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(26,108,246,.12) 0%,transparent 65%);top:-150px;left:-150px;pointer-events:none}
        body::after{content:'';position:fixed;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(0,201,167,.1) 0%,transparent 65%);bottom:-120px;right:-120px;pointer-events:none}
        .hero{text-align:center;margin-bottom:52px;animation:up .6s cubic-bezier(.16,1,.3,1) both}
        @keyframes up{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
        .logo-wrap{width:80px;height:80px;background:linear-gradient(135deg,var(--blue),var(--mint));border-radius:22px;display:inline-flex;align-items:center;justify-content:center;font-size:34px;color:#fff;margin-bottom:20px;box-shadow:0 12px 48px rgba(26,108,246,.3)}
        h1{font-family:'Sora',sans-serif;font-size:2.4rem;font-weight:800;color:var(--text);letter-spacing:-1px;margin-bottom:10px}
        h1 span{background:linear-gradient(135deg,var(--blue),var(--mint));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .sub{color:var(--muted);font-size:.95rem}
        /* Portal cards */
        .portals{display:grid;grid-template-columns:1fr 1fr;gap:20px;width:100%;max-width:620px;animation:up .6s .1s cubic-bezier(.16,1,.3,1) both}
        .portal-card{
            background:rgba(255,255,255,.03);border:1px solid var(--border);
            border-radius:20px;padding:32px 28px;text-decoration:none;
            display:flex;flex-direction:column;align-items:center;text-align:center;
            transition:all .25s;position:relative;overflow:hidden;
        }
        .portal-card::before{content:'';position:absolute;inset:0;opacity:0;transition:opacity .25s}
        .portal-card.teacher::before{background:linear-gradient(160deg,rgba(26,108,246,.07) 0%,transparent 60%)}
        .portal-card.student::before{background:linear-gradient(160deg,rgba(0,201,167,.07) 0%,transparent 60%)}
        .portal-card:hover{transform:translateY(-4px)}
        .portal-card.teacher:hover{border-color:rgba(26,108,246,.35);box-shadow:0 20px 60px rgba(26,108,246,.15)}
        .portal-card.student:hover{border-color:rgba(0,201,167,.35);box-shadow:0 20px 60px rgba(0,201,167,.12)}
        .portal-card:hover::before{opacity:1}
        .card-icon{
            width:64px;height:64px;border-radius:18px;display:flex;align-items:center;
            justify-content:center;font-size:26px;color:#fff;margin-bottom:20px;position:relative;z-index:1;
        }
        .teacher .card-icon{background:linear-gradient(135deg,var(--blue),#0f57d4);box-shadow:0 8px 28px rgba(26,108,246,.35)}
        .student .card-icon{background:linear-gradient(135deg,var(--mint),#00a381);box-shadow:0 8px 28px rgba(0,201,167,.3)}
        .card-label{font-family:'Sora',sans-serif;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1.2px;padding:4px 12px;border-radius:999px;margin-bottom:12px;position:relative;z-index:1}
        .teacher .card-label{background:rgba(26,108,246,.12);color:#6aabff;border:1px solid rgba(26,108,246,.2)}
        .student .card-label{background:rgba(0,201,167,.1);color:var(--mint);border:1px solid rgba(0,201,167,.18)}
        .card-title{font-family:'Sora',sans-serif;font-size:1.2rem;font-weight:700;color:var(--text);margin-bottom:8px;position:relative;z-index:1}
        .card-desc{color:var(--muted);font-size:.82rem;line-height:1.6;margin-bottom:20px;position:relative;z-index:1}
        .card-btn{
            padding:10px 24px;border-radius:10px;font-family:'Sora',sans-serif;font-size:.82rem;
            font-weight:700;display:inline-flex;align-items:center;gap:8px;position:relative;z-index:1;
            transition:all .2s;
        }
        .teacher .card-btn{background:linear-gradient(135deg,var(--blue),#0f57d4);color:#fff;box-shadow:0 4px 16px rgba(26,108,246,.3)}
        .student .card-btn{background:linear-gradient(135deg,var(--mint),#00a381);color:#fff;box-shadow:0 4px 16px rgba(0,201,167,.25)}
        .portal-card:hover .card-btn{transform:scale(1.04)}
        footer{margin-top:36px;color:var(--muted);font-size:.75rem;animation:up .6s .2s cubic-bezier(.16,1,.3,1) both}
        @media(max-width:520px){.portals{grid-template-columns:1fr} h1{font-size:1.8rem}}
    </style>
</head>
<body>
<div class="hero">
    <div class="logo-wrap"><i class="fas fa-graduation-cap"></i></div>
    <h1>Attend<span>Ease</span></h1>
    <p class="sub">Attendance Management System — Choose your portal</p>
</div>

<div class="portals">
    <!-- Teacher -->
    <a href="teacher_login.php" class="portal-card teacher">
        <div class="card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="card-label">Admin Panel</div>
        <div class="card-title">Teacher Portal</div>
        <p class="card-desc">Manage students, mark attendance, and generate full reports.</p>
        <div class="card-btn"><i class="fas fa-sign-in-alt"></i>Enter as Teacher</div>
    </a>

    <!-- Student -->
    <a href="student_login.php" class="portal-card student">
        <div class="card-icon"><i class="fas fa-user-graduate"></i></div>
        <div class="card-label">Student Portal</div>
        <div class="card-title">Student Portal</div>
        <p class="card-desc">View your personal attendance history and percentage.</p>
        <div class="card-btn"><i class="fas fa-sign-in-alt"></i>Enter as Student</div>
    </a>
</div>

<footer>&copy; <?= date('Y') ?> AttendEase &mdash; College Project</footer>
</body>
</html>
