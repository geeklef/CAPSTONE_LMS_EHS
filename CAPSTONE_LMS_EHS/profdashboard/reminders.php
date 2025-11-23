<?php
session_start();
include '../config/db_connect.php';

if(!isset($_SESSION['teacher_id'])) die("Access denied. No teacher logged in.");
$teacher_id = $_SESSION['teacher_id'];

// Fetch Teacher Name
$teacher_name = 'Professor';
$stmt = $conn->prepare("SELECT first_name,last_name FROM teachers_account WHERE teacher_id=:teacher_id");
$stmt->execute(['teacher_id'=>$teacher_id]);
if($row=$stmt->fetch(PDO::FETCH_ASSOC)){
    $teacher_name = $row['first_name'].' '.$row['last_name'];
}

// Fetch all classes handled by professor
$stmt = $conn->prepare("SELECT class_id, strand, section, course_name FROM prof_courses WHERE teacher_id=:teacher_id");
$stmt->execute(['teacher_id'=>$teacher_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$notifications = [];

foreach($classes as $class){
    $class_id = $class['class_id'];
    $strand = $class['strand'];
    $section = $class['section'];
    $course_name = $class['course_name'];

    // Get students in this class with pass/fail status
    $stmt = $conn->prepare("
        SELECT s.stud_id, s.first_name, s.last_name, sp.final_grade, sp.pass_fail_status
        FROM students_account s
        JOIN student_enrollments se ON se.student_id = s.stud_id
        JOIN prof_courses pc ON pc.class_id = se.course_id
        LEFT JOIN student_predictions sp ON sp.stud_id=s.stud_id AND sp.class_id=pc.class_id
        WHERE pc.class_id=:class_id
    ");
    $stmt->execute(['class_id'=>$class_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($students as $s){
        $status = strtolower($s['pass_fail_status'] ?? '');
        if($status==='fail' || $status==='pass'){
            $notifications[] = [
                'student_name' => $s['first_name'].' '.$s['last_name'],
                'class_name'   => $course_name.' - '.$strand.' '.$section,
                'date'         => date('F j, Y'),
                'status'       => $s['pass_fail_status'],
                'final_grade'  => $s['final_grade'] ?? 'N/A',
                'message'      => $status==='fail' ? 
                                  "Heads up! This student is close to failing — intervene promptly." :
                                  "Good job! The student is passing. Encourage continued progress.",
                'file_link'    => '' // Optional: put reviewer file if available
            ];
        }
    }
}

// Optional: professor profile image
$imageQuery = $conn->prepare("SELECT image_url FROM teacher_profile_images WHERE teacher_id=:teacher_id ORDER BY uploaded_at DESC LIMIT 1");
$imageQuery->execute(['teacher_id'=>$teacher_id]);
$image = $imageQuery->fetch(PDO::FETCH_ASSOC);
$profileImg = $image ? $image['image_url'] : 'default_prof.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Reminders - Professor</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
<style>
@import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap');

/* === keep your exact student reminders CSS === */
* {margin:0;padding:0;box-sizing:border-box;font-family:"Quicksand",sans-serif;}
body {display:flex;height:100vh;background-color:#f4f7f9;}
.sidebar {position:fixed;top:0;left:0;height:100vh;width:250px;background-color:#004aad;color:white;padding:20px;overflow-y:auto;transition:left 0.3s ease;z-index:999;}
.sidebar h3 {margin-left:40px;margin-bottom:40px;}
.sidebar .menu a {display:block;padding:10px 0;color:white;text-decoration:none;margin-bottom:10px;}
.sidebar .menu a.active {color:white;font-weight:600;position:relative;}
.sidebar .menu a.active::before {content:"";position:absolute;left:-20px;top:0;bottom:0;width:5px;background-color:white;border-radius:10px;}
.sidebar .menu a .material-icons {font-size:22px;}

.main {margin-left:250px;flex:1;padding:20px;transition:margin-left 0.3s ease;}
.topbar {background-color:#004aad;padding:20px;color:white;font-size:24px;border-radius:10px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;}
.menu-btn {display:none;background:none;border:none;color:white;font-size:26px;cursor:pointer;}

/* Card styles */
.rewards {display:flex;align-items:center;gap:15px;padding:20px;border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,0.1);font-size:15px;border:2px solid transparent;margin-bottom:20px;transition: transform 0.2s ease, box-shadow 0.2s ease;}
.rewards:hover {transform:translateY(-3px);box-shadow:0 6px 15px rgba(0,0,0,0.15);}
.icon-box {display:flex;justify-content:center;align-items:center;width:60px;height:60px;border:2px solid;border-radius:10px;margin-right:30px;margin-left:30px;}
.icon-box .material-icons {font-size:35px;}
.rewards.fail {background-color:#ffe5e5;border-color:#ff9b9b;color:#b30000;}
.rewards.fail .icon-box {border-color:#b30000;color:#b30000;}
.rewards.great {background-color:#e8fce8;border-color:#a7f0a7;color:#007a00;}
.rewards.great .icon-box {border-color:#007a00;color:#007a00;}
.rewards div {display:flex;flex-direction:column;justify-content:center;line-height:1.6;}
.rewards h4 {margin-bottom:5px;}
.rewards p {margin:3px 0;}
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
@media (max-width:768px){.sidebar{left:-250px}.sidebar.active{left:0}.main{margin-left:0}.menu-btn{display:block}.rewards{flex-direction:column;align-items:flex-start;gap:10px}}
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
      <a href="/CAPSTONE_LMS_EHS/profdashboard/course.php"><span class="material-icons">menu_book</span> Courses</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/performance.php"><span class="material-icons">bar_chart</span> Performance</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/reminders.php"class="active"><span class="material-icons">notifications</span> Reminders</a>
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
    <span>Reminders</span>
    <button class="menu-btn" id="menuBtn">&#9776;</button>
  </div>

  <?php if(!$notifications): ?>
    <p>No notifications at the moment.</p>
  <?php else: ?>
    <?php foreach($notifications as $n): ?>
      <div class="rewards <?= strtolower($n['status'])==='fail'?'fail':'great' ?>">
        <div class="icon-box">
          <span class="material-icons"><?= strtolower($n['status'])==='fail'?'sentiment_dissatisfied':'sentiment_very_satisfied' ?></span>
        </div>
        <div>
          <h4><?= htmlspecialchars($n['student_name']) ?></h4>
          <p>Class: <?= htmlspecialchars($n['class_name']) ?></p>
          <p>Date: <?= htmlspecialchars($n['date']) ?></p>
          <p>Academic Performance Status: <?= htmlspecialchars($n['status']) ?></p>
          <p>Grade: <?= htmlspecialchars($n['final_grade']) ?></p>
          <p>Message: <?= htmlspecialchars($n['message']) ?></p>
          <?php if(!empty($n['file_link'])): ?>
          <div class="file-preview">
            <a href="<?= htmlspecialchars($n['file_link']) ?>" target="_blank">
              <span class="material-icons">insert_drive_file</span>
              <span class="file-name">Reviewer File</span>
            </a>
          </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
const menuBtn = document.getElementById("menuBtn");
const sidebar = document.getElementById("sidebar");
menuBtn.addEventListener("click",()=>{sidebar.classList.toggle("active");});
</script>

</body>
</html>
