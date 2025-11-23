<?php
session_start();
include '../config/db_connect.php';

// 🔹 Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) die("Access denied. No teacher logged in.");
$teacher_id = $_SESSION['teacher_id'];

// 🔹 Fetch teacher name
$teacher_name = 'Professor';
$stmt = $conn->prepare("SELECT first_name, last_name FROM teachers_account WHERE teacher_id = :teacher_id");
$stmt->execute(['teacher_id'=>$teacher_id]);
if($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $teacher_name = $row['first_name'].' '.$row['last_name'];
}

// 🔹 Fetch all classes handled by the teacher
$stmt = $conn->prepare("
    SELECT course_id, class_id, course_name, strand, section, day, time_start, time_end
    FROM prof_courses
    WHERE teacher_id = :teacher_id
    ORDER BY strand, section
");
$stmt->execute(['teacher_id' => $teacher_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Optional: fetch profile image
$imageQuery = $conn->prepare("SELECT image_url FROM teacher_profile_images WHERE teacher_id = :teacher_id ORDER BY uploaded_at DESC LIMIT 1");
$imageQuery->execute(['teacher_id' => $teacher_id]);
$image = $imageQuery->fetch(PDO::FETCH_ASSOC);
$profileImg = $image ? $image['image_url'] : 'default_prof.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Performance Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <link href="/CAPSTONE_LMS_EHS/assets/prof/css/performance.css" rel="stylesheet" />
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
    </style>
</head>
<body>
<div class="sidebar">
  <div class="profile">
    <img src="/CAPSTONE_LMS_EHS/assets/landingpage/ehslogo.png" alt="Profile Image" class="top-profile-img">
    <h3>Eusebio High School</h3>
  </div>

   <div class="menu">
      <a href="/CAPSTONE_LMS_EHS/profdashboard/profhome.php"><span class="material-icons">dashboard</span> Dashboard</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/course.php"><span class="material-icons">menu_book</span> Courses</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/performance.php"class="active"><span class="material-icons">bar_chart</span> Performance</a>
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
    <span>Students Performance</span>
    <button class="menu-btn" id="menuBtn">&#9776;</button>
  </div>

  <div class="task-list">
    <?php if($classes): ?>
      <?php foreach($classes as $c): ?>
      <div class="task-card">
        <a href="performancelist.php?class_id=<?= $c['class_id'] ?>" class="view-records">View Records</a>
        <div class="task-icon"><span class="material-icons">bar_chart</span></div>
        <div class="task-content">
          <h4><?= htmlspecialchars($c['course_name']) ?>
            <span class="task-tag" style="background-color:#e8f0ff;color:red;">Performance</span>
          </h4>
          <p><?= htmlspecialchars($c['strand']) ?> | Section <?= htmlspecialchars($c['section']) ?></p>
          <p><?= htmlspecialchars($c['day'] ?? '-') ?>, <?= htmlspecialchars($c['time_start'] ?? '-') ?> - <?= htmlspecialchars($c['time_end'] ?? '-') ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No classes assigned to you.</p>
    <?php endif; ?>
  </div>
</div>

<script>
const menuBtn = document.getElementById('menuBtn');
const sidebar = document.querySelector('.sidebar');
menuBtn.addEventListener('click', ()=> sidebar.classList.toggle('active'));
</script>
</body>
</html>
