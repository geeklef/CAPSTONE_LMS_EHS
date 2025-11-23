<?php
include '../../../config/db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $exam_id = $_POST['exam_id'] ?? null;
    $question_type = $_POST['question_type'] ?? '';
    $question_text = $_POST['question_text'] ?? '';
    $options_json = $_POST['options'] ?? '[]';
    $correct_answer_json = $_POST['correct_answer'] ?? '[]';

    if (!$exam_id || !$question_text) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing required fields."]);
        exit();
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO exam_questions (exam_id, question_type, question_text, options, correct_answer)
            VALUES (:exam_id, :question_type, :question_text, :options, :correct_answer)
        ");
        $stmt->execute([
            ':exam_id' => $exam_id,
            ':question_type' => $question_type,
            ':question_text' => $question_text,
            ':options' => $options_json,
            ':correct_answer' => $correct_answer_json
        ]);

        echo json_encode(["status" => "success"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
