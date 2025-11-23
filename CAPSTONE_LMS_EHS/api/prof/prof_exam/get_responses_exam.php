<?php
include '../../../config/db_connect.php';
header('Content-Type: application/json');

$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) {
    echo json_encode([]);
    exit;
}

try {
    // Get total points for this exam
    $examStmt = $conn->prepare("SELECT total_points FROM prof_exam WHERE exam_id = :exam_id");
    $examStmt->execute([':exam_id' => $exam_id]);
    $totalPoints = $examStmt->fetchColumn() ?: 0;

    // Fetch student exam responses
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
        FROM stud_exam_results r
        INNER JOIN students_account s ON r.stud_id = s.stud_id
        WHERE r.exam_id = :exam_id
        ORDER BY r.submitted_at DESC
    ");
    $query->execute([':exam_id' => $exam_id]);
    $rows = $query->fetchAll(PDO::FETCH_ASSOC);

    // Append total_points to each row
    foreach ($rows as &$row) {
        $row['total_points'] = $totalPoints;
    }

    echo json_encode($rows ?: []);
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
