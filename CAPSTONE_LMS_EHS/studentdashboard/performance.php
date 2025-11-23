<?php
session_start();
include '../config/db_connect.php'; // Supabase PDO connection

if (!isset($_SESSION['student_id'])) {
    die("Access denied. No student logged in.");
}

$student_id = $_SESSION['student_id'];

// Fetch student info
$stmt = $conn->prepare("SELECT first_name, last_name FROM students_account WHERE stud_id = :sid");
$stmt->execute(['sid'=>$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$student_name = $student ? $student['first_name'].' '.$student['last_name'] : 'Student';

// Fetch classes the student is enrolled in
$stmt = $conn->prepare("
    SELECT pc.class_id, pc.course_name, pc.strand, pc.section, pc.teacher_id
    FROM prof_courses pc
    JOIN student_enrollments se ON se.course_id = pc.course_id
    WHERE se.student_id = :sid
");
$stmt->execute(['sid'=>$student_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch predictions for all classes
$predictions = [];
foreach ($classes as $c) {
    $stmt2 = $conn->prepare("
        SELECT q1_grade, q2_grade, q3_grade, q4_grade, final_grade, pass_fail_status
        FROM student_predictions
        WHERE stud_id=:sid AND class_id=:cid
    ");

    $stmt2->execute(['sid'=>$student_id,'cid'=>$c['class_id']]);
    $pred = $stmt2->fetch(PDO::FETCH_ASSOC);
    $predictions[$c['class_id']] = $pred ?: [
        'q1_grade'=>0, 'q2_grade'=>0, 'q3_grade'=>0, 'q4_grade'=>0,
        'final_grade'=>0, 'pass_fail_status'=>'Unknown'
    ];
}

$imageQuery = $conn->prepare("SELECT image_url FROM student_profile_images WHERE student_id = :student_id ORDER BY uploaded_at DESC LIMIT 1");
$imageQuery->execute(['student_id' => $student_id]);
$image = $imageQuery->fetch(PDO::FETCH_ASSOC);

$profileImg = $image ? $image['image_url'] : 'default_prof.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>LMS Dashboard</title>
<link href="/CAPSTONE_LMS_EHS/assets/student/css/performance.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style>
.modal {
  display: none; /* hide by default */
  position: fixed;
  z-index: 999;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  justify-content: center; /* will apply when JS sets display:flex */
  align-items: center;
  padding: 10px;
}

.modal-content {
  position: relative; /* needed for absolute positioning of the X button */
  background: #fff;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  padding: 30px 25px;
  text-align: center;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.modal-content h3 {
  margin-bottom: 20px;
  font-size: 20px;
  color: #004aad;
}

.modal-content table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

.modal-content th,
.modal-content td {
  border: 1px solid #ddd;
  padding: 10px 12px;
  font-size: 14px;
  text-align: center;
}

.modal-content th {
  background-color: #004aad;
  color: #fff;
  font-weight: 600;
}

.modal-content p {
  margin-top: 10px;
  font-weight: bold;
  color: #333;
}

/* Close button positioned at top-right */
.close {
  position: absolute;
  top: 15px;
  right: 15px;
  cursor: pointer;
  font-weight: 600;
  font-size: 22px;
  background: transparent;
  color: #333;
  border: none;
  padding: 5px;
  transition: color 0.2s;
}

.close:hover {
  color: #004aad;
}

/* Close modal button at bottom */
.close-modal {
  cursor: pointer;
  font-weight: 600;
  font-size: 16px;
  padding: 8px 15px;
  background: #004aad;
  color: #fff;
  border: none;
  border-radius: 6px;
  margin-top: 15px;
  transition: background 0.2s;
}

.close-modal:hover {
  background: #003b87;
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
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/userhome.php" ><span class="material-icons">dashboard</span> Dashboard</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/courses.php"><span class="material-icons">menu_book</span> Courses</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/performance.php"class="active"><span class="material-icons">bar_chart</span> Performance</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/reminders.php" ><span class="material-icons">notifications</span> Reminders</a>
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
    <span>Performance</span>
    <button class="menu-btn" id="menuBtn">&#9776;</button>
  </div>

  <?php foreach($classes as $c): 
      $pred = $predictions[$c['class_id']];
  ?>
  <div class="rewards" data-class="<?= $c['class_id'] ?>" 
       data-q1="<?= $pred['q1_grade'] ?>" 
       data-q2="<?= $pred['q2_grade'] ?>" 
       data-q3="<?= $pred['q3_grade'] ?>" 
       data-q4="<?= $pred['q4_grade'] ?>" 
       data-final="<?= $pred['final_grade'] ?>" 
       data-status="<?= htmlspecialchars($pred['pass_fail_status']) ?>">
    <div>
      <h4><?= htmlspecialchars($c['course_name']) ?></h4>
      <p><strong>Strand & Section:</strong> <?= htmlspecialchars($c['strand'].'-'.$c['section']) ?></p>
      <p>Teacher ID: <?= htmlspecialchars($c['teacher_id']) ?></p>
      <p>IT 103<br><br><span class="statusDisplay">Status: <?= htmlspecialchars($pred['pass_fail_status']) ?></span></p>
    </div>
    <button class="btn view-records">View Records</button>
  </div>
  <?php endforeach; ?>

</div>

<div id="recordsModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3 id="modalCourseName">Performance</h3>
    <table>
      <tr><th>Category</th><th>Score</th></tr>
      <tr><td>Quarter 1</td><td id="q1">0</td></tr>
      <tr><td>Quarter 2</td><td id="q2">0</td></tr>
      <tr><td>Quarter 3</td><td id="q3">0</td></tr>
      <tr><td>Quarter 4</td><td id="q4">0</td></tr>
      <tr><td>Final Grade</td><td id="finalGrade">0</td></tr>
    </table>
    <p id="modalStatus" style="margin-top:15px;font-weight:bold;">Status: Unknown</p>
    <button class="btn close-modal">Close</button>
  </div>
</div>

<script>
document.querySelectorAll('.view-records').forEach(btn => {
  btn.addEventListener('click', () => {
    const rewards = btn.closest('.rewards');
    document.getElementById('modalCourseName').innerText = 'Performance in ' + rewards.querySelector('h4').innerText;
    document.getElementById('q1').innerText = rewards.dataset.q1;
    document.getElementById('q2').innerText = rewards.dataset.q2;
    document.getElementById('q3').innerText = rewards.dataset.q3;
    document.getElementById('q4').innerText = rewards.dataset.q4;
    document.getElementById('finalGrade').innerText = rewards.dataset.final;
    document.getElementById('modalStatus').innerText = 'Status: ' + rewards.dataset.status;
    document.getElementById('recordsModal').style.display = 'flex';
  });
});

document.querySelectorAll('.close, .close-modal').forEach(btn => {
  btn.addEventListener('click', () => {
    btn.closest('.modal').style.display = 'none';
  });
});

window.addEventListener('click', e => {
  if(e.target.classList.contains('modal')) e.target.style.display = 'none';
});
</script>
</body>
</html>
