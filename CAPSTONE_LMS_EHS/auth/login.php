<?php
include '../config/db_connect.php';
session_start();

$loginError = '';
$loginSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        // --- Check students table ---
        $stmt = $conn->prepare("SELECT * FROM students_account WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            if ($password === $student['password']) {
                $_SESSION['student_id'] = $student['stud_id'];
                $loginSuccess = true;

                // --- Run prediction Python script for this student ---
                $student_id = $student['stud_id'];

                // Fetch all classes the student is enrolled in
                $stmt2 = $conn->prepare("SELECT course_id FROM student_enrollments WHERE student_id=:sid");
                $stmt2->execute(['sid'=>$student_id]);
                $classes = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                foreach($classes as $c){
                    $class_id = $c['course_id'];

                    // Run Python prediction script with arguments
                    // Make sure your Python script accepts command line arguments: student_id class_id
                    $cmd = escapeshellcmd("python3 /CAPSTONE_LMS_EHS/ml_model/predict_student.py $student_id $class_id");
                    exec($cmd, $output, $return_var);
                }

                header("refresh:2; url=/CAPSTONE_LMS_EHS/studentdashboard/userhome.php");
                exit;
            } else {
                $loginError = 'Invalid Email or Password';
            }
        } else {
            // --- Check teachers table ---
            $stmt = $conn->prepare("SELECT * FROM teachers_account WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($teacher) {
                if ($password === $teacher['teacher_pass']) {
                    $_SESSION['teacher_id'] = $teacher['teacher_id'];
                    $loginSuccess = true;

                    // --- Run prediction for all students in teacher's classes ---
                    $teacher_id = $teacher['teacher_id'];
                    $stmt2 = $conn->prepare("
                        SELECT se.student_id, se.course_id 
                        FROM student_enrollments se
                        JOIN prof_courses pc ON se.course_id = pc.course_id
                        WHERE pc.teacher_id = :tid
                    ");
                    $stmt2->execute(['tid'=>$teacher_id]);
                    $students = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                    foreach($students as $s){
                        $cmd = escapeshellcmd("python3 /CAPSTONE_LMS_EHS/ml_model/predict_student.py {$s['student_id']} {$s['course_id']}");
                        exec($cmd, $output, $return_var);
                    }

                    header("refresh:2; url=/CAPSTONE_LMS_EHS/profdashboard/profhome.php");
                    exit;
                } else {
                    $loginError = 'Invalid Email or Password';
                }
            } else {
                // --- Admin login ---
                if ($email === 'admin@123' && $password === '123456') {
                    $_SESSION['admin'] = true;
                    $loginSuccess = true;
                    header("refresh:2; url=/CAPSTONE_LMS_EHS/admindashboard/adminhome.php");
                    exit;
                } else {
                    $loginError = 'Invalid Email or Password';
                }
            }
        }
    } catch (PDOException $e) {
        $loginError = 'Database Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EHS Log In</title>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <style>
    * { 
      margin: 0; 
      padding: 0; 
      box-sizing: border-box; 
      font-family: 'Quicksand', sans-serif; 
    }

    body { 
      display: flex; 
      min-height: 100vh; 
      background: #fff; 
      flex-wrap: wrap; 
    }

    .login-container { 
      width: 40%; 
      background-color: #f7f9fc; 
      display: flex; 
      flex-direction: column; 
      justify-content: center; 
      align-items: center; 
      padding: 50px 30px; 
      box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1); 
      transition: all 0.3s ease; 
    }

    .login-container img { 
      width: 120px; 
      margin-bottom: 15px; 
    }

    .login-container h2 { 
      color: #004aad; 
      margin-bottom: 25px; 
      font-size: 28px; 
      text-align: center; 
    }

    .login-container input { 
      width: 350px;           
      max-width: 100%; 
      padding: 12px; 
      margin-bottom: 20px; 
      border: 1px solid #ccc; 
      border-radius: 8px; 
      font-size: 15px; 
    }

    .login-container button { 
      width: 350px;           
      max-width: 100%; 
      padding: 12px; 
      background: #004aad; 
      border: none; 
      border-radius: 8px; 
      color: white; 
      font-weight: 600; 
      cursor: pointer; 
      transition: all 0.2s ease; 
    }

    .login-container button:hover { 
      background: #003b8e; 
      transform: translateY(-2px); 
    }

    .home-icon { 
      margin-top: 30px; 
      width: 100%; 
      max-width: 400px; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      position: relative; 
    }

    .home-icon::before, 
    .home-icon::after { 
      content: ""; 
      flex: 1; 
      height: 2px; 
      background: #004aad; 
      opacity: 0.5; 
      margin: 0 15px; 
      border-radius: 2px; 
    }

    .home-icon a { 
      text-decoration: none; 
      color: #004aad; 
      display: flex; 
      justify-content: center; 
      align-items: center; 
      width: 55px; 
      height: 55px; 
      border: 3px solid #004aad; 
      border-radius: 50%; 
      box-shadow: 0 4px 10px rgba(0, 74, 173, 0.3); 
      transition: all 0.3s ease; 
      background: white; 
      position: relative; 
    }

    .home-icon a:hover { 
      background: #004aad; 
      color: white; 
      transform: scale(1.1); 
      box-shadow: 0 6px 15px rgba(0, 74, 173, 0.4); 
    }

    .home-icon .material-icons { 
      font-size: 28px; 
    }

    .cover-photo { 
      width: 60%; 
      background: url('/CAPSTONE_LMS_EHS/assets/auth/loginbg.png') no-repeat center center/cover; 
      display: flex; 
      justify-content: center; 
      align-items: center; 
      color: white; 
      text-align: center; 
      padding: 50px; 
      position: relative; 
    }

    .cover-photo::before { 
      content: ""; 
      position: absolute; 
      inset: 0; 
      background: rgba(0, 0, 70, 0.5); 
    }

    .cover-content { 
      position: relative; 
      z-index: 1; 
    }

    .cover-content h1 { 
      font-size: 2.3rem; 
      font-weight: 700; 
      margin-bottom: 10px; 
    }

    .cover-content p { 
      font-size: 1.1rem; 
    }

    .gif-container { 
      display: none; 
      position: fixed; 
      top: 0; 
      left: 0; 
      width: 100%; 
      height: 100%; 
      background: rgba(0, 0, 0, 0.7); 
      justify-content: center; 
      align-items: center; 
      z-index: 1000; 
    }

    .gif-box { 
      background: #fff; 
      padding: 30px 50px; 
      border-radius: 12px; 
      text-align: center; 
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); 
    }

    .gif-box img { 
      width: 120px; 
      margin-bottom: 10px; 
    }

    .gif-box p { 
      font-size: 20px; 
      font-weight: bold; 
      margin: 0; 
    }

    .success-box p { 
      color: #1A8E57; 
    }

    .error-box p { 
      color: #D9534F; 
    }

    @media screen and (max-width: 900px) {
      body { 
        flex-direction: column; 
      }

      .login-container, 
      .cover-photo { 
        width: 100%; 
        height: auto; 
      }

      .cover-photo { 
        min-height: 250px; 
      }

      .home-icon::before, 
      .home-icon::after { 
        display: none; 
      }

      .login-container input, 
      .login-container button {
        width: 90%; 
      }
    }
    .login-container form {
      display: flex;
      flex-direction: column;
      align-items: center; 
      width: 100%;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <img src="/CAPSTONE_LMS_EHS/assets/auth/ehslogo.png" alt="SSS Logo">
    <h2>Sign In</h2>
    
    <form method="POST" action="login.php">
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="Password" required />
      <button type="submit">Sign In</button>
    </form>

    <div class="home-icon">
      <a href="/CAPSTONE_LMS_EHS/landingpage/homepage.html" title="Go to Homepage">
        <span class="material-icons">home</span>
      </a>
    </div>
  </div>

  <div class="cover-photo">
    <div class="cover-content">
      <h1>Learning Management System</h1>
      <p>Eusebio High School Portal</p>
    </div>
  </div>

<?php if ($loginSuccess): ?>
  <div class="gif-container success-container">
    <div class="gif-box success-box">
      <img src="check.png" alt="Success">
      <p>Log In Successfully</p>
    </div>
  </div>
<?php elseif ($loginError): ?>
  <div class="gif-container error-container">
    <div class="gif-box error-box">
      <img src="wrong.png" alt="Error">
      <p><?php echo htmlspecialchars($loginError); ?></p>
    </div>
  </div>
<?php endif; ?>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const successPopup = document.querySelector('.success-container');
    const errorPopup = document.querySelector('.error-container');

    const showPopup = (popup) => {
      if (!popup) return;
      popup.style.display = 'flex';
      setTimeout(() => {
        popup.style.display = 'none';
      }, 1500);

      const gifBox = popup.querySelector('.gif-box');
      popup.addEventListener('click', (e) => {
        if (!gifBox.contains(e.target)) popup.style.display = 'none';
      });
    }

    if (successPopup) showPopup(successPopup);
    if (errorPopup) showPopup(errorPopup);
  });
</script>

</body>
</html>
