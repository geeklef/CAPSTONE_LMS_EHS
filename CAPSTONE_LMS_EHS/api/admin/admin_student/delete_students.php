<?php
include '../../../config/db_connect.php'; // Supabase PDO connection

if (isset($_POST['stud_id'])) {
  $stud_id = $_POST['stud_id'];

  try {
    $stmt = $conn->prepare("DELETE FROM students_account WHERE stud_id = :stud_id");
    $stmt->bindValue(':stud_id', $stud_id);

    if ($stmt->execute()) {
      echo "✅ Student deleted successfully.";
    } else {
      echo "❌ Error deleting student.";
    }
  } catch (PDOException $e) {
    echo "❌ Error: " . htmlspecialchars($e->getMessage());
  }
}

$conn = null;
?>
