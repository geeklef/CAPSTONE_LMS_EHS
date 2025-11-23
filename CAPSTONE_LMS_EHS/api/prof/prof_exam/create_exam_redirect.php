<?php
include '../../../config/db_connect.php';
session_start();

// Get teacher and class info
$teacher_id = $_SESSION['teacher_id'] ?? 1;
$strand = $_POST['strand'] ?? '';
$section = $_POST['section'] ?? '';
$course = $_POST['course'] ?? '';
$class_id = $_POST['class_id'] ?? null;
$teacher_id = $_POST['teacher_id'] ?? $teacher_id; // fallback to session

// Validate required fields
if (empty($strand) || empty($section) || empty($course)) {
    die("Missing required parameters.");
}

try {
    // Automatically set date posted, due date, and due time
    $date_posted = date('Y-m-d H:i:s');
    $due_date = date('Y-m-d', strtotime('+3 days'));
    $due_time = '23:59:00'; // 11:59 PM

    // Insert a new exam
    $stmt = $conn->prepare("
        INSERT INTO prof_exam 
        (teacher_id, class_id, strand, section, title, description, date_posted, due_date, due_time, total_points)
        VALUES 
        (:teacher_id, :class_id, :strand, :section, 'Untitled Exam', '', :date_posted, :due_date, :due_time, 0)
    ");
    $stmt->execute([
        ':teacher_id' => $teacher_id,
        ':class_id' => $class_id,
        ':strand' => $strand,
        ':section' => $section,
        ':date_posted' => $date_posted,
        ':due_date' => $due_date,
        ':due_time' => $due_time
    ]);

    // Get the new exam ID
    $exam_id = $conn->lastInsertId();

    // Redirect teacher to the exam creation page
    header("Location: /CAPSTONE_LMS_EHS/profdashboard/openreq/exam_create.php?exam_id=$exam_id&strand=" . urlencode($strand) . "&section=" . urlencode($section) . "&course=" . urlencode($course));
    exit;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
