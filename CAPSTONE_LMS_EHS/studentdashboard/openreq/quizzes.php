<?php
include '../../config/db_connect.php';
session_start();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --- Student session ---
$student_id = $_SESSION['student_id'] ?? 0;
if (!$student_id) {
    die("⚠️ Error: No valid student session found. Please log in again.");
}

// --- Course/session info ---
$class_id = $_GET['class_id'] ?? ($_SESSION['class_id'] ?? '');
$section  = $_GET['section']  ?? ($_SESSION['section'] ?? '');
$course   = $_GET['course']   ?? ($_SESSION['course'] ?? '');
$strand   = $_GET['strand']   ?? ($_SESSION['strand'] ?? '');

if (!empty($class_id)) $_SESSION['class_id'] = $class_id;
if (!empty($section))  $_SESSION['section']  = $section;
if (!empty($course))   $_SESSION['course']   = $course;
if (!empty($strand))   $_SESSION['strand']   = $strand;

// --- Fetch student info ---
$stmt = $conn->prepare("SELECT first_name, last_name FROM students_account WHERE stud_id = :id");
$stmt->execute(['id' => $student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$student_name = $student ? $student['first_name'] . ' ' . $student['last_name'] : 'Unknown Student';

// --- Fetch latest profile image ---
try {
    $imgStmt = $conn->prepare("SELECT image_url FROM student_profile_images WHERE student_id = :sid ORDER BY uploaded_at DESC LIMIT 1");
    $imgStmt->execute(['sid' => $student_id]);
    $img = $imgStmt->fetch(PDO::FETCH_ASSOC);
    $profileImg = $img ? $img['image_url'] : 'default_prof.jpg';
} catch (PDOException $e) {
    $profileImg = 'default_prof.jpg';
}

// --- Handle cancel submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_submission']) && isset($_POST['cancel_submission_id'])) {
    $cancel_id = intval($_POST['cancel_submission_id']);
    if ($cancel_id > 0) {
        try {
            // Get file path for optional Supabase deletion
            $stmt = $conn->prepare("SELECT file_path FROM stud_quiz_results WHERE quiz_id = :qid AND stud_id = :sid");
            $stmt->execute(['qid' => $cancel_id, 'sid' => $student_id]);
            $file = $stmt->fetchColumn();

            // Delete submission
            $stmt = $conn->prepare("DELETE FROM stud_quiz_results WHERE quiz_id = :qid AND stud_id = :sid");
            $stmt->execute(['qid' => $cancel_id, 'sid' => $student_id]);

            // Optional: delete file from Supabase if needed (uncomment if using)
            /*
            if ($file && str_contains($file, $supabaseUrl)) {
                $path = str_replace(rtrim($supabaseUrl, '/') . "/storage/v1/object/public/$supabaseBucket/", '', $file);
                $delUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/$supabaseBucket/" . rawurlencode($path);
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $delUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => 'DELETE',
                    CURLOPT_HTTPHEADER => ["Authorization: Bearer $supabaseAnonKey"]
                ]);
                curl_exec($ch);
                curl_close($ch);
            }
            */

            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        } catch (PDOException $e) {
            die("Error cancelling submission: " . $e->getMessage());
        }
    }
}

// --- Fetch quizzes ---
try {
    $stmt = $conn->prepare("
        SELECT q.*, r.score, r.submitted_at
        FROM prof_quiz q
        LEFT JOIN stud_quiz_results r
        ON q.quiz_id = r.quiz_id AND r.stud_id = :sid
        WHERE q.strand = :strand AND q.section = :section AND q.class_id = :class_id
        ORDER BY q.due_date DESC
    ");
    $stmt->execute([
        'sid' => $student_id,
        'strand' => $strand,
        'section' => $section,
        'class_id' => $class_id
    ]);
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching quizzes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LMS Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="/CAPSTONE_LMS_EHS/assets/student/css/quizzes.css" rel="stylesheet">
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
      <a href="viewstrand.html" class="back-btn">
        <span class="material-icons">arrow_back</span>
      </a>
      Course Requirements (<?= htmlspecialchars($strand) ?> - <?= htmlspecialchars($section) ?>)
    </div>

  <div class="filter-buttons">
   <a href="/CAPSTONE_LMS_EHS/studentdashboard/all.php?class_id=<?= urlencode($class_id) ?>&strand=<?= urlencode($strand) ?>&section=<?= urlencode($section) ?>" class="filter-btn">
    <span class="material-icons">grid_view</span>All
</a>
    <a href="announcement.php" class="filter-btn"><span class="material-icons">campaign</span>Announcements</a>
    <a href="activities.php" class="filter-btn"><span class="material-icons">event</span>Activities</a>
    <a href="module.php" class="filter-btn "><span class="material-icons">menu_book</span>Modules</a>
    <a href="quizzes.php" class="filter-btn active"><span class="material-icons">quiz</span>Quizzes</a>
    <a href="exam.php" class="filter-btn"><span class="material-icons">school</span>Exam</a>
    <a href="attendance.php" class="filter-btn"><span class="material-icons">fact_check</span>Attendance</a>
  </div>

    <!-- Task Cards -->
    <div class="task-list">
      <?php if (empty($quizzes)): ?>
        <p style="text-align:center; color:gray;">No quizzes available yet.</p>
      <?php else: ?>
        <?php foreach ($quizzes as $quiz): ?>
          <div class="task-card">
            <div class="task-icon">
              <span class="material-icons">quiz</span>
            </div>
            <div class="task-content">
              <h4><?= htmlspecialchars($quiz['title']) ?>
                <span class="task-tag" style="background-color:#fde0dc;color:#c2185b;">Quiz</span>
              </h4>
              <p><?= htmlspecialchars($quiz['description'] ?? 'No description provided.') ?></p>
              <p>Date Posted: <?= date('F d, Y', strtotime($quiz['date_posted'])) ?> |
                Due: <?= date('F d, Y', strtotime($quiz['due_date'])) ?> -
                <?= date('h:i A', strtotime($quiz['due_time'])) ?></p>
            </div>

            <?php if ($quiz['score'] !== null): ?>
              <div class="task-meta" style="color:green;">
                ✅ Completed
              </div>
            <?php else: ?>
              <a href="/CAPSTONE_LMS_EHS/studentdashboard/openreq/quizzes_answer.php?quiz_id=<?= $quiz['quiz_id'] ?>"class="task-meta">Take Quiz</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <script src="viewsubject.js"></script>
</body>

</html>
