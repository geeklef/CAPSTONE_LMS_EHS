<?php
include '../../../config/db_connect.php';
header('Content-Type: application/json');

$question_id = $_POST['question_id'] ?? 0;

if (!$question_id) {
    echo json_encode(['success' => false, 'message' => 'Question ID required.']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM exam_questions WHERE question_id = :question_id");
    $stmt->execute(['question_id' => $question_id]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
