<?php
include '../../config/db_connect.php';
session_start();

$teacher_id = $_SESSION['teacher_id'] ?? 1; // fallback for testing

// 🧭 Get class info from URL
$class_id = $_GET['class_id'] ?? '';
$section = $_GET['section'] ?? '';
$strand = $_GET['strand'] ?? '';

// ⚠️ Require these parameters
if (empty($class_id) || empty($section) || empty($strand)) {
    die("Missing class_id, section, or strand from URL.");
}

// 🧑‍🏫 Get teacher name
$teacher_name = 'Unknown Teacher';
try {
    $stmt = $conn->prepare("SELECT first_name, last_name FROM teachers_account WHERE teacher_id = :teacher_id");
    $stmt->execute(['teacher_id' => $teacher_id]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $teacher_name = $row['first_name'] . ' ' . $row['last_name'];
    }
} catch (PDOException $e) {
    die("Error fetching teacher name: " . $e->getMessage());
}

// ➕ Add new announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];

    try {
        $stmt = $conn->prepare("
            INSERT INTO prof_announcements (teacher_id, class_id, section, strand, title, \"desc\")
            VALUES (:teacher_id, :class_id, :section, :strand, :title, :description)
        ");
        $stmt->execute([
            'teacher_id' => $teacher_id,
            'class_id' => $class_id,
            'section' => $section,
            'strand' => $strand,
            'title' => $title,
            'description' => $description
        ]);
    } catch (PDOException $e) {
        die("Error adding announcement: " . $e->getMessage());
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=$class_id&section=$section&strand=$strand");
    exit;
}

// ✏️ Edit announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_announcement'])) {
    $announce_id = $_POST['announce_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];

    try {
        $stmt = $conn->prepare("
            UPDATE prof_announcements
            SET title = :title, \"desc\" = :description
            WHERE announce_id = :announce_id AND teacher_id = :teacher_id
        ");
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'announce_id' => $announce_id,
            'teacher_id' => $teacher_id
        ]);
    } catch (PDOException $e) {
        die("Error editing announcement: " . $e->getMessage());
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=$class_id&section=$section&strand=$strand");
    exit;
}

// ❌ Delete announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    $announce_id = $_POST['announce_id'];
    try {
        $stmt = $conn->prepare("
            DELETE FROM prof_announcements
            WHERE announce_id = :announce_id AND teacher_id = :teacher_id
        ");
        $stmt->execute([
            'announce_id' => $announce_id,
            'teacher_id' => $teacher_id
        ]);
    } catch (PDOException $e) {
        die("Error deleting announcement: " . $e->getMessage());
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?class_id=$class_id&section=$section&strand=$strand");
    exit;
}

// 📜 Fetch announcements
$announcements = [];
try {
    $stmt = $conn->prepare("
        SELECT *
        FROM prof_announcements
        WHERE class_id = :class_id AND strand = :strand AND section = :section
        ORDER BY announce_id DESC
    ");
    $stmt->execute([
        'class_id' => $class_id,
        'strand' => $strand,
        'section' => $section
    ]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching announcements: " . $e->getMessage());
}


// ➡ Forward announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forward_announcement'])) {
    $announce_id = $_POST['announce_id'];
    $class_forward = $_POST['class_forward'];

    try {
        // 1️⃣ Fetch the original announcement
        $stmt = $conn->prepare("
            SELECT title, \"desc\" 
            FROM prof_announcements 
            WHERE announce_id = :announce_id AND teacher_id = :teacher_id
        ");
        $stmt->execute([
            'announce_id' => $announce_id,
            'teacher_id' => $teacher_id
        ]);
        $original = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$original) {
            throw new Exception("Original announcement not found.");
        }

        // 2️⃣ Fetch the section and strand from the destination class
        $stmt = $conn->prepare("
            SELECT section, strand, grade
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
        $grade_forward   = $destClass['grade'] ?? null; // optional

        // 3️⃣ Insert a new announcement for the new class
        $stmt = $conn->prepare("
            INSERT INTO prof_announcements (teacher_id, class_id, section, strand, title, \"desc\")
            VALUES (:teacher_id, :class_id, :section, :strand, :title, :description)
        ");
        $stmt->execute([
            'teacher_id'   => $teacher_id,
            'class_id'     => $class_forward,
            'section'      => $section_forward,
            'strand'       => $strand_forward,
            'title'        => $original['title'],
            'description'  => $original['desc']
        ]);

    } catch (Exception $e) {
        die("Error forwarding announcement: " . $e->getMessage());
    }

    // Redirect back to current page
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
<title>Professor Announcements</title>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="/CAPSTONE_LMS_EHS/assets/prof/css/announcement.css" rel="stylesheet">
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
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/announcement.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn active"><span class="material-icons">campaign</span>Announcements</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/activities.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">event</span>Activities</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/module.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">menu_book</span>Module</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/quizzes.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">quiz</span>Quizzes</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/exam.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">school</span>Exam</a>
    <a href="/CAPSTONE_LMS_EHS/profdashboard/openreq/attendance.php?class_id=<?= $class_id ?>&section=<?= $section ?>&strand=<?= $strand ?>" class="filter-btn"><span class="material-icons">fact_check</span>Attendance</a>  
</div>

    <!-- Task Cards -->
    <div class="task-list">
        <!-- Add New Announcement -->
        <div class="task-card new-task" id="newAnnouncementBtn">
            <div class="task-icon"><span class="material-icons">add_circle</span></div>
            <div class="task-content">
                <h4>Create New Announcement</h4>
                <p>Click the plus icon to add a new announcement for this course.</p>
            </div>
        </div>

        <!-- Add Modal -->
        <div id="announcementModal" class="modal">
            <div class="modal-content">
                <span class="close-btn">&times;</span>
                <h3>Create New Announcement</h3>
                <form method="POST">
                    <input type="hidden" name="add_announcement" value="1">
                    <label>Title:</label>
                    <input type="text" name="title" placeholder="Enter announcement title" required>
                    <label>Description:</label>
                    <textarea name="description" rows="4" placeholder="Enter announcement details" required></textarea>
                    <button type="submit" class="submit-btn">Post</button>
                </form>
            </div>
        </div>

        <!-- Display Announcements -->
        <?php foreach ($announcements as $a): ?>
        <div class="task-card">
            <div class="task-icon"><span class="material-icons">campaign</span></div>
            <div class="task-content">
                <h4><?= htmlspecialchars($a['title']) ?><span class="task-tag" style="background-color:#e8f0ff;color:#004aad;">Announcements</span></h4>
                <p><?= nl2br(htmlspecialchars($a['desc'])) ?></p>
                <p>Date Posted: <?= date("F d, Y | h:i A", strtotime($a['date_posted'])) ?></p>
            </div>

            <!-- Dropdown -->
            <div class="task-meta">
                <span class="material-icons menu-icon" onclick="toggleDropdown(this)">more_vert</span>
                <div class="dropdown-menu">
                    <a href="#" onclick="openEditModal(<?= $a['announce_id'] ?>)">Edit</a>
                    <form method="POST" onsubmit="return confirm('Are you sure?');">
                        <input type="hidden" name="announce_id" value="<?= $a['announce_id'] ?>">
                        <button type="submit" name="delete_announcement" style="background:none;border:none;width:100%;text-align:left;">Delete</button>
                    </form>
                    <a href="#" onclick="openForwardModal(<?= $a['announce_id'] ?>)">Forward</a>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal" id="editModal<?= $a['announce_id'] ?>">
            <div class="modal-content">
                <span class="close-btn" onclick="closeEditModal(<?= $a['announce_id'] ?>)">&times;</span>
                <h3>Edit Announcement</h3>
                <form method="POST">
                    <input type="hidden" name="edit_announcement" value="1">
                    <input type="hidden" name="announce_id" value="<?= $a['announce_id'] ?>">
                    <label>Title:</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($a['title']) ?>" required>
                    <label>Description:</label>
                    <textarea name="description" rows="4" required><?= htmlspecialchars($a['desc']) ?></textarea>
                    <button type="submit" class="submit-btn">Update</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

<!-- Forward Modal -->
<div class="modal" id="forwardModal<?= $a['announce_id'] ?>">
    <div class="modal-content">
        <span class="close-btn" onclick="closeForwardModal(<?= $a['announce_id'] ?>)">&times;</span>
        <h3>Forward Announcement</h3>
        <form method="POST">
            <input type="hidden" name="forward_announcement" value="1">
            <input type="hidden" name="announce_id" value="<?= $a['announce_id'] ?>">
            
            <label>Class ID:</label>
            <input type="number" name="class_forward" placeholder="Enter class ID" required>

            
            <button type="submit" class="submit-btn">Forward</button>
        </form>
    </div>
</div>


</div>

<script>
const modal = document.getElementById("announcementModal");
const btn = document.getElementById("newAnnouncementBtn");
const span = modal.querySelector(".close-btn");

// Open modal
btn.onclick = () => modal.style.display = "flex";

// Close modal when clicking the X
span.onclick = () => modal.style.display = "none";

// Close modal when clicking outside the modal content
window.onclick = e => {
  if (e.target === modal) modal.style.display = "none";
};

// Dropdown menu toggle
function toggleDropdown(icon) {
    const menu = icon.nextElementSibling;
    document.querySelectorAll('.dropdown-menu').forEach(m => { if(m !== menu) m.style.display='none'; });
    menu.style.display = menu.style.display==='block'?'none':'block';
}

// Edit modal open/close
function openEditModal(id) { document.getElementById("editModal"+id).style.display="flex"; }
function closeEditModal(id) { document.getElementById("editModal"+id).style.display="none"; }

// Forward
function openForwardModal(id) { document.getElementById("forwardModal"+id).style.display="flex"; }
function closeForwardModal(id) { document.getElementById("forwardModal"+id).style.display="none"; }

function toggleDropdown(icon) {
    const menu = icon.nextElementSibling;
    document.querySelectorAll('.dropdown-menu').forEach(m => {
        if(m !== menu) m.style.display='none';
    });
    if(menu.style.display==='block'){
        menu.style.display='none';
    } else {
        menu.style.display='block';
        menu.style.zIndex = 999;  // ensure on top
    }
}



</script>
</body>
</html>
