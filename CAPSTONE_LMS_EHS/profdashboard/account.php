<?php
session_start();
include '../config/db_connect.php'; // your Supabase DB connection

if (!isset($_SESSION['teacher_id'])) {
    die("Access denied. No teacher logged in.");
}
$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher info
$query = $conn->prepare("SELECT * FROM teachers_account WHERE teacher_id = :teacher_id");
$query->execute(['teacher_id' => $teacher_id]);
$teacher = $query->fetch(PDO::FETCH_ASSOC);

// Fetch profile image if exists
$imageQuery = $conn->prepare("SELECT image_url FROM teacher_profile_images WHERE teacher_id = :teacher_id ORDER BY uploaded_at DESC LIMIT 1");
$imageQuery->execute(['teacher_id' => $teacher_id]);
$image = $imageQuery->fetch(PDO::FETCH_ASSOC);

$profileImg = $image ? $image['image_url'] : 'default_prof.jpg'; // default fallback (your existing image)
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LMS Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
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
  transition: left 0.3s ease; /* smooth slide animation */
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

        .main {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
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
        
/* Topbar */
.topbar {
  background-color: #004aad;
  padding: 20px;
  color: white;
  font-size: 24px;
  border-radius: 10px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between; /* aligns title left, menu right */
}

/* Menu button */
.menu-btn {
  display: none;
  background: none;
  border: none;
  color: white;
  font-size: 28px;
  cursor: pointer;
}

        .stats {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }

        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 32%;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .certificates, .rewards {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        }

        .btn:hover {
            background-color: #003c8f;
        }

        .icon-row span {
            display: inline-block;
            width: 30px;
            height: 30px;
            background-color: green;
            border-radius: 50%;
            margin: 0 5px;
        }

        .rewards p,
        .certificates p {
            margin-bottom: 10px;
            margin-top: 5px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .modal-content input[type="text"],
        .modal-content input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .modal .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }

        .modal-content h3 {
            text-align: center;
            margin-bottom: 15px;
        }

        
     /* --- Responsive Layout --- */
@media (max-width: 768px) {
  .sidebar {
    left: -250px;
  }

  .sidebar.active {
    left: 0;
  }

  .main {
    margin-left: 0;
  }

  .menu-btn {
    display: block;
    color: white;
  }

  .rewards {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .modal-content {
    width: 90%;
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
    <h3>Eusebio High School  </h3>
    </div>
    
   <div class="menu">
      <a href="/CAPSTONE_LMS_EHS/profdashboard/profhome.php" ><span class="material-icons">dashboard</span> Dashboard</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/course.php"><span class="material-icons">menu_book</span> Courses</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/performance.php"><span class="material-icons">bar_chart</span> Performance</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/reminders.php"><span class="material-icons">notifications</span> Reminders</a>
      <a href="/CAPSTONE_LMS_EHS/profdashboard/account.php" class="active"><span class="material-icons">account_circle</span> Account</a>
      <a href="/CAPSTONE_LMS_EHS/auth/login.php"><span class="material-icons">logout</span> Logout</a>
      </div>
       <!-- BOTTOM PROFILE -->
  <div class="sidebar-bottom">
    <div class="bottom-img-container">
       <img src="<?php echo htmlspecialchars($profileImg); ?>" alt="Profile Image" class="bottom-profile-img">>
      <span class="online-dot"></span>
    </div>

    <div>
     <p>        <?php
            if (!empty($teacher['first_name']) || !empty($teacher['last_name'])) {
                echo htmlspecialchars(trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? '')));
            } else {
                echo htmlspecialchars($teacher['fullname'] ?? 'Unknown');
            }
        ?></p>
        <p class="bottom-status">Teacher</p>
    </div>
  </div>

</div>

<div class="main">
    <div class="topbar">
      <span>Account Settings</span>
      <button class="menu-btn" id="menu-btn">&#9776;</button>

    </div>

    <div class="rewards">
        <div>
            <h4>Account Information:</h4><br>
            <p>Username: <?php echo htmlspecialchars($teacher['teacher_user'] ?? $teacher['email'] ?? ''); ?></p>
            <p>Name: <?php
                if (!empty($teacher['first_name']) || !empty($teacher['last_name'])) {
                    echo htmlspecialchars(trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? '')));
                } else {
                    echo htmlspecialchars($teacher['fullname'] ?? '');
                }
            ?></p>
            <p>School ID: <?php echo htmlspecialchars($teacher['teacher_id'] ?? ''); ?></p>
            <p>Strand: <?php echo htmlspecialchars($teacher['department'] ?? ''); ?></p>

            <button class="btn" style="margin-top: 10px; margin-bottom: 8px;" onclick="openModal()">Update Information</button>
        </div>
    </div>

    <!-- Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Update Profile Picture</h3>
            <form id="uploadForm" enctype="multipart/form-data">
                <label>Username:</label>
                <input type="text" value="<?php echo htmlspecialchars($teacher['teacher_user'] ?? $teacher['email'] ?? ''); ?>" readonly><br><br>

                <label>Teacher ID:</label>
                <input type="text" value="<?php echo htmlspecialchars($teacher['teacher_id'] ?? ''); ?>" readonly><br><br>

                <label>Deparment:</label>
                <input type="text" value="<?php echo htmlspecialchars($teacher['department'] ?? ''); ?>" readonly><br><br>

                <label>Name:</label>
                <input type="text" value="<?php
                    if (!empty($teacher['first_name']) || !empty($teacher['last_name'])) {
                        echo htmlspecialchars(trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? '')));
                    } else {
                        echo htmlspecialchars($teacher['fullname'] ?? '');
                    }
                ?>" readonly><br><br>

                <label for="profilePic">Profile Picture:</label>
                <input type="file" id="profilePic" name="profilePic" accept="image/*" required><br><br>

                <button type="submit" class="btn" style="width: 100%;">Save Picture</button>
            </form>
        </div>
    </div>
</div>

<script>

    const menuBtn = document.getElementById('menu-btn');
    const sidebar = document.getElementById('sidebar');

    menuBtn.addEventListener('click', () => {
      sidebar.classList.toggle('active');
    });

// Modal controls
const modal = document.getElementById("updateModal");
function openModal() { modal.style.display = "flex"; }
function closeModal() { modal.style.display = "none"; }
window.onclick = function(e) { if (e.target === modal) closeModal(); }

// ✅ Supabase config (your working key)
const SUPABASE_URL = "https://fgsohkazfoskhxhndogu.supabase.co";
const SUPABASE_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZnc29oa2F6Zm9za2h4aG5kb2d1Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjA0MzU4MDIsImV4cCI6MjA3NjAxMTgwMn0.EHpoxrGBEx9j2MYQPbhGo-l65hmfijmBBRY65xMVY7c";

document.getElementById("uploadForm").addEventListener("submit", async function(e) {
    e.preventDefault();
    const file = document.getElementById("profilePic").files[0];
    if (!file) return alert("Please choose an image.");

    const fileExt = file.name.split('.').pop();
    const fileName = `teacher_<?php echo $teacher_id; ?>_${Date.now()}.${fileExt}`;
    const uploadUrl = `${SUPABASE_URL}/storage/v1/object/teacher-profile-images/${fileName}`;

    try {
        const uploadRes = await fetch(uploadUrl, {
            method: "POST",
            headers: {
                "Authorization": `Bearer ${SUPABASE_KEY}`,
                "apikey": SUPABASE_KEY,
                "Content-Type": file.type
            },
            body: file
        });

        if (!uploadRes.ok) {
            const text = await uploadRes.text();
            console.error("Upload failed:", text);
            alert("❌ Upload failed. Check Supabase bucket permissions.");
            return;
        }

        // Build public URL
        const publicUrl = `${SUPABASE_URL}/storage/v1/object/public/teacher-profile-images/${fileName}`;

        // Send to save_image.php to insert DB record
        const formData = new FormData();
        formData.append("teacher_id", "<?php echo $teacher_id; ?>");
        formData.append("image_url", publicUrl);

        const saveRes = await fetch("../api/prof/prof_account/upload_img_prof.php", {
            method: "POST",
            body: formData
        });

        const saveMsg = await saveRes.text();
        alert(saveMsg);
        document.getElementById("sidebarProfileImg").src = publicUrl;
        closeModal();
        location.reload();
    } catch (err) {
        console.error(err);
        alert("❌ Upload error. Please check console.");
    }
});
</script>
</body>
</html>
