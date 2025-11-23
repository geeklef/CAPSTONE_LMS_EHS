<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['teacher_id'])) die("Access denied.");
$teacher_id = $_SESSION['teacher_id'];

$class_id = $_GET['class_id'] ?? '';
if (!$class_id) die("Class ID missing.");

// Fetch class info (teacher can only access their own classes)
$stmt = $conn->prepare("SELECT * FROM prof_courses WHERE class_id=:class_id AND teacher_id=:teacher_id");
$stmt->execute(['class_id'=>$class_id, 'teacher_id'=>$teacher_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$class) die("Class not found or access denied.");

// Fetch students enrolled in this course
$stmt = $conn->prepare("
    SELECT s.stud_id, s.first_name, s.last_name
    FROM students_account s
    JOIN student_enrollments se ON se.student_id = s.stud_id
    WHERE se.course_id=:course_id
");
$stmt->execute(['course_id'=>$class['course_id']]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total quiz points for this class
$stmt = $conn->prepare("SELECT COALESCE(SUM(total_points),0) as total_quiz_points FROM prof_quiz WHERE class_id=:class_id");
$stmt->execute(['class_id'=>$class_id]);
$totalQuizPoints = $stmt->fetchColumn();

// Fetch total exam points for this class
$stmt = $conn->prepare("SELECT COALESCE(SUM(total_points),0) as total_exam_points FROM prof_exam WHERE class_id=:class_id");
$stmt->execute(['class_id'=>$class_id]);
$totalExamPoints = $stmt->fetchColumn();

// Fetch total number of activities for this class
$stmt = $conn->prepare("SELECT COUNT(*) FROM prof_activities WHERE class_id=:class_id");
$stmt->execute(['class_id'=>$class_id]);
$totalActivities = (int)$stmt->fetchColumn();

// Fetch total attendance entries for this class
$stmt = $conn->prepare("SELECT COUNT(*) FROM prof_attendance WHERE class_id=:class_id");
$stmt->execute(['class_id'=>$class_id]);
$totalAttendance = (int)$stmt->fetchColumn();

// Build student data with computed grades
$studentData = [];
foreach ($students as $s) {
    $stud_id = $s['stud_id'];

// --- Quiz Average
$stmt = $conn->prepare("SELECT COALESCE(SUM(score),0) as total_score FROM stud_quiz_results sq
                        JOIN prof_quiz pq ON pq.quiz_id = sq.quiz_id
                        WHERE sq.stud_id=:stud_id AND pq.class_id=:class_id");
$stmt->execute(['stud_id'=>$stud_id,'class_id'=>$class_id]);
$quizScore = $stmt->fetchColumn();
$quizAvg = $totalQuizPoints > 0 ? ($quizScore/(float)$totalQuizPoints)*100 : 0;

// --- Exam Average
$stmt = $conn->prepare("SELECT COALESCE(SUM(score),0) as total_score FROM stud_exam_results se
                        JOIN prof_exam pe ON pe.exam_id = se.exam_id
                        WHERE se.stud_id=:stud_id AND pe.class_id=:class_id");
$stmt->execute(['stud_id'=>$stud_id,'class_id'=>$class_id]);
$examScore = $stmt->fetchColumn();
$examAvg = $totalExamPoints > 0 ? ($examScore/(float)$totalExamPoints)*100 : 0;

// --- Activity Average (max 100 per activity)
$stmt = $conn->prepare("SELECT COALESCE(SUM(score),0) as total_score FROM stud_activity_submissions
                        WHERE stud_id=:stud_id AND class_id=:class_id");
$stmt->execute(['stud_id'=>$stud_id,'class_id'=>$class_id]);
$activityScore = $stmt->fetchColumn();
$activityAvg = $totalActivities > 0 ? ($activityScore/((float)$totalActivities*100))*100 : 0;

// --- Attendance %
$stmt = $conn->prepare("SELECT COUNT(*) FROM stud_attendance sa
                        JOIN prof_attendance pa ON pa.attendance_id = sa.prof_attendance_id
                        WHERE sa.student_id=:stud_id AND pa.class_id=:class_id AND sa.status='Present'");
$stmt->execute(['stud_id'=>$stud_id,'class_id'=>$class_id]);
$presentCount = (int)$stmt->fetchColumn();
$attendancePct = $totalAttendance > 0 ? ($presentCount/(float)$totalAttendance)*100 : 0;

// --- Final Grade (weights: Quiz 20%, Exam 40%, Activity 30%, Attendance 10%)
$finalGrade = ($quizAvg*0.2) + ($examAvg*0.4) + ($activityAvg*0.3) + ($attendancePct*0.1);
$status = $finalGrade >= 75 ? 'Pass' : 'Fail';

    // --- Weakness & Quote (from predictions if exists)
    $stmt = $conn->prepare("SELECT weakness, quote FROM student_predictions WHERE stud_id=:stud_id AND class_id=:class_id");
    $stmt->execute(['stud_id'=>$stud_id,'class_id'=>$class_id]);
    $pred = $stmt->fetch(PDO::FETCH_ASSOC);

    $studentData[] = [
        'id'=>$stud_id,
        'name'=>$s['first_name'].' '.$s['last_name'],
        'quiz'=>round($quizAvg,2),
        'exam'=>round($examAvg,2),
        'activity'=>round($activityAvg,2),
        'attendance'=>round($attendancePct,2),
        'final_grade'=>round($finalGrade,2),
        'status'=>$status,
        'weakness'=>$pred['weakness'] ?? '-',
        'quote'=>$pred['quote'] ?? '-'
    ];
}

// Optional: fetch profile image
$imageQuery = $conn->prepare("SELECT image_url FROM teacher_profile_images WHERE teacher_id = :teacher_id ORDER BY uploaded_at DESC LIMIT 1");
$imageQuery->execute(['teacher_id' => $teacher_id]);
$image = $imageQuery->fetch(PDO::FETCH_ASSOC);
$profileImg = $image ? $image['image_url'] : 'default_prof.jpg';

// Fetch teacher name
$teacher_name = 'Professor';
$stmt = $conn->prepare("SELECT first_name, last_name FROM teachers_account WHERE teacher_id = :teacher_id");
$stmt->execute(['teacher_id'=>$teacher_id]);
if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $teacher_name = $row['first_name'].' '.$row['last_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Performance List</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
<link href="/CAPSTONE_LMS_EHS/assets/prof/css/performancelist.css" rel="stylesheet" />
<style>
    /* Sidebar */
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
      transition: left 0.3s ease;
      z-index: 1000;
      display: flex;
  flex-direction: column;
  justify-content: space-between;
    }

    .sidebar h3 {
      margin-left: 5px;
      margin-bottom: 40px;

     
    }

    .sidebar .menu a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 0;
      color: rgb(202, 201, 201);
      text-decoration: none;
      margin-bottom: 10px;
      font-size: 16px;
      transition: all 0.3s ease;
    }

    .sidebar .menu a:hover {
      background-color: rgba(255, 255, 255, 0.15);
      padding-left: 10px;
      border-radius: 5px;
    }

    .sidebar .menu a.active {
      color: white;
      font-weight: 600;
      position: relative;
    }

    .sidebar .menu a.active::before {
      content: "";
      position: absolute;
      left: -20px;
      top: 0;
      bottom: 0;
      width: 5px;
      background-color: white;
      border-radius: 10px;
    }

    .sidebar .menu a .material-icons {
      font-size: 22px;
    }
/* Top profile image */
.top-profile-img {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  object-fit: cover;
  margin-left: 40px;
}

/* Bottom container */
.sidebar-bottom {
   margin-top: auto;
  padding: 10px;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
  
}




/* Bottom image wrapper (for online dot) */
.bottom-img-container {
  position: relative;
}

/* Bottom profile image */
.bottom-profile-img {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  object-fit: cover;
}

/* Online dot */
.online-dot {
  position: absolute;
  right: -2px;
  bottom: -2px;
  width: 13px;
  height: 13px;
  background: #00e676;
  border-radius: 50%;
  border: 2px solid #004aad;
}

/* Bottom text */
.bottom-name {
  margin: 0;
  color: #fff;
  font-weight: 600;
  font-size: 15px;
}

.bottom-status {
  margin: 0;
  margin-top: -2px;
  font-size: 13px;
  color: #d8e7ff;
}
</style>
</head>
<body>
<!-- SIDEBAR AND LAYOUT SAME AS YOUR ORIGINAL CODE -->
<div class="sidebar">
   <div class="profile">
    <img src="/CAPSTONE_LMS_EHS/assets/landingpage/ehslogo.png" alt="Profile Image" class="top-profile-img">
    <h3>Eusebio High School</h3>
  </div>
   <div class="menu">
      <a href="/CAPSTONE_LMS_EHS/profdashboard/profhome.php"><span class="material-icons">dashboard</span> Dashboard</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/course.php"><span class="material-icons">menu_book</span> Courses</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/performance.php"class="active"><span class="material-icons">bar_chart</span> Performance</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/reminders.php"><span class="material-icons">notifications</span> Reminders</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/account.php"><span class="material-icons">account_circle</span> Account</a>
      <a href="/CAPSTONE_LMS_EHS/auth/login.php"><span class="material-icons">logout</span> Logout</a>
  </div>
  <div class="sidebar-bottom">
    <div class="bottom-img-container">
       <img src="<?=htmlspecialchars($profileImg)?>" alt="Profile Image" class="bottom-profile-img">
      <span class="online-dot"></span>
    </div>
    <div>
      <p><?=$teacher_name?></p>
      <p class="bottom-status">Teacher</p>
    </div>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <a href="performance.php" class="back-btn"><span class="material-icons">arrow_back</span></a>
    <?= htmlspecialchars($class['course_name'].' ('.$class['strand'].' - Section '.$class['section'].')') ?>
  </div>

  <div class="rewards">
    <div class="rewards-header"><h3>Students Lists</h3></div>
    <div style="width:100%;">
      <table class="records-table">
        <thead>
          <tr>
            <th>School ID</th>
            <th>Name</th>
            <th>Quiz %</th>
            <th>Exam %</th>
            <th>Activity %</th>
            <th>Attendance %</th>
            <th>Final Grade</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($studentData as $s): ?>
          <tr>
            <td><?= $s['id'] ?></td>
            <td><?= htmlspecialchars($s['name']) ?></td>
            <td><?= $s['quiz'] ?></td>
            <td><?= $s['exam'] ?></td>
            <td><?= $s['activity'] ?></td>
            <td><?= $s['attendance'] ?></td>
            <td><?= $s['final_grade'] ?></td>
            <td class="<?= strtolower($s['status'])=='pass'?'status-passing':'status-failing' ?>"><?= htmlspecialchars($s['status']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
