<?php
// activities.php (student-side)
// Place this file where your student pages are. Adjust include path if necessary.
include '../../config/db_connect.php'; // your PDO $conn
session_start();

// --- Config ---
// Supabase config (set your real anon/service key)
$supabaseUrl = 'https://fgsohkazfoskhxhndogu.supabase.co';
$supabaseBucket = 'student_submissions';
$supabaseAnonKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZnc29oa2F6Zm9za2h4aG5kb2d1Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjA0MzU4MDIsImV4cCI6MjA3NjAxMTgwMn0.EHpoxrGBEx9j2MYQPbhGo-l65hmfijmBBRY65xMVY7c'; // <<< REPLACE THIS

// --- Session / GET params ---
$student_id = $_SESSION['student_id'] ?? 0;
$teacher_id = $_GET['teacher_id'] ?? ($_SESSION['teacher_id'] ?? 0);
$strand     = $_GET['strand'] ?? ($_SESSION['strand'] ?? '');
$section    = $_GET['section'] ?? ($_SESSION['section'] ?? '');
$course     = $_GET['course'] ?? ($_SESSION['course'] ?? '');

// minimal access check
if ($student_id == 0) {
    die("Access denied. Please login.");
}

// persist course/strand/section in session for navigation convenience
if (!empty($teacher_id)) $_SESSION['teacher_id'] = $teacher_id;
if (!empty($strand)) $_SESSION['strand'] = $strand;
if (!empty($section)) $_SESSION['section'] = $section;
if (!empty($course)) $_SESSION['course'] = $course;

// helper: fetch student name
$student_name = 'Student';
try {
    $s = $conn->prepare("SELECT first_name, last_name FROM students_account WHERE stud_id = :sid LIMIT 1");
    $s->execute(['sid' => $student_id]);
    if ($r = $s->fetch(PDO::FETCH_ASSOC)) $student_name = trim($r['first_name'].' '.$r['last_name']);
} catch (PDOException $e) {
    // ignore — fallback name used
}

// helper: fetch teacher name (for path)
$teacher_name = 'Teacher';
try {
    $s = $conn->prepare("SELECT first_name, last_name FROM teachers_account WHERE teacher_id = :tid LIMIT 1");
    $s->execute(['tid' => $teacher_id]);
    if ($r = $s->fetch(PDO::FETCH_ASSOC)) $teacher_name = trim($r['first_name'].' '.$r['last_name']);
} catch (PDOException $e) {
    // ignore
}
$teacher_folder = preg_replace('/\s+/', '', $teacher_name);
$student_folder = preg_replace('/\s+/', '', $student_name);
$section_folder = preg_replace('/\s+/', '', "{$strand}-{$section}");

// --- Get class_id if needed (some setups use class_id instead of course) ---
$class_id = $_GET['class_id'] ?? ($_SESSION['class_id'] ?? '');
if (empty($class_id) && !empty($course)) {
    try {
        $q = $conn->prepare("SELECT class_id FROM prof_courses WHERE course_name = :course LIMIT 1");
        $q->execute(['course' => $course]);
        $class_id = $q->fetchColumn() ?: '';
        if (!empty($class_id)) $_SESSION['class_id'] = $class_id;
    } catch (PDOException $e) { /* ignore */ }
}

// ----------------------
// Server handlers
// ----------------------

// 1) Record "opened_at" (AJAX POST with action=open_activity)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'open_activity')) {
    $activity_id = intval($_POST['activity_id'] ?? 0);
    if ($activity_id > 0) {
        try {
            // Insert a row if not exists, set opened_at to now() if null
            // Postgres: use ON CONFLICT to preserve earlier opened_at but set if null
            $sql = "
              INSERT INTO stud_activity_submissions (activity_id, stud_id, class_id, opened_at)
              VALUES (:aid, :sid, :cid, NOW())
              ON CONFLICT (activity_id, stud_id) DO UPDATE
              SET opened_at = COALESCE(stud_activity_submissions.opened_at, NOW())
            ";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'aid' => $activity_id,
                'sid' => $student_id,
                'cid' => $class_id
            ]);
            echo json_encode(['status' => 'ok']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'msg' => 'Invalid activity id']);
    }
    exit;
}

// 2) Handle submission (form POST with file)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_activity_id'])) {
    $activity_id = intval($_POST['submit_activity_id']);
    if ($activity_id <= 0) {
        die("Invalid activity id.");
    }

    if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
        die("File upload error.");
    }

    // prepare upload path and name
    $origName = basename($_FILES['submission_file']['name']);
    $timestamp = time();
    $newFileName = $timestamp . '_' . preg_replace('/[^A-Za-z0-9_\-\.]/','_', $origName);
    $remotePath = "$teacher_folder/$section_folder/submitted/$student_folder/$newFileName";

    // upload to Supabase storage using REST API (PUT/POST raw bytes)
    $fileContents = file_get_contents($_FILES['submission_file']['tmp_name']);
    $uploadUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/$supabaseBucket/" . rawurlencode($remotePath);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $uploadUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $fileContents,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $supabaseAnonKey",
            "Content-Type: application/octet-stream"
        ],
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err || ($httpCode < 200 || $httpCode >= 300)) {
        // fallback: attempt local move (if desired) OR fail
        // For now, we die with helpful message
        die("Upload to Supabase failed: $err (HTTP $httpCode). Response: $resp");
    }

    // public URL to stored file (Supabase public storage path)
    $publicUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/public/$supabaseBucket/" . rawurlencode($remotePath);

    // Insert or update submission record with submitted_at and file info
    try {
    $sql = "
      INSERT INTO public.stud_activity_submissions 
        (activity_id, stud_id, class_id, opened_at, submitted_at, file_name, file_path)
      VALUES 
        (:aid, :sid, :cid, 
         (SELECT opened_at 
            FROM public.stud_activity_submissions 
           WHERE activity_id = :aid AND stud_id = :sid), 
         NOW(), :fname, :fpath)
      ON CONFLICT (activity_id, stud_id) DO UPDATE
      SET submitted_at = NOW(),
          file_name = EXCLUDED.file_name,
          file_path = EXCLUDED.file_path
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        'aid'   => $activity_id,
        'sid'   => $student_id,
        'cid'   => $class_id,
        'fname' => $origName,
        'fpath' => $publicUrl
    ]);
} catch (PDOException $e) {
    die("Database error saving submission: " . $e->getMessage());
}


    // redirect back to same page to avoid resubmission
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
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
    $profileImg = 'abo.jpg';
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


// ----------------------
// Fetch activities for display (student view only)
// ----------------------
try {
    $sql = "SELECT a.* FROM prof_activities a
            WHERE a.class_id = :class_id AND a.section = :section
            ORDER BY a.activity_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['class_id' => $class_id, 'section' => $section]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching activities: " . $e->getMessage());
}

// also get current submissions map so we can show "View File" if submitted
$subs = [];
try {
    $s = $conn->prepare("SELECT activity_id, file_name, file_path, submitted_at, opened_at FROM stud_activity_submissions WHERE stud_id = :sid");
    $s->execute(['sid' => $student_id]);
    foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $row) $subs[$row['activity_id']] = $row;
} catch (PDOException $e) {
    // ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Course Activities</title>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="/CAPSTONE_LMS_EHS/assets/student/css/activities.css" rel="stylesheet">
  <style>
    /* Minimal inline tweaks to ensure layout matches your student HTML */
    .task-tag{background-color:#fff2cc;color:#b88a00;padding:4px 8px;border-radius:6px;margin-left:8px;font-size:0.8rem;}
    .task-list .task-card {margin-bottom:16px;}
    .submit-btn {background:#004aad;color:#fff;padding:8px 12px;border:none;border-radius:6px;cursor:pointer;}
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
      Course Activities (<?php echo htmlspecialchars($strand . ' - ' . $section); ?>)
    </div>

    <div class="filter-buttons">
   <a href="/CAPSTONE_LMS_EHS/studentdashboard/all.php?class_id=<?= urlencode($class_id) ?>&strand=<?= urlencode($strand) ?>&section=<?= urlencode($section) ?>" class="filter-btn">
    <span class="material-icons">grid_view</span>All
</a>
      <a href="announcement.php" class="filter-btn"><span class="material-icons">campaign</span>Announcements</a>
      <a href="activities.php" class="filter-btn active"><span class="material-icons">event</span>Activities</a>
      <a href="module.php" class="filter-btn "><span class="material-icons">menu_book</span>Modules</a>
      <a href="quizzes.php" class="filter-btn"><span class="material-icons">quiz</span>Quizzes</a>
      <a href="exam.php" class="filter-btn"><span class="material-icons">school</span>Exam</a>
      <a href="attendance.php" class="filter-btn"><span class="material-icons">fact_check</span>Attendance</a>
    </div>

    <div class="task-list">
      <?php if (empty($activities)): ?>
        <p style="padding:20px;">No activities posted yet.</p>
      <?php else: foreach ($activities as $act): 
          $aid = (int)$act['activity_id'];
          $submitted = $subs[$aid]['file_path'] ?? null;
      ?>
        <div class="task-card">
          <div class="task-icon"><span class="material-icons">event</span></div>
          <div class="task-content">
            <h4><?php echo htmlspecialchars($act['title']); ?>
              <span class="task-tag">Activity</span>
            </h4>
            <p><?php echo nl2br(htmlspecialchars($act['desc'])); ?></p>
            <p>Date Posted: <?php echo htmlspecialchars($act['date_posted'] ?? ''); ?></p>
            <p>Due Date: <?php echo htmlspecialchars(($act['due_date'] ?? '') . ' | ' . ($act['due_time'] ?? '')); ?></p>
            <?php if (!empty($act['file_path'])): ?>
              <div class="file-overview">
                <!-- File link -->
<a href="javascript:void(0);" 
   data-file="<?php echo htmlspecialchars($act['file_path']); ?>" 
   class="file-name">
  <?php echo htmlspecialchars($act['file_name'] ?? 'Attachment'); ?>
</a>

              </div>
            <?php endif; ?>

            <?php if ($submitted): ?>
              <p><strong>Your Submission:</strong> <a href="<?php echo htmlspecialchars($subs[$aid]['file_path']); ?>" target="_blank">
                <?php echo htmlspecialchars($subs[$aid]['file_name']); ?></a>
                <br><small>Submitted at: <?php echo htmlspecialchars($subs[$aid]['submitted_at']); ?></small>
              </p>
              <form method="post" onsubmit="return confirm('Cancel submission?');">
                <input type="hidden" name="cancel_submission_id" value="<?php echo $aid; ?>">
                <button type="submit" name="cancel_submission" style="background:#e74c3c;border:none;color:#fff;padding:6px 10px;border-radius:6px;">Cancel Submission</button>
              </form>
            <?php else: ?>
              <button class="submit-btn" onclick="openSubmitModal(<?php echo $aid; ?>)">Submit Work</button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- Submit Modal -->
  <div id="submitModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" >&times;</span>
      <h3>Submit Your Work</h3>
      <form id="submitForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="submit_activity_id" id="submit_activity_id" value="">
        <div style="margin:12px 0;">
          <input type="file" name="submission_file" required accept=".pdf,.doc,.docx,.jpg,.png">
        </div>
        <div>
          <button type="button" class="cancel-btn" id="cancelSubmit">Cancel</button>
          <button type="submit" class="submit-btn">Upload & Submit</button>
        </div>
      </form>
    </div>
  </div>

    <!-- ===== File Preview Modal ===== -->
    <div id="fileOverviewModal" class="modal">
      <div class="modal-content file-viewer">
        <span class="close-btn">&times;</span>
        <h2>File Overview</h2>
        <iframe id="fileFrame" frameborder="0"></iframe>
      </div>
    </div>


<script>
  // open modal and record opened_at via AJAX
  function openSubmitModal(activityId) {
    // record opened time
    fetch(window.location.href, {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: 'action=open_activity&activity_id=' + encodeURIComponent(activityId)
    }).catch(()=>{/* ignore errors */});

    document.getElementById('submit_activity_id').value = activityId;
    const modal = document.getElementById('submitModal');
    modal.style.display = 'flex';
  }

  // modal close handlers
  document.querySelector('#submitModal .close-btn').onclick = function(){ document.getElementById('submitModal').style.display='none'; }
  document.getElementById('cancelSubmit').onclick = function(){ document.getElementById('submitModal').style.display='none'; }

  // clicking outside closes
  window.addEventListener('click', function(e){
    const modal = document.getElementById('submitModal');
    if (e.target === modal) modal.style.display = 'none';
  });

// ===== File Preview Modal =====
const fileLinks = document.querySelectorAll('.file-name'); // only once
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
        } else if (['doc','docx','ppt','pptx','xls','xlsx'].includes(ext)) {
            fileFrame.src = `https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(fileSrc)}`;
        } else if (['jpg','jpeg','png','gif','bmp'].includes(ext)) {
            fileFrame.src = fileSrc;
        } else {
            window.open(fileSrc,'_blank');
            return;
        }
        fileModal.style.display = 'flex';
    });
});

closeFileBtn.addEventListener('click', () => { fileModal.style.display = 'none'; fileFrame.src=''; });
window.addEventListener('click', e => { if(e.target === fileModal){ fileModal.style.display='none'; fileFrame.src=''; } });

</script>

</body>
</html>
