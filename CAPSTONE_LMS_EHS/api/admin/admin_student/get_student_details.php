<?php
include '../../../config/db_connect.php';
header('Content-Type: application/json');

// Get ID
$stud_id = $_GET['stud_id'] ?? null;

if (!$stud_id) {
    echo json_encode(["error" => "Missing stud_id"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM students_account WHERE stud_id = :stud_id");
    $stmt->bindValue(':stud_id', $stud_id, PDO::PARAM_INT);
    $stmt->execute();

    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        echo json_encode($student);
    } else {
        echo json_encode(["error" => "Student not found"]);
    }

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
