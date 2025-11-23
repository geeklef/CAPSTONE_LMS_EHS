<?php
include '../../../config/db_connect.php'; // Supabase PDO connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Collect form data safely
  $course_id   = $_POST['course_id'] ?? ''; // ✅ manually entered ID
  $course_name = $_POST['subject_name'] ?? '';
  $prof_name   = $_POST['teacher'] ?? '';
  $grade       = $_POST['grade_level'] ?? '';
  $strand      = $_POST['strand'] ?? '';
  $section     = $_POST['section'] ?? '';
  $day         = $_POST['day'] ?? '';
  $time_start  = $_POST['time_start'] ?? '';
  $time_end    = $_POST['time_end'] ?? '';

  try {
    // ✅ Insert into database with manual course_id
    $stmt = $conn->prepare("
      INSERT INTO prof_courses (course_id, course_name, prof_name, grade, strand, section, day, time_start, time_end)
      VALUES (:course_id, :course_name, :prof_name, :grade, :strand, :section, :day, :time_start, :time_end)
    ");

    $stmt->bindValue(':course_id', $course_id);
    $stmt->bindValue(':course_name', $course_name);
    $stmt->bindValue(':prof_name', $prof_name);
    $stmt->bindValue(':grade', $grade);
    $stmt->bindValue(':strand', $strand);
    $stmt->bindValue(':section', $section);
    $stmt->bindValue(':day', $day);
    $stmt->bindValue(':time_start', $time_start);
    $stmt->bindValue(':time_end', $time_end);

    if ($stmt->execute()) {
      echo "✅ Schedule successfully added.";
    } else {
      echo "❌ Failed to add schedule.";
    }

  } catch (PDOException $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
  }
}

$conn = null;
?>
