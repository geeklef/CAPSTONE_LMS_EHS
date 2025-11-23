<?php
header("Content-Type: application/json");
include '../../../config/db_connect.php'; // Supabase PDO connection

$course_id = $_GET['course_id'] ?? '';

if (empty($course_id)) {
  echo json_encode(["error" => "Missing course_id"]);
  exit;
}

try {
  // ✅ Select from prof_courses in Supabase
  $stmt = $conn->prepare("
    SELECT course_id, course_name, prof_name, grade, strand, section, day, time_start, time_end
    FROM prof_courses
    WHERE course_id = :course_id
    LIMIT 1
  ");
  $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
  $stmt->execute();

  $course = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($course) {
    echo json_encode($course, JSON_PRETTY_PRINT);
  } else {
    echo json_encode(["error" => "Course not found"]);
  }

} catch (PDOException $e) {
  echo json_encode(["error" => $e->getMessage()]);
}

$conn = null;
?>
