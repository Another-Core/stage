<?php
// Change the username or password if your XAMPP MySQL has different settings.
$pdo = new PDO(
    'mysql:host=localhost;dbname=shift_assignment;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);