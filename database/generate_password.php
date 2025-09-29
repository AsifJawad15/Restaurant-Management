<?php
// Generate password hash for pass1234
$password = 'pass1234';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";
?>