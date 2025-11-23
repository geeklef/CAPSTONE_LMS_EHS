<?php
include '../../../config/db_connect.php'; // Supabase PDO connection

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['teacher_id'])) {
  $teacher_id = $_POST['teacher_id'];

  try {
    $stmt = $conn->prepare("DELETE FROM teachers_account WHERE teacher_id = :teacher_id");
    $stmt->bindValue(':teacher_id', $teacher_id);

    if ($stmt->execute()) {
      if ($stmt->rowCount() > 0) {
        echo "✅ Professor deleted successfully.";
      } else {
        echo "⚠️ No matching record found.";
      }
    } else {
      echo "❌ Deletion failed.";
    }
  } catch (PDOException $e) {
    echo "❌ Database Error: " . htmlspecialchars($e->getMessage());
  }
} else {
  echo "❌ Invalid request.";
}

$conn = null;
?>
