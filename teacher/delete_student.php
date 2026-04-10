<?php
/**
 * teacher/delete_student.php
 * Deletes a student and their user account (cascade deletes attendance too).
 */
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php"); exit();
}

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    // Get user_id first so we can delete the user (cascades to student + attendance)
    $stmt = $pdo->prepare("SELECT user_id, name FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();

    if ($student) {
        // Deleting the user cascades to students + attendance via FK
        $del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $del->execute([$student['user_id']]);
        // Redirect with success message
        header("Location: add_student.php?deleted=" . urlencode($student['name']));
        exit();
    }
}
header("Location: add_student.php?err=notfound");
exit();
