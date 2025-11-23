<?php
include '../config/db_connect.php'; // Adjust path if needed

// Total Students
$stmt = $conn->prepare("SELECT COUNT(*) as total_students FROM students_account");
$stmt->execute();
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['total_students'] ?? 0;

// Total Teachers
$stmt = $conn->prepare("SELECT COUNT(*) as total_teachers FROM teachers_account");
$stmt->execute();
$total_teachers = $stmt->fetch(PDO::FETCH_ASSOC)['total_teachers'] ?? 0;

// Students Performance (Pass/Fail) from student_predictions
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN LOWER(pass_fail_status)='pass' THEN 1 ELSE 0 END) AS passing,
        SUM(CASE WHEN LOWER(pass_fail_status)='fail' THEN 1 ELSE 0 END) AS failing
    FROM student_predictions
");
$stmt->execute();
$perf = $stmt->fetch(PDO::FETCH_ASSOC);

$passing = $perf['passing'] ?? 0;
$failing = $perf['failing'] ?? 0;

$total_perf = $passing + $failing;
$passing_percent = $total_perf ? round(($passing / $total_perf) * 100) : 0;
$failing_percent = $total_perf ? round(($failing / $total_perf) * 100) : 0;

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LMS Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
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

    

    .main {
      margin-left: 250px;
      flex: 1;
      padding: 20px;
    }

    .topbar {
      background-color: #004aad;
      padding: 20px;
      color: white;
      font-size: 24px;
      border-radius: 10px;
    }

    .stats {
      display: flex;
      justify-content: space-between;
      margin: 20px 0;
    }

    .stat-box {
      background: white;
      padding: 40px;
      border-radius: 10px;
      width: 32%;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .row-flex {
      display: flex;
      justify-content: space-between;
      gap: 20px;
      margin-bottom: 20px;
    }

    .rewards,
    .certificates {
      flex: 1;
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

    .toggle-row {
      display: flex;
      justify-content: space-between;
      margin: 20px 0;
      gap: 20px;
    }

    .toggle-btn {
      flex: 1;
      background-color: #004aad;
      color: #ffffff;
      padding: 40px;
      text-align: center;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
      text-decoration: none;
      font-size: 20px;
      font-weight: bold;
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: background 0.3s ease, transform 0.2s ease;
    }

    .toggle-btn .material-icons {
      font-size: 50px;
      margin-bottom: 10px;
    }

    .toggle-btn:hover {
      background-color: #1b398a;
      transform: translateY(-2px);
    }



  

    .progress-row {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    margin-top: 20px;
    }

    .progress-card {
    flex: 1;
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    .progress-card h4 {
    margin-bottom: 10px;
    font-size: 18px;
    }

    .progress-bar {
    background-color: #eee;
    border-radius: 10px;
    overflow: hidden;
    height: 25px;
    }

    .progress-bar-inner {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    }

    .pass {
    background-color: #4caf50; /* Green */
    width: 50%;
    }

    .fail {
    background-color: #f44336; /* Red */
    width: 50%;
    }




  </style>
</head>

<body>
  <div class="sidebar">
    <div class="profile" style="text-align: center;">
      <span class="material-icons" style="font-size: 100px;">account_circle</span>
      <h3>Administrator</h3>
    </div>

  
<div class="menu">
      <a href="adminhome.php"  class="active"><span class="material-icons">dashboard</span> Dashboard</a>
      <a href="courses.php"><span class="material-icons">schedule</span> Schedules</a>
      <a href="prof.php"><span class="material-icons">co_present</span> Teacher</a>
      <a href="student.php"><span class="material-icons">backpack</span> Student</a>
       <a href="settings.php"><span class="material-icons">settings</span> Settings</a>
        <a href="/CAPSTONE_LMS_EHS/auth/login.php">
        <span class="material-icons">logout</span>
        Logout
      </a>
    </div>
  </div>

  <div class="main">
    <div class="topbar">Dashboard</div>

    <!-- Promo Banner -->
    <div class="promo-banner">
      <div class="promo-content">
        <p class="promo-label">LEARNING MANAGEMENT SYSTEM</p>
        <h2>Welcome To Eusebio High School,<br>Administrator</h2>
        <a href="https://www.facebook.com/EusebioHighSchoolOfficial" target="_blank" class="promo-btn">Join Facebook Page</a>
      </div>
    </div>

    <div class="progress-card" style="display: flex; justify-content: space-between; padding: 40px; height: 200px;">

      <!-- Students Performance -->
      <div style="flex: 1; padding: 0 30px;">
        <h4>Students Performance</h4>
        <div style="margin-bottom: 10px; font-weight: 600;">Passing (GREEN) / Failing (RED)</div>
        <div style="height: 25px; width: 100%; background-color: #eee; border-radius: 20px; overflow: hidden; display: flex;">
          <div style="width: <?php echo $passing_percent; ?>%; background-color: #4CAF50; text-align: center; color: white; font-weight: bold; line-height: 25px;">
    <?php echo $passing_percent; ?>%
</div>
<div style="width: <?php echo $failing_percent; ?>%; background-color: #f44336; text-align: center; color: white; font-weight: bold; line-height: 25px;">
    <?php echo $failing_percent; ?>%</div>

        </div>
      </div>

      <!-- Divider -->
      <div style="width: 1px; background-color: #ccc;"></div>

      <!-- Total Students -->
      <div style="flex: 1; padding: 0 20px; text-align: center;">
        <h4>Total Students</h4>
        <div style="display: flex; align-items: center; justify-content: center; margin-top: 20px;">
          <span class="material-icons" style="font-size: 70px; color: #004aad; margin-right: 10px;">groups</span>
          <div style="font-size: 36px; font-weight: bold; color: #004aad;"><?= $total_students ?></div>
        </div>
      </div>

      <!-- Divider -->
      <div style="width: 1px; background-color: #ccc;"></div>

      <!-- Total Teachers -->
      <div style="flex: 1; padding: 0 20px; text-align: center;">
        <h4>Total Teachers</h4>
        <div style="display: flex; align-items: center; justify-content: center; margin-top: 20px;">
          <span class="material-icons" style="font-size: 70px; color: #004aad; margin-right: 12px;">group</span>
          <div style="font-size: 36px; font-weight: bold; color: #004aad;"><?= $total_teachers ?></div>
        </div>
      </div>

    </div>

    <!-- Toggle Button Row -->
    <div class="toggle-row">
      <a href="courses.php" class="toggle-btn">
        <span class="material-icons">schedule</span>
        Schedules
      </a>

      <a href="prof.php" class="toggle-btn">
        <span class="material-icons">person</span>
        Teachers
      </a>

      <a href="student.php" class="toggle-btn">
        <span class="material-icons">groups</span>
        Students
      </a>

     
    </div>

  </div>
</body>

</html>
