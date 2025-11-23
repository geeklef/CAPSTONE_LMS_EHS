<?php
include '../../../config/db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "msg" => "Invalid request"]);
    exit;
}

$class_id    = $_POST["class_id"] ?? null;
$course_id   = $_POST["course_id"] ?? null;
$course_name = $_POST["course_name"] ?? null;
$grade       = $_POST["grade"] ?? null;
$strand      = $_POST["strand"] ?? null;
$section     = $_POST["section"] ?? null;
$day         = $_POST["day"] ?? null;
$time_start  = $_POST["time_start"] ?? null;
$time_end    = $_POST["time_end"] ?? null;

$teacher_id  = $_SESSION['teacher_id'] ?? null;

if (!$class_id) {
    echo json_encode(["status" => "error", "msg" => "Missing class_id"]);
    exit;
}

try {

    $sql = "UPDATE prof_courses 
            SET course_id   = :course_id,
                course_name = :course_name,
                grade       = :grade,
                strand      = :strand,
                section     = :section,
                day         = :day,
                time_start  = :time_start,
                time_end    = :time_end
            WHERE class_id = :class_id AND teacher_id = :teacher_id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":course_id"   => $course_id,
        ":course_name" => $course_name,
        ":grade"       => $grade,
        ":strand"      => $strand,
        ":section"     => $section,
        ":day"         => $day,
        ":time_start"  => $time_start,
        ":time_end"    => $time_end,
        ":class_id"    => $class_id,
        ":teacher_id"  => $teacher_id
    ]);

    echo json_encode(["status" => "success", "msg" => "Class updated successfully"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
