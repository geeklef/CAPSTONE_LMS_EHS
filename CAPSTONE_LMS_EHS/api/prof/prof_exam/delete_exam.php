<?php
include '../../../config/db_connect.php';
session_start();

$exam_id = $_GET['exam_id'] ?? 0;

if ($exam_id) {
    try {
        // Delete all questions associated with this exam
        $stmt = $conn->prepare("DELETE FROM exam_questions WHERE exam_id = :exam_id");
        $stmt->execute(['exam_id' => $exam_id]);

        // Delete the exam itself
        $stmt = $conn->prepare("DELETE FROM prof_exam WHERE exam_id = :exam_id");
        $stmt->execute(['exam_id' => $exam_id]);

        // Redirect back to exams page
        header("Location: /CAPSTONE_LMS_EHS/profdashboard/openreq/exam.php");
        exit;
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>
