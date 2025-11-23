<?php
include '../../../config/db_connect.php';
header('Content-Type: application/json');

$quiz_id = $_GET['quiz_id'] ?? 0;

if (!$quiz_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id = :quiz_id ORDER BY question_id ASC");
    $stmt->execute(['quiz_id' => $quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode JSONB fields
    foreach ($questions as &$q) {
        $q['options'] = $q['options'] ? json_decode($q['options'], true) : [];
        $q['correct_answer'] = $q['correct_answer'] ? json_decode($q['correct_answer'], true) : [];
    }

    echo json_encode($questions);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
