<?php
include '../../../config/db_connect.php'; // Supabase connection

$department = $_GET['department'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM teachers_account WHERE 1=1";

if (!empty($department)) {
  $query .= " AND department = :department";
}
if (!empty($search)) {
  $query .= " AND (CAST(teacher_id AS TEXT) ILIKE :search OR teacher_user ILIKE :search OR first_name ILIKE :search OR last_name ILIKE :search)";
}

try {
  $stmt = $conn->prepare($query);

  if (!empty($department)) {
    $stmt->bindValue(':department', $department);
  }
  if (!empty($search)) {
    $stmt->bindValue(':search', "%$search%");
  }

  $stmt->execute();

  if ($stmt->rowCount() > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      echo "<tr style='background-color: #f9f9f9;'>
        <td style='padding: 10px;'>{$row['teacher_id']}</td>
        <td style='padding: 10px;'>{$row['teacher_user']}</td>
        <td style='padding: 10px;'>{$row['first_name']} {$row['last_name']}</td>
        <td style='padding: 10px;'>{$row['department']}</td>
        <td style='padding: 10px;'>
<span class='material-icons edit-btn' style='color: #004aad; cursor: pointer; margin-right: 10px;' onclick='openEditModal(\"{$row['teacher_id']}\")'>edit</span>
<span class='material-icons' style='color: red; cursor: pointer;' onclick='deleteProfessor(\"{$row['teacher_id']}\")'>delete</span>
        </td>
      </tr>";
    }
  } else {
    echo "<tr><td colspan='5' style='padding:10px; text-align:center;'>No teachers found.</td></tr>";
  }

} catch (PDOException $e) {
  echo "<tr><td colspan='5' style='padding:10px; color:red; text-align:center;'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

$conn = null;
?>
