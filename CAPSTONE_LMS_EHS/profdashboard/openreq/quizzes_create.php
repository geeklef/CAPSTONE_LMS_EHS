<?php
include '../../config/db_connect.php';
session_start();

$teacher_id = $_SESSION['teacher_id'] ?? 1;
$strand = $_GET['strand'] ?? ($_SESSION['strand'] ?? '');
$section = $_GET['section'] ?? ($_SESSION['section'] ?? '');
$course_name = $_GET['course'] ?? ($_SESSION['course'] ?? '');
$quiz_id = $_GET['quiz_id'] ?? null;

if (!empty($strand)) $_SESSION['strand'] = $strand;
if (!empty($section)) $_SESSION['section'] = $section;
if (!empty($course_name)) $_SESSION['course'] = $course_name;

// 🔹 Fetch class_id automatically
$stmt = $conn->prepare("
    SELECT class_id 
    FROM prof_courses 
    WHERE teacher_id = :teacher_id AND strand = :strand AND section = :section 
    LIMIT 1
");
$stmt->execute([
    ':teacher_id' => $teacher_id,
    ':strand' => $strand,
    ':section' => $section
]);
$class_id = $stmt->fetchColumn() ?: null;
$stmt = null;

$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Create Quiz</title>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="/CAPSTONE_LMS_EHS/assets/prof/css/create_quiz_exam.css" rel="stylesheet">

<style>

   * { box-sizing: border-box; }
    body {
      font-family: 'Quicksand', sans-serif;
      background-color: #f5f7fb;
      margin: 0;
      padding: 0;
    }

    /* HEADER */
    .header {
      background-color: #004aad;
      color: white;
      padding: 15px 25px;
      font-size: 20px;
      font-weight: 600;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .header-buttons {
      display: flex;
      gap: 10px;
    }

    .action-btn {
      background-color: white;
      color: #004aad;
      border: none;
      padding: 10px 22px;
      border-radius: 25px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s ease;
    }

    .action-btn:hover {
      background-color: #e8eefc;
    }

    /* MAIN CONTENT */
    .container {
      padding: 30px;
      max-width: 900px;
      margin: 0 auto;
    }

    .question-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      padding: 20px;
      margin-bottom: 20px;
      position: relative;
    }

    .question-title {
      font-weight: 600;
      margin-bottom: 10px;
      color: #222;
    }

    .option label { margin-left: 5px; color: #333; }

    .delete-btn {
      position: absolute;
      top: 15px;
      right: 15px;
      background: none;
      border: none;
      color: #004aad;
      font-size: 18px;
      cursor: pointer;
    }
    .delete-btn:hover { color: red; }

    .no-questions {
      text-align: center;
      color: #777;
      font-weight: 500;
      margin-top: 40px;
    }

    /* MODALS */
    .modal {
      display: none;
      position: fixed;
      z-index: 999;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.4);
      backdrop-filter: blur(3px);
      justify-content: center;
      align-items: center;
      padding: 15px;
    }

    .modal-content {
      background-color: white;
      border-radius: 12px;
      width: 100%;
      max-width: 500px;
      padding: 25px;
      box-shadow: 0 5px 12px rgba(0,0,0,0.2);
      position: relative;
      animation: fadeInUp 0.3s ease;
    }

    .responses-modal .modal-content {
      max-width: 900px;
      overflow-x: auto;
    }

    @keyframes fadeInUp {
      from {transform: translateY(20px); opacity: 0;}
      to {transform: translateY(0); opacity: 1;}
    }

    .modal h2 {
      color: #004aad;
      margin-top: 0;
      font-weight: 700;
      font-size: 20px;
    }

    .close-btn {
      position: absolute;
      top: 12px;
      right: 15px;
      background: none;
      border: none;
      color: #004aad;
      font-size: 22px;
      cursor: pointer;
    }
    .close-btn:hover { color: red; }

    select, input[type="text"] {
      width: 100%;
      padding: 9px 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
      margin: 8px 0 14px 0;
      font-family: 'Quicksand', sans-serif;
      font-size: 14px;
    }

    .add-option-btn {
      color: #004aad;
      cursor: pointer;
      font-size: 14px;
      display: inline-block;
      margin-top: 5px;
    }

    .save-btn {
      background-color: #004aad;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 10px 20px;
      cursor: pointer;
      font-weight: 600;
      transition: 0.2s;
      float: right;
    }

    .save-btn:hover { background-color: #003a8f; }

    .option {
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .option input[type="text"] { flex: 1; }
    .correct-radio { cursor: pointer; accent-color: #004aad; }

    .modal-footer {
      margin-top: 20px;
      text-align: right;
      clear: both;
    }

    /* RESPONSES TABLE */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    th, td {
      border-bottom: 1px solid #eee;
      padding: 10px 15px;
      text-align: left;
      white-space: nowrap;
    }

    th {
      background-color: #004aad;
      color: white;
      font-weight: 600;
    }

    td {
      font-size: 14px;
      color: #333;
    }

    .action-links button {
      background: none;
      border: none;
      cursor: pointer;
      font-size: 14px;
      color: #004aad;
      margin-right: 10px;
    }

    .action-links button:hover {
      text-decoration: underline;
    }

    /* =============================
   RESPONSIVE DESIGN
============================= */
@media (max-width: 900px) {
  .container {
    padding: 30px;
  }

  .responses-modal .modal-content {
    max-width: 95%;
  }

  th, td {
    padding: 8px 10px;
    font-size: 13px;
  }

  .action-btn {
    padding: 8px 16px;
    font-size: 13px;
  }

  .modal-content {
    max-width: 90%;
    padding: 20px;
  }
}

@media (max-width: 600px) {
  .header {
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
    
  }

  .header-buttons {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    width: 100%;
    justify-items: center;
  }

  
  .header span {
    margin-bottom: 15px;
    display: block;
     text-align: center;
  }

  .action-btn {
    width: 100%;
    padding: 20px;
  
  }

  .container {
    padding: 15px;
  }

  .question-card {
    padding: 15px;
  }

  th, td {
    font-size: 12px;
    padding: 6px 8px;
  }

  .modal-content {
    max-width: 95%;
    padding: 15px;
  }

  .save-btn {
    width: 100%;
    float: none;
  }

  table {
    display: block;
    overflow-x: auto;
    white-space: nowrap;
  }
}


</style>
</head>
<body>

<div class="header">
  <span>Questionnaire Form</span>
  <div class="header-buttons">
    <button class="action-btn" id="openModal">+ Create Question</button>
    <button class="action-btn" id="openPublish">📤 Publish</button>
    <button class="action-btn" id="openResponses">🧾 Responses</button> 
    <button class="action-btn" id="backBtn">⬅ Back to Quizzes</button>
  </div>
</div>

<div class="container" id="questionList">
  <p class="no-questions">No questions created yet.</p>
</div>

<!-- Create Question Modal -->
<div class="modal" id="questionModal">
  <div class="modal-content">
    <button class="close-btn" id="closeModal">&times;</button>
    <h2>Create a Question</h2>

    <label>Question Type</label>
    <select id="questionType">
      <option value="multiple">Multiple Choice</option>
      <option value="checkbox">Checkboxes</option>
      <option value="short">Short Answer</option>
    </select>

    <label>Question</label>
    <input type="text" id="questionText" placeholder="Enter your question..." />

    <div id="optionsContainer"></div>

    <div class="modal-footer">
      <button class="save-btn" id="saveQuestion">Save</button>
    </div>
  </div>
</div>

<!-- Publish Modal -->
<div class="modal" id="publishModal">
  <div class="modal-content">
    <button class="close-btn" id="closePublish">&times;</button>
    <h2>Publish Quiz</h2>

    <form id="publishForm">
      <label>Quiz Title</label>
      <input type="text" name="quiz_title" id="quizTitle" required>

      <label>Description</label>
      <textarea name="description" id="quizDesc" rows="4"></textarea>

      <label>Due Date</label>
      <input type="date" name="due_date" id="dueDate" required>

      <label>Due Time</label>
      <input type="time" name="due_time" id="dueTime" required>

      <div class="modal-footer">
        <button type="submit" class="save-btn" id="publishQuizBtn">Publish</button>
      </div>
    </form>
  </div>
</div>

<!-- Responses Modal -->
<div class="modal responses-modal" id="responsesModal">
  <div class="modal-content">
    <button class="close-btn" id="closeResponses">&times;</button>
    <h2>Responses</h2>
    <table>
      <thead>
        <tr>
          <th>School ID</th>
          <th>Student Name</th>
          <th>Grade & Strand</th>
          <th>Score</th>
          <th>Submitted</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="responseTable">
        <tr><td colspan="6">No responses yet.</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
const classId = <?php echo json_encode($class_id); ?>;
const quizId = <?php echo json_encode($quiz_id); ?>;

$(document).ready(function() {
  const modal = $('#questionModal');
  const publishModal = $('#publishModal');
  const responsesModal = $('#responsesModal');
  const optionsContainer = $('#optionsContainer');
  const questionList = $('#questionList');

  // --- FETCH QUESTIONS ---
  function loadQuestions() {
    $.ajax({
      url: '/CAPSTONE_LMS_EHS/api/prof/prof_quiz/get_quiz.php',
      type: 'GET',
      data: { quiz_id: quizId },
      dataType: 'json',
      success: function(data) {
        questionList.empty();
        if (!Array.isArray(data) || data.length === 0) {
          questionList.html('<p class="no-questions">No questions created yet.</p>');
          return;
        }
        data.forEach(q => {
          let optionsHTML = '';
          if (q.question_type !== 'short') {
            q.options.forEach(opt => {
              const mark = opt.is_correct ? '<strong style="color:#004aad">(Correct)</strong>' : '';
              optionsHTML += `<div class="option">
                                <input type="${q.question_type === 'multiple' ? 'radio' : 'checkbox'}" disabled />
                                <label>${opt.option} ${mark}</label>
                              </div>`;
            });
          } else {
            optionsHTML = `<input type="text" disabled placeholder="Short answer text..." style="width:100%; padding:6px; border:1px solid #ccc; border-radius:6px;">`;
          }

          const newQuestion = $(`<div class="question-card" data-question-id="${q.question_id}">
                                    <button class="delete-btn" data-id="${q.question_id}">Delete</button>
                                    <div class="question-title">${q.question_text}</div>
                                    ${optionsHTML}
                                  </div>`);
          questionList.append(newQuestion);
        });
      },
      error: function(xhr) {
        alert('Error fetching questions: ' + xhr.responseText);
      }
    });
  }
  loadQuestions();

  // --- OPEN MODALS ---
  $('#openModal').click(() => {
    modal.fadeIn(200).css('display', 'flex');
    $('#questionText').val('');
    optionsContainer.empty();
    $('#questionType').val('multiple');
    addOption();
  });
  $('#openPublish').click(() => publishModal.fadeIn(200).css('display', 'flex'));
  $('#openResponses').click(() => {
    loadResponses();
    responsesModal.fadeIn(200).css('display', 'flex');
  });

  // --- CLOSE MODALS ---
  $('.close-btn').click(function() { $(this).closest('.modal').fadeOut(200); });
  $(window).on('click', function(e) { if ($(e.target).hasClass('modal')) $(e.target).fadeOut(200); });

  // --- QUESTION TYPE CHANGE ---
  $('#questionType').change(function() {
    optionsContainer.empty();
    if ($(this).val() !== 'short') addOption();
  });

  function addOption() {
    const type = $('#questionType').val();
    const inputType = type === 'checkbox' ? 'checkbox' : 'radio';
    optionsContainer.append(`<div class="option">
      <input type="${inputType}" name="correctAnswer" class="correct-radio" />
      <input type="text" class="option-input" placeholder="Option text..." />
    </div>`);
    if (!$('#addOption').length) {
      optionsContainer.append(`<span class="add-option-btn" id="addOption">+ Add option</span>`);
    }
  }

  $(document).on('click', '#addOption', function() {
    const type = $('#questionType').val();
    const inputType = type === 'checkbox' ? 'checkbox' : 'radio';
    $(this).before(`<div class="option">
      <input type="${inputType}" class="correct-radio" />
      <input type="text" class="option-input" placeholder="Option text..." />
    </div>`);
  });

  // --- SAVE QUESTION ---
  $('#saveQuestion').click(function() {
    const type = $('#questionType').val();
    const text = $('#questionText').val().trim();
    if (!text) return alert('Please enter a question.');

    let options = [];
    let correctAnswers = [];
    $('#questionModal .option').each(function() {
      const val = $(this).find('.option-input').val().trim();
      const isCorrect = $(this).find('.correct-radio').is(':checked');
      if (val) {
        options.push({ option: val, is_correct: isCorrect });
        if (isCorrect) correctAnswers.push(val);
      }
    });

    if (type === 'short') correctAnswers = [];
    if (options.length === 0 && type !== 'short') return alert('Please add at least one option.');

    $.ajax({
      url: '/CAPSTONE_LMS_EHS/api/prof/prof_quiz/add_question.php',
      type: 'POST',
      data: {
        quiz_id: quizId,
        question_type: type,
        question_text: text,
        options: JSON.stringify(options),
        correct_answer: JSON.stringify(correctAnswers)
      },
      dataType: 'json',
      success: function(data) {
        if (data.success) {
          alert('✅ Question saved successfully!');
          loadQuestions();
          $('#questionModal').fadeOut(200);
        } else {
          alert('❌ Error: ' + data.message);
        }
      },
      error: function(xhr) {
        alert('Server error: ' + xhr.responseText);
      }
    });
  });

  // --- DELETE QUESTION ---
  $(document).on('click', '.question-card .delete-btn', function() {
    const questionCard = $(this).closest('.question-card');
    const questionId = questionCard.data('question-id');

    if (!confirm('Are you sure you want to delete this question?')) return;

    $.ajax({
      url: '/CAPSTONE_LMS_EHS/api/prof/prof_quiz/delete_question.php',
      type: 'POST',
      data: { question_id: questionId },
      dataType: 'json',
      success: function(data) {
        if (data.success) {
          questionCard.fadeOut(200, function() {
            $(this).remove();
            if ($('.question-card').length === 0) questionList.html('<p class="no-questions">No questions created yet.</p>');
          });
        } else {
          alert('❌ Error: ' + data.message);
        }
      },
      error: function(xhr) {
        alert('Server error: ' + xhr.responseText);
      }
    });
  });

  // --- PUBLISH QUIZ ---
  $('#publishForm').submit(function(e) {
    e.preventDefault();
    const quizTitle = $('#quizTitle').val().trim();
    const description = $('#quizDesc').val().trim();
    const dueDate = $('#dueDate').val();
    const dueTime = $('#dueTime').val();

    if (!quizTitle) return alert('Please enter a quiz title.');

    $.ajax({
      url: '/CAPSTONE_LMS_EHS/api/prof/prof_quiz/add_quiz.php',
      type: 'POST',
      data: {
        quiz_id: quizId,
        quiz_title: quizTitle,
        description: description,
        due_date: dueDate,
        due_time: dueTime,
        class_id: classId
      },
      dataType: 'json',
      success: function(data) {
        if (data.success) {
          alert('✅ Quiz published successfully!');
          $('#publishModal').fadeOut(200);
        } else {
          alert('❌ ' + data.message);
        }
      },
      error: function(xhr) {
        alert('Server error: ' + xhr.responseText);
      }
    });
  });

  // --- RESPONSES ---
  function loadResponses() {
    $.ajax({
      url: '/CAPSTONE_LMS_EHS/api/prof/prof_quiz/get_responses_quiz.php',
      type: 'GET',
      data: { quiz_id: quizId },
      dataType: 'json',
      success: function(data) {
        const tbody = $('#responseTable');
        tbody.empty();
        if (!Array.isArray(data) || data.length === 0) {
          tbody.append('<tr><td colspan="6">No responses yet.</td></tr>');
          return;
        }

        const maxScore = data[0]?.max_score || data.length || 0; // optional if passed from backend

        data.forEach(r => {
          const studentName = `${r.first_name} ${r.last_name}`;
          const score = r.score ?? 0;
          tbody.append(`
            <tr>
              <td>${r.stud_id}</td>
              <td>${studentName}</td>
              <td>Grade ${r.grade} - ${r.strand}</td>
              <td>${score} / ${maxScore}</td>
              <td>${new Date(r.submitted_at).toLocaleString()}</td>
              <td>
                <button class="view-btn" data-result-id="${r.result_id}">View</button>
                <button class="edit-btn" data-result-id="${r.result_id}" data-score="${score}">Edit</button>
              </td>
            </tr>
          `);
        });
      },
      error: function(xhr) {
        alert('Error loading responses: ' + xhr.responseText);
      }
    });
  }

// --- VIEW STUDENT ANSWERS POPUP ---
$(document).on('click', '.view-btn', function() {
    const resultId = $(this).data('result-id');
    if (!resultId) return alert("Missing result ID.");

    // Open the popup with the student answers page
    window.open(
        `/CAPSTONE_LMS_EHS/profdashboard/openreq/quiz_results_view.php?result_id=${resultId}`,
        '_blank',
        'width=900,height=700,scrollbars=yes,resizable=yes'
    );
});


  // --- EDIT SCORE ---
  $(document).on('click', '.edit-btn', function() {
    const resultId = $(this).data('result-id');
    const currentScore = $(this).data('score');
    const newScore = prompt(`Enter new score (0-${currentScore}):`, currentScore);
    if (newScore === null) return;
    const numScore = parseInt(newScore);
    if (isNaN(numScore) || numScore < 0) return alert('Invalid score');

    $.ajax({
      url: '/CAPSTONE_LMS_EHS/api/prof/prof_quiz/update_score.php',
      type: 'POST',
      data: { result_id: resultId, score: numScore },
      dataType: 'json',
      success: function(resp) {
        if (resp.success) {
          alert('✅ Score updated');
          loadResponses();
        } else {
          alert('❌ ' + resp.message);
        }
      },
      error: function(xhr) {
        alert('Server error: ' + xhr.responseText);
      }
    });
  });

  // --- BACK BUTTON ---
  $('#backBtn').click(function() {
    window.location.href = '/CAPSTONE_LMS_EHS/profdashboard/openreq/quizzes.php';
  });
});
</script>

</body>
</html>
