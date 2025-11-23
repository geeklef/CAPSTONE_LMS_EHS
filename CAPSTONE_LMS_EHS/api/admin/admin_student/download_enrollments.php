<?php
include '../../../config/db_connect.php';

$stud_id = $_GET['stud_id'] ?? 0;
if(!$stud_id){ die('Missing student ID'); }

$stmt = $conn->prepare("
    SELECT pc.course_name, pc.day, pc.time_start, pc.time_end, pc.prof_name, pc.section, pc.strand
    FROM student_enrollments se
    JOIN prof_courses pc ON se.course_id = pc.course_id
    WHERE se.student_id = :stud_id
");
$stmt->bindValue(':stud_id', $stud_id, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="Student_'.$stud_id.'_Enrollments.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, array_keys($data[0] ?? [])); // header
foreach($data as $row){ fputcsv($output, $row); }
fclose($output);
$conn = null;
exit;
?>
