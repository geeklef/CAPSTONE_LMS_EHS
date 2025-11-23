<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/CAPSTONE_LMS_EHS/config/db_connect.php';

// ✅ Ensure student is logged in
if (!isset($_SESSION['student_id'])) {
    die("Access denied. Student not logged in.");
}
$student_id = $_SESSION['student_id'];

// ✅ Get exam ID
$exam_id = $_GET['exam_id'] ?? null;
if (!$exam_id) die("Exam ID missing.");

// ✅ Check if student already submitted
$stmt = $conn->prepare("SELECT 1 FROM stud_exam_results WHERE exam_id = :exam_id AND stud_id = :stud_id");
$stmt->execute(['exam_id' => $exam_id, 'stud_id' => $student_id]);
$already_submitted = $stmt->fetchColumn();

// ✅ Fetch exam info
$stmt = $conn->prepare("SELECT * FROM prof_exam WHERE exam_id = :exam_id");
$stmt->execute(['exam_id' => $exam_id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Fetch questions
$stmt = $conn->prepare("SELECT * FROM exam_questions WHERE exam_id = :exam_id ORDER BY question_id ASC");
$stmt->execute(['exam_id' => $exam_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= htmlspecialchars($exam['title'] ?? 'Exam') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body { font-family:'Quicksand',sans-serif; background:#f5f7fb; margin:0; }
.header { background:#004aad; color:white; padding:15px 25px; display:flex; justify-content:space-between; align-items:center; }
.submit-btn { background:white; color:#004aad; border:none; padding:10px 22px; border-radius:25px; font-weight:600; cursor:pointer; transition:0.3s; }
.submit-btn:hover { background:#e8eefc; }
.container { padding:30px; max-width:900px; margin:auto; }
.question-card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.08); padding:20px; margin-bottom:20px; }
.question-title { font-weight:600; margin-bottom:10px; color:#222; }
.option { margin-bottom:8px; display:flex; align-items:center; gap:6px; }
.option label { color:#333; }
input[type="radio"], input[type="checkbox"] { accent-color:#004aad; cursor:pointer; }
input[type="text"], textarea { width:100%; padding:9px 10px; border:1px solid #ccc; border-radius:6px; font-family:'Quicksand',sans-serif; font-size:14px; }

/* MODALS */
.modal { display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.4); backdrop-filter:blur(3px); justify-content:center; align-items:center; }
.modal-content { background:white; border-radius:12px; width:100%; max-width:400px; padding:25px; text-align:center; animation:fadeInUp 0.3s ease; }
@keyframes fadeInUp { from {transform:translateY(20px); opacity:0;} to {transform:translateY(0); opacity:1;} }
.modal-content h2 { color:#004aad; margin-bottom:10px; font-weight:700; }
.modal-content p { font-size:14px; color:#444; }
.close-btn { margin-top:15px; background:#004aad; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600; transition:0.2s; }
.close-btn:hover { background:#003a8f; }
</style>
</head>
<body>

<div class="header">
    <span><?= htmlspecialchars($exam['title'] ?? 'Exam') ?></span>
    <button class="submit-btn" id="submitExam">Submit</button>
</div>

<div class="container">
<?php foreach($questions as $index => $q): ?>
    <div class="question-card">
        <div class="question-title"><?= ($index+1) . ". " . htmlspecialchars($q['question_text']); ?></div>
        <?php
        $qtype = $q['question_type'];
        $qname = "q".$q['question_id'];
        $options = json_decode($q['options'], true) ?? [];

        if ($qtype === 'multiple' || $qtype === 'checkbox'):
            foreach ($options as $opt):
                $type = $qtype === 'multiple' ? 'radio' : 'checkbox';
        ?>
        <div class="option">
            <input type="<?= $type ?>" name="<?= $qname . ($type==='checkbox'?'[]':'') ?>" value="<?= htmlspecialchars($opt['option']) ?>">
            <label><?= htmlspecialchars($opt['option']) ?></label>
        </div>
        <?php endforeach; ?>
        <?php elseif ($qtype === 'short'): ?>
            <input type="text" name="<?= $qname ?>" placeholder="Your answer...">
        <?php else: ?>
            <textarea name="<?= $qname ?>" rows="3" placeholder="Write your answer here..."></textarea>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<!-- ✅ Modals -->
<div class="modal" id="submitModal">
    <div class="modal-content">
        <h2>Submission Successful</h2>
        <p>Your answers have been recorded. Thank you!</p>
        <button class="close-btn" id="closeModal">Back to Dashboard</button>
    </div>
</div>
<div class="modal" id="alreadySubmittedModal">
    <div class="modal-content">
        <h2>Exam Already Submitted</h2>
        <p>You have already submitted this exam.</p>
        <button class="close-btn" id="backToDashboard">OK</button>
    </div>
</div>

<script>
$(document).ready(function(){
    <?php if($already_submitted): ?>
        $('#alreadySubmittedModal').fadeIn(200).css('display','flex');
    <?php endif; ?>

    $('#submitExam').click(function(){
        let answers = {};
        <?php foreach($questions as $q): ?>
        {
            const name = "q<?= $q['question_id'] ?>";
            let val;
            if ('<?= $q['question_type'] ?>' === 'short') {
                val = $("[name='"+name+"']").val();
            } else if ('<?= $q['question_type'] ?>' === 'multiple') {
                val = $("[name='"+name+"']:checked").val() || '';
            } else { // checkbox
                val = [];
                $("[name='"+name+"[]']:checked").each(function(){ val.push($(this).val()); });
            }
            answers[name] = val;
        }
        <?php endforeach; ?>

        // ✅ Submit to PHP
        $.post('/CAPSTONE_LMS_EHS/api/student/student_exam/submit_exam.php', 
  { exam_id: <?= $exam_id ?>, answers: JSON.stringify(answers) },
  function(resp){
      if(resp.status === 'success'){
          $('#submitModal').fadeIn(200).css('display','flex');
      } else if(resp.status === 'already') {
          $('#alreadySubmittedModal').fadeIn(200).css('display','flex');
      } else {
          alert("Error: " + resp.message);
      }
}, 'json').fail(function(xhr){
    alert("Server error: " + xhr.responseText);
});

    });

    $('#closeModal').click(function(){ window.location.href='/CAPSTONE_LMS_EHS/studentdashboard/openreq/exam.php'; });
    $('#backToDashboard').click(function(){ window.location.href='/CAPSTONE_LMS_EHS/studentdashboard/openreq/exam.php'; });
});
</script>
</body>
</html>
