<?php
$host = '104.154.142.250';
$dbname = 'cim_training';
$username = 'srmotgnp24';
$password = 'nj56q1npL93aG3eo';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => "Database connection failed: " . $e->getMessage()]));
}
