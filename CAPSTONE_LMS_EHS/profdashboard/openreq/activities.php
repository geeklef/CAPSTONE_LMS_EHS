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

// Fetch teacher name
$teacher_name = 'Unknown Teacher';
$prof_name = 'DefaultProf';
$stmt = $conn->prepare("SELECT first_name, last_name FROM teachers_account WHERE teacher_id = :teacher_id");
$stmt->execute(['teacher_id' => $teacher_id]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $teacher_name = $row['first_name'] . ' ' . $row['last_name'];
    $prof_name = preg_replace('/\s+/', '', $row['first_name'] . $row['last_name']);
}

// Get class_id
$class_id = '';
if (!empty($strand) && !empty($section)) {
    $stmt = $conn->prepare("SELECT class_id FROM prof_courses WHERE teacher_id = :teacher_id AND strand = :strand AND section = :section LIMIT 1");
    $stmt->execute(['teacher_id' => $teacher_id, 'strand' => $strand, 'section' => $section]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $class_id = $row['class_id'];
}

$stmt = $conn->prepare("SELECT strand FROM prof_courses WHERE class_id = :class_id LIMIT 1");
$stmt->execute(['class_id' => $class_id]);
$strand = $stmt->fetchColumn();

// Add new activity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_activity'])) {
    $title = $_POST['title'] ?? '';
    $desc = $_POST['desc'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $due_time = $_POST['due_time'] ?? '';
    $date_posted = date('Y-m-d');
    $fileName = null;
    $fileUrl = null;

    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $fileTmp  = $_FILES['file']['tmp_name'];
        $fileName = basename($_FILES['file']['name']);
        $filePath = "$prof_name/{$strand}-{$section}/activity/$fileName";

        $supabaseUrl = 'https://fgsohkazfoskhxhndogu.supabase.co';
        $bucket      = 'teacher_activity_file';
        $anonKey     = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZnc29oa2F6Zm9za2h4aG5kb2d1Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjA0MzU4MDIsImV4cCI6MjA3NjAxMTgwMn0.EHpoxrGBEx9j2MYQPbhGo-l65hmfijmBBRY65xMVY7c';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "$supabaseUrl/storage/v1/object/$bucket/$filePath",
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => file_get_contents($fileTmp),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $anonKey",
                "Content-Type: application/octet-stream"
            ]
        ]);
        $uploadResponse = curl_exec($ch);
        $uploadError    = curl_error($ch);
        curl_close($ch);

        if ($uploadError) {
            error_log("Supabase upload error: $uploadError");
        } else {
            $fileUrl = "$supabaseUrl/storage/v1/object/public/$bucket/$filePath";
        }
    }

    $sql = "INSERT INTO prof_activities 
        (teacher_id, class_id, section, title, date_posted, due_date, due_time, \"desc\", file_name, file_path)
        VALUES (:teacher_id, :class_id, :section, :title, :date_posted, :due_date, :due_time, :desc, :file_name, :file_path)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'teacher_id' => $teacher_id,
        'class_id' => $class_id,
        'section' => $section,
        'title' => $title,
        'date_posted' => $date_posted,
        'due_date' => $due_date,
        'due_time' => $due_time,
        'desc' => $desc,
        'file_name' => $fileName,
        'file_path' => $fileUrl
    ]);

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Delete activity
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM prof_activities WHERE activity_id = :id");
    $stmt->execute(['id' => $delete_id]);
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Edit activity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_activity_id'])) {
    $edit_id = intval($_POST['edit_activity_id']);
    $edit_title = $_POST['edit_title'];
    $edit_desc = $_POST['edit_desc'];
    $edit_due_date = $_POST['edit_due_date'];
    $edit_due_time = $_POST['edit_due_time'];

    $stmt = $conn->prepare("UPDATE prof_activities 
        SET title = :title, due_date = :due_date, due_time = :due_time, \"desc\" = :desc 
        WHERE activity_id = :id");
    $stmt->execute([
        'title' => $edit_title,
        'due_date' => $edit_due_date,
        'due_time' => $edit_due_time,
        'desc' => $edit_desc,
        'id' => $edit_id
    ]);

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

// Fetch activities
$stmtFetch = $conn->prepare("SELECT * FROM prof_activities WHERE teacher_id = :teacher_id AND class_id = :class_id AND section = :section ORDER BY activity_id DESC");
$stmtFetch->execute(['teacher_id' => $teacher_id, 'class_id' => $class_id, 'section' => $section]);
$activities = $stmtFetch->fetchAll(PDO::FETCH_ASSOC);


// Forward activity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_activity_id'])) {
    $forward_id = intval($_POST['forward_activity_id']);
    $target_class = intval($_POST['forward_class_id']);

    // Get strand and section from target class
    $stmtClass = $conn->prepare("SELECT strand, section FROM prof_courses WHERE class_id = :class_id LIMIT 1");
    $stmtClass->execute(['class_id' => $target_class]);
    $target = $stmtClass->fetch(PDO::FETCH_ASSOC);

    if ($target) {
        $target_strand = $target['strand'];
        $target_section = $target['section'];
        $date_posted = date('Y-m-d');

        // Copy activity to target class
        $stmt = $conn->prepare("INSERT INTO prof_activities 
            (teacher_id, class_id, section, title, date_posted, due_date, due_time, \"desc\", file_name, file_path)
            SELECT teacher_id, :target_class, :target_section, title, :date_posted, due_date, due_time, \"desc\", file_name, file_path
            FROM prof_activities
            WHERE activity_id = :activity_id
        ");
        $stmt->execute([
            'target_class' => $target_class,
            'target_section' => $target_section,
            'date_posted' => $date_posted,
            'activity_id' => $forward_id
        ]);
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
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

  /* Responsive Styles */
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

  /* Mobile adjustments */
  @media (max-width: 900px) {
    .task-card {
      flex-direction: column;
      align-items: flex-start;
      position: relative;
    }

    .task-meta {
      position: absolute;
      top: 10px;
      right: 10px;
      margin: 0;
    }

    .dropdown-menu {
      right: 5px;
      top: 35px;
      min-width: 130px;
    }
  }

  @media (max-width: 600px) {
    .task-meta {
      top: 8px;
      right: 8px;
    }

    .dropdown-menu {
      top: 30px;
      right: 5px;
    }
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

<!-- Sidebar -->
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
      <p><?php echo $prof_name; ?></p>
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
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/activities.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn active"><span class="material-icons">event</span>Activities</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/module.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">menu_book</span>Module</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/quizzes.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">quiz</span>Quizzes</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/exam.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">school</span>Exam</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/attendance.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">fact_check</span>Attendance</a>
  </div>

  <div class="task-list">
    <!-- New Activity Card -->
    <div class="task-card new-task" id="newAnnouncementBtn">
      <div class="task-icon"><span class="material-icons">add_circle</span></div>
      <div class="task-content">
        <h4>Create New Activity</h4>
        <p>Click the plus icon to add a new activity for this course.</p>
      </div>
    </div>

    <!-- ===== Create Activity Modal ===== -->
    <div id="announcementModal" class="modal">
      <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h3>Create New Activity</h3>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="add_activity" value="1">
          <label>Title:</label>
          <input type="text" name="title" placeholder="Enter activity title" required>
          <label>Description:</label>
          <textarea name="desc" rows="4" placeholder="Enter activity details" required></textarea>
          <label>Due Date:</label>
          <input type="date" name="due_date" required>
          <label>Due Time:</label>
          <input type="time" name="due_time" required>
          <label>Attach File (optional):</label>
          <input type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.jpg,.png">
          <button type="submit" class="submit-btn">Post</button>
        </form>
      </div>
    </div>

    <!-- ===== Edit Activity Modal ===== -->
    <div id="editModal" class="modal">
      <div class="modal-content">
        <span class="close-btn" id="closeEdit">&times;</span>
        <h3>Edit Activity</h3>
        <form method="POST">
          <input type="hidden" name="edit_activity_id" id="edit_activity_id">
          <label>Title:</label>
          <input type="text" name="edit_title" id="edit_title" required>
          <label>Description:</label>
          <textarea name="edit_desc" id="edit_desc" rows="4" required></textarea>
          <label>Due Date:</label>
          <input type="date" name="edit_due_date" id="edit_due_date" required>
          <label>Due Time:</label>
          <input type="time" name="edit_due_time" id="edit_due_time" required>
          <button type="submit" class="submit-btn">Update</button>
        </form>
      </div>
    </div>
<!-- ===== Forward Activity Modal ===== -->
<div id="forwardModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" id="closeForward">&times;</span>
    <h3>Forward Activity</h3>
    <form method="POST" id="forwardForm">
      <input type="hidden" name="forward_activity_id" id="forward_activity_id">
      <label>Class ID:</label>
      <input type="number" name="forward_class_id" id="forward_class_id" placeholder="Enter Class ID" required>
      <button type="submit" class="submit-btn">Forward</button>
    </form>
  </div>
</div>


    <!-- ===== File Overview Modal ===== -->
    <div id="fileOverviewModal" class="modal">
      <div class="modal-content file-viewer">
        <span class="close-btn">&times;</span>
        <h2>File Overview</h2>
        <iframe id="fileFrame" frameborder="0"></iframe>
      </div>
    </div>

    <!-- ===== Activity Cards ===== -->
    <?php foreach ($activities as $activity): ?>
      <div class="task-card">
        <div class="task-icon"><span class="material-icons">event</span></div>
        <div class="task-content">
          <h4><?php echo htmlspecialchars($activity['title']); ?><span class="task-tag">Activity</span></h4>
          <p><?php echo nl2br(htmlspecialchars($activity['desc'])); ?></p>
          <p>Date Posted: <?php echo htmlspecialchars($activity['date_posted']); ?></p>
          <p>Due Date: <?php echo htmlspecialchars($activity['due_date'] . ' | ' . $activity['due_time']); ?></p>
          <?php if (!empty($activity['file_path'])): ?>
            <div class="file-overview">
              <a href="#" class="file-name" data-file="<?php echo htmlspecialchars($activity['file_path']); ?>">
                <?php echo htmlspecialchars($activity['file_name']); ?>
              </a>
            </div>
          <?php endif; ?>
        </div>
        <div class="task-meta">
          <span class="material-icons menu-icon" onclick="toggleDropdown(this)">more_vert</span>
          <div class="dropdown-menu">
            <a href="#" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($activity)); ?>)">Edit</a>
            <a href="actlist.php?activity_id=<?php echo $activity['activity_id']; ?>">Submitted Works</a>
            <a href="#" onclick="openForwardModal(<?php echo htmlspecialchars(json_encode($activity)); ?>)">Forward</a>
            <a href="?delete_id=<?php echo $activity['activity_id']; ?>" onclick="return confirm('Delete this activity?')">Delete</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
// ===== Modal logic =====
const modal = document.getElementById("announcementModal");
const openBtn = document.getElementById("newAnnouncementBtn");
const closeBtn = modal.querySelector(".close-btn");
openBtn.onclick = () => modal.style.display = "flex";
closeBtn.onclick = () => modal.style.display = "none";
window.onclick = e => { if (e.target === modal) modal.style.display = "none"; };

// ===== Dropdown menu =====
function toggleDropdown(icon) {
    const menu = icon.nextElementSibling;
    document.querySelectorAll(".dropdown-menu").forEach(m => { if (m !== menu) m.style.display = "none"; });
    menu.style.display = (menu.style.display === "block") ? "none" : "block";
}
window.addEventListener("click", e => { if (!e.target.matches('.menu-icon')) document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = "none"); });

// ===== Edit Modal =====
const editModal = document.getElementById("editModal");
const closeEdit = document.getElementById("closeEdit");
function openEditModal(activity) {
    document.getElementById("edit_activity_id").value = activity.activity_id;
    document.getElementById("edit_title").value = activity.title;
    document.getElementById("edit_desc").value = activity.desc;
    document.getElementById("edit_due_date").value = activity.due_date;
    document.getElementById("edit_due_time").value = activity.due_time;
    editModal.style.display = "flex";
}
closeEdit.onclick = () => editModal.style.display = "none";
window.onclick = e => { if (e.target === editModal) editModal.style.display = "none"; };

// ===== File Preview Modal =====
const fileLinks = document.querySelectorAll('.file-name');
const fileModal = document.getElementById('fileOverviewModal');
const fileFrame = document.getElementById('fileFrame');
const closeFileBtn = fileModal.querySelector('.close-btn');

fileLinks.forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        const fileSrc = link.dataset.file;
        if (!fileSrc) return;

        const ext = fileSrc.split('.').pop().toLowerCase();
        if (ext === 'pdf') {
            fileFrame.src = `https://docs.google.com/gview?url=${encodeURIComponent(fileSrc)}&embedded=true`;
        } else if (['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'].includes(ext)) {
            fileFrame.src = `https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(fileSrc)}`;
        } else if (['jpg','jpeg','png','gif','bmp'].includes(ext)) {
            fileFrame.src = fileSrc;
        } else {
            window.open(fileSrc, '_blank');
            return;
        }
        fileModal.style.display = 'flex';
    });
});

const forwardModal = document.getElementById('forwardModal');
const closeForward = document.getElementById('closeForward');

function openForwardModal(activity) {
    document.getElementById('forward_activity_id').value = activity.activity_id;
    forwardModal.style.display = 'flex';
}
closeForward.onclick = () => forwardModal.style.display = 'none';
window.addEventListener('click', e => { if (e.target === forwardModal) forwardModal.style.display = 'none'; });


closeFileBtn.addEventListener('click', () => { fileModal.style.display = 'none'; fileFrame.src = ''; });
window.addEventListener('click', e => { if (e.target === fileModal) { fileModal.style.display = 'none'; fileFrame.src = ''; } });
</script>

</body>
</html>
