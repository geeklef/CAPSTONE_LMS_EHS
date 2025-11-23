<?php
session_start();
include '../config/db_connect.php'; // your database connection

if(!isset($_SESSION['student_id'])) die("Access denied.");
$student_id = $_SESSION['student_id'];

    $stmt = $conn->prepare("SELECT first_name, last_name FROM students_account WHERE stud_id = :id");
    $stmt->execute(['id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    $student_name = $student ? $student['first_name'] . ' ' . $student['last_name'] : 'Unknown Student';
      
// Fetch all quizzes for this student
$quizzes = $conn->prepare("
    SELECT q.quiz_id, q.title, q.total_points, sqr.score, 'quiz' AS type
    FROM prof_quiz q
    LEFT JOIN stud_quiz_results sqr
      ON sqr.quiz_id = q.quiz_id AND sqr.stud_id = :student_id
    WHERE q.strand = :strand AND q.section = :section
");
$quizzes->execute([
    'student_id' => $student_id,
    'strand' => 'STEM',
    'section' => 'A'
]);
$quizzes = $quizzes->fetchAll(PDO::FETCH_ASSOC);

// Fetch all exams for this student
$exams = $conn->prepare("
    SELECT e.exam_id, e.title, e.total_points, ser.score, 'exam' AS type
    FROM prof_exam e
    LEFT JOIN stud_exam_results ser
      ON ser.exam_id = e.exam_id AND ser.stud_id = :student_id
    WHERE e.strand = :strand AND e.section = :section
");
$exams->execute([
    'student_id' => $student_id,
    'strand' => 'STEM',
    'section' => 'A'
]);
$exams = $exams->fetchAll(PDO::FETCH_ASSOC);

// Merge quizzes and exams
$tasks = array_merge($quizzes, $exams);
$imageQuery = $conn->prepare("SELECT image_url FROM student_profile_images WHERE student_id = :student_id ORDER BY uploaded_at DESC LIMIT 1");
$imageQuery->execute(['student_id' => $student_id]);
$image = $imageQuery->fetch(PDO::FETCH_ASSOC);

$profileImg = $image ? $image['image_url'] : 'default_prof.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reminders</title>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<style>
/* --- YOUR ORIGINAL CSS KEPT --- */
@import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap');
* {margin:0;padding:0;box-sizing:border-box;font-family:"Quicksand",sans-serif;}
body {display:flex;height:100vh;background-color:#f4f7f9;}
.sidebar {position:fixed;top:0;left:0;height:100vh;width:250px;background-color:#004aad;color:white;padding:20px;overflow-y:auto;transition:left 0.3s ease;z-index:999;}
.sidebar h2 {margin-bottom:10px;}
.sidebar h3 {margin-left:40px;margin-bottom:40px;}
.sidebar .menu a {display:block;padding:10px 0;color:white;text-decoration:none;margin-bottom:10px;transition:background 0.3s;}
.sidebar .menu a:hover {background: rgba(255,255,255,0.2);border-radius:5px;padding-left:10px;}
.main {margin-left:250px;flex:1;padding:20px;transition:margin-left 0.3s ease;}
.topbar {background-color:#004aad;padding:20px;color:white;font-size:24px;border-radius:10px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;}
.menu-btn {display:none;background:none;border:none;color:white;font-size:26px;cursor:pointer;}
.rewards {display:flex;align-items:center;gap:15px;padding:20px;border-radius:12px;box-shadow:0 2px 6px rgba(0,0,0,0.1);font-size:15px;border:2px solid transparent;margin-bottom:20px;}
.icon-box {display:flex;justify-content:center;align-items:center;width:60px;height:60px;border:2px solid;border-radius:10px;margin-right:30px;margin-left:30px;}
.icon-box .material-icons {font-size:35px;}
.rewards.fail {background-color:#ffe5e5;border-color:#ff9b9b;color:#b30000;}
.rewards.fail .icon-box {border-color:#b30000;color:#b30000;}
.rewards.great {background-color:#e8fce8;border-color:#a7f0a7;color:#007a00;}
.rewards.great .icon-box {border-color:#007a00;color:#007a00;}
.rewards div {display:flex;flex-direction:column;justify-content:center;line-height:1.6;}
.rewards h4 {margin-bottom:5px;}
.rewards p {margin:3px 0;}
@media (max-width:768px) {.sidebar {left:-250px;}.sidebar.active {left:0;}.main {margin-left:0;}.menu-btn {display:block;}.rewards {flex-direction:column;align-items:flex-start;gap:10px;}}
.sidebar .menu a {display:flex;align-items:center;gap:10px;padding:10px 0;color:rgb(202,201,201);text-decoration:none;margin-bottom:10px;font-size:16px;transition:all 0.3s ease;}
.sidebar .menu a:hover {background-color: rgba(255,255,255,0.15);padding-left:10px;border-radius:5px;}
.sidebar .menu a.active {color:white;font-weight:600;position:relative;}
.sidebar .menu a.active::before {content:"";position:absolute;left:-20px;top:0;bottom:0;width:5px;background-color:white;border-radius:10px;}
.sidebar .menu a .material-icons {font-size:22px;}
.rewards {transition:transform 0.2s ease,box-shadow 0.2s ease;}
.rewards:hover {transform:translateY(-3px);box-shadow:0 6px 15px rgba(0,0,0,0.15);}
.retake-link {color:#004aad; font-weight:600; text-decoration:none; margin-top:5px; display:inline-block;}
.retake-link:hover {text-decoration:underline;}
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
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/userhome.php" ><span class="material-icons">dashboard</span> Dashboard</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/courses.php"><span class="material-icons">menu_book</span> Courses</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/performance.php"><span class="material-icons">bar_chart</span> Performance</a>
    <a href="/CAPSTONE_LMS_EHS/studentdashboard/reminders.php" class="active"><span class="material-icons">notifications</span> Reminders</a>
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
    <span>Reminders</span>
    <button class="menu-btn" id="menuBtn">&#9776;</button>
  </div>

<?php foreach($tasks as $task): 
      $score = $task['score'] ?? 0;
      $total = $task['total_points'] ?? 0;
      $percent = ($total == 0) ? 0 : ($score / $total) * 100;
      $status = ($percent >= 50) ? 'Passing' : 'Failing';
      $card_class = ($status === 'Passing') ? 'great' : 'fail';
      $icon = ($status === 'Passing') ? 'sentiment_very_satisfied' : 'sentiment_dissatisfied';
      $date = date("F d, Y");
?>
  <div class="rewards <?= $card_class ?>">
    <div class="icon-box">
      <span class="material-icons"><?= $icon ?></span>
    </div>
    <div>
      <h4><?= htmlspecialchars($task['title']) ?></h4>
      <p>Date: <?= $date ?></p>
      <p>Academic Performance Status: <?= $status ?></p>
      <p>
        <?php if($status === 'Failing'): ?>
          Heads up! You’re close to failing this <?= $task['type'] ?> — but it’s not too late. Stay focused and push through!
        <?php else: ?>
          Great job! You’re passing this <?= $task['type'] ?>. Keep up the good work and aim even higher!
        <?php endif; ?>
      </p>

      <?php if($status === 'Failing'): ?>
        <!-- Retake link for failing tasks -->
        <a class="retake-link" href="<?= $task['type']==='quiz' ? '/CAPSTONE_LMS_EHS/studentdashboard/openreq/student_quiz_retake.php?quiz_id='.$task['quiz_id'] : '/CAPSTONE_LMS_EHS/studentdashboard/openreq/student_exam_retake.php?exam_id='.$task['exam_id'] ?>">
          Retake <?= ucfirst($task['type']) ?>
        </a>
      <?php endif; ?>

    </div>
  </div>
<?php endforeach; ?>

</div>

<script>
const menuBtn = document.getElementById("menuBtn");
const sidebar = document.getElementById("sidebar");
menuBtn.addEventListener("click", () => { sidebar.classList.toggle("active"); });
</script>
</body>
</html>
