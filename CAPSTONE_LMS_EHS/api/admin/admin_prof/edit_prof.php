<?php
include '../../../config/db_connect.php'; // Supabase PDO connection

$teacher_id   = $_POST['teacher_id'] ?? '';
$teacher_user = $_POST['teacher_user'] ?? '';
$teacher_pass = $_POST['teacher_pass'] ?? '';
$first_name   = $_POST['first_name'] ?? '';
$last_name    = $_POST['last_name'] ?? '';
$email        = $_POST['email'] ?? '';
$subject_id   = $_POST['subject_id'] ?? '';
$department   = $_POST['department'] ?? '';

if ($teacher_id && $teacher_user && $teacher_pass && $first_name && $last_name && $email && $subject_id && $department) {
    try {
        $stmt = $conn->prepare("
            UPDATE teachers_account
            SET teacher_user = :teacher_user,
                teacher_pass = :teacher_pass,
                first_name   = :first_name,
                last_name    = :last_name,
                email        = :email,
                subject_id   = :subject_id,
                department   = :department
            WHERE teacher_id = :teacher_id
        ");

        $stmt->bindValue(':teacher_user', $teacher_user);
        $stmt->bindValue(':teacher_pass', $teacher_pass);
        $stmt->bindValue(':first_name', $first_name);
        $stmt->bindValue(':last_name', $last_name);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':subject_id', $subject_id);
        $stmt->bindValue(':department', $department);
        $stmt->bindValue(':teacher_id', $teacher_id);

        if ($stmt->execute()) {
            echo "✅ Professor updated successfully.";
        } else {
            echo "❌ Failed to update professor.";
        }

    } catch (PDOException $e) {
        echo "❌ Error: " . htmlspecialchars($e->getMessage());
    }
} else {
    echo "⚠️ Missing required fields.";
}

$conn = null;
?>
