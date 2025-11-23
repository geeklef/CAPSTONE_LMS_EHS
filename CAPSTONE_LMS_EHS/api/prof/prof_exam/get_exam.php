<?php
include '../../../config/db_connect.php';
header('Content-Type: application/json');

$exam_id = $_GET['exam_id'] ?? 0;

if (!$exam_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM exam_questions WHERE exam_id = :exam_id ORDER BY question_id ASC");
    $stmt->execute(['exam_id' => $exam_id]);
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
?>
