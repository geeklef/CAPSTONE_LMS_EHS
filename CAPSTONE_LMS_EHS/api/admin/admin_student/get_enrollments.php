<?php
include '../../../config/db_connect.php';

$stud_id = $_GET['stud_id'] ?? 0;

if(!$stud_id){ echo json_encode(['error'=>'Missing student ID']); exit; }

$stmt = $conn->prepare("
    SELECT pc.course_name, pc.day, pc.time_start, pc.time_end, pc.prof_name, pc.section, pc.strand
    FROM student_enrollments se
    JOIN prof_courses pc ON se.course_id = pc.course_id
    WHERE se.student_id = :stud_id
");
$stmt->bindValue(':stud_id', $stud_id, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($result);
$conn = null;
?>
