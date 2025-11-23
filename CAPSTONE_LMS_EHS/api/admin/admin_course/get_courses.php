<?php
include '../../../config/db_connect.php'; // Supabase PDO connection

$strand = $_GET['strand'] ?? '';
$grade = $_GET['grade'] ?? '';
$search = $_GET['search'] ?? '';

try {
  $query = "SELECT * FROM prof_courses WHERE 1=1";
  $params = [];

  if (!empty($strand)) {
    $query .= " AND strand = :strand";
    $params[':strand'] = $strand;
  }

  if (!empty($grade)) {
    $query .= " AND grade = :grade";
    $params[':grade'] = $grade;
  }

  if (!empty($search)) {
    $query .= " AND (course_name ILIKE :search OR prof_name ILIKE :search OR section ILIKE :search)";
    $params[':search'] = "%$search%";
  }

  $query .= " ORDER BY grade, strand, section, day, time_start";

  $stmt = $conn->prepare($query);
  $stmt->execute($params);
  $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (count($courses) === 0) {
    echo "<tr><td colspan='8' style='text-align:center;'>No schedules found.</td></tr>";
  } else {
    foreach ($courses as $row) {
      echo "
        <tr>
          <td>{$row['course_id']}</td>
          <td>{$row['course_name']}</td>
          <td>{$row['prof_name']}</td>
          <td>{$row['grade']}</td>
          <td>{$row['strand']}</td>
          <td>{$row['section']}</td>
          <td>{$row['day']} | {$row['time_start']} - {$row['time_end']}</td>
          <td>
            <span class='material-icons edit-btn' style='cursor:pointer; color:#004aad;'>edit</span>
            <span class='material-icons delete-btn' style='cursor:pointer; color:red;'>delete</span>
          </td>
        </tr>
      ";
    }
  }

} catch (PDOException $e) {
  echo "<tr><td colspan='8'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
}

$conn = null;
?>
