<?php
// reset_admin_password.php
require 'vendor/autoload.php';

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

// 1. Database Connection
$dsn = 'mysql:host=127.0.0.1;dbname=esportify;charset=utf8mb4';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Hash "password"
    // Using default PHP password_hash which Symfony uses by default for 'auto'
    $newPassword = password_hash('password', PASSWORD_BCRYPT);

    // 3. Update User
    $email = 'admin@admin.com';
    $stmt = $pdo->prepare("UPDATE user SET password = :password, role = 'ROLE_ADMIN' WHERE email = :email");
    $stmt->execute([
        'password' => $newPassword,
        'email' => $email
    ]);

    if ($stmt->rowCount() > 0) {
        echo "Password for '$email' has been reset to 'password'.\n";
    } else {
        // User might not exist or password was already same
        // Check if user exists
        $check = $pdo->prepare("SELECT id FROM user WHERE email = :email");
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
             echo "User '$email' exists but password wasn't changed (maybe it was already 'password'?)\n";
        } else {
             // Create the user
             $insert = $pdo->prepare("INSERT INTO user (email, password, nom, role, pseudo) VALUES (:email, :password, 'Admin', 'ROLE_ADMIN', 'Admin')");
             $insert->execute([
                'email' => $email,
                'password' => $newPassword
             ]);
             echo "User '$email' created with password 'password'.\n";
        }
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
