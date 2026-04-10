<?php
/**
 * student_login.php - Student Login Portal
 * Only accepts accounts with role = 'student'
 */
session_start();
require_once 'db.php';

// Already logged in as student
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
    header("Location: student/dashboard.php"); exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'student'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = 'student';

            // Load student profile into session
            $s = $pdo->prepare("SELECT id, name FROM students WHERE user_id = ?");
            $s->execute([$user['id']]);
            $student = $s->fetch();
            if ($student) {
                $_SESSION['student_id']   = $student['id'];
                $_SESSION['student_name'] = $student['name'];
            }
            header("Location: student/dashboard.php");
            exit();
        } else {
            $error = 'Invalid credentials or you are not registered as a student.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal — AttendEase</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:    #08131a;
            --panel: #0b1a20;
            --mint:  #00c9a7;
            --mint2: #00a381;
            --teal:  #0891b2;
            --text:  #e2f0ee;
            --muted: #6b8f88;
            --border: rgba(0,201,167,0.08);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            flex-direction: row-reverse; /* form on left, brand on right */
            overflow: hidden;
        }

        /* ── Right branding panel ── */
        .brand-panel {
            width: 45%;
            background: linear-gradient(160deg, #051a14 0%, #031410 60%, #020d0b 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 56px;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0,201,167,0.15) 0%, transparent 65%);
            bottom: -120px; left: -100px;
        }
        .brand-panel::after {
            content: '';
            position: absolute;
            width: 280px; height: 280px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0,201,167,0.08) 0%, transparent 65%);
            top: -40px; right: -60px;
        }

        .dot-pattern {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(0,201,167,0.08) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        .brand-content { position: relative; z-index: 2; }

        .student-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0,201,167,0.1);
            border: 1px solid rgba(0,201,167,0.2);
            border-radius: 999px;
            padding: 6px 16px;
            color: var(--mint);
            font-size: .78rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 32px;
        }

        .brand-logo {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, var(--mint), var(--mint2));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #fff;
            margin-bottom: 28px;
            box-shadow: 0 12px 40px rgba(0,201,167,0.3);
        }

        .brand-title {
            font-family: 'Sora', sans-serif;
            font-size: 2.6rem;
            font-weight: 800;
            color: var(--text);
            line-height: 1.15;
            letter-spacing: -1px;
            margin-bottom: 16px;
        }
        .brand-title span { color: var(--mint); }

        .brand-desc {
            color: var(--muted);
            font-size: .95rem;
            line-height: 1.7;
            max-width: 340px;
            margin-bottom: 40px;
        }

        .feature-list { display: flex; flex-direction: column; gap: 14px; }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 14px;
            color: var(--muted);
            font-size: .875rem;
        }
        .feature-icon {
            width: 36px; height: 36px;
            background: rgba(0,201,167,0.08);
            border: 1px solid rgba(0,201,167,0.15);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--mint);
            font-size: .8rem;
            flex-shrink: 0;
        }

        /* ── Left login form ── */
        .login-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
            background: var(--panel);
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            animation: fadeUp .5s cubic-bezier(.16,1,.3,1) both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .login-header { margin-bottom: 32px; }
        .login-header h2 {
            font-family: 'Sora', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 6px;
        }
        .login-header p { color: var(--muted); font-size: .88rem; }

        .role-strip {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(0,201,167,0.07);
            border: 1px solid rgba(0,201,167,0.18);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 24px;
        }
        .role-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: var(--mint);
            box-shadow: 0 0 8px var(--mint);
            animation: pulse 2s infinite;
        }
        @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
        .role-strip span { color: var(--mint); font-size: .82rem; font-weight: 600; }
        .role-strip small { color: var(--muted); font-size: .78rem; margin-left: auto; }

        .form-group { margin-bottom: 18px; }
        .form-label {
            display: block;
            color: var(--muted);
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 8px;
        }
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: .85rem;
            transition: color .2s;
        }
        .form-input {
            width: 100%;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(0,201,167,0.08);
            border-radius: 11px;
            padding: 13px 14px 13px 42px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: .92rem;
            outline: none;
            transition: all .2s;
        }
        .form-input::placeholder { color: rgba(107,143,136,0.5); }
        .form-input:focus {
            border-color: var(--mint);
            background: rgba(0,201,167,0.04);
            box-shadow: 0 0 0 3px rgba(0,201,167,0.1);
        }
        .form-input:focus + .input-icon,
        .input-wrap:focus-within .input-icon { color: var(--mint); }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--mint), var(--mint2));
            border: none;
            border-radius: 11px;
            color: #fff;
            font-family: 'Sora', sans-serif;
            font-size: .95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
            box-shadow: 0 6px 24px rgba(0,201,167,0.3);
            letter-spacing: .3px;
            margin-top: 6px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 32px rgba(0,201,167,0.4);
        }

        .alert-error {
            background: rgba(231,76,60,0.08);
            border: 1px solid rgba(231,76,60,0.2);
            border-radius: 10px;
            padding: 12px 16px;
            color: #ff7070;
            font-size: .855rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .divider {
            text-align: center;
            margin: 24px 0 20px;
            position: relative;
            color: var(--muted);
            font-size: .78rem;
        }
        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 38%;
            height: 1px;
            background: rgba(0,201,167,0.08);
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        .portal-switch {
            text-align: center;
            padding: 14px;
            border: 1px solid rgba(0,201,167,0.08);
            border-radius: 11px;
            color: var(--muted);
            font-size: .845rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all .2s;
        }
        .portal-switch:hover {
            background: rgba(255,255,255,0.02);
            border-color: rgba(0,201,167,0.2);
            color: var(--text);
        }
        .portal-switch .sw-icon {
            width: 28px; height: 28px;
            background: rgba(26,108,246,0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6aabff;
            font-size: .75rem;
        }

        .footer { text-align: center; color: var(--muted); font-size: .75rem; margin-top: 28px; }

        @media (max-width: 768px) {
            .brand-panel { display: none; }
            body { flex-direction: column; }
            .login-panel { background: var(--bg); }
        }
    </style>
</head>
<body>

<!-- Right branding panel -->
<div class="brand-panel">
    <div class="dot-pattern"></div>
    <div class="brand-content">
        <div class="student-badge">
            <i class="fas fa-user-graduate"></i>
            Student Portal
        </div>
        <div class="brand-logo"><i class="fas fa-graduation-cap"></i></div>
        <h1 class="brand-title">Your<br><span>Attendance</span><br>Dashboard</h1>
        <p class="brand-desc">
            Track your daily attendance, view your performance, and stay on top of your academic record.
        </p>
        <div class="feature-list">
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                <span>View your personal attendance history</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-percentage"></i></div>
                <span>Track your attendance percentage</span>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-bell"></i></div>
                <span>Get alerts when attendance drops below 75%</span>
            </div>
        </div>
    </div>
</div>

<!-- Left login form -->
<div class="login-panel">
    <div class="login-box">
        <div class="login-header">
            <h2>Student Sign In</h2>
            <p>Access your personal attendance portal</p>
        </div>

        <div class="role-strip">
            <div class="role-dot"></div>
            <span>Student Portal</span>
            <small>Personal Access</small>
        </div>

        <?php if (!empty($error)): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="student_login.php">
            <div class="form-group">
                <label class="form-label">Username</label>
                <div class="input-wrap">
                    <input type="text" name="username" class="form-input"
                        placeholder="Your student username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required autocomplete="username">
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrap">
                    <input type="password" name="password" class="form-input"
                        placeholder="Your password"
                        required autocomplete="current-password">
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fas fa-sign-in-alt" style="margin-right:8px"></i>Sign In to Student Portal
            </button>
        </form>

        <div class="divider">or</div>

        <a href="teacher_login.php" class="portal-switch">
            <div class="sw-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            Switch to Teacher Admin Panel
            <i class="fas fa-arrow-right" style="margin-left:auto;font-size:.75rem"></i>
        </a>

        <p class="footer">&copy; <?= date('Y') ?> AttendEase &mdash; Student Portal</p>
    </div>
</div>
</body>
</html>
