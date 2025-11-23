    <?php
    // 🧩 DEBUGGING get_prof_details.php
    include '../../../config/db_connect.php';
    header('Content-Type: application/json');

    // 1️⃣ Ensure PHP errors are visible for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    try {
        // ✅ Use your existing Supabase connection from db_connect.php
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password";
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo json_encode(['error' => '❌ DB connection failed: ' . $e->getMessage()]);
        exit;
    }

    // 2️⃣ Check incoming parameter
    $teacher_id = $_GET['teacher_id'] ?? null;
    if (!$teacher_id) {
        echo json_encode(['error' => '❌ Missing teacher_id parameter']);
        exit;   
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM teachers_account WHERE teacher_id = :teacher_id");
        $stmt->execute(['teacher_id' => $teacher_id]);
        $professor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($professor) {
            echo json_encode($professor);
        } else {
            echo json_encode(['error' => '❌ Professor not found for ID ' . $teacher_id]);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => '❌ Query failed: ' . $e->getMessage()]);
    }
    ?>
