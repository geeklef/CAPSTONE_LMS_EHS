<?php
include '../config/db_connect.php'; // ✅ Supabase PDO connection

session_start();
$student_id = $_SESSION['student_id'] ?? 0;
$student_name = 'Student';
$strand = '';
$section = '';

if ($student_id > 0) {
    $sql = "SELECT CONCAT(first_name, ' ', last_name) AS name, strand, section 
            FROM students_account 
            WHERE stud_id = :student_id";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['student_id' => $student_id]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $student_name = $row['name'];
        $strand = $row['strand'] ?: 'Strand';
        $section = $row['section'] ?: 'Section';
    }
    $stmt = null;
}

$strand_display = $strand;
$section_display = $section;

$course_name = $_GET['course'] ?? ($_SESSION['course'] ?? '');

if (!empty($course_name)) {
    $_SESSION['course'] = $course_name;
}

$class_id = '';
if (!empty($strand) && !empty($section) && !empty($course_name)) {
    $sql = "SELECT class_id FROM prof_courses 
            WHERE strand = :strand AND section = :section AND course_name = :course_name 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'strand' => $strand,
        'section' => $section,
        'course_name' => $course_name
    ]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $class_id = $row['class_id'];
    }
    $stmt = null;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LMS Course Requirements</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }

        body {
            display: flex;
            height: 100vh;
            background-color: #f4f7f9;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: #004aad;
            color: white;
            padding: 20px;
            overflow-y: auto;
        }

        .sidebar h2 {
            margin-bottom: 10px;
        }

        .sidebar h3 {
            margin-left: 40px;
            margin-bottom: 40px;
        }

        .sidebar .menu a {
            display: block;
            padding: 10px 0;
            color: white;
            text-decoration: none;
            margin-bottom: 10px;
        }

        .sidebar .menu a:hover {
            background-color: #004aad;
            padding-left: 10px;
        }

        .main {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }

        .topbar {
            background-color: #004aad;
            padding: 20px;
            color: white;
            font-size: 24px;
            border-radius: 10px;
            display: flex;
            align-items: center;
        }

        .back-btn {
            display: inline-block;
            vertical-align: middle;
            text-decoration: none;
            margin-right: 10px;
            color: white;
        }

        .back-btn .material-icons {
            font-size: 28px;
            vertical-align: middle;
        }

        .stats {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    margin: 27px 0;
    gap: 20px;
}

.stat-box {
    background: white;
    padding: 20px 20px 20px 60px;
    border-radius: 10px;
    height: 118px;
    width: calc(33% - 14px); /* Adjust for margins/gaps */
    min-width: 220px; /* Optional: prevent too small on smaller screens */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    position: relative;
}

.stat-box p {
    font-size: 20px;
    margin-left: 30px;
    margin-bottom: 10px;
}

.material-icons.stat-icon {
    position: absolute;
    top: 30px;
    left: 20px;
    font-size: 50px;
    color: #004aad;
}

.btn {
    background-color: #004aad;
    color: white;
    padding: 8px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
    margin-left: 30px;
    margin-top: 20px;
    margin-bottom: 10px;
}

        .certificates,
        .rewards {
            display: flex;
            justify-content: space-between;
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .course-item {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }

        .course-item img {
            width: 50px;
            height: 50px;
            margin-right: 15px;
        }

        .progress-bar {
            width: 100%;
            height: 5px;
            background: #ddd;
            margin: 5px 0;
            border-radius: 10px;
        }

        .progress-fill {
            height: 100%;
            background-color: #004aad;
            border-radius: 10px;
        }


        .btn:hover {
            background-color: #004aad;
        }

        .icon-row span {
            display: inline-block;
            width: 30px;
            height: 30px;
            background-color: #ccc;
            border-radius: 50%;
            margin: 0 5px;
        }

        .rewards p,
        .certificates p {
            margin: 5px 0 10px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="profile">
        <img src="abo.jpg" alt="Profile Image"
             style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-left: 40px;">
        <h3><?php echo htmlspecialchars($student_name); ?></h3>
    </div>
    <div class="menu">
        <a href="userhome.php">Dashboard</a>
        <a href="courses.php">Courses</a>
        <a href="performance.php">Performance</a>
        <a href="reminders.php">Reminders</a>
        <a href="account.php">Account</a>
        <a href="/CAPSTONE LMS EHS/login.php">Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <a href="courses.php" class="back-btn">
            <span class="material-icons">arrow_back</span>
        </a>
        Course Requirements (<?php echo htmlspecialchars($strand_display) . " - " . htmlspecialchars($section_display); ?>)

    </div>

    <div class="stats">
        <div class="stat-box">
            <span class="material-icons stat-icon">campaign</span>
            <p>Announcements</p>
            <a href="openreq/announcement.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="btn">View Announcements</a>
        </div>
        <div class="stat-box">
            <span class="material-icons stat-icon">assignment</span>
            <p>Activities</p>
            <a href="openreq/activities.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>&course=<?= urlencode($course_name) ?>" class="btn">Open Activities</a>
        </div>
        <div class="stat-box">
            <span class="material-icons stat-icon">folder</span>
            <p>Modules</p>
            <a href="openreq/module.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>&course=<?= urlencode($course_name) ?>" class="btn">Open Modules</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box">
            <span class="material-icons stat-icon">quiz</span>
            <p>Quizzes</p>
            <a href="openreq/quizzes.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>&course=<?= urlencode($course_name) ?>" class="btn">Open Quizzes</a>
        </div>
        <div class="stat-box">
            <span class="material-icons stat-icon">fact_check</span>
            <p>Exams</p>
            <a href="openreq/exam.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>&course=<?= urlencode($course_name) ?>" class="btn">Open Exams</a>
        </div>
        <div class="stat-box">
            <span class="material-icons stat-icon">event_available</span>
            <p>Attendance</p>
            <a href="openreq/attendance.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>&course=<?= urlencode($course_name) ?>" class="btn">Open Attendance</a>
        </div>
    </div>

</div>

</body>
</html>
