<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/CAPSTONE_LMS_EHS/config/db_connect.php';

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ------------------------------
// 1️⃣ Validate student session
// ------------------------------
if (!isset($_SESSION['student_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Student not logged in."]);
    exit;
}
$student_id = $_SESSION['student_id'];

// ------------------------------
// 2️⃣ Validate POST data
// ------------------------------
$quiz_id = $_POST['quiz_id'] ?? null;
$answers_json = $_POST['answers'] ?? null;

if (!$quiz_id || !$answers_json) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing quiz_id or answers"]);
    exit;
}

$submitted_answers = json_decode($answers_json, true);
if ($submitted_answers === null) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON format in answers"]);
    exit;
}

try {
    // ------------------------------
    // 3️⃣ Check student exists
    // ------------------------------
    $stmt = $conn->prepare("SELECT stud_id FROM students_account WHERE stud_id = :stud_id");
    $stmt->execute(['stud_id' => $student_id]);
    if (!$stmt->fetch()) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Student ID not found"]);
        exit;
    }

    // ------------------------------
    // 4️⃣ Check if quiz already submitted
    // ------------------------------
    $stmt = $conn->prepare("SELECT 1 FROM stud_quiz_results WHERE quiz_id = :quiz_id AND stud_id = :stud_id");
    $stmt->execute(['quiz_id' => $quiz_id, 'stud_id' => $student_id]);
    if ($stmt->fetch()) {
        echo json_encode(["status" => "already", "message" => "Quiz already submitted"]);
        exit;
    }

    // ------------------------------
    // 5️⃣ Fetch quiz questions
    // ------------------------------
    $stmt = $conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id = :quiz_id");
    $stmt->execute(['quiz_id' => $quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$questions) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "No questions found for this quiz"]);
        exit;
    }

    // ------------------------------
    // 6️⃣ Calculate score
    // ------------------------------
    $score = 0;

    foreach ($questions as $q) {
        $qid = "q".$q['question_id'];
        $submitted = $submitted_answers[$qid] ?? null;
        $correct = json_decode($q['correct_answer'], true);

        if ($q['question_type'] === 'multiple' || $q['question_type'] === 'short') {
            $correct_val = is_array($correct) ? $correct[0] : $correct;
            if ($submitted !== null && strtolower(trim($submitted)) === strtolower(trim($correct_val))) {
                $score++;
            }
        } elseif ($q['question_type'] === 'checkbox') {
            $submitted_array = is_array($submitted) ? $submitted : [];
            $correct_array = is_array($correct) ? $correct : [];
            sort($submitted_array);
            sort($correct_array);
            if ($submitted_array === $correct_array) {
                $score++;
            }
        }
    }

    // ------------------------------
    // 7️⃣ Insert results
    // ------------------------------
    $stmt = $conn->prepare("
        INSERT INTO stud_quiz_results (quiz_id, stud_id, answers, score, submitted_at) 
        VALUES (:quiz_id, :stud_id, :answers, :score, NOW())
    ");

    $stmt->execute([
        'quiz_id' => $quiz_id,
        'stud_id' => $student_id,
        'answers' => json_encode($submitted_answers),
        'score' => $score
    ]);

    // ------------------------------
    // 8️⃣ Success response
    // ------------------------------
    echo json_encode([
        "status" => "success",
        "message" => "Quiz submitted successfully.",
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
