<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/CAPSTONE_LMS_EHS/config/db_connect.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Validate student
if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Student not logged in."]);
    exit;
}
$student_id = $_SESSION['student_id'];

// Validate POST
$exam_id = $_POST['exam_id'] ?? null;
$answers_json = $_POST['answers'] ?? null;

if (!$exam_id || !$answers_json) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing exam_id or answers"]);
    exit;
}

$submitted_answers = json_decode($answers_json, true);
if ($submitted_answers === null) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON format in answers"]);
    exit;
}

try {
    // Check student exists
    $stmt = $conn->prepare("SELECT stud_id FROM students_account WHERE stud_id = :stud_id");
    $stmt->execute(['stud_id' => $student_id]);
    if (!$stmt->fetch()) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Student ID not found"]);
        exit;
    }

    // Fetch exam questions
    $stmt = $conn->prepare("SELECT * FROM exam_questions WHERE exam_id = :exam_id");
    $stmt->execute(['exam_id' => $exam_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$questions) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "No questions found for this exam"]);
        exit;
    }

    // Calculate score
    $score = 0;
    foreach ($questions as $q) {
        $qid = "q".$q['question_id'];
        $submitted = $submitted_answers[$qid] ?? null;
        $correct = json_decode($q['correct_answer'], true);

        if ($q['question_type'] === 'multiple' || $q['question_type'] === 'short') {
            if (is_array($correct)) {
                $correct_val = count($correct) > 0 ? $correct[0] : '';
            } else {
                $correct_val = $correct ?? '';
            }
            if ($submitted !== null && strtolower(trim($submitted)) === strtolower(trim($correct_val))) {
                $score++;
            }
        } elseif ($q['question_type'] === 'checkbox') {
            $submitted_array = is_array($submitted) ? $submitted : [];
            $correct_array = is_array($correct) ? $correct : [];
            sort($submitted_array);
            sort($correct_array);
            if ($submitted_array === $correct_array) $score++;
        }
    }

    // Check if result exists
    $stmt = $conn->prepare("SELECT result_id FROM stud_exam_results WHERE exam_id = :exam_id AND stud_id = :stud_id");
    $stmt->execute(['exam_id' => $exam_id, 'stud_id' => $student_id]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        $stmt = $conn->prepare("
            UPDATE stud_exam_results
            SET answers = :answers, score = :score, submitted_at = NOW()
            WHERE result_id = :result_id
        ");
        $stmt->execute([
            'answers' => json_encode($submitted_answers),
            'score' => $score,
            'result_id' => $existing
        ]);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO stud_exam_results (exam_id, stud_id, answers, score, submitted_at) 
            VALUES (:exam_id, :stud_id, :answers, :score, NOW())
        ");
        $stmt->execute([
            'exam_id' => $exam_id,
            'stud_id' => $student_id,
            'answers' => json_encode($submitted_answers),
            'score' => $score
        ]);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Exam submitted successfully",
        "score" => $score
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
