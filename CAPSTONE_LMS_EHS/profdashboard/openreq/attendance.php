<?php
include '../../config/db_connect.php'; // Supabase PDO connection

session_start();

$teacher_id = $_SESSION['teacher_id'] ?? 1;
$strand = $_GET['strand'] ?? ($_SESSION['strand'] ?? '');
$section = $_GET['section'] ?? ($_SESSION['section'] ?? '');
$course_name = $_GET['course'] ?? ($_SESSION['course'] ?? '');

if (!empty($strand)) $_SESSION['strand'] = $strand;
if (!empty($section)) $_SESSION['section'] = $section;
if (!empty($course_name)) $_SESSION['course'] = $course_name;

$class_id = '';
$stmt = $conn->prepare("SELECT class_id FROM prof_courses WHERE teacher_id = :teacher_id AND strand = :strand AND section = :section LIMIT 1");
$stmt->execute(['teacher_id' => $teacher_id, 'strand' => $strand, 'section' => $section]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $class_id = $row['class_id'];
}
$stmt = null;

$teacher_name = 'Unknown_Teacher';
$stmt = $conn->prepare("SELECT first_name, last_name FROM teachers_account WHERE teacher_id = :teacher_id");
$stmt->execute(['teacher_id' => $teacher_id]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $teacher_name = $row['first_name'] . ' ' . $row['last_name'];
}
$stmt = null;

// Handle adding attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_attendance'])) {
        $title = $_POST['title'] ?? '';
        $desc = $_POST['desc'] ?? '';
        $due_date = $_POST['due_date'] ?? '';
        $due_time = $_POST['due_time'] ?? '';
        $date_posted = date('Y-m-d');

        $stmt = $conn->prepare("INSERT INTO prof_attendance 
            (teacher_id, class_id, section, title, date_posted, due_date, due_time, \"desc\") 
            VALUES (:teacher_id, :class_id, :section, :title, :date_posted, :due_date, :due_time, :desc)");
        $stmt->execute([
            'teacher_id' => $teacher_id,
            'class_id' => $class_id,
            'section' => $section,
            'title' => $title,
            'date_posted' => $date_posted,
            'due_date' => $due_date,
            'due_time' => $due_time,
            'desc' => $desc
        ]);
        $stmt = null;

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    if (isset($_POST['edit_attendance_id'])) {
        $edit_id = intval($_POST['edit_attendance_id']);
        $edit_title = $_POST['edit_title'];
        $edit_desc = $_POST['edit_desc'];
        $edit_due_date = $_POST['edit_due_date'];
        $edit_due_time = $_POST['edit_due_time'];

        $stmt = $conn->prepare("UPDATE prof_attendance SET title = :title, \"desc\" = :desc, due_date = :due_date, due_time = :due_time WHERE attendance_id = :attendance_id");
        $stmt->execute([
            'title' => $edit_title,
            'desc' => $edit_desc,
            'due_date' => $edit_due_date,
            'due_time' => $edit_due_time,
            'attendance_id' => $edit_id
        ]);
        $stmt = null;

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Handle delete
if (isset($_GET['delete_attendance'])) {
    $delete_id = intval($_GET['delete_attendance']);
    $stmt = $conn->prepare("DELETE FROM prof_attendance WHERE attendance_id = :attendance_id");
    $stmt->execute(['attendance_id' => $delete_id]);
    $stmt = null;

    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}

// Fetch attendance list
$attendance_list = [];
$stmt = $conn->prepare("SELECT * FROM prof_attendance WHERE teacher_id = :teacher_id AND class_id = :class_id AND section = :section ORDER BY attendance_id DESC");
$stmt->execute(['teacher_id' => $teacher_id, 'class_id' => $class_id, 'section' => $section]);
$attendance_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = null;


// ➡ Forward attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_attendance'])) {
    $attendance_id = $_POST['attendance_id'];
    $class_forward = $_POST['class_forward'];

    try {
        // 1️⃣ Fetch the original attendance
        $stmt = $conn->prepare("
            SELECT title, \"desc\", due_date, due_time 
            FROM prof_attendance 
            WHERE attendance_id = :attendance_id AND teacher_id = :teacher_id
        ");
        $stmt->execute([
            'attendance_id' => $attendance_id,
            'teacher_id' => $teacher_id
        ]);
        $original = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$original) {
            throw new Exception("Original attendance not found.");
        }

        // 2️⃣ Fetch the section and strand from the destination class
        $stmt = $conn->prepare("
            SELECT section, strand 
            FROM prof_courses 
            WHERE class_id = :class_id
        ");
        $stmt->execute(['class_id' => $class_forward]);
        $destClass = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$destClass) {
            throw new Exception("Destination class not found.");
        }

        $section_forward = $destClass['section'];
        $strand_forward  = $destClass['strand'];

        // 3️⃣ Insert a new attendance for the new class
        $stmt = $conn->prepare("
            INSERT INTO prof_attendance (teacher_id, class_id, section, title, date_posted, due_date, due_time, \"desc\")
            VALUES (:teacher_id, :class_id, :section, :title, :date_posted, :due_date, :due_time, :desc)
        ");
        $stmt->execute([
            'teacher_id' => $teacher_id,
            'class_id'   => $class_forward,
            'section'    => $section_forward,
            'title'      => $original['title'],
            'date_posted'=> date('Y-m-d'),
            'due_date'   => $original['due_date'],
            'due_time'   => $original['due_time'],
            'desc'       => $original['desc']
        ]);

    } catch (Exception $e) {
        die("Error forwarding attendance: " . $e->getMessage());
    }

    // Redirect back
    header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=$class_id&section=$section&strand=$strand");
    exit;
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

  <!-- Google Fonts and Icons -->
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
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

</head>

<body>
   <div class="sidebar" id="sidebar">
    <div class="profile">
    <img src="/CAPSTONE_LMS_EHS/assets/landingpage/ehslogo.png" alt="Profile Image" class="top-profile-img">
    <h3>Eusebio High School</h3>
  </div>

   <div class="menu">
      <a href="/CAPSTONE_LMS_EHS/profdashboard/profhome.php"><span class="material-icons">dashboard</span> Dashboard</a>
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
    <a href="/CAPSTONE_LMS_EHS/profdashboard/all.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">grid_view</span>All</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/announcement.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">campaign</span>Announcements</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/activities.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">event</span>Activities</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/module.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">menu_book</span>Module</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/quizzes.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">quiz</span>Quizzes</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/exam.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">school</span>Exam</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/attendance.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn active"><span class="material-icons">fact_check</span>Attendance</a>  
  
  </div>

<!-- Task Cards -->
<div class="task-list">
  <div class="task-card new-task" id="newAttendanceBtn" onclick="openAddModal()">
    <div class="task-icon">
      <span class="material-icons">add_circle</span>
    </div>
    <div class="task-content">
      <h4>Create New Attendance</h4>
      <p>Click the plus icon to add a new attendance for this course.</p>
    </div>
  </div>

  <?php foreach ($attendance_list as $attendance): ?>
  <div class="task-card">
    <div class="task-icon">
      <span class="material-icons">fact_check</span>
    </div>
    <div class="task-content">
      <h4><?= htmlspecialchars($attendance['title']) ?>
        <span class="task-tag" style="background-color:#fff2e6;color:#b85b00;">Attendance</span>
      </h4>
      <p><?= nl2br(htmlspecialchars($attendance['desc'])) ?></p>
      <p>Date Posted: <?= date("F j, Y", strtotime($attendance['date_posted'])) ?></p>
      <p>Due Date: <?= date("F j, Y", strtotime($attendance['due_date'])) ?> | <?= htmlspecialchars($attendance['due_time']) ?></p>
    </div>
    <div class="task-meta">
      <span class="material-icons menu-icon" onclick="toggleMenu(this)">more_vert</span>
      <div class="dropdown-menu">
        <a href="#" onclick="openEditModal(
          <?= $attendance['attendance_id'] ?>,
          '<?= htmlspecialchars($attendance['title'], ENT_QUOTES) ?>',
          '<?= htmlspecialchars($attendance['due_date'], ENT_QUOTES) ?>',
          '<?= htmlspecialchars($attendance['due_time'], ENT_QUOTES) ?>',
          `<?= htmlspecialchars($attendance['desc'], ENT_QUOTES) ?>`
        )">Edit</a>
        <a href="?delete_attendance=<?= $attendance['attendance_id'] ?>" onclick="return confirm('Are you sure you want to delete this attendance?');">Delete</a>
        <a href="#" onclick="openForwardModal(<?= $attendance['attendance_id'] ?>)">Forward</a>
        <a href="#" onclick="viewAttendance(<?= $attendance['attendance_id'] ?>)">View Attendance</a>
        
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Add Attendance Modal -->
<div id="addModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeAddModal()">&times;</span>
    <h3>Create New Attendance</h3>
    <form method="POST">
      <input type="hidden" name="add_attendance" value="1">
      <label>Title:</label>
      <input type="text" name="title" required placeholder="Enter attendance title">
      <label>Description:</label>
      <textarea name="desc" rows="4" placeholder="Enter attendance description..."></textarea>
      <label>Due Date:</label>
      <input type="date" name="due_date" required>
      <label>Due Time:</label>
      <input type="time" name="due_time" required>
      <button type="submit" class="submit-btn">Post</button>
    </form>
  </div>
</div>


<!-- Forward Modal -->
<div class="modal" id="forwardModal<?= $attendance['attendance_id'] ?>">
    <div class="modal-content">
        <span class="close-btn" onclick="closeForwardModal(<?= $attendance['attendance_id'] ?>)">&times;</span>
        <h3>Forward Attendance</h3>
        <form method="POST">
            <input type="hidden" name="forward_attendance" value="1">
            <input type="hidden" name="attendance_id" value="<?= $attendance['attendance_id'] ?>">
            
            <label>Target Class ID:</label>
            <input type="number" name="class_forward" placeholder="Enter class ID" required>
            
            <button type="submit" class="submit-btn">Forward</button>
        </form>
    </div>
</div>


<!-- View Attendance Modal -->
<div id="viewAttendanceModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeViewAttendanceModal()">&times;</span>
    <h3>Student Attendance</h3>
    <table id="attendanceTable" style="width:100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th style="border-bottom:1px solid #ccc; padding:8px;">Student Name</th>
          <th style="border-bottom:1px solid #ccc; padding:8px;">Status</th>
          <th style="border-bottom:1px solid #ccc; padding:8px;">Date Marked</th>
        </tr>
      </thead>
      <tbody id="attendanceTableBody">
        <!-- JS will fill this -->
      </tbody>
    </table>
  </div>
</div>


<!-- Edit Attendance Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeEditModal()">&times;</span>
    <h3>Edit Attendance</h3>
    <form method="POST">
      <input type="hidden" name="edit_attendance_id" id="edit_attendance_id">
      <label>Title:</label>
      <input type="text" name="edit_title" id="edit_title" required>
      <label>Description:</label>
      <textarea name="edit_desc" id="edit_desc" rows="4" required></textarea>
      <label>Due Date:</label>
      <input type="date" name="edit_due_date" id="edit_due_date" required>
      <label>Due Time:</label>
      <input type="time" name="edit_due_time" id="edit_due_time" required>
      <button type="submit" class="submit-btn">Save Changes</button>
    </form>
  </div>
</div>

<script>
function toggleMenu(icon) {
  document.querySelectorAll('.dropdown-menu').forEach(menu => {
    if (menu !== icon.nextElementSibling) menu.style.display = 'none';
  });
  const menu = icon.nextElementSibling;
  menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
}

function openAddModal() { document.getElementById('addModal').style.display = 'flex'; }
function closeAddModal() { document.getElementById('addModal').style.display = 'none'; }

function openEditModal(id, title, dueDate, dueTime, desc) {
  document.getElementById('edit_attendance_id').value = id;
  document.getElementById('edit_title').value = title;
  document.getElementById('edit_due_date').value = dueDate;
  document.getElementById('edit_due_time').value = dueTime;
  document.getElementById('edit_desc').value = desc;
  document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

window.onclick = function(event) {
  const addModal = document.getElementById('addModal');
  const editModal = document.getElementById('editModal');
  if (event.target === addModal) addModal.style.display = 'none';
  if (event.target === editModal) editModal.style.display = 'none';
}

function viewAttendance(attendance_id) {
  $.ajax({
    url: '/CAPSTONE_LMS_EHS/api/prof/prof_attendance/view_attendance.php',
    type: 'POST',
    data: { attendance_id: attendance_id },
    dataType: 'json',
    success: function(resp) {
      if(resp.status === 'success') {
        let tbody = '';
        resp.data.forEach(s => {
          tbody += `<tr>
            <td style="border-bottom:1px solid #ccc; padding:8px;">${s.first_name} ${s.last_name}</td>
            <td style="border-bottom:1px solid #ccc; padding:8px;">${s.status}</td>
            <td style="border-bottom:1px solid #ccc; padding:8px;">${s.date_marked}</td>
          </tr>`;
        });
        $('#attendanceTableBody').html(tbody);
        $('#viewAttendanceModal').fadeIn(200).css('display','flex');
      } else {
        alert(resp.message);
      }
    },
    error: function() {
      alert('Error fetching attendance data.');
    }
  });
}

function closeViewAttendanceModal() {
  $('#viewAttendanceModal').fadeOut(200);
}

function openForwardModal(attendance_id) {
  document.getElementById('forward_attendance_id').value = attendance_id;
  document.getElementById('forwardAttendanceModal').style.display = 'flex';
}

document.getElementById('closeForwardAttendance').addEventListener('click', function() {
  document.getElementById('forwardAttendanceModal').style.display = 'none';
});

window.addEventListener('click', function(e) {
  const modal = document.getElementById('forwardAttendanceModal');
  if (e.target === modal) modal.style.display = 'none';
});

// Forward modal open/close
function openForwardModal(id) { document.getElementById("forwardModal"+id).style.display="flex"; }
function closeForwardModal(id) { document.getElementById("forwardModal"+id).style.display="none"; }

</script>

</body>
</html>
