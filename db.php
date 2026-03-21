<?php
$mysqli = new mysqli("localhost", "root", "", "campus_navigator");

if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error);
}
?>