<?php
include '../../../config/db_connect.php';
session_start();

$quiz_id = $_GET['quiz_id'] ?? 0;

if ($quiz_id) {
    // Delete all questions first
    $stmt = $conn->prepare("DELETE FROM quiz_questions WHERE quiz_id = :quiz_id");
    $stmt->execute(['quiz_id' => $quiz_id]);

    // Delete the quiz itself
    $stmt = $conn->prepare("DELETE FROM prof_quiz WHERE quiz_id = :quiz_id");
    $stmt->execute(['quiz_id' => $quiz_id]);

    // Redirect back to quizzes page
    header("Location: /CAPSTONE_LMS_EHS/profdashboard/openreq/quizzes.php");
    exit;
}
?>
