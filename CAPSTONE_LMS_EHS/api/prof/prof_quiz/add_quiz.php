<?php
include '../../../config/db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quiz_id = $_POST['quiz_id'] ?? 0;
    $quiz_title = $_POST['quiz_title'] ?? '';
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? null;
    $due_time = $_POST['due_time'] ?? null;
    $strand = $_POST['strand'] ?? '';
    $section = $_POST['section'] ?? '';
    $course_name = $_POST['course_name'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? 0;
    $class_id = $_POST['class_id'] ?? null;

    if (empty($quiz_id) || empty($quiz_title)) {
        die(json_encode(['success' => false, 'message' => 'Missing quiz ID or title.']));
    }

    try {
        // ✅ Count total questions (1 point each)
        $stmtCount = $conn->prepare("SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = :quiz_id");
        $stmtCount->execute([':quiz_id' => $quiz_id]);
        $total_points = (int)$stmtCount->fetchColumn();

        // ✅ Update quiz with class_id included
        $stmt = $conn->prepare("
            UPDATE prof_quiz
            SET title = :title,
                description = :description,
                due_date = :due_date,
                due_time = :due_time,
                total_points = :total_points,
                class_id = :class_id
            WHERE quiz_id = :quiz_id
        ");
        $stmt->execute([
            ':title' => $quiz_title,
            ':description' => $description,
            ':due_date' => $due_date,
            ':due_time' => $due_time,
            ':total_points' => $total_points,
            ':class_id' => $class_id,
            ':quiz_id' => $quiz_id
        ]);

        echo json_encode(['success' => true, 'message' => 'Quiz published successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
