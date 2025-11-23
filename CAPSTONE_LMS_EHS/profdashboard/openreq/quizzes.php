<?php
include '../../config/db_connect.php';
session_start();

$teacher_id = $_SESSION['teacher_id'] ?? 1;
$strand = $_GET['strand'] ?? ($_SESSION['strand'] ?? '');
$section = $_GET['section'] ?? ($_SESSION['section'] ?? '');
$course_name = $_GET['course'] ?? ($_SESSION['course'] ?? '');

if (!empty($strand)) $_SESSION['strand'] = $strand;
if (!empty($section)) $_SESSION['section'] = $section;
if (!empty($course_name)) $_SESSION['course'] = $course_name;

// Get class_id
$stmt = $conn->prepare("
    SELECT class_id 
    FROM prof_courses 
    WHERE teacher_id = :teacher_id AND strand = :strand AND section = :section 
    LIMIT 1
");
$stmt->execute([
    'teacher_id' => $teacher_id,
    'strand' => $strand,
    'section' => $section
]);
$class_id = $stmt->fetchColumn(); // this will be used in INSERT

$stmt = null;

// Get teacher name
$teacher_name = 'Unknown Teacher';
$stmt = $conn->prepare("SELECT first_name, last_name FROM teachers_account WHERE teacher_id = :teacher_id");
$stmt->execute(['teacher_id' => $teacher_id]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $teacher_name = $row['first_name'] . ' ' . $row['last_name'];
}
$stmt = null;

// Fetch quizzes (now matches Supabase structure)
$stmt = $conn->prepare("
    SELECT * 
    FROM prof_quiz 
    WHERE teacher_id = :teacher_id 
    AND class_id = :class_id 
    AND section = :section 
    ORDER BY quiz_id DESC
");
$stmt->execute([
    'teacher_id' => $teacher_id,
    'class_id' => $class_id,
    'section' => $section
]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = null;


// Handle Forward Quiz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_quiz_id'])) {
    $forward_id = intval($_POST['forward_quiz_id']);
    $target_class = intval($_POST['forward_class_id']);

    // Fetch quiz details
    $stmt = $conn->prepare("SELECT * FROM prof_quiz WHERE quiz_id = :quiz_id");
    $stmt->execute(['quiz_id' => $forward_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($quiz) {
        // Get target class info
        $stmtClass = $conn->prepare("SELECT section, strand FROM prof_courses WHERE class_id = :class_id LIMIT 1");
        $stmtClass->execute(['class_id' => $target_class]);
        $classData = $stmtClass->fetch(PDO::FETCH_ASSOC);

        $stmtInsert = $conn->prepare("
            INSERT INTO prof_quiz 
            (teacher_id, class_id, strand, section, title, date_posted, description, due_date, due_time, total_points)
            VALUES 
            (:teacher_id, :class_id, :strand, :section, :title, :date_posted, :description, :due_date, :due_time, :total_points)
        ");
        $stmtInsert->execute([
            'teacher_id' => $teacher_id,
            'class_id' => $target_class,
            'strand' => $classData['strand'] ?? '',
            'section' => $classData['section'] ?? '',
            'title' => $quiz['title'],
            'date_posted' => date('Y-m-d'),
            'description' => $quiz['description'],
            'due_date' => $quiz['due_date'],
            'due_time' => $quiz['due_time'],
            'total_points' => $quiz['total_points']
        ]);

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}



// Optional: professor profile image
$imageQuery = $conn->prepare("SELECT image_url FROM teacher_profile_images WHERE teacher_id = :teacher_id ORDER BY uploaded_at DESC LIMIT 1");
$imageQuery->execute(['teacher_id' => $teacher_id]);
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
  <link href="/CAPSTONE_LMS_EHS/assets/prof/css/activities.css" rel="stylesheet">
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



    .task-meta {
  position: relative;
}
/* Dropdown Menu */
.dropdown-menu {
  position: absolute;
  top: auto;        /* allow overriding */
  bottom: 35px;     /* show above the icon */
  right: 0;
  background-color: #fff;
  border: 1px solid #ddd;
  border-radius: 8px;
  min-width: 120px;
  display: none;
  flex-direction: column;
  z-index: 2000;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.dropdown-menu a {
  padding: 8px 12px;
  text-decoration: none;
  color: #333;
  font-size: 14px;
  transition: background 0.2s;
}

.dropdown-menu a:hover {
  background-color: #f0f0f0;
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
      <a href="/CAPSTONE_LMS_EHS/profdashboard/profhome.php" ><span class="material-icons">dashboard</span> Dashboard</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/course.php"class="active"><span class="material-icons">menu_book</span> Courses</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/performance.php"><span class="material-icons">bar_chart</span> Performance</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/reminders.php"><span class="material-icons">notifications</span> Reminders</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/account.php"><span class="material-icons">account_circle</span> Account</a>
      <a href="/CAPSTONE_LMS_EHS/auth/login.php"><span class="material-icons">logout</span> Logout</a>
      </div>


  <!-- BOTTOM PROFILE -->
  <div class="sidebar-bottom">
    <div class="bottom-img-container">
       <img src="<?php echo htmlspecialchars($profileImg); ?>" alt="Profile Image" class="bottom-profile-img">>
      <span class="online-dot"></span>
    </div>

    <div>
      <p><?php echo $teacher_name; ?></p>
      <p class="bottom-status">Teacher</p>
    </div>
  </div>

</div>

<div class="main">
  <div class="topbar">
   <a href="/CAPSTONE_LMS_EHS/profdashboard/course.php?strand=<?= urlencode($strand) ?>&section=<?= urlencode($section) ?>" class="back-btn">
            <span class="material-icons">arrow_back</span>
        </a>
        Course Announcements (<?= htmlspecialchars($strand) ?> - <?= htmlspecialchars($section) ?>)
  </div>

  <div class="filter-buttons">
    <a href="/CAPSTONE_LMS_EHS/profdashboard/all.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn "><span class="material-icons">grid_view</span>All</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/announcement.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">campaign</span>Announcements</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/activities.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">event</span>Activities</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/module.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">menu_book</span>Module</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/quizzes.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn active"><span class="material-icons">quiz</span>Quizzes</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/exam.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">school</span>Exam</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/attendance.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">fact_check</span>Attendance</a>  
  </div>

  <div class="task-list">

    <!-- Create New Quiz -->
    <form method="POST" action="/CAPSTONE_LMS_EHS/api/prof/prof_quiz/create_quiz_redirect.php" style="text-decoration: none;">
      <input type="hidden" name="strand" value="<?= htmlspecialchars($strand) ?>">
      <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
      <input type="hidden" name="course" value="<?= htmlspecialchars($course_name) ?>">
      <input type="hidden" name="class_id" value="<?= htmlspecialchars($class_id) ?>"> <!-- ✅ added -->
      <input type="hidden" name="teacher_id" value="<?= htmlspecialchars($teacher_id) ?>"> <!-- optional but useful -->
      <button type="submit" class="task-card new-task" style="border:none; background:none; cursor:pointer; width:100%;">
        <div class="task-icon">
          <span class="material-icons">add_circle</span>
        </div>
        <div class="task-content">
          <h4>Create New Quiz</h4>
          <p>Click to add a new quiz for this course.</p>
        </div>
      </button>
    </form>

<!-- ===== Forward Quiz Modal ===== -->
<div id="forwardQuizModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close-btn" id="closeForwardQuiz">&times;</span>
    <h3>Forward Quiz</h3>
    <form method="POST" id="forwardQuizForm">
      <input type="hidden" name="forward_quiz_id" id="forward_quiz_id">
      <label>Target Class ID:</label>
      <input type="number" name="forward_class_id" id="forward_class_id" placeholder="Enter Class ID" required>
      <button type="submit" class="submit-btn">Forward</button>
    </form>
  </div>
</div>


    <!-- Display Quizzes -->
    <?php foreach($quizzes as $quiz): ?>
      <div class="task-card" onclick="window.location='quizzes_create.php?quiz_id=<?= $quiz['quiz_id'] ?>&strand=<?= urlencode($strand) ?>&section=<?= urlencode($section) ?>&course=<?= urlencode($course_name) ?>'">
        <div class="task-icon">
          <span class="material-icons">quiz</span>
        </div>
        <div class="task-content">
          <h4><?= htmlspecialchars($quiz['title']) ?>
            <span class="task-tag" style="background-color:#fde0dc;color:#c2185b;">Quiz</span>
          </h4>
          <p><?= htmlspecialchars($quiz['description'] ?? 'No description provided.') ?></p>
          <p>Date Posted: <?= date('F d, Y', strtotime($quiz['date_posted'])) ?> | 
             Due: <?= date('F d, Y', strtotime($quiz['due_date'])) ?> - <?= date('h:i A', strtotime($quiz['due_time'])) ?></p>
        </div>

        <!-- Menu -->
        <div class="task-meta">
          <span class="material-icons menu-icon">more_vert</span>
          <div class="dropdown-menu">
            <a href="quizzes_create.php?quiz_id=<?= $quiz['quiz_id'] ?>&strand=<?= urlencode($strand) ?>&section=<?= urlencode($section) ?>&course=<?= urlencode($course_name) ?>">Edit</a>
            <a href="#" onclick="openForwardModal(<?= $quiz['quiz_id'] ?>)">Forward</a>
            <a href="/CAPSTONE_LMS_EHS/api/prof/prof_quiz/delete_quiz.php?quiz_id=<?= $quiz['quiz_id'] ?>" onclick="return confirm('Are you sure you want to delete this quiz and all its questions?');">Delete</a>
          </div>
        </div>

      </div>
    <?php endforeach; ?>

  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
  const menuIcons = document.querySelectorAll('.menu-icon');

  menuIcons.forEach(icon => {
    icon.addEventListener('click', e => {
      e.stopPropagation(); // prevent document click from hiding immediately
      const dropdown = icon.nextElementSibling;

      // hide all other dropdowns
      document.querySelectorAll('.dropdown-menu').forEach(dm => {
        if (dm !== dropdown) dm.style.display = 'none';
      });

      // toggle current dropdown
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });
  });

  // hide dropdown when clicking outside
  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu').forEach(dm => dm.style.display = 'none');
  });
});


// Forward Quiz Modal
const forwardModal = document.getElementById('forwardQuizModal');
const forwardQuizIdInput = document.getElementById('forward_quiz_id');

function openForwardModal(quizId) {
  forwardQuizIdInput.value = quizId;
  forwardModal.style.display = 'flex';
}

document.getElementById('closeForwardQuiz').onclick = () => {
  forwardModal.style.display = 'none';
};

// Close modal when clicking outside
window.onclick = (event) => {
  if (event.target == forwardModal) {
    forwardModal.style.display = 'none';
  }
};

</script>
</body>
</html>
