<?php
include '../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teacher_id = $_POST['teacher_id'] ?? null;
    $image_url = $_POST['image_url'] ?? null;

    if (!$teacher_id || !$image_url) {
        die("Missing data");
    }

    $stmt = $conn->prepare("INSERT INTO teacher_profile_images (teacher_id, image_url, uploaded_at) VALUES (:teacher_id, :image_url, NOW())");
    $stmt->execute([
        'teacher_id' => $teacher_id,
        'image_url' => $image_url
    ]);

    echo "✅ Profile image updated successfully!";
}
?>
