<?php
include '../../../config/db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_id = $_POST['exam_id'] ?? 0;
    $exam_title = $_POST['exam_title'] ?? '';
    $description = $_POST['description'] ?? '';
    $due_date = $_POST['due_date'] ?? null;
    $due_time = $_POST['due_time'] ?? null;
    $strand = $_POST['strand'] ?? '';
    $section = $_POST['section'] ?? '';
    $course_name = $_POST['course_name'] ?? '';
    $teacher_id = $_POST['teacher_id'] ?? 0;
    $class_id = $_POST['class_id'] ?? null;

    if (empty($exam_id) || empty($exam_title)) {
        die(json_encode(['success' => false, 'message' => 'Missing exam ID or title.']));
    }

    try {
        // ✅ Count total questions (1 point each)
        $stmtCount = $conn->prepare("SELECT COUNT(*) FROM exam_questions WHERE exam_id = :exam_id");
        $stmtCount->execute([':exam_id' => $exam_id]);
        $total_points = (int)$stmtCount->fetchColumn();

        // ✅ Update exam record
        $stmt = $conn->prepare("
            UPDATE prof_exam
            SET title = :title,
                description = :description,
                due_date = :due_date,
                due_time = :due_time,
                total_points = :total_points,
                class_id = :class_id
            WHERE exam_id = :exam_id
        ");
        $stmt->execute([
            ':title' => $exam_title,
            ':description' => $description,
            ':due_date' => $due_date,
            ':due_time' => $due_time,
            ':total_points' => $total_points,
            ':class_id' => $class_id,
            ':exam_id' => $exam_id
        ]);

        echo json_encode(['success' => true, 'message' => 'Exam published successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
