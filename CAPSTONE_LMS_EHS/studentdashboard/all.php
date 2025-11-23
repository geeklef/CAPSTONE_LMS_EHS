<?php
session_start();
include '../config/db_connect.php'; // ✅ Supabase PDO connection

// ✅ Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    die("Access denied. No student logged in.");
}

$student_id = $_SESSION['student_id'];
$class_id = $_GET['class_id'] ?? '';

if (empty($class_id)) {
    die("Missing course information (need class_id).");
}

try {
    // Fetch strand and section from prof_courses using class_id
$stmt = $conn->prepare("
    SELECT strand, section, course_name
    FROM prof_courses
    WHERE class_id = :cid
    LIMIT 1
");
$stmt->execute(['cid' => $class_id]);
$courseData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$courseData) {
        die("⚠️ No course found for this class ID.");
    }

    $strand  = $courseData['strand'];
    $section = $courseData['section'];
    $course_name = $courseData['course_name'];

    // Store in session for other queries
    $_SESSION['class_id'] = $class_id;
    $_SESSION['strand']   = $strand;
    $_SESSION['section']  = $section;

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}


if (empty($strand)) {
    die("Missing course information (need strand).");
}

try {
    // 🎓 Get student info
    $stmt = $conn->prepare("SELECT first_name, last_name FROM students_account WHERE stud_id = :id");
    $stmt->execute(['id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_name = $student ? $student['first_name'] . ' ' . $student['last_name'] : 'Unknown Student';

    // 🧭 Get student's enrolled class info
    $stmt2 = $conn->prepare("
        SELECT pc.course_id, pc.course_name, pc.section, pc.strand
        FROM student_enrollments se
        JOIN prof_courses pc ON se.course_id = pc.course_id
        WHERE se.student_id = :sid AND pc.strand = :strand
    ");
    $stmt2->execute(['sid' => $student_id, 'strand' => $strand]);
    $courseData = $stmt2->fetch(PDO::FETCH_ASSOC);

    if (!$courseData) {
        die("⚠️ No course details found for this strand.");
    }

    // Keep original class_id from GET or from session
$class_id = $class_id; 

    $section = $courseData['section'];
    $strand = $courseData['strand'];
    $_SESSION['class_id'] = $class_id;
    $_SESSION['strand']   = $strand;
    $_SESSION['section']  = $section;

    // 📢 Fetch Announcements
    $annStmt = $conn->prepare("
        SELECT a.*, ta.first_name, ta.last_name 
        FROM prof_announcements a
        JOIN teachers_account ta ON a.teacher_id = ta.teacher_id
        WHERE a.class_id = :cid
        ORDER BY a.announce_id DESC
    ");
    $annStmt->execute(['cid' => $class_id]);
    $announcements = $annStmt->fetchAll(PDO::FETCH_ASSOC);

    // 📝 Fetch Activities
    $actStmt = $conn->prepare("
        SELECT ac.*, ta.first_name, ta.last_name 
        FROM prof_activities ac
        JOIN teachers_account ta ON ac.teacher_id = ta.teacher_id
        WHERE ac.class_id = :cid
        ORDER BY ac.activity_id DESC
    ");
    $actStmt->execute(['cid' => $class_id]);
    $activities = $actStmt->fetchAll(PDO::FETCH_ASSOC);

    // 📘 Fetch Modules
    $modStmt = $conn->prepare("
        SELECT m.*, ta.first_name, ta.last_name 
        FROM prof_modules m
        JOIN teachers_account ta ON m.teacher_id = ta.teacher_id
        WHERE m.class_id = :cid
        ORDER BY m.module_id DESC
    ");
    $modStmt->execute(['cid' => $class_id]);
    $modules = $modStmt->fetchAll(PDO::FETCH_ASSOC);

    // 📊 Fetch Quizzes
    $quizStmt = $conn->prepare("
        SELECT q.*, ta.first_name, ta.last_name 
        FROM prof_quiz q
        JOIN teachers_account ta ON q.teacher_id = ta.teacher_id
        WHERE q.class_id = :cid
        ORDER BY q.quiz_id DESC
    ");
    $quizStmt->execute(['cid' => $class_id]);
    $quizzes = $quizStmt->fetchAll(PDO::FETCH_ASSOC);

    // 📝 Fetch Exams
    $examStmt = $conn->prepare("
        SELECT e.*, ta.first_name, ta.last_name 
        FROM prof_exam e
        JOIN teachers_account ta ON e.teacher_id = ta.teacher_id  
        WHERE e.class_id = :cid
        ORDER BY e.exam_id DESC
    ");
    $examStmt->execute(['cid' => $class_id]);
    $exams = $examStmt->fetchAll(PDO::FETCH_ASSOC);

    // 📅 Fetch Attendance
    $attStmt = $conn->prepare("
        SELECT a.*, ta.first_name, ta.last_name 
        FROM prof_attendance a
        JOIN teachers_account ta ON a.teacher_id = ta.teacher_id
        WHERE a.class_id = :cid
        ORDER BY a.attendance_id DESC
    ");
    $attStmt->execute(['cid' => $class_id]);
    $attendance = $attStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
$imageQuery = $conn->prepare("SELECT image_url FROM student_profile_images WHERE student_id = :student_id ORDER BY uploaded_at DESC LIMIT 1");
$imageQuery->execute(['student_id' => $student_id]);
$image = $imageQuery->fetch(PDO::FETCH_ASSOC);

$profileImg = $image ? $image['image_url'] : 'default_prof.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LMS Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="/CAPSTONE_LMS_EHS/assets/student/css/all.css" rel="stylesheet">

  <style>

     .sidebar h2 {
      margin-bottom: 10px;
    }

    .sidebar h3 {
      margin-left: 40px;
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
<div class="sidebar" id="sidebar">
    <div class="profile">
    <img src="/CAPSTONE_LMS_EHS/assets/landingpage/ehslogo.png" alt="Profile Image" class="top-profile-img">
    <h3>Eusebio High School</h3>
  </div>

  <div class="menu">
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/userhome.php"><span class="material-icons">dashboard</span> Dashboard</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/courses.php" class="active"><span class="material-icons">menu_book</span> Courses</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/performance.php"><span class="material-icons">bar_chart</span> Performance</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/reminders.php"><span class="material-icons">notifications</span> Reminders</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/account.php"><span class="material-icons">account_circle</span> Account</a>
    <a href="/CAPSTONE_LMS_EHS/auth/login.html"><span class="material-icons">logout</span> Logout</a>
  </div>

  <!-- BOTTOM PROFILE -->
  <div class="sidebar-bottom">
    <div class="bottom-img-container">
       <img src="<?php echo htmlspecialchars($profileImg); ?>" alt="Profile Image" class="bottom-profile-img">
      <span class="online-dot"></span>
    </div>

    <div>
      <p><?php echo $student_name; ?></p>
      <p class="bottom-status">Student</p>
    </div>
  </div>


</div>

<div class="main">
  <div class="topbar">
    <a href="courses.php" class="back-btn"><span class="material-icons">arrow_back</span></a>
    Course Requirements (<?= htmlspecialchars("$strand - $section") ?>)
  </div>

  <div class="filter-buttons">
    <a href="#" class="filter-btn active"><span class="material-icons">grid_view</span>All</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/openreq/announcement.php " class="filter-btn"><span class="material-icons">campaign</span>Announcements</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/openreq/activities.php" class="filter-btn"><span class="material-icons">event</span>Activities</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/openreq/module.php" class="filter-btn"><span class="material-icons">menu_book</span>Modules</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/openreq/quizzes.php" class="filter-btn"><span class="material-icons">quiz</span>Quizzes</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/openreq/exam.php" class="filter-btn"><span class="material-icons">school</span>Exam</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/openreq/attendance.php" class="filter-btn"><span class="material-icons">fact_check</span>Attendance</a>
  </div>

  <!-- ✅ Unified Content Display -->
  <div class="task-list">

    <?php
    $allData = [
        ['icon' => 'campaign', 'color' => '#e8f0ff', 'label' => 'Announcements', 'data' => $announcements, 'title_key' => 'title', 'desc_key' => 'desc'],
        ['icon' => 'event', 'color' => '#fff4e6', 'label' => 'Activities', 'data' => $activities, 'title_key' => 'title', 'desc_key' => 'desc'],
        ['icon' => 'menu_book', 'color' => '#e6ffed', 'label' => 'Modules', 'data' => $modules, 'title_key' => 'title', 'desc_key' => 'desc'],
        ['icon' => 'quiz', 'color' => '#f0e6ff', 'label' => 'Quizzes', 'data' => $quizzes, 'title_key' => 'title', 'desc_key' => 'desc'],
        ['icon' => 'school', 'color' => '#ffe6eb', 'label' => 'Exams', 'data' => $exams, 'title_key' => 'title', 'desc_key' => 'desc'],
        ['icon' => 'fact_check', 'color' => '#e6f9ff', 'label' => 'Attendance', 'data' => $attendance, 'title_key' => 'title', 'desc_key' => 'desc']
    ];

    $empty = true;

    foreach ($allData as $set) {
        if (!empty($set['data'])) {
            $empty = false;
            foreach ($set['data'] as $item): ?>
                <div class="task-card">
                  <div class="task-icon"><span class="material-icons"><?= $set['icon'] ?></span></div>
                  <div class="task-content">
                    <h4><?= htmlspecialchars($item[$set['title_key']] ?? 'Untitled') ?>
                      <span class="task-tag" style="background-color:<?= $set['color'] ?>;color:#004aad;"><?= $set['label'] ?></span>
                    </h4>
                    <p><?= nl2br(htmlspecialchars($item[$set['desc_key']] ?? 'No description.')) ?></p>
                    <p style="font-size:0.85rem;color:#555;">
                      Posted by: <?= htmlspecialchars(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? '')) ?> |
                      <?= date("F d, Y | h:i A", strtotime($item['created_at'] ?? 'now')) ?>
                    </p>
                  </div>
                </div>
            <?php endforeach;
        }
    }

    if ($empty): ?>
        <p style="padding:20px;">No records yet for this course.</p>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
