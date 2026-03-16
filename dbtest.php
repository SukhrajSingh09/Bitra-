<?php
$mysqli = new mysqli("localhost","2436376","72s51w","db2436376");
if ($mysqli -> connect_errno) {
echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
exit();
}
?>