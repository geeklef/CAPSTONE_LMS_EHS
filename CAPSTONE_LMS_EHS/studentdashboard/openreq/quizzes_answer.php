<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'].'/CAPSTONE_LMS_EHS/config/db_connect.php';

// ✅ Ensure student is logged in
if(!isset($_SESSION['student_id'])) {
    die("Access denied. Student not logged in.");
}
$student_id = $_SESSION['student_id'];

// ✅ Get exam (quiz) ID
$quiz_id = $_GET['quiz_id'] ?? null;
if(!$quiz_id) die("Exam ID missing.");

// ✅ Check if student already submitted
$stmt = $conn->prepare("SELECT 1 FROM stud_quiz_results WHERE quiz_id = :quiz_id AND stud_id = :stud_id");
$stmt->execute(['quiz_id'=>$quiz_id, 'stud_id'=>$student_id]);
$already_submitted = $stmt->fetchColumn();

// ✅ Fetch questions for this exam
$stmt = $conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id = :quiz_id ORDER BY question_id ASC");
$stmt->execute(['quiz_id' => $quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Exam - Student View</title>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    * { box-sizing: border-box; }
    body { font-family: 'Quicksand', sans-serif; background-color: #f5f7fb; margin:0; padding:0; }

    .header { background-color: #004aad; color:white; padding:15px 25px; font-size:20px; font-weight:600; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 8px rgba(0,0,0,0.1);}
    .submit-btn { background:white; color:#004aad; border:none; padding:10px 22px; border-radius:25px; font-weight:600; cursor:pointer; transition:0.3s ease;}
    .submit-btn:hover { background:#e8eefc; }

    .container { padding:30px; max-width:900px; margin:0 auto; }
    .question-card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.08); padding:20px; margin-bottom:20px; }
    .question-title { font-weight:600; margin-bottom:10px; color:#222; }
    .option { margin-bottom:8px; display:flex; align-items:center; gap:6px; }
    .option label { color:#333; }
    input[type="radio"], input[type="checkbox"] { accent-color:#004aad; cursor:pointer; }
    input[type="text"] { width:100%; padding:9px 10px; border:1px solid #ccc; border-radius:6px; font-family:'Quicksand',sans-serif; font-size:14px; }

    /* MODALS */
    .modal { display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; background-color: rgba(0,0,0,0.4); backdrop-filter: blur(3px); justify-content:center; align-items:center; padding:15px; }
    .modal-content { background:white; border-radius:12px; width:100%; max-width:400px; padding:25px; box-shadow:0 5px 12px rgba(0,0,0,0.2); text-align:center; animation:fadeInUp 0.3s ease; }
    @keyframes fadeInUp { from {transform:translateY(20px); opacity:0;} to {transform:translateY(0); opacity:1;} }
    .modal-content h2 { color:#004aad; margin-bottom:10px; font-weight:700; }
    .modal-content p { font-size:14px; color:#444; }
    .close-btn { margin-top:15px; background:#004aad; color:white; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-weight:600; transition:0.2s; }
    .close-btn:hover { background:#003a8f; }
  </style>
</head>
<body>
  <div class="header">
    <span>Quiz Form</span>
    <button class="submit-btn" id="submitExam">Submit</button>
  </div>

  <div class="container">
    <?php foreach($questions as $index => $q): ?>
      <div class="question-card">
        <div class="question-title"><?php echo ($index+1) . ". " . htmlspecialchars($q['question_text']); ?></div>
        <?php
        $qtype = $q['question_type'];
        $qname = "q".$q['question_id'];
        $options = json_decode($q['options'], true) ?? [];
        if($qtype === 'multiple' || $qtype === 'checkbox'):
            foreach($options as $opt):
                $type = $qtype === 'multiple' ? 'radio' : 'checkbox';
        ?>
            <div class="option">
              <input type="<?php echo $type; ?>" name="<?php echo $qname . ($type==='checkbox'?'[]':''); ?>" value="<?php echo htmlspecialchars($opt['option']); ?>">
              <label><?php echo htmlspecialchars($opt['option']); ?></label>
            </div>
        <?php
            endforeach;
        elseif($qtype === 'short'): ?>
            <input type="text" name="<?php echo $qname; ?>" placeholder="Your answer..." />
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- ✅ Modal for successful submission -->
  <div class="modal" id="submitModal">
    <div class="modal-content">
      <h2>Submission Successful</h2>
      <p>Your answers have been recorded. Thank you for completing the exam.</p>
      <button class="close-btn" id="closeModal">Back to Dashboard</button>
    </div>
  </div>

  <!-- ✅ Modal for already submitted -->
  <div class="modal" id="alreadySubmittedModal" style="display:none;">
    <div class="modal-content">
      <h2>Exam Already Submitted</h2>
      <p>You have already submitted this exam.</p>
      <button class="close-btn" id="backToDashboard">OK</button>
    </div>
  </div>

  <script>
   $(document).ready(function(){
    // Show modal if already submitted
    <?php if ($already_submitted): ?>
        $('#alreadySubmittedModal').fadeIn(200).css('display','flex');
    <?php endif; ?>

    // Always attach click handler
    $('#backToDashboard').on('click', function(){
        window.location.href = '/CAPSTONE_LMS_EHS/studentdashboard/openreq/quizzes.php';
    });

    $('#closeModal').on('click', function(){
        window.location.href = '/CAPSTONE_LMS_EHS/studentdashboard/openreq/quizzes.php';
    });

    $('#submitExam').click(function() {
        let answers = {};
        <?php foreach($questions as $q): ?>
        {
            const name = "q<?php echo $q['question_id']; ?>";
            let val;
            if('<?php echo $q['question_type']; ?>' === 'short') {
                val = $("[name='"+name+"']").val();
            } else if('<?php echo $q['question_type']; ?>' === 'multiple') {
                val = $("[name='"+name+"']:checked").val() || '';
            } else { // checkbox
                val = [];
                $("[name='"+name+"[]']:checked").each(function(){ val.push($(this).val()); });
            }
            answers[name] = val;
        }
        <?php endforeach; ?>

        $.post('/CAPSTONE_LMS_EHS/api/student/student_quiz/submit_quiz.php', 
            {quiz_id: <?php echo $quiz_id; ?>, answers: JSON.stringify(answers)},
            function(resp) {
                if(resp.status === 'success') {
                    $('#submitModal').fadeIn(200).css('display','flex');
                } else if(resp.status === 'already') {
                    $('#alreadySubmittedModal').fadeIn(200).css('display','flex');
                } else {
                    alert("Error: " + resp.message);
                }
            }, 'json'
        ).fail(function(xhr){
            alert("Server error: " + xhr.responseText);
        });
    });
});

  </script>
</body>
</html>
