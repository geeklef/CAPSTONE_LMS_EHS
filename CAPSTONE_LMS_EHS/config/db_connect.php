<?php
$host = 'aws-1-ap-southeast-1.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.fgsohkazfoskhxhndogu';
$password = 'lms_ehs_123';

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "✅ Connected successfully to Supabase!";
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}
?>
