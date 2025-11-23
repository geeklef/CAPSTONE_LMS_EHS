<?php
// student/course.php
session_start();
include '../config/db_connect.php'; // adjust path if necessary

// Require logged-in student
if (!isset($_SESSION['student_id'])) {
    header('Location: /CAPSTONE_LMS_EHS/auth/login.php');
    exit;
}
$student_id = (int) $_SESSION['student_id'];

// --- AJAX: Join course by class code ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join_course') {
    header('Content-Type: application/json; charset=utf-8');
    $class_code = trim($_POST['class_code'] ?? '');

    if ($class_code === '') {
        echo json_encode(['status' => 'error', 'errors' => ['Class code is required.']]);
        exit;
    }

    try {
        // Find class by code
        $find = $conn->prepare("SELECT class_id, course_id FROM prof_courses WHERE class_code = :code LIMIT 1");
        $find->execute(['code' => $class_code]);
        $pc = $find->fetch(PDO::FETCH_ASSOC);

        if (!$pc) {
            echo json_encode(['status' => 'error', 'errors' => ['Invalid class code.']]);
            exit;
        }

        $course_id = isset($pc['course_id']) ? (int)$pc['course_id'] : null;
        $class_id  = isset($pc['class_id']) ? (int)$pc['class_id'] : null;

        if (!$course_id) {
            echo json_encode(['status' => 'error', 'errors' => ['Course not found for that class code.']]);
            exit;
        }

        // Check duplicate enrollment
        $chk = $conn->prepare("SELECT 1 FROM student_enrollments WHERE student_id = :student_id AND course_id = :course_id LIMIT 1");
        $chk->execute(['student_id' => $student_id, 'course_id' => $course_id]);
        if ($chk->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['status' => 'error', 'errors' => ['You are already enrolled in this course.']]);
            exit;
        }

        // Insert enrollment
        $ins = $conn->prepare("INSERT INTO student_enrollments (student_id, course_id) VALUES (:student_id, :course_id)");
        $ins->execute(['student_id' => $student_id, 'course_id' => $course_id]);

        echo json_encode(['status' => 'success', 'message' => 'Enrolled successfully.']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'errors' => [$e->getMessage()]]);
        exit;
    }
}

// --- AJAX: Unenroll action ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'unenroll') {
    header('Content-Type: application/json; charset=utf-8');
    $course_id = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
    if (!$course_id) {
        echo json_encode(['status' => 'error', 'errors' => ['Invalid course id.']]);
        exit;
    }

    try {
        $del = $conn->prepare("DELETE FROM student_enrollments WHERE student_id = :student_id AND course_id = :course_id");
        $del->execute(['student_id' => $student_id, 'course_id' => $course_id]);
        echo json_encode(['status' => 'success', 'message' => 'Unenrolled successfully.']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'errors' => [$e->getMessage()]]);
        exit;
    }
}

// --- Normal page rendering below ---
// fetch student name
$student_name = '';
try {
    $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) AS name FROM students_account WHERE stud_id = :student_id LIMIT 1");
    $stmt->execute(['student_id' => $student_id]);
    if ($r = $stmt->fetch(PDO::FETCH_ASSOC)) $student_name = $r['name'];
} catch (Exception $e) {
    // ignore
}

// profile image
$profileImg = 'default_prof.jpg';
try {
    $imageQuery = $conn->prepare("SELECT image_url FROM student_profile_images WHERE student_id = :student_id ORDER BY uploaded_at DESC LIMIT 1");
    $imageQuery->execute(['student_id' => $student_id]);
    $image = $imageQuery->fetch(PDO::FETCH_ASSOC);
    if ($image && !empty($image['image_url'])) $profileImg = $image['image_url'];
} catch (Exception $e) {
    // ignore
}

// fetch enrolled classes (join prof_courses)
$courses = [];
try {
    $sql = "SELECT pc.course_name, pc.course_id, pc.class_id, pc.section, pc.strand, pc.day, pc.time_start, pc.time_end, pc.class_code, pc.prof_name, pc.grade
            FROM student_enrollments se
            JOIN prof_courses pc ON se.course_id = pc.course_id
            WHERE se.student_id = :student_id
            ORDER BY pc.course_name, pc.section";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['student_id' => $student_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Courses — Student</title>
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

/* Modal Overlay */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: none;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.55);
  backdrop-filter: blur(3px);
  z-index: 10000;
}

/* Modal Box */
.modal-content {
  width: 420px;
  background: #ffffff;
  border-radius: 14px;
  padding: 25px 28px;
  animation: fadeIn 0.25s ease-out;
  box-shadow: 0 6px 20px rgba(0,0,0,0.15);
  position: relative;
  font-family: "Roboto", sans-serif;
}

/* Close Button */
.modal-content .close-btn {
  position: absolute;
  top: 12px;
  right: 14px;
  cursor: pointer;
  font-size: 22px;
  color: #333;
  transition: 0.2s;
}
.modal-content .close-btn:hover {
  color: #ff3b30;
}

/* Modal Heading */
.modal-content h3 {
  margin: 0;
  font-size: 22px;
  font-weight: 600;
  color: #004aad;
  text-align: center;
}

/* Description Text */
.modal-content p {
  margin-top: 6px;
  color: #555;
  text-align: center;
  font-size: 14px;
}

/* Input Field */
#classCode {
  width: 100%;
  padding: 12px 14px;
  margin-top: 10px;
  border: 1px solid #d6d6d6;
  border-radius: 8px;
  font-size: 15px;
  outline: none;
  transition: 0.2s;
}
#classCode:focus {
  border-color: #004aad;
  box-shadow: 0 0 0 2px rgba(0, 74, 173, 0.15);
}

/* Button Container */
.modal-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

/* Buttons */
.submit-btn {
  padding: 10px 18px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  transition: 0.2s;
}

#submitJoin {
  background: #004aad;
  color: white;
}
#submitJoin:hover {
  background: #003680;
}

#cancelJoin {
  background: #888;
  color: #fff;
}
#cancelJoin:hover {
  background: #666;
}

/* Error Message */
#joinErrors {
  margin-top: 10px;
  color: #d32f2f;
  font-size: 14px;
  padding-left: 4px;
}

/* Animation */
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.95); }
  to   { opacity: 1; transform: scale(1); }
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
      <a href="/CAPSTONE_LMS_EHS/studentdashboard/profhome.php"><span class="material-icons">dashboard</span> Dashboard</a>
      <a href="/CAPSTONE_LMS_EHS/studentdashboard/course.php" class="active"><span class="material-icons">menu_book</span> Courses</a>
      <a href="/CAPSTONE_LMS_EHS/studentdashboard/performance.php"><span class="material-icons">bar_chart</span> Performance</a>
      <a href="/CAPSTONE_LMS_EHS/studentdashboard/reminders.php"><span class="material-icons">notifications</span> Reminders</a>
      <a href="/CAPSTONE_LMS_EHS/studentdashboard/account.php"><span class="material-icons">account_circle</span> Account</a>
      <a href="/CAPSTONE_LMS_EHS/auth/login.php"><span class="material-icons">logout</span> Logout</a>
    </div>

    <div class="sidebar-bottom">
      <div class="bottom-img-container">
        <img src="<?php echo htmlspecialchars($profileImg); ?>" alt="Profile Image" class="bottom-profile-img">
        <span class="online-dot"></span>
      </div>
      <div>
        <p style="color:white; font-weight:600;"><?php echo htmlspecialchars($student_name); ?></p>
        <p class="bottom-status">Student</p>
      </div>
    </div>
  </div>

  <div class="main">
    <div class="topbar">
      <span>Courses</span>
      <button class="menu-btn" id="menuBtn">&#9776;</button>
    </div>

    <div class="task-list">
      <!-- Join Class card (top) -->
      <div class="task-card new-task" id="joinCard" style="cursor:pointer;">
        <div class="task-icon"><span class="material-icons">add_circle</span></div>
        <div class="task-content">
          <h4>Join Class</h4>
          <p>Enter a class code to join a course.</p>
        </div>
      </div>

      <!-- Dynamic class cards -->
      <?php if (!empty($courses)): ?>
        <?php foreach ($courses as $c): 
          $c_course_name = $c['course_name'] ?? 'Unnamed Course';
          $c_course_id   = $c['course_id'] ?? '';
          $c_section     = $c['section'] ?? '';
          $c_strand      = $c['strand'] ?? '';
          $c_day         = $c['day'] ?? '';
          $c_time_start  = !empty($c['time_start']) ? date("g:i A", strtotime($c['time_start'])) : '';
          $c_time_end    = !empty($c['time_end']) ? date("g:i A", strtotime($c['time_end'])) : '';
          $c_class_code  = $c['class_code'] ?? '';
          $card_href     = "all.php?class_id=" . urlencode($c['class_id'] ?? $c_course_id);
        ?>
          <div class="task-card"
               data-href="<?php echo htmlspecialchars($card_href); ?>"
               data-course-id="<?php echo htmlspecialchars($c['course_id']); ?>">
            <div class="task-icon"><span class="material-icons">school</span></div>

            <div class="task-content">
              <h4><?php echo htmlspecialchars($c_course_name); ?> <span class="task-tag">Class</span></h4>
              <p><?php echo htmlspecialchars($c_course_id); ?></p>
              <p><?php echo htmlspecialchars(($c['grade'] ?? '') !== '' ? ($c['grade'] . ' - ' . ($c_strand ?: '')) : ($c_strand ?: '')); ?> | Section <?php echo htmlspecialchars($c_section); ?></p>
              <p><?php echo htmlspecialchars($c_day); ?> <?php if($c_time_start || $c_time_end) echo ', ' . htmlspecialchars($c_time_start . ' - ' . $c_time_end); ?></p>
            </div>

            <div class="task-meta">
              <span class="material-icons menu-icon">more_vert</span>
              <div class="dropdown-menu" style="display:none;">
                <!-- Only Unenroll for students -->
                <a href="#" class="unenroll-link" data-course-id="<?php echo htmlspecialchars($c['course_id']); ?>">Unenroll</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="task-card">
          <div class="task-content">
            <h4>No classes yet</h4>
            <p>You haven't joined any classes yet. Click "Join Class" to enroll using a class code.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

<!-- Join Modal -->
<div class="modal" id="joinModal" aria-hidden="true">
  <div class="modal-content">
    
    <span class="close-btn" id="closeJoin">&times;</span>

    <h3>Join Class</h3>
    <p>Enter the <strong>Class Code</strong> provided by your teacher.</p>

    <label for="classCode" style="margin-top:15px; display:block;">Class Code</label>
    <input type="text" id="classCode" placeholder="e.g. OI86-28PI">

    <div id="joinErrors" style="display:none;"></div>

    <div class="modal-buttons">
      <button class="submit-btn" id="cancelJoin">Cancel</button>
      <button class="submit-btn" id="submitJoin">Join</button>
    </div>

  </div>
</div>


<script>
  // Sidebar toggle (mobile)
  const menuBtn = document.getElementById('menuBtn');
  const sidebar = document.getElementById('sidebar');
  menuBtn?.addEventListener('click', () => sidebar.classList.toggle('active'));

  // Card click navigation
  document.querySelectorAll('.task-card').forEach(card => {
    card.addEventListener('click', function(e) {
      // skip if clicked dropdown/menu
      if (e.target.closest('.dropdown-menu') || e.target.closest('.menu-icon') || e.target.tagName === 'A' || e.target.tagName === 'BUTTON') return;
      const link = this.getAttribute('data-href');
      if (link) window.location.href = link;
    });
  });

  // Join card -> open modal
  const joinCard = document.getElementById('joinCard');
  const joinModal = document.getElementById('joinModal');
  const closeJoin = document.getElementById('closeJoin');
  const cancelJoin = document.getElementById('cancelJoin');
  const submitJoin = document.getElementById('submitJoin');
  const classCodeInput = document.getElementById('classCode');
  const joinErrors = document.getElementById('joinErrors');

  function openJoinModal() {
    joinErrors.style.display = 'none';
    joinErrors.textContent = '';
    classCodeInput.value = '';
    joinModal.style.display = 'flex';
    classCodeInput.focus();
  }
  function closeJoinModal() { joinModal.style.display = 'none'; }

  joinCard?.addEventListener('click', openJoinModal);
  closeJoin?.addEventListener('click', closeJoinModal);
  cancelJoin?.addEventListener('click', closeJoinModal);
  window.addEventListener('click', (e) => { if (e.target === joinModal) closeJoinModal(); });

  submitJoin?.addEventListener('click', () => {
    const code = classCodeInput.value.trim();
    joinErrors.style.display = 'none';
    joinErrors.textContent = '';
    if (!code) {
      joinErrors.style.display = 'block';
      joinErrors.textContent = 'Please enter a class code.';
      return;
    }

    const form = new FormData();
    form.append('action','join_course');
    form.append('class_code', code);

    fetch(window.location.href, {
      method: 'POST',
      credentials: 'same-origin',
      body: form
    })
    .then(r => r.json().catch(()=>({status:'error', errors:['Invalid server response.']})))
    .then(data => {
      if (data.status === 'success') {
        // small success feedback and reload
        classCodeInput.value = '';
        closeJoinModal();
        alert(data.message || 'Enrolled successfully.');
        window.location.reload();
      } else {
        joinErrors.style.display = 'block';
        joinErrors.innerHTML = (data.errors || [data.message || 'Unknown error']).map(x => `<div>${x}</div>`).join('');
      }
    })
    .catch(err => {
      joinErrors.style.display = 'block';
      joinErrors.textContent = 'Network error. See console.';
      console.error(err);
    });
  });

  // 3-dot dropdown
  document.querySelectorAll('.menu-icon').forEach(icon => {
    const dropdown = icon.nextElementSibling;
    const card = icon.closest('.task-card');
    icon.addEventListener('click', (e) => {
      e.stopPropagation();
      // close others
      document.querySelectorAll('.dropdown-menu').forEach(m => { if (m !== dropdown) m.style.display = 'none'; });
      dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
      if (dropdown.style.display === 'block') card.style.zIndex = 100; else card.style.zIndex = 1;
    });
  });

  // close dropdown when clicking outside
  document.addEventListener('click', (e) => {
    document.querySelectorAll('.dropdown-menu').forEach(drop => {
      const prev = drop.previousElementSibling;
      if (!drop.contains(e.target) && !(prev && prev.contains(e.target))) {
        drop.style.display = 'none';
        const parent = drop.closest('.task-card');
        if (parent) parent.style.zIndex = 1;
      }
    });
  });

  // Unenroll button (AJAX)
document.querySelectorAll('.unenroll-link').forEach(link => {
  link.addEventListener('click', function(e) {
    e.stopPropagation();
    const courseId = this.getAttribute('data-course-id');
    if (!courseId) return alert('Course id missing.');

    if (!confirm('Unenroll from this class?')) return;

    const form = new FormData();
    form.append('action', 'unenroll');
    form.append('course_id', courseId);

    fetch(window.location.href, {
      method: 'POST',
      credentials: 'same-origin',
      body: form
    })
    .then(r => r.json().catch(()=>({status:'error', errors:['Invalid response from server.']})))
    .then(data => {
      if (data.status === 'success') {
        const card = this.closest('.task-card');
        if (card) card.remove();
        alert(data.message || 'Unenrolled.');
      } else {
        alert((data.errors || [data.message || 'Error'])[0]);
      }
    })
    .catch(err => {
      console.error(err);
      alert('Network error while unenrolling. See console.');
    });
  });
});


</script>
</body>
</html>
