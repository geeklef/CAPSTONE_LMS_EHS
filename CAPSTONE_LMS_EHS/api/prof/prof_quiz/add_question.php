<?php
include '../../../config/db_connect.php';
session_start();

$quiz_id = $_POST['quiz_id'] ?? 0;
$question_type = $_POST['question_type'] ?? '';
$question_text = $_POST['question_text'] ?? '';
$options = $_POST['options'] ?? '[]';          // JSON string
$correct_answer = $_POST['correct_answer'] ?? '[]'; // JSON string

if (!$quiz_id || !$question_type || !$question_text) {
    echo json_encode(['success'=>false, 'message'=>'Missing required fields.']);
    exit;
}

try {
    $stmt = $conn->prepare("
        INSERT INTO quiz_questions (quiz_id, question_type, question_text, options, correct_answer)
        VALUES (:quiz_id, :question_type, :question_text, :options::jsonb, :correct_answer::jsonb)
        RETURNING question_id
    ");

    $stmt->execute([
        'quiz_id' => $quiz_id,
        'question_type' => $question_type,
        'question_text' => $question_text,
        'options' => $options,
        'correct_answer' => $correct_answer
    ]);

    $question_id = $stmt->fetchColumn();
    echo json_encode(['success'=>true, 'question_id'=>$question_id]);

} catch(PDOException $e) {
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>
