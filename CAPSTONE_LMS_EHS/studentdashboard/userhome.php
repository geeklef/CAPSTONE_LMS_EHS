<?php
session_start();

include '../config/db_connect.php'; // Supabase PDO connection

// ✅ Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    die("No student logged in. Session missing student_id.");
}

$student_id = $_SESSION['student_id'];
$courses = [];

// ✅ Fetch Enrolled Courses
$sql = "SELECT pc.course_name 
        FROM student_enrollments se
        JOIN prof_courses pc ON se.course_id = pc.course_id
        WHERE se.student_id = :student_id";

$stmt_courses = $conn->prepare($sql);
if (!$stmt_courses) {
    die("Course SQL Prepare failed.");
}

$stmt_courses->execute(['student_id' => $student_id]);
$result = $stmt_courses->fetchAll(PDO::FETCH_ASSOC);
foreach ($result as $row) {
    $courses[] = $row['course_name'];
}
$stmt_courses = null;

// ✅ Fetch Student Name
$sql2 = "SELECT first_name, last_name FROM students_account WHERE stud_id = :student_id";
$stmt_name = $conn->prepare($sql2);
if (!$stmt_name) {
    die("Name SQL Prepare failed.");
}

$stmt_name->execute(['student_id' => $student_id]);
$row = $stmt_name->fetch(PDO::FETCH_ASSOC);
$student_name = '';
if ($row) {
    $student_name = $row['first_name'] . ' ' . $row['last_name'];
}

// Fetch profile image if exists
$imageQuery = $conn->prepare("SELECT image_url FROM student_profile_images WHERE student_id = :student_id ORDER BY uploaded_at DESC LIMIT 1");
$imageQuery->execute(['student_id' => $student_id]);
$image = $imageQuery->fetch(PDO::FETCH_ASSOC);

$profileImg = $image ? $image['image_url'] : 'default_prof.jpg'; // fallback default

$stmt_name = null;

$conn = null;
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>LMS Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"/>
    <link rel="stylesheet" href="your_styles.css">
    <style>
       @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap');

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Quicksand", sans-serif;
    }

    body {
      display: flex;
      height: 100vh;
      background-color: #f4f7f9;
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







    /* Main Content */
    .main {
      margin-left: 250px;
      flex: 1;
      padding: 20px;
      transition: margin-left 0.3s ease;
    }

    .topbar {
      background-color: #004aad;
      padding: 20px;
      color: white;
      font-size: 24px;
      border-radius: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .menu-btn {
      display: none;
      font-size: 28px;
      cursor: pointer;
    }

    .stats {
      display: flex;
      justify-content: space-between;
      margin: 20px 0;
      flex-wrap: wrap;
      gap: 10px;
    }

    .stat-box {
      background: white;
      padding: 20px;
      border-radius: 10px;
      width: 32%;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      flex: 1 1 30%;
      min-width: 200px;
    }

    .row-flex {
      display: flex;
      justify-content: space-between;
      gap: 20px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .rewards,
    .certificates {
      flex: 1 1 45%;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .section {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .section h3 {
      margin-bottom: 20px;
    }

    .btn {
      background-color: #004aad;
      color: white;
      padding: 8px 15px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      text-decoration: none;
      font-size: 14px;
    }

    .btn:hover {
      background-color: #003b87;
    }

    .green-circle-btn {
      width: 50px;
      height: 50px;
      background-color: #003b87;
      border-radius: 50%;
      border: none;
      color: white;
      font-weight: bold;
      cursor: pointer;
      margin-right: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .course-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 18px;
    }

    .course-item {
      background-color: #fff;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 0 8px rgba(0, 0, 0, 0.05);
    }

    .icon-heading {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .icon-heading .material-icons {
      font-size: 38px;
      color: #004aad;
    }

    .promo-banner {
      background: url('dash.png') center/cover no-repeat;
      color: white;
      padding: 30px 40px;
      border-radius: 15px;
      margin: 20px 0;
      position: relative;
      overflow: hidden;
    }

    .promo-banner::after {
      content: "";
      position: absolute;
      top: -20px;
      right: -20px;
      width: 150px;
      height: 150px;
      background: rgba(255, 255, 255, 0.1);
      transform: rotate(45deg);
      border-radius: 20px;
    }

    .promo-content {
      max-width: 600px;
    }

    .promo-label {
      font-size: 12px;
      letter-spacing: 1px;
      opacity: 0.8;
      margin-bottom: 10px;
      text-transform: uppercase;
    }

    .promo-banner h2 {
      font-size: 24px;
      margin: 0 0 15px;
      line-height: 1.3;
    }

    .promo-btn {
      background-color: #ffffff;
      color: #004aad;
      padding: 8px 16px;
      font-size: 15px;
      font-weight: bold;
      border: none;
      border-radius: 8px;
      text-decoration: none;
      display: inline-block;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .promo-btn:hover {
      background-color: #f0f0f0;
    }

    /* Responsive Design */
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
      }

      .topbar {
        font-size: 20px;
      }
    }

    @media (max-width: 600px) {
      .promo-banner h2 {
        font-size: 18px;
      }

      .promo-btn {
        font-size: 13px;
        padding: 6px 12px;
      }

      .rewards,
      .certificates {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
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
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/userhome.php" class="active"><span class="material-icons">dashboard</span> Dashboard</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/courses.php"><span class="material-icons">menu_book</span> Courses</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/performance.php"><span class="material-icons">bar_chart</span> Performance</a>
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
      <span>Dashboard</span>
      <span class="material-icons menu-btn" id="menu-btn">menu</span>
    </div>

    <div class="promo-banner">
        <div class="promo-content">
            <p class="promo-label">LEARNING MANAGEMENT SYSTEM</p>
            <h2>Welcome To Eusebio High School,<br>Student <?php echo htmlspecialchars($student_name); ?></h2>
            <a href="https://www.facebook.com/EusebioHighSchoolOfficial" target="_blank" class="promo-btn">Join Facebook Page</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="icon-heading">
                <span class="material-icons">school</span>
                <div>
                    <h3><?php echo count($courses); ?></h3>
                    <p>Courses To Do</p>
                </div>
            </div>
        </div>
        <div class="stat-box">
            <div class="icon-heading">
                <span class="material-icons">check_circle</span>
                <div>
                    <h3>6</h3> <!-- Replace with dynamic attendance count -->
                    <p>Attendance</p>
                </div>
            </div>
        </div>
        <div class="stat-box">
            <div class="icon-heading">
                <span class="material-icons">error</span>
                <div>
                    <h3>1</h3> <!-- Replace with dynamic missing submissions count -->
                    <p>Missing</p>
                </div>
            </div>
        </div>
    </div>

   <div class="row-flex">
  <div class="rewards">
    <div class="icon-heading">
      <span class="material-icons">description</span>
      <div>
        <h4>STUDENTS RECORDS</h4>
        <p>View your Students Records</p>
      </div>
    </div>
    <a href="performance.php" class="btn">View All</a>
  </div>

  <div class="certificates">
    <div class="icon-heading">
      <span class="material-icons">notifications</span>
      <div>
        <h4>REMINDERS</h4>
        <p>View your Reminders</p>
      </div>
    </div>
    <a href="reminders.php" class="btn">View All</a>
  </div>
</div>
    <div class="section">
        <h3>Enrolled Courses</h3>
        <div class="course-grid">
            <?php foreach ($courses as $course): ?>
                <div class="course-item"><p>- <?php echo htmlspecialchars($course); ?></p></div>
            <?php endforeach; ?>

            <?php if (empty($courses)): ?>
                <p>No courses enrolled yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const menuBtn = document.getElementById('menu-btn');
    const sidebar = document.getElementById('sidebar');

    menuBtn.addEventListener('click', () => {
      sidebar.classList.toggle('active');
    });
  </script>


</body>
</html>
