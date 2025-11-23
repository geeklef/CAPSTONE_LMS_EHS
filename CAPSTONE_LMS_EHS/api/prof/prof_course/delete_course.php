<?php
include '../../../config/db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "msg" => "Invalid request"]);
    exit;
}

$class_id   = $_POST["class_id"] ?? null;
$teacher_id = $_SESSION['teacher_id'] ?? null;

if (!$class_id) {
    echo json_encode(["status" => "error", "msg" => "Missing class_id"]);
    exit;
}

try {
    // Only delete the class that belongs to this teacher
    $stmt = $pdo->prepare("DELETE FROM prof_courses WHERE class_id = :class_id AND teacher_id = :teacher_id");
    $stmt->execute([
        ":class_id"   => $class_id,
        ":teacher_id" => $teacher_id
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "msg" => "Class deleted successfully"]);
    } else {
        echo json_encode(["status" => "error", "msg" => "Class not found or you don't have permission to delete it"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
