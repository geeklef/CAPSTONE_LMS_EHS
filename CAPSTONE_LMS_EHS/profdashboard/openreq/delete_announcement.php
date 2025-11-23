<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'ehs_lms_db';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
$teacher_id = $_SESSION['teacher_id'] ?? 1;

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Only delete announcements that belong to the logged-in teacher
    $stmt = $conn->prepare("DELETE FROM prof_announcements WHERE announce_id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $id, $teacher_id);
    $stmt->execute();
    $stmt->close();
}

// Redirect back to announcement page
header("Location: ../announcements.php"); // Adjust if needed
exit;
