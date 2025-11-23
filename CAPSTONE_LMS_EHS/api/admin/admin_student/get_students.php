<?php
include '../../../config/db_connect.php';

// Get filters safely
$strand = $_GET['strand'] ?? '';
$grade  = $_GET['grade'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM students_account WHERE 1=1";

// Add filters dynamically
$params = [];
if (!empty($strand)) {
    $query .= " AND strand = :strand";
    $params[':strand'] = $strand;
}
if (!empty($grade)) {
    $query .= " AND grade_level = :grade";
    $params[':grade'] = $grade;
}
if (!empty($search)) {
    $query .= " AND (CAST(stud_id AS TEXT) ILIKE :search OR first_name ILIKE :search OR last_name ILIKE :search OR stud_user ILIKE :search)";
    $params[':search'] = "%$search%";
}

try {
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>
                <td style='padding: 10px;'>{$row['stud_id']}</td>
                <td style='padding: 10px;'>{$row['stud_user']}</td>
                <td style='padding: 10px;'>{$row['first_name']} {$row['last_name']}</td>
                <td style='padding: 10px;'>{$row['grade_level']}</td>
                <td style='padding: 10px;'>{$row['strand']}</td>
                <td style='padding: 10px;'>{$row['section']}</td>
<td style='padding: 10px;'>
    <span class='material-icons edit-btn' 
          style='color: #004aad; cursor: pointer; margin-right: 10px;'
          onclick='openEditModal(\"{$row['stud_id']}\")'>edit</span>
    <span class='material-icons' 
          style='color: red; cursor: pointer; margin-right: 10px;'
          onclick='deleteStudent(\"{$row['stud_id']}\")'>delete</span>
    <span class='material-icons' 
          style='color: green; cursor: pointer; margin-right: 10px;'
          onclick='viewEnrollments(\"{$row['stud_id']}\")'>visibility</span>
    <span class='material-icons' 
          style='color: #004aad; cursor: pointer;'
          onclick='downloadEnrollments(\"{$row['stud_id']}\")'>file_download</span>
</td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='7' style='padding:10px; text-align:center;'>No students found.</td></tr>";
    }

} catch (PDOException $e) {
    echo "<tr><td colspan='7' style='padding:10px; text-align:center; color:red;'>Error: "
         . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

$conn = null;
?>
