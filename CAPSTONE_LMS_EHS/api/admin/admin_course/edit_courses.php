<?php
include '../../../config/db_connect.php'; // Supabase PDO connection

// Collect POST values — FIXED to match your modal form
$course_id   = $_POST['course_id'] ?? '';
$course_name = $_POST['course_name'] ?? '';   // was subject_name
$prof_name   = $_POST['prof_name'] ?? '';     // was teacher
$grade       = $_POST['grade'] ?? '';         // was grade_level
$strand      = $_POST['strand'] ?? '';
$section     = $_POST['section'] ?? '';
$day         = $_POST['day'] ?? '';
$time_start  = $_POST['time_start'] ?? '';
$time_end    = $_POST['time_end'] ?? '';

if ($course_id && $course_name && $prof_name && $grade && $strand && $section && $day && $time_start && $time_end) {
  try {

    // UPDATE QUERY (matches your prof_courses table)
    $stmt = $conn->prepare("
      UPDATE prof_courses
      SET 
          course_name = :course_name,
          prof_name   = :prof_name,
          grade       = :grade,
          strand      = :strand,
          section     = :section,
          day         = :day,
          time_start  = :time_start,
          time_end    = :time_end
      WHERE course_id = :course_id
    ");

    // Bind values
    $stmt->bindValue(':course_id', $course_id);
    $stmt->bindValue(':course_name', $course_name);
    $stmt->bindValue(':prof_name', $prof_name);
    $stmt->bindValue(':grade', $grade);
    $stmt->bindValue(':strand', $strand);
    $stmt->bindValue(':section', $section);
    $stmt->bindValue(':day', $day);
    $stmt->bindValue(':time_start', $time_start);
    $stmt->bindValue(':time_end', $time_end);

    // Execute
    if ($stmt->execute()) {
      echo "✅ Course updated successfully.";
    } else {
      echo "❌ Failed to update course.";
    }

  } catch (PDOException $e) {
    echo "❌ Database Error: " . htmlspecialchars($e->getMessage());
  }
} else {
  echo "⚠️ Missing required fields.";
}

$conn = null;
?>
