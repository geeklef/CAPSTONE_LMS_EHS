<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/CAPSTONE_LMS_EHS/config/db_connect.php';

// ✅ Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    die("Access denied. Student not logged in.");
}
$student_id = $_SESSION['student_id'];

// ✅ Fetch course/class info from GET or SESSION
$teacher_id = $_GET['teacher_id'] ?? ($_SESSION['teacher_id'] ?? 1);
$strand = $_GET['strand'] ?? ($_SESSION['strand'] ?? '');
$section = $_GET['section'] ?? ($_SESSION['section'] ?? '');
$course_name = $_GET['course'] ?? ($_SESSION['course'] ?? '');

if (!empty($strand)) $_SESSION['strand'] = $strand;
if (!empty($section)) $_SESSION['section'] = $section;
if (!empty($course_name)) $_SESSION['course'] = $course_name;

// ✅ Get class_id from prof_courses
$class_id = null;
$stmt = $conn->prepare("SELECT class_id FROM prof_courses WHERE teacher_id = :teacher_id AND strand = :strand AND section = :section LIMIT 1");
$stmt->execute(['teacher_id'=>$teacher_id,'strand'=>$strand,'section'=>$section]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) $class_id = $row['class_id'];
$stmt = null;

// ✅ Fetch student name
$student_name = 'Student';
$profileImg = 'default_prof.jpg'; // default
$stmt = $conn->prepare("SELECT first_name, last_name FROM students_account WHERE stud_id = :id");
$stmt->execute(['id'=>$student_id]);
if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $student_name = $row['first_name'].' '.$row['last_name'];
}

// ✅ Fetch student profile image if available
$stmt = $conn->prepare("SELECT image_url FROM student_profile_images WHERE student_id = :id ORDER BY uploaded_at DESC LIMIT 1");
$stmt->execute(['id'=>$student_id]);
if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $profileImg = $row['image_url'];
}

// ✅ Handle marking attendance via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $prof_attendance_id = intval($_POST['prof_attendance_id']);
    if ($prof_attendance_id <= 0) {
        echo json_encode(['status'=>'error','message'=>'Invalid attendance ID']);
        exit();
    }

    // Prevent duplicate marking
    $check = $conn->prepare("SELECT * FROM stud_attendance WHERE student_id = :student_id AND prof_attendance_id = :attendance_id");
    $check->execute(['student_id'=>$student_id,'attendance_id'=>$prof_attendance_id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        // Determine if Late based on current time vs due_time
        $stmt = $conn->prepare("SELECT due_time, due_date FROM prof_attendance WHERE attendance_id = :id");
        $stmt->execute(['id'=>$prof_attendance_id]);
        $att = $stmt->fetch(PDO::FETCH_ASSOC);
        $status = 'Present';
        if ($att) {
            $dueTimestamp = strtotime($att['due_date'].' '.$att['due_time']);
            if (time() > $dueTimestamp) $status = 'Late';
        }

        // Insert attendance safely using direct SQL (works with Supabase pgBouncer)
$query = "
    INSERT INTO stud_attendance (student_id, prof_attendance_id, status, date_marked)
    VALUES ($student_id, $prof_attendance_id, '$status', NOW())
";

$result = $conn->exec($query);

if ($result === false) {
    echo json_encode(['status'=>'error','message'=>'Insert failed']);
    exit();
}

    }

    echo json_encode(['status'=>'success']);
    exit();
}

// ✅ Fetch active attendances for this class
$attendance_list = [];
$stmt = $conn->prepare("
    SELECT a.*, sa.status 
    FROM prof_attendance a
    LEFT JOIN stud_attendance sa 
    ON a.attendance_id = sa.prof_attendance_id AND sa.student_id = :sid
    WHERE a.teacher_id = :teacher_id AND a.class_id = :class_id AND a.section = :section
    ORDER BY a.attendance_id DESC
");
$stmt->execute(['sid'=>$student_id,'teacher_id'=>$teacher_id,'class_id'=>$class_id,'section'=>$section]);
$attendance_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Set status to 'Absent' if no record and past due
foreach($attendance_list as &$att) {
    if (!$att['status']) {
        $dueTimestamp = strtotime($att['due_date'].' '.$att['due_time']);
        $att['status'] = (time() > $dueTimestamp) ? 'Absent' : 'Not Marked';
    }
}
unset($att);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance</title>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="/CAPSTONE_LMS_EHS/assets/student/css/all.css" rel="stylesheet">
<style>
/* Modal Styles */
.modal { display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.4); justify-content:center; align-items:center; }
.modal-content { background:white; border-radius:12px; width:100%; max-width:400px; padding:25px; text-align:center; }
.close-btn { cursor:pointer; font-weight:600; }
.submit-btn { background:#004aad;color:white;padding:10px 22px;border:none;border-radius:25px;cursor:pointer; }
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
    <a href="/CAPSTONE_LMS_EHS/auth/login.php"><span class="material-icons">logout</span> Logout</a>
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
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/courses.php" class="back-btn"><span class="material-icons">arrow_back</span></a>
    Course Requirements (<?= htmlspecialchars($strand).' - '.htmlspecialchars($section) ?>)
</div>

  <div class="filter-buttons">
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/all.php?class_id=<?= urlencode($class_id) ?>&strand=<?= urlencode($strand) ?>&section=<?= urlencode($section) ?>" class="filter-btn">
    <span class="material-icons">grid_view</span>All
</a>
    <a href="announcement.php" class="filter-btn"><span class="material-icons">campaign</span>Announcements</a>
    <a href="activities.php" class="filter-btn"><span class="material-icons">event</span>Activities</a>
    <a href="module.php" class="filter-btn "><span class="material-icons">menu_book</span>Modules</a>
    <a href="quizzes.php" class="filter-btn"><span class="material-icons">quiz</span>Quizzes</a>
    <a href="exam.php" class="filter-btn"><span class="material-icons">school</span>Exam</a>
    <a href="attendance.php" class="filter-btn active"><span class="material-icons">fact_check</span>Attendance</a>
  </div>

<div class="task-list">
<?php foreach($attendance_list as $attendance): ?>
<div class="task-card">
    <div class="task-icon"><span class="material-icons">fact_check</span></div>
    <div class="task-content">
        <h4><?= htmlspecialchars($attendance['title']) ?> 
            <?php 
            // Status badge
            $status_color = match(strtolower($attendance['status'])) {
                'present' => '#e0f7e9', // greenish
                'late' => '#fff4e5',    // orange
                'absent' => '#fde0dc',  // red/pink
                default => '#f0f0f0',   // gray for not marked
            };
            $status_text = htmlspecialchars($attendance['status']);
            ?>
            <span class="task-tag" style="background-color:<?= $status_color ?>;color:#000;"><?= $status_text ?></span>
        </h4>
        <p><?= nl2br(htmlspecialchars($attendance['desc'])) ?></p>
        <p>Date Posted: <?= date("F j, Y", strtotime($attendance['date_posted'])) ?></p>
        <p>Due Date: <?= date("F j, Y", strtotime($attendance['due_date'])) ?> | <?= htmlspecialchars($attendance['due_time']) ?></p>
    </div>

    <?php if(strtolower($attendance['status']) === 'not marked'): ?>
        <a href="#" class="task-meta mark-attendance" data-id="<?= $attendance['attendance_id'] ?>">Mark Attendance</a>
    <?php else: ?>
        <div class="task-meta" style="color:gray; cursor:default;">Attendance Recorded</div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<!-- Mark Attendance Modal -->
<div id="attendanceModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" id="closeModal">&times;</span>
    <h2>Attendance</h2>
    <p>Are you sure you want to mark your attendance for today?</p>
    <button class="submit-btn" id="confirmAttendance">Present</button>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let selectedAttendanceId = null;

$('.mark-attendance').click(function(e){
    e.preventDefault();
    selectedAttendanceId = parseInt($(this).data('id'));
    if(!selectedAttendanceId) return alert("Invalid attendance ID");
    $('#attendanceModal').fadeIn(200).css('display','flex');
});

$('#closeModal').click(function(){ $('#attendanceModal').fadeOut(200); });

$('#confirmAttendance').click(function(){
    if(selectedAttendanceId){
        $.post(window.location.href, { mark_attendance: 1, prof_attendance_id: selectedAttendanceId }, function(resp){
            let res = JSON.parse(resp);
            if(res.status === 'success'){
                alert('Attendance marked successfully!');
                location.reload();
            } else {
                alert(res.message);
            }
        });
    }
});
</script>

</div>
</body>
</html>
