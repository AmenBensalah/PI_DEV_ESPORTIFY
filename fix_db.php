<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'esportify';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully\n";

$sql = "ALTER TABLE equipe ADD manager_id INT DEFAULT NULL";
if ($conn->query($sql) === TRUE) {
    echo "Column manager_id added successfully\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

$sql = "ALTER TABLE equipe ADD CONSTRAINT FK_2443196276C50E4A FOREIGN KEY (manager_id) REFERENCES user (id)";
if ($conn->query($sql) === TRUE) {
    echo "Foreign key constraint added successfully\n";
} else {
    echo "Error adding constraint: " . $conn->error . "\n";
}

$conn->close();
?>
