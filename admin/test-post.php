<?php
/**
 * Test POST to login.php
 */

$url = 'http://localhost/Restaurant-Management/admin/login.php';
$data = [
    'email' => 'admin@restaurant.com',
    'password' => 'pass1234'
];

$postData = http_build_query($data);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $postData
    ]
]);

echo "Sending POST request to login.php...\n";
echo "Data: " . $postData . "\n\n";

$result = file_get_contents($url, false, $context);

echo "Response:\n";
echo $result;
?>