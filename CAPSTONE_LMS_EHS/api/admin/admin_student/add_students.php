<?php
include '../../../config/db_connect.php'; // Supabase PDO connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stud_id = $_POST['stud_id'];
  $stud_user = $_POST['stud_user'];
  $password = $_POST['password'];
  $first_name = $_POST['first_name'];
  $last_name = $_POST['last_name'];
  $email = $_POST['email'];
  $grade_level = $_POST['grade_level'];
  $strand = $_POST['strand'];
  $section = $_POST['section'];

  $status = "Active";

  try {
    $stmt = $conn->prepare("INSERT INTO students_account 
      (stud_id, stud_user, password, first_name, last_name, email, grade_level, strand, section, status)
      VALUES (:stud_id, :stud_user, :password, :first_name, :last_name, :email, :grade_level, :strand, :section, :status)");

    $stmt->bindValue(':stud_id', $stud_id);
    $stmt->bindValue(':stud_user', $stud_user);
    $stmt->bindValue(':password', $password);
    $stmt->bindValue(':first_name', $first_name);
    $stmt->bindValue(':last_name', $last_name);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':grade_level', $grade_level);
    $stmt->bindValue(':strand', $strand);
    $stmt->bindValue(':section', $section);
    $stmt->bindValue(':status', $status);

   if ($stmt->execute()) {
  echo json_encode([
    "status" => "success",
    "message" => "Student successfully added."
  ]);
} else {
  echo json_encode([
    "status" => "error",
    "message" => "Error occurred while inserting data."
  ]);
}


  } catch (PDOException $e) {
  echo json_encode([
    "status" => "error",
    "message" => $e->getMessage()
  ]);
}

}

$conn = null;
?>
