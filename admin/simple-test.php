<?php
echo "PHP is working! Current time: " . date('Y-m-d H:i:s');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<br><strong>POST request received!</strong>";
    echo "<br>POST data: " . print_r($_POST, true);
}
?>

<form method="POST">
    <input type="text" name="test" value="hello">
    <button type="submit">Submit Test</button>
</form>