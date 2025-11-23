<?php
include '../config/db_connect.php'; // ✅ Use Supabase connection

session_start();
$teacher_id = $_SESSION['teacher_id'] ?? 1;

// Handle strand, section, and course via URL or session
$strand = $_GET['strand'] ?? ($_SESSION['strand'] ?? '');
$section = $_GET['section'] ?? ($_SESSION['section'] ?? '');
$course_name = $_GET['course'] ?? ($_SESSION['course'] ?? '');

if (!empty($strand)) $_SESSION['strand'] = $strand;
if (!empty($section)) $_SESSION['section'] = $section;
if (!empty($course_name)) $_SESSION['course'] = $course_name;

// 🔹 Fetch Teacher Name
$teacher_name = 'Unknown Teacher';
try {
    $stmt = $conn->prepare("SELECT first_name, last_name FROM teachers_account WHERE teacher_id = :teacher_id");
    $stmt->execute(['teacher_id' => $teacher_id]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $teacher_name = $row['first_name'] . ' ' . $row['last_name'];
    }
} catch (PDOException $e) {
    die("Error fetching teacher: " . $e->getMessage());
}

// 🔹 Fetch class_id from prof_courses
$class_id = '';
if (!empty($strand) && !empty($section)) {
    try {
        $stmt = $conn->prepare("SELECT class_id FROM prof_courses WHERE teacher_id = :teacher_id AND strand = :strand AND section = :section LIMIT 1");
        $stmt->execute([
            'teacher_id' => $teacher_id,
            'strand' => $strand,
            'section' => $section
        ]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $class_id = $row['class_id'];
        }
    } catch (PDOException $e) {
        die("Error fetching class_id: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LMS Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Quicksand", sans-serif; }
    body { display: flex; height: 100vh; background-color: #f4f7f9; }
    .sidebar {
      position: fixed; top: 0; left: 0; height: 100vh; width: 250px;
      background-color: #004aad; color: white; padding: 20px; overflow-y: auto;
    }
    .sidebar h3 {
      margin-left: 40px; margin-bottom: 40px;
    }
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
    }
    .back-btn {
      display: inline-block; vertical-align: middle; text-decoration: none;
      margin-right: 10px; color: white;
    }
    .back-btn .material-icons {
      font-size: 28px; vertical-align: middle;
    }
    .stats {
      display: flex; flex-wrap: wrap; gap: 20px; margin: 27px 0;
    }
    .stat-box {
      background: white; padding: 20px 20px 20px 60px;
      border-radius: 10px; width: 32%; height: 119px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); position: relative;
    }
    .stat-box p {
      font-size: 20px; margin-left: 30px; margin-bottom: 16px;
    }
    .material-icons.stat-icon {
      position: absolute; top: 30px; left: 20px; font-size: 50px; color: #004aad;
    }
    .btn {
      background-color: #004aad; color: white; padding: 8px 15px; border: none;
      border-radius: 5px; cursor: pointer; text-decoration: none;
      font-size: 14px; margin-left: 30px; margin-top: 10px; margin-bottom: 10px;
    }
    .btn:hover {
      background-color: #003080;
    }
  </style>
</head>

<body>
  <div class="sidebar">
    <div class="profile">
      <img src="fuego.jpg" alt="Profile Image"
        style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-left: 40px;">
      <h3><?php echo htmlspecialchars($teacher_name); ?></h3>
    </div>
    <div class="menu">
      <a href="/CAPSTONE_LMS_EHS/profdashboard/profhome.php">Dashboard</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/course.php">Courses</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/performance.php">Performance</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/reminders.php">Reminders</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/account.php">Account</a>
      <a href="/CAPSTONE_LMS_EHS/auth/login.php">Logout</a>
    </div>
  </div>

  <!-- 🔒 DO NOT TOUCH ANYTHING BELOW THIS LINE INSIDE MAIN DIV -->
  <div class="main">
    <div class="topbar">
      <a href="viewstrand.php?course=<?= urlencode($_GET['course'] ?? '') ?>" class="back-btn">
        <span class="material-icons">arrow_back</span>
      </a>
      Course Requirements (<?php echo htmlspecialchars($strand) . " - " . htmlspecialchars($section); ?>)
    </div>

    <div class="stats">
      <div class="stat-box">
        <span class="material-icons stat-icon">campaign</span>
        <p>Announcements</p>
        <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/announcement.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="btn">
          Go to Announcements
        </a>
      </div>
      <div class="stat-box">
        <span class="material-icons stat-icon">assignment</span>
        <p>Activities</p>
        <a href="openreq/activities.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>&course=<?= urlencode($course_name) ?>" class="btn">
          Open Activities
        </a>
      </div>
      <div class="stat-box">
        <span class="material-icons stat-icon">folder</span>
        <p>Modules</p>
        <a href="openreq/module.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>&course=<?= urlencode($course_name) ?>" class="btn">
          Open Module
        </a>
      </div>
    </div>

    <div class="stats">
      <div class="stat-box">
        <span class="material-icons stat-icon">quiz</span>
        <p>Quizzes</p>
        <a href="openreq/quizzes.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>&course=<?= urlencode($course_name) ?>" class="btn">
          Open Quizzes
        </a>
      </div>
      <div class="stat-box">
        <span class="material-icons stat-icon">fact_check</span>
        <p>Exam</p>
        <a href="openreq/exam.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>&course=<?= urlencode($course_name) ?>" class="btn">
          Open Exam
        </a>
      </div>
      <div class="stat-box">
        <span class="material-icons stat-icon">event_available</span>
        <p>Attendance</p>
        <a href="openreq/attendance.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>&course=<?= urlencode($course_name) ?>" class="btn">
          Open Attendace
        </a>
      </div>
    </div>
  </div>
</body>
</html>
