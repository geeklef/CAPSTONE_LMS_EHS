<?php
include '../../../config/db_connect.php';
session_start();

if(!isset($_SESSION['teacher_id'])) {
    die(json_encode(['status'=>'error','message'=>'Not logged in']));
}

$attendance_id = $_POST['attendance_id'] ?? null;
if(!$attendance_id) die(json_encode(['status'=>'error','message'=>'Missing attendance ID']));

$stmt = $conn->prepare("
    SELECT sa.status, sa.date_marked, s.first_name, s.last_name
    FROM stud_attendance sa
    JOIN students_account s ON sa.student_id = s.stud_id
    WHERE sa.prof_attendance_id = :attendance_id
    ORDER BY s.last_name, s.first_name
");
$stmt->execute(['attendance_id' => $attendance_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['status'=>'success','data'=>$data]);
