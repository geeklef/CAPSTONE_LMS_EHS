<?php
// view_answers.php
include '../../../config/db_connect.php';
session_start();

$result_id = $_GET['result_id'] ?? null;

if (!$result_id) {
    echo json_encode(['success' => false, 'message' => 'Missing result ID']);
    exit;
}

// Fetch the answers and corresponding questions
$stmt = $conn->prepare("
    SELECT q.question_text, q.question_type, q.options, r.answers
    FROM stud_quiz_results r
    JOIN quiz_questions q ON r.quiz_id = q.quiz_id
    WHERE r.result_id = :result_id
");
$stmt->execute([':result_id' => $result_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data found']);
    exit;
}

// Combine answers with questions
$answers = [];
foreach ($data as $row) {
    $decodedAnswers = $row['answers'] ? json_decode($row['answers'], true) : [];

    // Ensure student_answer is always an array
    if (!is_array($decodedAnswers)) {
        $decodedAnswers = [$decodedAnswers];
    }

    $answers[] = [
        'question_text' => $row['question_text'],
        'question_type' => $row['question_type'],
        'options' => $row['options'] ? json_decode($row['options'], true) : [],
        'student_answer' => $decodedAnswers
    ];
}

echo json_encode(['success' => true, 'data' => $answers]);
