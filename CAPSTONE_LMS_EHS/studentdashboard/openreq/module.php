<?php
include '../../config/db_connect.php';
session_start();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$student_id = $_SESSION['student_id'] ?? 0;

// 🔍 Validate student session
if (!$student_id) {
    die("⚠️ Error: No valid student session found. Please log in again.");
}

// Fetch course info
$class_id = $_GET['class_id'] ?? ($_SESSION['class_id'] ?? '');
$section  = $_GET['section']  ?? ($_SESSION['section'] ?? '');
$course   = $_GET['course']   ?? ($_SESSION['course'] ?? '');
$strand   = $_GET['strand']   ?? ($_SESSION['strand'] ?? ''); // 🧩 add this line

if (!empty($class_id)) $_SESSION['class_id'] = $class_id;
if (!empty($section))  $_SESSION['section']  = $section;
if (!empty($course))   $_SESSION['course']   = $course;
if (!empty($strand))   $_SESSION['strand']   = $strand; // 🧩 add this too

// Fetch student info
$stmt = $conn->prepare("SELECT first_name, last_name FROM students_account WHERE stud_id = :id");
$stmt->execute(['id' => $student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$student_name = $student ? $student['first_name'] . ' ' . $student['last_name'] : 'Unknown Student';

// Fetch modules
$stmt = $conn->prepare("SELECT * FROM prof_modules WHERE class_id = :class_id AND section = :section ORDER BY date_posted DESC");
$stmt->execute(['class_id' => $class_id, 'section' => $section]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// AJAX endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: text/plain');
    $action = $_POST['action'];
    $module_id = intval($_POST['module_id']);

    try {
        if ($action === 'log_time') {
            $duration = intval($_POST['duration']); // seconds

            // Check if an entry already exists
            $check = $conn->prepare("SELECT id, duration_seconds FROM stud_module_time WHERE stud_id = :sid AND module_id = :mid");
            $check->execute(['sid' => $student_id, 'mid' => $module_id]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $new_duration = $existing['duration_seconds'] + $duration;
                $update = $conn->prepare("
                    UPDATE stud_module_time 
                    SET duration_seconds = :dur, close_time = NOW() 
                    WHERE id = :id
                ");
                $update->execute([
                    'dur' => $new_duration,
                    'id' => $existing['id']
                ]);
                echo "✅ Updated total view time: {$new_duration}s";
            } else {
                // Check if valid student exists first (to avoid FK error)
                $scheck = $conn->prepare("SELECT stud_id FROM students_account WHERE stud_id = :sid");
                $scheck->execute(['sid' => $student_id]);
                if (!$scheck->fetch()) {
                    echo "❌ No valid student found for ID: {$student_id}";
                    exit;
                }

                // Insert new time record
                $insert = $conn->prepare("
                    INSERT INTO stud_module_time (stud_id, module_id, open_time, close_time, duration_seconds)
                    VALUES (:sid, :mid, NOW(), NOW(), :duration)
                ");
                $insert->execute([
                    'sid' => $student_id,
                    'mid' => $module_id,
                    'duration' => $duration
                ]);
                echo "✅ Time logged: {$duration}s";
            }
            exit;
        }

        if ($action === 'get_time') {
            $check = $conn->prepare("
                SELECT duration_seconds 
                FROM stud_module_time 
                WHERE stud_id = :sid AND module_id = :mid
            ");
            $check->execute(['sid' => $student_id, 'mid' => $module_id]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);
            echo $existing ? intval($existing['duration_seconds']) : 0;
            exit;
        }
    } catch (PDOException $e) {
        echo "❌ Error: " . $e->getMessage();
        exit;
    }
}


// --- Fetch latest profile image ---
try {
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
} catch (PDOException $e) {
    $profileImg = 'default_prof.jpg';
}

// --- Handle cancel submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_submission']) && isset($_POST['cancel_submission_id'])) {
    $cancel_id = intval($_POST['cancel_submission_id']);
    if ($cancel_id > 0) {
        try {
            // Optionally, fetch file path to delete from Supabase
            $stmt = $conn->prepare("SELECT file_path FROM stud_activity_submissions WHERE activity_id = :aid AND stud_id = :sid");
            $stmt->execute(['aid' => $cancel_id, 'sid' => $student_id]);
            $file = $stmt->fetchColumn();

            // Delete the record
            $stmt = $conn->prepare("DELETE FROM stud_activity_submissions WHERE activity_id = :aid AND stud_id = :sid");
            $stmt->execute(['aid' => $cancel_id, 'sid' => $student_id]);

            // Optional: Delete file from Supabase via REST API if exists
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

            // Redirect back to avoid resubmission
            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        } catch (PDOException $e) {
            die("Error cancelling submission: " . $e->getMessage());
        }
    }
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
<link href="/CAPSTONE_LMS_EHS/assets/student/css/module.css" rel="stylesheet">
<style>
/* Add Okay button style */
#okButton {
    margin-top: 10px;
    padding: 8px 15px;
    background-color: #1b8a4d;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
#okButton:hover {
    background-color: #166a3a;
}
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
  <a href="viewstrand.php" class="back-btn"><span class="material-icons">arrow_back</span></a>
  Course Modules (<?php echo htmlspecialchars("$strand - $section"); ?>)
</div>


  <div class="filter-buttons">
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/all.php?class_id=<?= urlencode($class_id) ?>&strand=<?= urlencode($strand) ?>&section=<?= urlencode($section) ?>" class="filter-btn">
    <span class="material-icons">grid_view</span>All
</a>
    <a href="announcement.php" class="filter-btn"><span class="material-icons">campaign</span>Announcements</a>
    <a href="activities.php" class="filter-btn"><span class="material-icons">event</span>Activities</a>
    <a href="module.php" class="filter-btn active"><span class="material-icons">menu_book</span>Modules</a>
    <a href="quizzes.php" class="filter-btn"><span class="material-icons">quiz</span>Quizzes</a>
    <a href="exam.php" class="filter-btn"><span class="material-icons">school</span>Exam</a>
    <a href="attendance.php" class="filter-btn"><span class="material-icons">fact_check</span>Attendance</a>
  </div>

  <div class="task-list">
    <?php foreach ($modules as $m): ?>
    <div class="task-card">
      <div class="task-icon"><span class="material-icons">menu_book</span></div>
      <div class="task-content">
        <h4><?php echo htmlspecialchars($m['title']); ?> 
          <span class="task-tag" style="background-color:#e0f7e9;color:#1b8a4d;">Module</span>
        </h4>
        <p><?php echo htmlspecialchars($m['desc']); ?></p>
        <div class="file-overview">
          <a href="#" class="file-name" data-file="<?php echo htmlspecialchars($m['file_path']); ?>" data-mid="<?php echo $m['module_id']; ?>">
            <?php echo htmlspecialchars($m['file_name']); ?>
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- File Overview Modal -->
<div id="fileOverviewModal" class="modal">
  <div class="modal-content file-viewer">
    <span class="close-btn">&times;</span>
    <h2>File Overview</h2>
    <div id="viewTimer" style="font-weight:bold;color:#004aad;margin-bottom:10px;">Viewing time: 0s</div>
    <iframe id="fileFrame" src="" frameborder="0" style="width:100%;height:400px;"></iframe>
    <button id="okButton">Okay</button>
  </div>
</div>

<script>
const modal = document.getElementById('fileOverviewModal');
const iframe = document.getElementById('fileFrame');
const closeBtn = modal.querySelector('.close-btn');
const okButton = document.getElementById('okButton');
const timerDisplay = document.getElementById('viewTimer');
let openTime = 0;
let activeModuleId = null;
let timerInterval = null;

// Open module file
document.querySelectorAll('.file-name').forEach(link => {
  link.addEventListener('click', async e => {
    e.preventDefault();
    const fileSrc = link.dataset.file;
    const mid = link.dataset.mid;
    activeModuleId = mid;

    // Fetch previous accumulated time
    let prevTime = 0;
    try {
      const formData = new FormData();
      formData.append('action', 'get_time');
      formData.append('module_id', mid);
      const res = await fetch(window.location.href, { method: 'POST', body: formData });
      prevTime = parseInt(await res.text()) || 0;
    } catch (err) { console.error(err); }

    openTime = Date.now();
    let seconds = prevTime;
    timerDisplay.textContent = `Viewing time: ${seconds}s`;

    timerInterval = setInterval(() => {
      seconds++;
      timerDisplay.textContent = `Viewing time: ${seconds}s`;
    }, 1000);

    const ext = fileSrc.split('.').pop().toLowerCase();
    if (ext === 'pdf') {
      iframe.src = `https://docs.google.com/gview?url=${encodeURIComponent(fileSrc)}&embedded=true`;
    } else if (['doc','docx','ppt','pptx'].includes(ext)) {
      iframe.src = `https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(fileSrc)}`;
    } else {
      iframe.src = fileSrc;
    }

    modal.style.display = 'flex';
  });
});

// Save time and close modal
function saveTime() {
    if (!activeModuleId) return;
    const duration = Math.floor((Date.now() - openTime) / 1000);
    if (duration <= 0) return;

    const formData = new FormData();
    formData.append('action', 'log_time');
    formData.append('module_id', activeModuleId);
    formData.append('duration', duration);

    fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => res.text())
        .then(txt => console.log(txt))
        .catch(err => console.error(err));

    clearInterval(timerInterval);
    modal.style.display = 'none';
    iframe.src = '';
    activeModuleId = null;
    openTime = 0;
}

okButton.onclick = saveTime;
closeBtn.onclick = saveTime;
window.onclick = e => { if(e.target === modal) saveTime(); };
</script>

</body>
</html>
