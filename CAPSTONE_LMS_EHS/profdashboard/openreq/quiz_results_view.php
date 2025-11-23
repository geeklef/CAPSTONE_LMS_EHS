<?php
include '../../config/db_connect.php';
session_start();

$result_id = $_GET['result_id'] ?? null;
if (!$result_id) {
    echo "<p>Missing result ID</p>";
    exit;
}

// Fetch the student answers and corresponding questions
$stmt = $conn->prepare("
    SELECT q.question_text, q.question_type, q.options, r.answers, r.score
    FROM stud_quiz_results r
    JOIN quiz_questions q ON r.quiz_id = q.quiz_id
    WHERE r.result_id = :result_id
");
$stmt->execute([':result_id' => $result_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$data) {
    echo "<p>No answers found.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Quiz Results - Student View</title>
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600&display=swap" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    * { box-sizing: border-box; }
    body { font-family: 'Quicksand', sans-serif; background-color: #f5f7fb; margin:0; padding:0; }
    .header { background-color: #004aad; color: white; padding: 15px 25px; font-size: 20px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);}
    .container { padding: 30px; max-width: 900px; margin: 0 auto;}
    .question-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 20px; margin-bottom: 20px; }
    .question-title { font-weight: 600; margin-bottom: 10px; color: #222; }
    .option { margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
    .option label { color: #333; }
    input[type="radio"], input[type="checkbox"] { accent-color: #004aad; cursor: pointer; }
    input[type="text"] { width: 100%; padding: 9px 10px; border: 1px solid #ccc; border-radius: 6px; font-family: 'Quicksand', sans-serif; font-size: 14px; }
    .correct { color: green; font-weight: 600; }
    .wrong { color: red; font-weight: 600; }
    .correct-answer { margin-top: 5px; font-style: italic; color: #004aad; }
  </style>
</head>
<body>
  <div class="header">
    <span>Quiz Results</span>
  </div>

<div class="container">
    <?php foreach ($data as $index => $q): 
        $options = $q['options'] ? json_decode($q['options'], true) : [];
        $student_answer = $q['answers'] ? json_decode($q['answers'], true) : [];
    ?>
    <div class="question-card">
        <div class="question-title"><?= ($index + 1) . '. ' . $q['question_text'] ?></div>

        <?php if ($q['question_type'] === 'short'): ?>
            <input type="text" value="<?= htmlspecialchars($student_answer ?? '') ?>" disabled>
        <?php else: ?>
            <?php foreach ($options as $opt): 
                $isChecked = in_array($opt['option'], (array)$student_answer);
                $isCorrect = $opt['is_correct'];
            ?>
            <div class="option">
                <input type="<?= $q['question_type'] === 'multiple' ? 'radio' : 'checkbox' ?>" 
                       disabled <?= $isChecked ? 'checked' : '' ?>>
                <label>
                    <?= $opt['option'] ?>
                    <?php if ($isChecked && $isCorrect): ?>
                        ✅ Correct
                    <?php elseif ($isChecked && !$isCorrect): ?>
                        ❌ Wrong (Correct: 
                        <?php 
                        $correctOptions = array_filter($options, fn($o) => $o['is_correct']);
                        echo implode(', ', array_map(fn($o) => $o['option'], $correctOptions));
                        ?>
                        )
                    <?php endif; ?>
                </label>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <p><strong>Score: <?= $data[0]['score'] ?? 'N/A' ?> / <?= count($data) ?></strong></p>
</div>


  <script>
    const quizResults = <?php echo json_encode($quizResults); ?>;

    function renderResults(data) {
      const container = $("#resultsContainer");
      container.empty();

      data.forEach((q, index) => {
        let html = `<div class="question-card">
                      <div class="question-title">${q.question_text}</div>`;

        if (q.question_type === "short") {
          const studentAnswer = q.student_answer[0] ?? '';
          const correctAnswer = q.correct_answer[0] ?? '';
          const isCorrect = studentAnswer === correctAnswer;

          html += `<input type="text" value="${studentAnswer}" disabled>`;
          html += isCorrect 
                  ? `<div class="correct">Correct ✅</div>` 
                  : `<div class="wrong">Wrong ❌</div><div class="correct-answer">Correct Answer: ${correctAnswer}</div>`;
        } else {
          q.options.forEach(opt => {
            const checked = q.student_answer.includes(opt.option) ? "checked" : "";
            let mark = "";

            if (checked) {
              mark = opt.is_correct ? '<span class="correct">✅ Correct</span>' : '<span class="wrong">❌ Wrong</span>';
            }

            html += `<div class="option">
                      <input type="${q.question_type}" disabled ${checked}>
                      <label>${opt.option} ${mark}</label>
                    </div>`;
          });
        }

        html += `</div>`;
        container.append(html);
      });
    }

    $(document).ready(function() {
      renderResults(quizResults);

      $("#backBtn").click(() => window.close()); // Close popup and return
    });
  </script>
</body>
</html>
