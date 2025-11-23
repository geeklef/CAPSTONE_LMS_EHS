<?php
include '../config/db_connect.php'; // uses your Supabase PDO connection

session_start();
$teacher_id = $_SESSION['teacher_id'] ?? 1;

// Handle course from GET or SESSION
if (!empty($_GET['course'])) {
    $_SESSION['course'] = $_GET['course'];
}
$course_name = $_SESSION['course'] ?? '';

// Get teacher name
$teacher_name = 'Unknown Teacher';
$stmt = $conn->prepare("SELECT first_name, last_name FROM teachers_account WHERE teacher_id = :teacher_id");
$stmt->execute(['teacher_id' => $teacher_id]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $teacher_name = $row['first_name'] . ' ' . $row['last_name'];
}

// Fetch strand-section pairs
$pairs = [];
if (!empty($course_name)) {
    $stmt = $conn->prepare("SELECT strand, section FROM prof_courses WHERE teacher_id = :teacher_id AND course_name = :course_name");
    $stmt->execute(['teacher_id' => $teacher_id, 'course_name' => $course_name]);
    $pairs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS Dashboard</title>

    <!-- Google Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
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

        .sidebar h2 {
            margin-bottom: 10px;
        }

        .sidebar h3 {
            margin-left: 40px;
            margin-bottom: 40px;
        }

        .sidebar .menu a {
            display: block;
            padding: 10px 0;
            color: white;
            text-decoration: none;
            margin-bottom: 10px;
        }

        .sidebar .menu a:hover {
            background-color: #004aad;
            padding-left: 10px;
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
            display: flex;
            align-items: center;
        }

        .back-btn {
            display: inline-block;
            vertical-align: middle;
            text-decoration: none;
            margin-right: 10px;
            color: white;
        }

        .back-btn .material-icons {
            font-size: 28px;
            vertical-align: middle;
        }

        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin: 27px 0;
        }

        .stat-box {
            background: white;
            padding: 20px 20px 20px 60px;
            border-radius: 10px;
            width: 32%;
            height: 119px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .stat-box p {
            font-size: 20px;
            margin-left: 30px;
            margin-bottom: 16px;
        }

        .material-icons.stat-icon {
            position: absolute;
            top: 30px;
            left: 20px;
            font-size: 50px;
            color: #004aad;
        }

        .certificates,
        .rewards {
            display: flex;
            justify-content: space-between;
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .course-item {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }

        .course-item img {
            width: 50px;
            height: 50px;
            margin-right: 15px;
        }

        .progress-bar {
            width: 100%;
            height: 5px;
            background: #ddd;
            margin: 5px 0;
            border-radius: 10px;
        }

        .progress-fill {
            height: 100%;
            background-color: #004aad;
            border-radius: 10px;
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
            margin-left: 30px;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .btn:hover {
            background-color: #004aad;
        }

        .icon-row span {
            display: inline-block;
            width: 30px;
            height: 30px;
            background-color: #ccc;
            border-radius: 50%;
            margin: 0 5px;
        }

        .rewards p,
        .certificates p {
            margin: 5px 0 10px;
        }

      /* ===== Responsive Styles ===== */

/* Medium screens (tablets) */
@media (max-width: 900px) {
    .main {
        margin-left: 0;
        padding: 15px;
    }

    .sidebar {
        left: -250px;
        transition: 0.3s;
        position: fixed;
        z-index: 1000;
    }

    .sidebar.active {
        left: 0;
    }

    .stat-box {
        width: 48%;
        padding: 20px 20px 20px 70px; /* increased left padding for icon */
        height: auto;
        margin-bottom: 15px;
        position: relative;
    }

    .stat-box p {
        margin-left: 40px; /* extra space from icon */
    }

    .material-icons.stat-icon {
        left: 20px; /* stays same, separates icon from left */
        font-size: 45px; /* slightly smaller on tablets */
    }

    .topbar {
        font-size: 20px;
        padding: 15px;
        display: flex;          /* keep arrow and text in a row */
        flex-direction: row;    /* force horizontal layout */
        align-items: center;    /* vertically center text with arrow */
        gap: 10px;              /* space between arrow and text */
        justify-content: flex-start; /* align left */
    }

    .btn {
        margin-left: 0;
        display: block;
        width: fit-content;
    }
}


/* Small screens (phones) */
@media (max-width: 600px) {
    .stat-box {
        width: 100%;
        padding: 20px 20px 20px 80px; /* more left padding for small screens */
        height: auto;
        margin-bottom: 15px;
        position: relative;
    }

    .stat-box p {
        margin-left: 45px; /* space from icon */
        font-size: 14px; /* slightly smaller text */
    }

    .material-icons.stat-icon {
        left: 20px;
        font-size: 40px; /* scale down for mobile */
    }

    .topbar {
        display: flex;
        flex-direction: row; /* keep arrow and text in a row */
        align-items: center; /* vertically align */
        gap: 10px;           /* small gap between arrow and text */
        padding: 12px 10px;  /* keep some padding */
    }


    .btn {
        width: 100%;
        margin: 10px 0 0 0;
        text-align: center;
    }

    .sidebar {
        width: 200px;
        padding: 15px;
    }

    .sidebar h3 {
        margin-left: 0;
        text-align: center;
    }

    .sidebar img {
        margin: 0 auto 80px auto;
        display: block;
    }

    .stats {
        gap: 10px;
    }
}



    </style>
</head>

<body>
    <div class="sidebar">
        <div class="profile">
            <img src="fuego.jpg" alt="Profile Image"
                style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-left: 40px;">
            <h3><?php echo htmlspecialchars($teacher_name); ?></h3>

        </div>
        <div class="menu">
            <a href="/CAPSTONE_LMS_EHS/profdashboard/profhome.php">Dashboard</a>
            <a href="/CAPSTONE_LMS_EHS/profdashboard/course.php">Courses</a>
            <a href="/CAPSTONE_LMS_EHS/profdashboard/performance.php">Performance</a>
            <a href="/CAPSTONE_LMS_EHS/profdashboard/reminders.php">Reminders</a>
            <a href="/CAPSTONE_LMS_EHS/profdashboard/account.php">Account</a>
            <a href="/CAPSTONE_LMS_EHS/auth/login.php">Logout</a>
        </div>
    </div>

    <div class="main">
        <div class="topbar">
            <a href="course.php" class="back-btn">
                <span class="material-icons">arrow_back</span>
            </a>
            Handled Strands & Sections
        </div>

        <div class="stats">
        <?php foreach ($pairs as $pair): ?>
            <?php
            // Get class_id for this teacher, course, strand, section
            $stmt = $conn->prepare("SELECT course_id FROM prof_courses WHERE teacher_id = :tid AND course_name = :course AND strand = :strand AND section = :section LIMIT 1");
            $stmt->execute([
                'tid' => $teacher_id,
                'course' => $course_name,
                'strand' => $pair['strand'],
                'section' => $pair['section']
            ]);
            $class = $stmt->fetch(PDO::FETCH_ASSOC);
            $class_id = $class['course_id'] ?? 0;
            ?>
            <div class="stat-box">
                <span class="material-icons stat-icon">folder</span>
                <p><?php echo htmlspecialchars($pair['strand']) . ' - ' . htmlspecialchars($pair['section']); ?></p>
                <a href="all.php?class_id=<?= $class_id ?>" class="btn">
                    Open Requirements
                </a>
            </div>
        <?php endforeach; ?>

        </div>
    </div>
</body>

</html>
