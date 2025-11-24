<?php
// courses.php
include '../config/db_connect.php'; // your Supabase PDO connection

session_start();

$teacher_id = $_SESSION['teacher_id'] ?? null;
if (!$teacher_id) {
    // optional fallback for local testing; remove or adjust in production
    $teacher_id = 1;
}
// --- Fetch professor name ---
$prof_name = '';
try {
    $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM teachers_account WHERE teacher_id = :teacher_id");
    $stmt->execute(['teacher_id' => $teacher_id]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $prof_name = $row['name'];
    }
} catch (Exception $e) {
    // ignore, keep empty name
}

// --- Helper: generate a unique class code ---
function generateClassCode($conn) {
    $tries = 0;
    do {
        $alpha1 = strtoupper(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 2));
        $alpha2 = strtoupper(substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 2));
        $num1 = rand(10, 99);
        $num2 = rand(10, 99);
        $code = "{$alpha1}{$num1}-{$num2}{$alpha2}";

        // check uniqueness
        $stmt = $conn->prepare("SELECT 1 FROM prof_courses WHERE class_code = :code LIMIT 1");
        $stmt->execute(['code' => $code]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        $tries++;
        if ($tries > 10) break;
    } while ($exists);
    return $code;
}

// --- Handle AJAX create-class request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_class') {
    // Collect and sanitize POST values
    $course_id    = trim($_POST['courseId'] ?? '');
    $course_name  = trim($_POST['courseName'] ?? '');
    $grade        = $_POST['grade'] !== '' ? (int)$_POST['grade'] : null;
    $strand       = trim($_POST['strand'] ?? '');
    $section      = trim($_POST['section'] ?? '');
    $day          = trim($_POST['days'] ?? ''); // DB column is `day`
    $time_start   = trim($_POST['timeStart'] ?? '');
    $time_end     = trim($_POST['timeFinish'] ?? '');

    // basic validation
    $errors = [];
    if ($course_id === '') $errors[] = 'Course ID is required.';
    if ($course_name === '') $errors[] = 'Course Name is required.';
    if ($strand === '') $errors[] = 'Strand is required.';
    if ($section === '') $errors[] = 'Section is required.';
    if ($day === '') $errors[] = 'Day is required.';
    if ($time_start === '') $errors[] = 'Start time is required.';
    if ($time_end === '') $errors[] = 'End time is required.';

    header('Content-Type: application/json');
    if (!empty($errors)) {
        echo json_encode(['status' => 'error', 'errors' => $errors]);
        exit;
    }

    try {
        // generate unique class code
        $class_code = generateClassCode($conn);

        // Insert into prof_courses
$insert = $conn->prepare("
INSERT INTO prof_courses
(course_id, course_name, grade, strand, section, day, time_start, time_end, teacher_id, class_code, prof_name)
VALUES (:course_id, :course_name, :grade, :strand, :section, :day, :time_start, :time_end, :teacher_id, :class_code, :prof_name)
      
");
    
$insert->execute([
    'course_id'   => $course_id,
    'course_name' => $course_name,
    'grade'       => $grade,
    'strand'      => $strand,
    'section'     => $section,
    'day'         => $day,
    'time_start'  => $time_start,
    'time_end'    => $time_end,
    'teacher_id'  => $teacher_id,
    'class_code'  => $class_code,
    'prof_name'   => $prof_name   // ← NEW
]);


        echo json_encode(['status' => 'success', 'message' => 'Class created.', 'class_code' => $class_code]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'errors' => [$e->getMessage()]]);
        exit;
    }
}



// --- Fetch profile image (optional) ---
$profileImg = 'default_prof.jpg'; // default image if none
try {
    $imageQuery = $conn->prepare("SELECT image_url FROM teacher_profile_images WHERE teacher_id = :teacher_id ORDER BY uploaded_at DESC LIMIT 1");
    $imageQuery->execute(['teacher_id' => $teacher_id]);
    $image = $imageQuery->fetch(PDO::FETCH_ASSOC);
    if ($image && !empty($image['image_url'])) {
        $profileImg = $image['image_url'];
    }
} catch (Exception $e) {
    // ignore, use default
}

// --- Fetch classes for this professor ---
$courses = [];
try {
    $stmt = $conn->prepare("SELECT * FROM prof_courses WHERE teacher_id = :teacher_id ORDER BY course_name, section");
    $stmt->execute(['teacher_id' => $teacher_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore; $courses stays empty
}


// --- Handle AJAX update-class request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_class') {

    header('Content-Type: application/json');

    // Force integer validation
// Force integers from POST
$class_id = filter_input(INPUT_POST, 'classId', FILTER_VALIDATE_INT);
$grade    = filter_input(INPUT_POST, 'grade', FILTER_VALIDATE_INT);

$errors = [];
if (!$class_id) $errors[] = 'Invalid class ID.';
if (!$grade || !in_array($grade, [11,12])) $errors[] = 'Grade must be 11 or 12.';

if (!empty($errors)) {
    echo json_encode(['status'=>'error','errors'=>$errors]);
    exit;
}


    $course_id   = trim($_POST['courseId'] ?? '');
    $course_name = trim($_POST['courseName'] ?? '');
    $strand      = trim($_POST['strand'] ?? '');
    $section     = trim($_POST['section'] ?? '');
    $day         = trim($_POST['days'] ?? '');
    $time_start  = trim($_POST['timeStart'] ?? '');
    $time_end    = trim($_POST['timeFinish'] ?? '');

    // --- Basic validation ---
    $errors = [];
    if (!$class_id) $errors[] = 'Invalid class ID.';
    if (!$grade || !in_array($grade, [11,12])) $errors[] = 'Grade must be 11 or 12.';
    if ($course_id === '') $errors[] = 'Course ID is required.';
    if ($course_name === '') $errors[] = 'Course Name is required.';
    if ($strand === '') $errors[] = 'Strand is required.';
    if ($section === '') $errors[] = 'Section is required.';
    if ($day === '') $errors[] = 'Day is required.';
    if ($time_start === '') $errors[] = 'Start time is required.';
    if ($time_end === '') $errors[] = 'End time is required.';

    if (!empty($errors)) {
        echo json_encode(['status'=>'error','errors'=>$errors]);
        exit;
    }

    // --- Proceed with SQL UPDATE ---
    try {
        $update = $conn->prepare("
            UPDATE prof_courses SET
                course_id = :course_id,
                course_name = :course_name,
                grade = :grade,
                strand = :strand,
                section = :section,
                day = :day,
                time_start = :time_start,
                time_end = :time_end
            WHERE class_id = :class_id AND teacher_id = :teacher_id
        ");

        $update->execute([
            'course_id'   => $course_id,
            'course_name' => $course_name,
            'grade'       => $grade,
            'strand'      => $strand,
            'section'     => $section,
            'day'         => $day,
            'time_start'  => $time_start,
            'time_end'    => $time_end,
            'class_id'    => $class_id,
            'teacher_id'  => $teacher_id
        ]);

        echo json_encode(['status'=>'success','message'=>'Class updated']);
        exit;
    } catch(Exception $e) {
        echo json_encode(['status'=>'error','errors'=>[$e->getMessage()]]);
        exit;
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
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <link href="/CAPSTONE_LMS_EHS/assets/prof/css/course.css" rel="stylesheet" />

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




    /* Ensure dropdown is on top of everything */
    .dropdown-menu {
      position: absolute !important;
      top: 30px; /* adjust as needed */
      right: 10px;
      z-index: 9999 !important;
      background: white;
      border: 1px solid #ccc;
      border-radius: 6px;
      min-width: 150px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }


    /* Modal overlay */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(0,0,0,0.5);
      z-index: 10000;
    }

    /* Modal content */
    .modal-content {
      background: white;
      border-radius: 8px;
      padding: 20px;
      position: relative;
      z-index: 10001;
    }

    /* Ensure task cards allow dropdown to overflow and are properly stacked */
    .task-card {
      position: relative;
      overflow: visible; /* already there */
      z-index: 1; /* set a default z-index */
    }

    /* Ensure dropdown is on top of everything else */
    .task-card .dropdown-menu {
      position: absolute;
      top: 30px;
      right: 10px;
      z-index: 9999; /* very high to always appear above cards */
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
      <a href="/CAPSTONE_LMS_EHS/profdashboard/profhome.php"><span class="material-icons">dashboard</span> Dashboard</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/course.php" class="active"><span class="material-icons">menu_book</span> Courses</a>
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
      <p><?php echo $prof_name; ?></p>
      <p class="bottom-status">Teacher</p>
    </div>
  </div>
  </div>

  <div class="main">
    <div class="topbar">
      <span>Account Settings</span>
      <button class="menu-btn" id="menuBtn">&#9776;</button>
    </div>

    <!-- Task Cards -->
    <div class="task-list">

      <!-- New Task Card for Creating Announcement (moved to top) -->
      <div class="task-card new-task" id="newAnnouncementBtn" style="cursor:pointer;">
        <div class="task-icon">
          <span class="material-icons">add_circle</span>
        </div>
        <div class="task-content">
          <h4>Create New Class</h4>
          <p>Click the plus icon to add a new class.</p>
        </div>
      </div>

      <!-- === CREATE CLASS MODAL === -->
      <div id="announcementModal" class="modal" style="display:none;">
        <div class="modal-content">
          <span class="close-btn">&times;</span>
          <h3>Create New Class</h3>
          <form id="announcementForm">
            <div class="form-row">
              <div>
                <label>Course ID:</label>
                <input type="text" id="courseId" name="courseId" placeholder="Enter Course ID" required>
              </div>
              <div>
                <label>Course Name:</label>
                <input type="text" id="courseName" name="courseName" placeholder="Enter Course Name" required>
              </div>
            </div>

            <div class="form-row">
              <div>
                <label>Grade:</label>
                <select id="grade" name="grade" class="input-style" required>
                  <option value="" disabled selected>Select Grade</option>
                  <option value="11">11</option>
                  <option value="12">12</option>
                </select>
              </div>
              <div>
                <label>Strand:</label>
                <input type="text" id="strand" name="strand" placeholder="Enter Strand" required>
              </div>
            </div>

            <div class="form-row">
              <div>
                <label>Section:</label>
                <input type="text" id="section" name="section" placeholder="Enter Section" required>
              </div>
              <div>
                <label>Days:</label>
                <select id="days" name="days" class="input-style" required>
                  <option value="" disabled selected>Select a day</option>
                  <option value="Monday">Monday</option>
                  <option value="Tuesday">Tuesday</option>
                  <option value="Wednesday">Wednesday</option>
                  <option value="Thursday">Thursday</option>
                  <option value="Friday">Friday</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div>
                <label>Time Start:</label>
                <input type="time" id="timeStart" name="timeStart" required>
              </div>
              <div>
                <label>Time Finish:</label>
                <input type="time" id="timeFinish" name="timeFinish" required>
              </div>
            </div>

            <button type="submit" class="submit-btn">Create Class</button>
          </form>
        </div>
      </div>

      <!-- === CLASS CODE MODAL === -->
      <div id="classCodeModal" class="modal">
        <div class="modal-content" style="max-width: 350px; text-align:center;">
          <span class="close-classcode-btn" style="float:right; cursor:pointer; font-size:22px;">&times;</span>
          <h3>Class Code</h3>

          <p id="displayClassCode" 
            style="font-size:22px; margin-top:10px; font-weight:bold; letter-spacing:2px;">
          </p>

          <button id="copyClassCodeBtn"
            style="margin-top:15px; padding:8px 18px; border:none; background:#4a90e2; color:white; cursor:pointer; border-radius:6px;">
            Copy Code
          </button>
        </div>
      </div>


<!-- === EDIT CLASS MODAL === -->
<div id="editModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close-edit-btn">&times;</span>
    <h3>Edit Class</h3>
    <form id="editForm">
      <input type="hidden" id="editClassId" name="classId">
      <div class="form-row">
        <div>
          <label>Course ID:</label>
          <input type="text" id="editCourseId" name="courseId" required>
        </div>
        <div>
          <label>Course Name:</label>
          <input type="text" id="editCourseName" name="courseName" required>
        </div>
      </div>

      <div class="form-row">
        <div>
          <label>Grade:</label>
          <select id="editGrade" name="grade" class="input-style" required>
            <option value="" disabled>Select Grade</option>
            <option value="11">11</option>
            <option value="12">12</option>
          </select>
        </div>
        <div>
          <label>Strand:</label>
          <input type="text" id="editStrand" name="strand" required>
        </div>
      </div>

      <div class="form-row">
        <div>
          <label>Section:</label>
          <input type="text" id="editSection" name="section" required>
        </div>
        <div>
          <label>Days:</label>
          <select id="editDays" name="days" class="input-style" required>
            <option value="" disabled>Select a day</option>
            <option value="Monday">Monday</option>
            <option value="Tuesday">Tuesday</option>
            <option value="Wednesday">Wednesday</option>
            <option value="Thursday">Thursday</option>
            <option value="Friday">Friday</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div>
          <label>Time Start:</label>
          <input type="time" id="editTimeStart" name="timeStart" required>
        </div>
        <div>
          <label>Time Finish:</label>
          <input type="time" id="editTimeFinish" name="timeFinish" required>
        </div>
      </div>

      <button type="submit" class="submit-btn">Update Class</button>
    </form>
  </div>
</div>


      <!-- Dynamic class cards -->
      <?php if (!empty($courses)): ?>
        <?php foreach ($courses as $c): ?>
          <?php
            // Normalize fields and fallbacks
            $c_course_name = $c['course_name'] ?? 'Unnamed Course';
            $c_course_id   = $c['course_id'] ?? '';
            $c_grade       = isset($c['grade']) && $c['grade'] !== null ? $c['grade'] : '';
            $c_strand      = $c['strand'] ?? '';
            $c_section     = $c['section'] ?? '';
            // your DB column might be `day` or `days`
            $c_day         = $c['day'] ?? ($c['days'] ?? '');
            $c_time_start  = isset($c['time_start']) ? date("g:i A", strtotime($c['time_start'])) : '';
            $c_time_end    = isset($c['time_end']) ? date("g:i A", strtotime($c['time_end'])) : (isset($c['time_finish']) ? date("g:i A", strtotime($c['time_finish'])) : '');
            $c_class_code  = $c['class_code'] ?? '';
            $card_href     = "all.php?class_id=" . urlencode($c['class_id'] ?? $c_course_id);
          ?>
            <div class="task-card"
                data-href="<?php echo $card_href; ?>"
                data-class-id="<?= htmlspecialchars($c['class_id'] ?? '') ?>"
                data-course-id="<?= htmlspecialchars($c['course_id'] ?? '') ?>"
                data-course-name="<?= htmlspecialchars($c['course_name'] ?? '') ?>"
                data-grade="<?= htmlspecialchars($c_grade) ?>"
                data-strand="<?= htmlspecialchars($c_strand) ?>"
                data-section="<?= htmlspecialchars($c_section) ?>"
                data-day="<?= htmlspecialchars($c_day) ?>"
                data-timestart="<?= htmlspecialchars($c['time_start'] ?? '') ?>"
                data-timeend="<?= htmlspecialchars($c['time_end'] ?? '') ?>"
                style="cursor:pointer; position:relative;"
            >

            <div class="task-icon">
              <span class="material-icons">school</span>
            </div>
            <div class="task-content">
              <h4><?php echo htmlspecialchars($c_course_name); ?>
                <span class="task-tag" style="background-color:#e8f0ff;color:green;">Class</span>
              </h4>
              <p><?php echo htmlspecialchars($c_course_id); ?></p>
              <p><?php echo htmlspecialchars(($c_grade !== '') ? $c_grade . " - " . ($c_strand ?: '') : ($c_strand ?: '')); ?> | Section <?php echo htmlspecialchars($c_section); ?></p>
              <p><?php echo htmlspecialchars($c_day); ?>, <?php echo htmlspecialchars($c_time_start); ?> - <?php echo htmlspecialchars($c_time_end); ?></p>
            </div>

            <!-- 3-dot menu -->
            <div class="task-meta">
              <span class="material-icons menu-icon">more_vert</span>
              <div class="dropdown-menu" style="display:none;">
                <a href="edit_class.php?class_id=<?php echo urlencode($c['class_id'] ?? $c_course_id); ?>">Edit</a>
                <a href="performance.php?class_id=<?php echo urlencode($c['class_id'] ?? $c_course_id); ?>">Class Grades</a>
                <a href="#" class="show-class-code" data-class-code="<?= htmlspecialchars($c_class_code) ?>">Show Class Code</a>

                <a href="/CAPSTONE_LMS_EHS/api/prof/prof_course/delete_course.php?class_id=<?php echo urlencode($c['class_id'] ?? $c_course_id); ?>" onclick="return confirm('Delete this class?')">Delete</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="task-card">
          <div class="task-content">
            <h4>No classes yet</h4>
            <p>You haven't created any classes. Click "Create New Class" to add one.</p>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>

<script>
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.querySelector('.sidebar');

menuBtn.addEventListener('click', () => {
  sidebar.classList.toggle('active');
});

// === CREATE CLASS MODAL LOGIC ===
document.addEventListener("DOMContentLoaded", function () {
  const newAnnouncementBtn = document.getElementById("newAnnouncementBtn");
  const announcementModal = document.getElementById("announcementModal");
  const closeBtn = announcementModal ? announcementModal.querySelector(".close-btn") : null;
  const announcementForm = document.getElementById("announcementForm");

  if (newAnnouncementBtn && announcementModal && closeBtn && announcementForm) {
    newAnnouncementBtn.addEventListener("click", () => {
      announcementModal.style.display = "flex";
    });

    closeBtn.addEventListener("click", () => {
      announcementModal.style.display = "none";
    });

    window.addEventListener("click", (e) => {
      if (e.target === announcementModal) announcementModal.style.display = "none";
    });

    announcementForm.addEventListener("submit", (e) => {
      e.preventDefault();
      const formData = new FormData(announcementForm);
      formData.append('action', 'create_class');

      fetch(window.location.href, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          alert('✅ Class created. Code: ' + data.class_code);
          window.location.reload();
        } else {
          const errs = data.errors || ['Unknown error'];
          alert('Error: ' + errs.join('\\n'));
        }
      })
      .catch(err => {
        console.error(err);
        alert('Error creating class. See console for details.');
      });
    });
  }
});


// === 3-DOT MENU DROPDOWN LOGIC WITH OUTSIDE CLICK CLOSE ===
document.querySelectorAll(".menu-icon").forEach((icon) => {
  const dropdown = icon.nextElementSibling;
  const card = icon.closest(".task-card");

  icon.addEventListener("click", (e) => {
    e.stopPropagation();

    // Close other dropdowns
    document.querySelectorAll(".dropdown-menu").forEach((menu) => {
      if (menu !== dropdown) menu.style.display = "none";
    });

    // Reset z-index of all cards
    document.querySelectorAll(".task-card").forEach(c => c.style.zIndex = 1);

    // Toggle current dropdown
    if (dropdown.style.display === "block") {
      dropdown.style.display = "none";
      card.style.zIndex = 1;
    } else {
      dropdown.style.display = "block";
      card.style.zIndex = 100; // bring card to front
    }
  });
});

// Close dropdowns if clicking outside
document.addEventListener("click", (e) => {
  document.querySelectorAll(".dropdown-menu").forEach((dropdown) => {
    // Only hide if the click is outside of dropdown and menu icon
    if (!dropdown.contains(e.target) && !dropdown.previousElementSibling.contains(e.target)) {
      dropdown.style.display = "none";
      const parentCard = dropdown.closest(".task-card");
      if (parentCard) parentCard.style.zIndex = 1;
    }
  });
});




// === EDIT CLASS MODAL (robust version) ===
(function() {
  // Helper: convert "8:00 AM" or "12:30 PM" into "08:00" / "12:30" (24h HH:MM)
  function toTimeInputValue(displayTime) {
    if (!displayTime) return '';
    // Normalize the string and remove extra whitespace
    let t = displayTime.trim();
    // If it's already 24h like "08:00" return it
    if (/^\d{1,2}:\d{2}$/.test(t)) {
      // ensure two-digit hour
      const parts = t.split(':');
      return parts[0].padStart(2, '0') + ':' + parts[1];
    }
    // Match patterns like "8:00 AM" or "12:30 PM"
    const m = t.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!m) return '';
    let hour = parseInt(m[1], 10);
    const minute = m[2];
    const ampm = m[3].toUpperCase();
    if (ampm === 'AM') {
      if (hour === 12) hour = 0;
    } else { // PM
      if (hour !== 12) hour += 12;
    }
    return String(hour).padStart(2, '0') + ':' + minute;
  }

  // Get all edit links inside dropdown that point to edit_class.php
  document.querySelectorAll(".dropdown-menu a[href^='edit_class.php']").forEach(btn => {
    btn.addEventListener("click", function(e) {
      e.preventDefault();
      e.stopPropagation();

      // Prefer class id from the card data attribute
      const card = this.closest(".task-card");
      const classId = card?.getAttribute('data-class-id') || new URL(this.href, window.location.href).searchParams.get("class_id");
      if (!classId) {
        console.error("No class id found for edit.");
        alert("Error: class id missing. See console.");
        return;
      }

      // populate hidden id
      document.getElementById("editClassId").value = classId;

      // Get card text nodes (the <p> elements)
      const p = card.querySelectorAll('p');
      // p[0] -> first p is course name repeated, but safer to use the h4 for course name
      const h4 = card.querySelector('h4');
      const displayedCourseName = h4 ? h4.childNodes[0].textContent.trim() : (p[0]?.textContent || '');
      document.getElementById("editCourseName").value = displayedCourseName;

      // Course ID is not reliably inside p[0]; if you have a DB value put it as data on card
      // Attempt to parse course id from first <p> (if you store "COURSEID Rest")
const realCourseId = card.getAttribute("data-course-id");
document.getElementById("editCourseId").value = realCourseId || '';


      // Grade/Strand/Section: p[1] expected "11 - STRAND | Section X"
      const line1 = p[1]?.textContent || '';
      const [gradeStrandPart, sectionPart] = line1.split('|').map(s => s ? s.trim() : '');
      const gradeStrand = (gradeStrandPart || '').split(' - ').map(s => s.trim());
      document.getElementById("editGrade").value = gradeStrand[0] || '';
      document.getElementById("editStrand").value = gradeStrand[1] || '';
      // sectionPart might be "Section 1" -> extract number/text after "Section "
      const sectionVal = (sectionPart && sectionPart.replace(/^Section\s*/i, '').trim()) || '';
      document.getElementById("editSection").value = sectionVal;

      // Day and times: p[2] expected "Monday, 8:00 AM - 9:00 AM"
      const line2 = p[2]?.textContent || '';
      const parts = line2.split(',');
      const day = parts[0]?.trim() || '';
      document.getElementById("editDays").value = day;

      const timeRange = (parts[1] || '').trim(); // "8:00 AM - 9:00 AM"
      const times = timeRange.split('-').map(s => s.trim());
      // Convert displayed times to HH:MM for <input type=time>
      document.getElementById("editTimeStart").value = toTimeInputValue(times[0] || '');
      document.getElementById("editTimeFinish").value = toTimeInputValue(times[1] || '');

      // show modal
      document.getElementById("editModal").style.display = "flex";
    });
  });

  // close button
  document.querySelector(".close-edit-btn").addEventListener("click", () => {
    document.getElementById("editModal").style.display = "none";
  });

  // close on background click
  window.addEventListener("click", (e) => {
    const modal = document.getElementById("editModal");
    if (e.target === modal) modal.style.display = "none";
  });

  // === UPDATE CLASS AJAX with stronger error handling ===
  document.getElementById("editForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'update_class');

    fetch(window.location.href, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
    .then(res => res.json().catch(() => ({ status: 'error', errors: ['Invalid JSON response from server. Check server logs.'] })))
    .then(data => {
      console.log("Update response:", data);
      if (data.status === 'success') {
        alert('✅ Class updated!');
        window.location.reload();
      } else {
        const errs = data.errors || [data.message || 'Unknown error'];
        alert('Error updating class:\\n' + errs.join('\\n'));
      }
    })
    .catch(err => {
      console.error("Fetch error:", err);
      alert('Network or fetch error while updating class. See console for details.');
    });
  });
})();

// === SHOW CLASS CODE MODAL ===
document.querySelectorAll(".show-class-code").forEach(btn => {
  btn.addEventListener("click", function (e) {
    e.preventDefault();
    e.stopPropagation();

    const classCode = this.getAttribute("data-class-code");
    const modal = document.getElementById("classCodeModal");
    document.getElementById("displayClassCode").textContent = classCode;
    modal.style.display = "flex";
  });
});

document.querySelector(".close-classcode-btn").addEventListener("click", () => {
  document.getElementById("classCodeModal").style.display = "none";
});

window.addEventListener("click", (e) => {
  const modal = document.getElementById("classCodeModal");
  if (e.target === modal) modal.style.display = "none";
});

// Copy class code
document.getElementById("copyClassCodeBtn").addEventListener("click", () => {
  const code = document.getElementById("displayClassCode").textContent;
  navigator.clipboard.writeText(code).then(() => {
    alert("Class Code Copied!");
  });
});

// === CARD CLICK NAVIGATION ===
document.querySelectorAll('.task-card').forEach(card => {
  card.addEventListener('click', function(e) {
    if (e.target.closest('.dropdown-menu') || e.target.closest('.menu-icon') || e.target.tagName === 'A') return;
    const link = card.getAttribute('data-href');
    if (link) window.location.href = link;
  });
});
</script>

</body>

</html>
