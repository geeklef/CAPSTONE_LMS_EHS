<?php
session_start();
include '../../../config/db_connect.php';

if (!isset($_SESSION['student_id'])) {
    die("No student session found");
}

$student_id = $_SESSION['student_id'];
$image_url = $_POST['image_url'] ?? '';

if (!$image_url) {
    die("❌ No image URL received.");
}

try {
    $stmt = $conn->prepare("INSERT INTO student_profile_images (student_id, image_url) VALUES (:student_id, :image_url)");
    $stmt->execute([':student_id' => $student_id, ':image_url' => $image_url]);
    echo "✅ Profile image uploaded successfully!";
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>
