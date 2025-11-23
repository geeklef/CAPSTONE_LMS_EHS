<?php
include '../../config/db_connect.php';
session_start();

// 🧭 Get student info from session
$student_id = $_SESSION['student_id'] ?? 1; // fallback for testing
$course     = $_GET['course'] ?? '';
$class_id   = $_SESSION['class_id'] ?? '';
$strand     = $_SESSION['strand'] ?? '';
$section    = $_SESSION['section'] ?? '';

// 🖼 Fetch latest profile image
$imageQuery = $conn->prepare("
    SELECT image_url 
    FROM student_profile_images 
    WHERE student_id = :student_id 
    ORDER BY uploaded_at DESC 
    LIMIT 1
");
$imageQuery->execute(['student_id' => $student_id]);
$image = $imageQuery->fetch(PDO::FETCH_ASSOC);

$profileImg = $image ? $image['image_url'] : 'default_prof.jpg';

// 🔍 Fetch course details if course provided but session missing
if (!empty($course) && (empty($class_id) || empty($strand) || empty($section))) {
    try {
        $stmt = $conn->prepare("
            SELECT class_id, strand, section 
            FROM prof_courses 
            WHERE course_name = :course
            LIMIT 1
        ");
        $stmt->execute(['course' => $course]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $class_id = $row['class_id'];
            $strand   = $row['strand'];
            $section  = $row['section'];

            $_SESSION['class_id'] = $class_id;
            $_SESSION['strand']   = $strand;
            $_SESSION['section']  = $section;
        }
    } catch (PDOException $e) {
        die("Error fetching course details: " . $e->getMessage());
    }
}

// ⚠️ Require class info
if (empty($class_id) || empty($strand) || empty($section)) {
    die("Missing class_id, strand, or section for course: " . htmlspecialchars($course));
}

// 🧑‍🎓 Fetch student name
$student_name = 'Unknown Student';
try {
    $stmt = $conn->prepare("
        SELECT first_name, last_name 
        FROM students_account 
        WHERE stud_id = :student_id
    ");
    $stmt->execute(['student_id' => $student_id]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $student_name = $row['first_name'] . ' ' . $row['last_name'];
    }
} catch (PDOException $e) {
    die("Error fetching student name: " . $e->getMessage());
}

// 📜 Fetch announcements
$announcements = [];
try {
    $stmt = $conn->prepare("
        SELECT a.*, t.first_name, t.last_name
        FROM prof_announcements a
        JOIN teachers_account t ON a.teacher_id = t.teacher_id
        WHERE a.class_id = :class_id
          AND a.strand = :strand
          AND a.section = :section
        ORDER BY a.announce_id DESC
    ");
    $stmt->execute([
        'class_id' => $class_id,
        'strand'   => $strand,
        'section'  => $section
    ]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching announcements: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Announcements</title>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
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

    /* Main Content */
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

    /* --- Filter Buttons --- */
    .filter-buttons {
      display: flex;
      gap: 15px;
      margin-top: 30px;
      flex-wrap: wrap;
    }

    .filter-btn {
      display: flex;
      align-items: center;
      gap: 8px;
      background: white;
      border: none;
      border-radius: 12px;
      padding: 10px 18px;
      font-size: 16px;
      font-weight: 600;
      color: #333;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .filter-btn:hover {
      background-color: #ebf2ff;
    }

    .filter-btn.active {
      background-color: #004aad;
      color: white;
    }

    .filter-btn .material-icons {
      font-size: 22px;
    }

    /* --- Task Cards --- */
    .task-list {
      margin-top: 30px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .task-card {
      background: white;
      border-radius: 15px;
      padding: 20px;
      display: flex;
      align-items: flex-start;
      gap: 15px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s ease;
    }

    .task-card:hover {
      transform: translateY(-3px);
    }

    .task-icon {
      background-color: #ebf2ff;
      color: #004aad;
      border-radius: 10px;
      padding: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .task-icon .material-icons {
      font-size: 30px;
    }

    .task-content h4 {
      font-size: 18px;
      margin-bottom: 5px;
      color: #333;
    }

    .task-content p {
      font-size: 14px;
      color: #666;
      margin-bottom: 10px;
    }

    .task-tag {
      display: inline-block;
      background-color: #ffe6e6;
      color: #cc0000;
      font-size: 12px;
      font-weight: 600;
      padding: 3px 8px;
      border-radius: 10px;
    }

    .task-meta {
      margin-left: auto;
      font-size: 14px;
      color: #004aad;
      font-weight: 600;
      text-decoration: none;
    }

    .task-meta:hover {
      text-decoration: underline;
    }
    .upload-section {
  margin-top: 10px;
}

.file-overview {
  background-color: #f8faff;
  border-radius: 10px;
  padding: 10px;
  margin-top: 12px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
}

.file-name {
  font-size: 15px;
  color: #004aad;
  font-weight: 600;
  text-decoration: underline;
  cursor: pointer;
  display: inline-block;
  margin-bottom: 5px;
  transition: 0.3s;
}

.file-name:hover {
  color: #003080;
}

.file-overview p {
  font-size: 13px;
  color: #666;
  margin: 0;
}

/* ========== MODAL STYLES ========== */
.modal {
  
  display: none; /* Hidden by default */
  position: fixed;
  z-index: 999;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.4); /* Dim background */
  justify-content: center;
  align-items: center;
  font-family: 'Quicksand', sans-serif;
}

.modal-content {
  background-color: #fff;
  padding: 25px 30px;
  border-radius: 15px;
  width: 400px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  position: relative;
  animation: fadeIn 0.3s ease;
  text-align: center;
}

/* Fade-in animation */
@keyframes fadeIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.modal-content h2 {
    color: #004aad;
    margin-bottom: 7px;

}

.modal-content h3 {
  color: #004aad;
  margin-bottom: 10px;
  font-weight: 600;
}

.modal-content p {
  color: #333;
  font-size: 14px;
  margin-bottom: 20px;
}

/* Close button (X) */
.close-btn {
  position: absolute;
  top: 12px;
  right: 18px;
  font-size: 22px;
  color: #555;
  cursor: pointer;
  transition: color 0.3s ease;
}

.close-btn:hover {
  color: #004aad;
}




/* Modal buttons */
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.cancel-btn,
.submit-btn {
  border: none;
  border-radius: 8px;
  padding: 8px 16px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.3s ease;
  font-weight: 600;
}

/* Cancel button */
.cancel-btn {
  background-color: #f0f0f0;
  color: #333;
}

.cancel-btn:hover {
  background-color: #ddd;
}

/* Submit button */
.submit-btn {
  background-color: #004aad;
  color: white;
  font-size: 16px;
}

.submit-btn:hover {
  background-color: #00358a;
}

/* Clean "Choose File" style */
#fileUpload {
  display: block;
  width: 100%;
  margin: 15px 0 25px 0;
  padding: 18px;
  border: 2px dashed #cbd5e1;
  border-radius: 12px;
  background-color: #f9fafb;
  color: #555;
  font-size: 15px;
  cursor: pointer;
  text-align: center;
  transition: all 0.3s ease;
}

/* Hover and focus effects */
#fileUpload:hover,
#fileUpload:focus {
  border-color: #004aad;
  background-color: #eef3ff;
  color: #004aad;
}

/* Hide default file upload button text */
#fileUpload::-webkit-file-upload-button {
  visibility: hidden;
}

/* Custom button text */
#fileUpload::before {
  content: "📂 Choose File";
  display: inline-block;
  background-color: #004aad;
  color: white;
  font-weight: 600;
  padding: 10px 18px;
  border-radius: 8px;
  margin-right: 10px;
  cursor: pointer;
  transition: background 0.3s ease;
}

#fileUpload:hover::before {
  background-color: #00358a;
}

/* Optional — add subtle text after selecting a file */
#fileUpload::file-selector-button {
  display: none;
}

/* Centered single button for attendance modal */
.modal-actions.single-btn {
  justify-content: center;
}


/* File Overview Modal Customization */
.single-btn {
  justify-content: center;
}

.long-btn {
  width: 100%;
  text-align: center;
  padding: 12px 0;
  font-size: 15px;
}

/* File Overview Modal */
#fileOverviewModal .modal-content.file-viewer {
  width: 80%;
  height: 90%;
  max-width: 900px;
  display: flex;
  flex-direction: column;
  align-items: stretch;
}

#fileOverviewModal h2 {
  margin-bottom: 10px;
  font-size: 18px;
  text-align: left;
  color: #004aad;
}

#fileOverviewModal iframe {
  flex: 1;
  width: 100%;
  height: 100%;
  border-radius: 8px;
  border: 1px solid #ccc;
}

/* Optional: adjust close button */
#fileOverviewModal .close-btn {
  position: absolute;
  top: 10px;
  right: 20px;
  font-size: 26px;
  color: #333;
  cursor: pointer;
  transition: 0.3s;
}

#fileOverviewModal .close-btn:hover {
  color: #004aad;
}

.filter-buttons a {
  text-decoration: none;
}

@media (max-width: 900px) {
  .main {
    margin-left: 0;
  }

  .sidebar {
    left: -260px;
  }

  .sidebar.active {
    left: 0;
  }

  .menu-btn {
    display: block;
    color: white;
    font-size: 30px;
    cursor: pointer;
  }

  .topbar {
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
  }

  .filter-buttons {
    justify-content: center;
  }

  .task-card {
    flex-direction: column;
    align-items: flex-start;
  }

  .task-meta {
    margin: 10px 0 0 0;
  }
}

@media (max-width: 600px) {
  .topbar {
    font-size: 18px;
    padding: 15px;
  }

  .filter-btn {
    flex: 1 1 45%;
    font-size: 14px;
    padding: 8px 10px;
    justify-content: center;
  }

  .task-content h4 {
    font-size: 16px;
  }

  .task-content p {
    font-size: 13px;
  }

  .file-name {
    font-size: 13px;
  }

  .modal-content {
    width: 90%;
    padding: 20px;
  }
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
        <a href="/CAPSTONE_LMS_EHS/studentdashboard/courses.php?strand=<?= urlencode($strand) ?>&section=<?= urlencode($section) ?>" class="back-btn">
            <span class="material-icons">arrow_back</span>
        </a>
        Course Announcements (<?= htmlspecialchars($strand) ?> - <?= htmlspecialchars($section) ?>)
    </div>

    <div class="filter-buttons">
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/all.php?class_id=<?= urlencode($class_id) ?>&strand=<?= urlencode($strand) ?>&section=<?= urlencode($section) ?>" class="filter-btn">
    <span class="material-icons">grid_view</span>All
</a>
      <a href="announcement.php" class="filter-btn active"><span class="material-icons">campaign</span>Announcements</a>
      <a href="activities.php" class="filter-btn"><span class="material-icons">event</span>Activities</a>
      <a href="module.php" class="filter-btn"><span class="material-icons">menu_book</span>Modules</a>
      <a href="quizzes.php" class="filter-btn"><span class="material-icons">quiz</span>Quizzes</a>
      <a href="exam.php" class="filter-btn"><span class="material-icons">school</span>Exam</a>
      <a href="attendance.php" class="filter-btn"><span class="material-icons">fact_check</span>Attendance</a>
    </div>

    <div class="task-list">
      <?php if (empty($announcements)): ?>
        <p style="padding:20px;">No announcements yet.</p>
      <?php else: ?>
        <?php foreach ($announcements as $a): ?>
          <div class="task-card">
            <div class="task-icon">
              <span class="material-icons">campaign</span>
            </div>
            <div class="task-content">
              <h4><?= htmlspecialchars($a['title']) ?>
                <span class="task-tag" style="background-color:#e8f0ff;color:#004aad;">Announcements</span>
              </h4>
              <p><?= nl2br(htmlspecialchars($a['desc'])) ?></p>
              <p style="font-size:0.85rem;color:#555;">
                Posted by: <?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?> | <?= date("F d, Y | h:i A", strtotime($a['date_posted'] ?? 'now')) ?>
              </p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
