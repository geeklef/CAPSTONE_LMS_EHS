<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use GuzzleHttp\Client;

// 🔧 Supabase configuration
$supabaseUrl = 'https://fgsohkazfoskhxhndogu.supabase.co';  // Replace with your Supabase URL
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZnc29oa2F6Zm9za2h4aG5kb2d1Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjA0MzU4MDIsImV4cCI6MjA3NjAxMTgwMn0.EHpoxrGBEx9j2MYQPbhGo-l65hmfijmBBRY65xMVY7c'; // Replace with your Supabase service role key

$client = new Client([
    'base_uri' => $supabaseUrl,
    'headers' => [
        'apikey' => $supabaseKey,
        'Authorization' => "Bearer $supabaseKey",
        'Content-Type' => 'application/json',
    ]
]);

if (isset($_FILES['file']['name']) && $_FILES['file']['error'] == 0) {
    $filePath = $_FILES['file']['tmp_name'];

    // Load Excel file
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $insertData = [];

    // Skip header row (start at row index 1)
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];

        $insertData[] = [
            'teacher_id'   => $row[0],
            'teacher_user' => $row[1],
            'teacher_pass' => $row[2], // Ideally hash passwords
            'first_name'   => $row[3],
            'last_name'    => $row[4],
            'email'        => $row[5],
            'subject_id'   => $row[6],
            'department'   => $row[7],
        ];
    }

    try {
        // 🚀 Insert into Supabase table (teachers_account)
        $response = $client->post('/rest/v1/teachers_account', [
            'json' => $insertData,
            'headers' => [
                'Prefer' => 'resolution=ignore-duplicates' // Avoid duplicate errors
            ]
        ]);

        if ($response->getStatusCode() == 201 || $response->getStatusCode() == 200) {
            echo "<script>alert('Teacher list uploaded successfully!'); window.location.href='../../../admindashboard/prof.php';</script>";
        } else {
            echo "<script>alert('Upload failed. Please try again.'); window.location.href='../../../admindashboard/prof.php';</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='../../../admindashboard/prof.php';</script>";
    }
} else {
    echo "<script>alert('File upload failed. Please try again.'); window.location.href='../../../admindashboard/prof.php';</script>";
}
?>
