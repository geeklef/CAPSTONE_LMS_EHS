<?php
include '../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_user = $_POST['teacher_user'] ?? '';
    $teacher_pass = $_POST['teacher_pass'] ?? '';
    $first_name   = $_POST['first_name'] ?? '';
    $last_name    = $_POST['last_name'] ?? '';
    $email        = $_POST['email'] ?? '';
    $subject_id   = $_POST['subject_id'] ?? '';
    $department   = $_POST['department'] ?? '';

    if (empty($teacher_user) || empty($teacher_pass) || empty($first_name) || empty($last_name) || empty($email) || empty($subject_id) || empty($department)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    try {
        $query = "INSERT INTO teachers_account (teacher_user, teacher_pass, first_name, last_name, email, subject_id, department)
                  VALUES (:teacher_user, :teacher_pass, :first_name, :last_name, :email, :subject_id, :department)";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':teacher_user' => $teacher_user,
            ':teacher_pass' => $teacher_pass,
            ':first_name'   => $first_name,
            ':last_name'    => $last_name,
            ':email'        => $email,
            ':subject_id'   => $subject_id,
            ':department'   => $department
        ]);
        echo json_encode(['status' => 'success', 'message' => 'Teacher added successfully!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    $conn = null;
}

?>
