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
$strand = $_GET['strand'] ?? ($_SESSION['strand'] ?? '');
$section = $_GET['section'] ?? ($_SESSION['section'] ?? '');
$course_name = $_GET['course'] ?? ($_SESSION['course'] ?? '');

if (!empty($strand)) $_SESSION['strand'] = $strand;
if (!empty($section)) $_SESSION['section'] = $section;
if (!empty($course_name)) $_SESSION['course'] = $course_name;

// Fetch teacher name
$teacher_name = 'Unknown_Teacher';
$query = "SELECT first_name, last_name FROM teachers_account WHERE teacher_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {    
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $teacher_name = $row['first_name'] . ' ' . $row['last_name'];
    }
    $stmt->close();
}

// Fetch exam results for this teacher
$results = [];
$stmt = $conn->prepare("
    SELECT s.first_name, s.last_name, e.title, r.score, r.date_taken
    FROM stud_exam_results r
    JOIN prof_exam e ON r.exam_id = e.exam_id
    JOIN students_account s ON r.student_id = s.stud_id
    WHERE e.teacher_id = ?
    ORDER BY r.date_taken DESC
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $results = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Exam Results</title>

  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Quicksand", sans-serif; }
    body { display: flex; height: 100vh; background-color: #f4f7f9; }
    .sidebar {
        position: fixed; top: 0; left: 0; height: 100vh; width: 250px;
        background-color: #004aad; color: white; padding: 20px; overflow-y: auto;
    }
    .sidebar h3 { margin-left: 40px; margin-bottom: 40px; }
    .sidebar .menu a {
        display: block; padding: 10px 0; color: white; text-decoration: none; margin-bottom: 10px;
    }
    .sidebar .menu a:hover {
        background-color: #004aad; padding-left: 10px;
    }
    .main {
        margin-left: 250px; flex: 1; padding: 20px;
    }
    .topbar {
        background-color: #004aad; padding: 20px; color: white;
        font-size: 24px; border-radius: 10px; display: flex; align-items: center;
        margin-bottom: 20px;
    }
    .back-btn { text-decoration: none; color: white; margin-right: 10px; }
    .back-btn .material-icons { font-size: 28px; vertical-align: middle; }

    table {
        width: 100%; border-collapse: collapse; margin-top: 15px;
        background: white; border-radius: 10px; overflow: hidden; font-size: 14px;
    }
    th, td {
        padding: 12px; text-align: left; border-bottom: 1px solid #ddd;
    }
    th {
        background-color: #004aad; color: white;
    }
    tr:hover {
        background-color: #f9f9f9;
    }
  </style>
</head>
<body>

<div class="sidebar">
    <div class="profile">
      <img src="fuego.jpg" alt="Profile Image" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-left: 40px;">
      <h3><?= htmlspecialchars($teacher_name) ?></h3>
    </div>
    <div class="menu">
      <a href="/CAPSTONE LMS EHS/profdashboard/profhome.php">Dashboard</a>
      <a href="/CAPSTONE LMS EHS/profdashboard/course.php">Courses</a>
      <a href="/CAPSTONE LMS EHS/profdashboard/performance.php">Performance</a>
      <a href="/CAPSTONE LMS EHS/profdashboard/reminders.php">Reminders</a>
      <a href="/CAPSTONE LMS EHS/profdashboard/account.php">Account</a>
      <a href="/CAPSTONE LMS EHS/login.php">Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <a href="exam.php?course=<?= urlencode($course_name) ?>&strand=<?= urlencode($strand) ?>&section=<?= urlencode($section) ?>" class="back-btn">
            <span class="material-icons">arrow_back</span>
        </a>
        Exam Results (<?= htmlspecialchars($strand) ?> - <?= htmlspecialchars($section) ?>)
    </div>

    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Exam Title</th>
                <th>Score</th>
                <th>Date Taken</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($results)): ?>
                <tr><td colspan="4">No exam results found.</td></tr>
            <?php else: ?>
                <?php foreach ($results as $res): ?>
                    <tr>
                        <td><?= htmlspecialchars($res['first_name'] . ' ' . $res['last_name']) ?></td>
                        <td><?= htmlspecialchars($res['title']) ?></td>
                        <td><?= htmlspecialchars($res['score']) ?></td>
                        <td><?= (new DateTime($res['date_taken']))->format('Y-m-d h:i A') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</div>
</body>
</html>
