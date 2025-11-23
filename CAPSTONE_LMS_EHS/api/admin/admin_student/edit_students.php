<?php
include '../../../config/db_connect.php';
header("Content-Type: application/json");

$stud_id = $_POST['stud_id'] ?? '';
$stud_user = $_POST['stud_user'] ?? '';
$password = $_POST['password'] ?? '';
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$grade_level = $_POST['grade_level'] ?? '';
$strand = $_POST['strand'] ?? '';
$section = $_POST['section'] ?? '';
$status = $_POST['status'] ?? '';

if (!$stud_id) {
    echo json_encode(["status" => "error", "message" => "Missing student ID"]);
    exit;
}

try {
    $stmt = $conn->prepare("
        UPDATE students_account SET
            stud_user = :stud_user,
            password = :password,
            first_name = :first_name,
            last_name = :last_name,
            email = :email,
            grade_level = :grade_level,
            strand = :strand,
            section = :section,
            status = :status
        WHERE stud_id = :stud_id
    ");

    $stmt->execute([
        ':stud_id' => $stud_id,
        ':stud_user' => $stud_user,
        ':password' => $password,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':email' => $email,
        ':grade_level' => $grade_level,
        ':strand' => $strand,
        ':section' => $section,
        ':status' => $status,
    ]);

    echo json_encode([
        "status" => "success",
        "message" => "Student updated successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

$conn = null;
