<?php
include '../../../config/db_connect.php';
header('Content-Type: application/json');

$quiz_id = $_GET['quiz_id'] ?? null;
if (!$quiz_id) {
    echo json_encode([]);
    exit;
}

try {
    // Fetch student quiz responses from the correct table
    $query = $conn->prepare("
        SELECT 
            r.result_id,
            s.stud_id,
            s.first_name,
            s.last_name,
            s.grade_level AS grade,
            s.strand,
            r.score,
            r.submitted_at
        FROM stud_quiz_results r
        INNER JOIN students_account s ON r.stud_id = s.stud_id
        WHERE r.quiz_id = :quiz_id
        ORDER BY r.submitted_at DESC
    ");
    $query->execute([':quiz_id' => $quiz_id]);
    $rows = $query->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($rows ?: []);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
