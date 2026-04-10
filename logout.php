<?php
/**
 * logout.php
 * Destroys the session and redirects to the correct portal login page.
 * This file lives in the ROOT of attendance_system/ folder.
 */
session_start();
$role = $_SESSION['role'] ?? 'student';
session_unset();
session_destroy();

if ($role === 'teacher') {
    header("Location: teacher_login.php");
} else {
    header("Location: student_login.php");
}
exit();
