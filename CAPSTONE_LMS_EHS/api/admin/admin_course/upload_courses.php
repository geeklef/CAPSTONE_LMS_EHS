<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use GuzzleHttp\Client;

// 🔧 Supabase configuration
$supabaseUrl = 'https://fgsohkazfoskhxhndogu.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZnc29oa2F6Zm9za2h4aG5kb2d1Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjA0MzU4MDIsImV4cCI6MjA3NjAxMTgwMn0.EHpoxrGBEx9j2MYQPbhGo-l65hmfijmBBRY65xMVY7c';

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

    // Load Excel
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    $insertData = [];

    // Skip header (start at row 1)
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (empty($row[0])) continue; // skip blank rows

        $insertData[] = [
            'course_name' => $row[0], // e.g., "General Science"
            'strand'      => $row[1], // e.g., "STEM"
            'section'     => $row[2], // e.g., "A"
            'prof_name'   => $row[3], // e.g., "Mr. Cruz"
            'schedule'    => $row[4]  // e.g., "MWF 8:00-9:00"
        ];
    }

    try {
        // 🚀 Insert to Supabase
        $response = $client->post('/rest/v1/prof_courses', [
            'json' => $insertData,
            'headers' => [
                'Prefer' => 'resolution=ignore-duplicates'
            ]
        ]);

        if (in_array($response->getStatusCode(), [200, 201])) {
            echo "<script>alert('Course list uploaded successfully!'); window.location.href='../../../admindashboard/courses.php';</script>";
        } else {
            echo "<script>alert('Upload failed. Please try again.'); window.location.href='../../../admindashboard/courses.php';</script>";
        }
    } catch (Exception $e) {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.location.href='../../../admindashboard/courses.php';</script>";
    }
} else {
    echo "<script>alert('File upload failed. Please try again.'); window.location.href='../../../admindashboard/courses.php';</script>";
}
?>
