<?php
include '../../../config/db_connect.php';
$id = $_POST['course_id'] ?? '';
if (!$id) exit('Missing course ID.');

$stmt = $conn->prepare("DELETE FROM prof_courses WHERE course_id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();

echo "Schedule deleted successfully.";
?>
