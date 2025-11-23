<?php
include '../../../config/db_connect.php';
session_start();

// --- Required: teacher_id from session ---
$teacher_id = $_SESSION['teacher_id'] ?? null;
if (!$teacher_id) {
    die("Access denied. No teacher logged in.");
}

// --- Required: class_id from POST ---
$class_id = $_POST['class_id'] ?? null;
if (!$class_id) {
    die("Missing class ID.");
}

try {
    // --- Fetch course info from class_id ---
    $stmt = $conn->prepare("SELECT strand, section, course_name FROM prof_courses WHERE class_id = :class_id AND teacher_id = :teacher_id LIMIT 1");
    $stmt->execute([
        ':class_id' => $class_id,
        ':teacher_id' => $teacher_id
    ]);
    $courseInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$courseInfo) {
        die("Class not found.");
    }

    $strand = $courseInfo['strand'];
    $section = $courseInfo['section'];
    $course = $courseInfo['course_name'];

    // --- Auto dates ---
    $date_posted = date('Y-m-d H:i:s');
    $due_date = date('Y-m-d', strtotime('+3 days'));
    $due_time = '23:59:00'; // default due time

    // --- Insert new quiz ---
    $stmt = $conn->prepare("
        INSERT INTO prof_quiz 
        (teacher_id, class_id, strand, section, title, description, date_posted, due_date, due_time, total_points)
        VALUES 
        (:teacher_id, :class_id, :strand, :section, 'Untitled Quiz', '', :date_posted, :due_date, :due_time, 0)
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

    // --- Get new quiz ID ---
    $quiz_id = $conn->lastInsertId();

    // --- Redirect to quiz creation page ---
    header("Location: /CAPSTONE_LMS_EHS/profdashboard/openreq/quizzes_create.php?quiz_id=$quiz_id&strand=" . urlencode($strand) . "&section=" . urlencode($section) . "&course=" . urlencode($course));
    exit;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
