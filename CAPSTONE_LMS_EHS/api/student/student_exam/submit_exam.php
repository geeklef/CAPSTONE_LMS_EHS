<?php
header('Content-Type: application/json');
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/CAPSTONE_LMS_EHS/config/db_connect.php';

// ✅ Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    die(json_encode(['status'=>'error','message'=>'Student not logged in.']));
}

$student_id = $_SESSION['student_id'];
$exam_id = $_POST['exam_id'] ?? null;
$answers_json = $_POST['answers'] ?? null;

if (!$exam_id || !$answers_json) {
    die(json_encode(['status'=>'error','message'=>'Missing exam ID or answers.']));
}

// Decode JSON answers
$submitted_answers = json_decode($answers_json, true);
if ($submitted_answers === null) {
    die(json_encode(['status'=>'error','message'=>'Invalid JSON in answers']));
}

try {
    // --- Check if student already submitted
    $stmt = $conn->prepare("SELECT 1 FROM stud_exam_results WHERE exam_id=:exam_id AND stud_id=:stud_id");
    $stmt->execute(['exam_id'=>$exam_id,'stud_id'=>$student_id]);
    if ($stmt->fetchColumn()) {
        die(json_encode(['status'=>'already','message'=>'You have already submitted this exam.']));
    }

    // --- Get class_id for this exam
    $stmt = $conn->prepare("SELECT class_id FROM prof_exam WHERE exam_id=:exam_id");
    $stmt->execute(['exam_id'=>$exam_id]);
    $class_id = $stmt->fetchColumn();
    if (!$class_id) {
        die(json_encode(['status'=>'error','message'=>'Class not found for this exam.']));
    }

    // --- Insert exam submission with temporary score 0 ---
    $stmt = $conn->prepare("
        INSERT INTO stud_exam_results (exam_id, stud_id, answers, score)
        VALUES (:exam_id, :stud_id, :answers, 0)
    ");
    $stmt->execute([
        'exam_id'=>$exam_id,
        'stud_id'=>$student_id,
        'answers'=>$answers_json
    ]);

    // --- Call Python prediction script ---
    $py_script = escapeshellcmd($_SERVER['DOCUMENT_ROOT'].'/CAPSTONE_LMS_EHS/ml_model/predict_student.py');
    $command = "python {$py_script} {$student_id} {$class_id}";
    $output = shell_exec($command);
    $prediction = json_decode($output,true);

    if (!$prediction) {
        die(json_encode(['status'=>'error','message'=>'Prediction failed. Check Python script output.']));
    }

    // --- Upsert predicted grades into student_predictions ---
    $stmt = $conn->prepare("SELECT id FROM student_predictions WHERE stud_id=:stud_id AND class_id=:class_id");
    $stmt->execute(['stud_id'=>$student_id,'class_id'=>$class_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $stmt = $conn->prepare("
            UPDATE student_predictions
            SET q1_grade=:q1,q2_grade=:q2,q3_grade=:q3,q4_grade=:q4,
                final_grade=:final, pass_fail_status=:status, weakness=:weakness, quote=:quote,
                last_updated=NOW()
            WHERE id=:id
        ");
        $stmt->execute([
            'q1'=>$prediction['q1_grade'],
            'q2'=>$prediction['q2_grade'],
            'q3'=>$prediction['q3_grade'],
            'q4'=>$prediction['q4_grade'],
            'final'=>$prediction['final_grade'],
            'status'=>$prediction['pass_fail_status'],
            'weakness'=>$prediction['weakness'],
            'quote'=>$prediction['quote'],
            'id'=>$existing['id']
        ]);
    } else {
        $stmt = $conn->prepare("
            INSERT INTO student_predictions
            (stud_id,class_id,q1_grade,q2_grade,q3_grade,q4_grade,final_grade,pass_fail_status,weakness,quote,last_updated)
            VALUES
            (:stud_id,:class_id,:q1,:q2,:q3,:q4,:final,:status,:weakness,:quote,NOW())
        ");
        $stmt->execute([
            'stud_id'=>$student_id,
            'class_id'=>$class_id,
            'q1'=>$prediction['q1_grade'],
            'q2'=>$prediction['q2_grade'],
            'q3'=>$prediction['q3_grade'],
            'q4'=>$prediction['q4_grade'],
            'final'=>$prediction['final_grade'],
            'status'=>$prediction['pass_fail_status'],
            'weakness'=>$prediction['weakness'],
            'quote'=>$prediction['quote']
        ]);
    }

    echo json_encode([
        'status'=>'success',
        'message'=>'Exam submitted and grade predicted.',
        'prediction'=>$prediction
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}
