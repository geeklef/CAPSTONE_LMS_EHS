<?php
include '../../../config/db_connect.php';
header('Content-Type: application/json');

$result_id = $_POST['result_id'] ?? null;
$score = $_POST['score'] ?? null;

if(!$result_id || !is_numeric($score)){
    echo json_encode(['success'=>false,'message'=>'Invalid input']);
    exit;
}

try{
    $stmt = $conn->prepare("UPDATE stud_quiz_results SET score=:score WHERE result_id=:result_id");
    $stmt->execute([':score'=>$score,':result_id'=>$result_id]);
    echo json_encode(['success'=>true]);
}catch(PDOException $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
