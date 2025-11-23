<?php
session_start();
include '../../config/db_connect.php';

// --- Session / GET params ---
$teacher_id = $_SESSION['teacher_id'] ?? 0;
$activity_id = $_GET['activity_id'] ?? ($_SESSION['activity_id'] ?? 0);
$strand = $_GET['strand'] ?? ($_SESSION['strand'] ?? '');
$section = $_GET['section'] ?? ($_SESSION['section'] ?? '');

// minimal access check
if($teacher_id == 0) die("Access denied. Teacher not logged in.");

// persist session
$_SESSION['activity_id'] = $activity_id;
$_SESSION['strand'] = $strand;
$_SESSION['section'] = $section;

// --- Teacher info ---
$teacher_name = 'Teacher';
try {
    $stmt = $conn->prepare("SELECT first_name,last_name FROM teachers_account WHERE teacher_id=:tid LIMIT 1");
    $stmt->execute(['tid'=>$teacher_id]);
    if($r=$stmt->fetch(PDO::FETCH_ASSOC)) $teacher_name = $r['first_name'].' '.$r['last_name'];
} catch(PDOException $e){}

// --- Handle grade update ---
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submission_id'], $_POST['grade'])){
    $submission_id = intval($_POST['submission_id']);
    $grade = intval($_POST['grade']);
    try{
        $stmt = $conn->prepare("UPDATE stud_activity_submissions SET score=:grade WHERE submission_id=:sid");
        $stmt->execute(['grade'=>$grade,'sid'=>$submission_id]);
    }catch(PDOException $e){}
}

// --- Fetch submissions for this activity ---
$submissions = [];
if($activity_id > 0){
    try{
        $sql = "SELECT s.submission_id, s.stud_id, s.file_name, s.file_path, s.submitted_at, s.score,
                       st.first_name, st.last_name
                FROM stud_activity_submissions s
                JOIN students_account st ON s.stud_id = st.stud_id
                WHERE s.activity_id=:aid
                ORDER BY s.submitted_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['aid'=>$activity_id]);
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }catch(PDOException $e){
        die("Error fetching submissions: ".$e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activity Submissions</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="/CAPSTONE_LMS_EHS/assets/prof/css/actlist.css" rel="stylesheet">
</head>
<body>

<div class="sidebar">
    <div class="profile">
        <img src="fuego.jpg" alt="Profile" style="width:100px;height:100px;border-radius:50%;object-fit:cover;margin-left:40px;">
        <h3><?= htmlspecialchars($teacher_name) ?></h3>
    </div>
    <div class="menu">
        <a href="/CAPSTONE_LMS_EHS/profdashboard/profhome.php"><span class="material-icons">dashboard</span> Dashboard</a>
        <a href="/CAPSTONE_LMS_EHS/profdashboard/course.php" class="active"><span class="material-icons">menu_book</span> Courses</a>
        <a href="/CAPSTONE_LMS_EHS/profdashboard/performance.php"><span class="material-icons">bar_chart</span> Performance</a>
        <a href="/CAPSTONE_LMS_EHS/profdashboard/reminders.php"><span class="material-icons">notifications</span> Reminders</a>
        <a href="/CAPSTONE_LMS_EHS/profdashboard/account.php"><span class="material-icons">account_circle</span> Account</a>
        <a href="/CAPSTONE_LMS_EHS/auth/login.php"><span class="material-icons">logout</span> Logout</a>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <a href="Activities.php" class="back-btn"><span class="material-icons">arrow_back</span></a>
        Activities Submitted Lists (<?= htmlspecialchars($strand.' - '.$section) ?>)
    </div>

    <div class="rewards">
        <div class="rewards-header">
            <h3>Students Lists</h3>
        </div>

        <div style="width:100%;">
            <table class="records-table" id="gradesTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>File Submitted</th>
                        <th>Grade</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($submissions as $sub):
                        $dt = new DateTime($sub['submitted_at']);
                        $date = $dt->format('Y-m-d');
                        $time = $dt->format('h:i A');
                        $score = $sub['score'];
                        $status_class = ($score>=75) ? 'status-passing' : 'status-failing';
                        $status_text = ($score>=75) ? 'Passing' : 'Failing';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($sub['first_name'].' '.$sub['last_name']) ?></td>
                        <td><?= $date ?></td>
                        <td><?= $time ?></td>
                        <td><span class="file-link" onclick="openFilePreview('<?= htmlspecialchars($sub['file_path']) ?>')"><?= htmlspecialchars($sub['file_name']) ?></span></td>
                        <td class="grade"><?= htmlspecialchars($score) ?></td>
                        <td class="status <?= $status_class ?>"><?= $status_text ?></td>
                        <td>
                            <button class="btn edit-btn" onclick="editGrade(this, <?= $sub['submission_id'] ?>)">Edit</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- File Preview Modal -->
<div id="filePreviewModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeFilePreview()">&times;</span>
        <h3>File Preview</h3>
        <iframe id="fileEmbed"></iframe>
    </div>
</div>

<script>
function editGrade(button, submissionId){
    const row = button.closest('tr');
    const gradeCell = row.querySelector('.grade');
    const statusCell = row.querySelector('.status');

    if(button.textContent==='Edit'){
        const currentGrade = gradeCell.textContent.trim();
        gradeCell.innerHTML=`<input type="number" value="${currentGrade}" style="width:60px;">`;
        button.textContent='Save';
    } else {
        const newGrade = row.querySelector('input').value;

        // Submit via POST
        const form = document.createElement('form');
        form.method='POST';
        form.style.display='none';
        form.innerHTML=`<input name="submission_id" value="${submissionId}">
                        <input name="grade" value="${newGrade}">`;
        document.body.appendChild(form);
        form.submit();

        // Update UI immediately
        gradeCell.textContent=newGrade;
        if(newGrade>=75){
            statusCell.textContent='Passing';
            statusCell.className='status status-passing';
        } else {
            statusCell.textContent='Failing';
            statusCell.className='status status-failing';
        }
        button.textContent='Edit';
    }
}

function openFilePreview(filePath){
    const modal=document.getElementById('filePreviewModal');
    const fileEmbed=document.getElementById('fileEmbed');
    const ext=filePath.split('.').pop().toLowerCase();

    if(ext==='pdf'){
        fileEmbed.src=`https://docs.google.com/gview?url=${encodeURIComponent(filePath)}&embedded=true`;
    } else if(['doc','docx','ppt','pptx','xls','xlsx'].includes(ext)){
        fileEmbed.src=`https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(filePath)}`;
    } else if(['jpg','jpeg','png','gif'].includes(ext)){
        fileEmbed.src=filePath;
    } else {
        alert('Preview not available for this file type.');
        return;
    }
    modal.style.display='flex';
}

function closeFilePreview(){
    document.getElementById('filePreviewModal').style.display='none';
    document.getElementById('fileEmbed').src='';
}

window.onclick=function(event){
    const modal=document.getElementById('filePreviewModal');
    if(event.target===modal) closeFilePreview();
}
</script>

</body>
</html>
