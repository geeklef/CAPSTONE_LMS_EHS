<?php
include '../config/db_connect.php'; // Adjust path if needed
session_start();



// Function to export all tables as CSV in a ZIP
if (isset($_GET['export_all'])) {
    $zip = new ZipArchive();
    $zipName = 'supabase_export_' . date('Ymd_His') . '.zip';
    $zipPath = sys_get_temp_dir() . '/' . $zipName;

    if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
        die("Cannot create zip file.");
    }

    // Fetch all table names from Supabase (PostgreSQL)
    $tables = $conn->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema='public' AND table_type='BASE TABLE';
    ")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $stmt = $conn->query("SELECT * FROM \"$table\"");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {
            $csvData = fopen('php://temp', 'r+');
            fputcsv($csvData, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($csvData, $row);
            }
            rewind($csvData);
            $csvContent = stream_get_contents($csvData);
            fclose($csvData);

            $zip->addFromString($table . '.csv', $csvContent);
        }
    }

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    readfile($zipPath);
    unlink($zipPath); // clean up temp file
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Settings</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap');
    * { margin:0; padding:0; box-sizing:border-box; font-family:"Quicksand",sans-serif; }
    .main { margin-left:250px; flex:1; padding:20px; }
    .topbar { background-color:#004aad; padding:20px; color:white; font-size:24px; border-radius:10px; }
    
    /* Export Card */
    .export-card { 
        background:white; 
        padding:40px; 
        border-radius:10px; 
        box-shadow:0 0 10px rgba(0,0,0,0.1); 
        max-width:600px; 
        margin:40px auto; 
        text-align:center; 
    }
    .export-card h2 { font-size:28px; margin-bottom:20px; color:#004aad; }
    .export-card p { font-size:16px; margin-bottom:30px; }
    .export-btn { 
        background-color:#004aad; 
        color:white; 
        padding:15px 25px; 
        border:none; 
        border-radius:8px; 
        cursor:pointer; 
        font-size:16px; 
        text-decoration:none; 
        display:inline-block; 
        transition: all 0.3s ease; 
    }
    .export-btn:hover { background-color:#003b87; transform: translateY(-2px); }
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
      overflow-x: hidden;
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

    .section {
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      margin-top: 30px;
    }

    .section h3 {
      margin-bottom: 20px;
    }

    .btn {
      background-color: white;
      color: #004aad;
      padding: 8px 15px;
      border: 2px solid #004aad;
      border-radius: 40px;
      cursor: pointer;
      font-size: 14px;
      margin-top: 30px;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .btn:hover {
      background-color: #003b8c;
      color: white;
    }

    /* Modal (DM style) */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 9999;
      justify-content: center;
      align-items: center;
    }

    .modal-visible {
      display: flex !important;
    }

    .modal-content {
      background: white;
      padding: 30px;
      border-radius: 15px;
      max-width: 500px;
      width: 90%;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      position: relative;
      top: 0;
      left: 0;
      transform: none;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: stretch;
    }

    .modal-close {
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 24px;
      cursor: pointer;
    }

    table th {
      background-color: #004aad;
      color: white;
      text-align: left;
      padding: 10px;
    }

    table td {
      padding: 10px;
    }

    table tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    /* Choice Modal Buttons */
    .choice-btn {
      background-color: #004aad;
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-size: 15px;
      width: 100%;
      margin: 10px 0;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: 0.3s ease;
    }

    .choice-btn:hover {
      background-color: #003b8c;
    }

    input[type="file"] {
      border: 1px solid #ccc;
      padding: 8px;
      border-radius: 5px;
      width: 100%;
    }

    label {
      display: block;
      margin-top: 10px;
    }

    input[type="text"],
    select {
      margin-top: 5px;
    }


  </style>
</head>
<body>
  <div class="sidebar">
    <div class="profile" style="text-align:center;">
      <span class="material-icons" style="font-size:100px;">account_circle</span>
      <h3>Administrator</h3>
    </div>
 <div class="menu">
      <a href="adminhome.php" ><span class="material-icons">dashboard</span> Dashboard</a>
      <a href="courses.php"><span class="material-icons">schedule</span> Schedules</a>
      <a href="prof.php"><span class="material-icons">co_present</span> Teacher</a>
      <a href="student.php"><span class="material-icons">backpack</span> Student</a>
       <a href="settings.php"class="active"><span class="material-icons">settings</span> Settings</a>
        <a href="/CAPSTONE_LMS_EHS/auth/login.php">
        <span class="material-icons">logout</span>
        Logout
      </a>
    </div>
  </div>
  <div class="main">
    <div class="topbar">Admin Settings</div>

    <div class="export-card">
      <h2>Export Entire Database</h2>
      <p>Click the button below to export <strong>all tables</strong> from your Supabase database into a ZIP file containing CSVs.</p>
      <a href="?export_all=1" class="export-btn">
        <span class="material-icons" style="vertical-align:middle;">download</span> Export Database
      </a>
    </div>
  </div>
</body>
</html>
