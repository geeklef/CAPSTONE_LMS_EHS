<?php
// Get file path from query parameter
$filePath = $_GET['file'] ?? '';
if (!$filePath) exit('No file specified');

// Supabase config
$supabaseUrl = 'https://fgsohkazfoskhxhndogu.supabase.co';
$serviceRoleKey = 'YOUR_SERVICE_ROLE_KEY';

// Fetch the file
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$supabaseUrl/storage/v1/object/teacher_activity_file/$filePath",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $serviceRoleKey"
    ],
    CURLOPT_FOLLOWLOCATION => true
]);
$data = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

// Serve file inline
header("Content-Type: $contentType");
header("Content-Disposition: inline; filename=\"" . basename($filePath) . "\"");
echo $data;
