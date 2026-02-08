<?php
// fix_admin_role.php
$dsn = 'mysql:host=127.0.0.1;dbname=esportify;charset=utf8mb4';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Force Admin user to have ROLE_ADMIN
    $email = 'admin@admin.com'; 
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        $updateStmt = $pdo->prepare("UPDATE user SET role = 'ROLE_ADMIN' WHERE id = :id");
        $updateStmt->execute(['id' => $admin['id']]);
        echo "Successfully updated user 'admin@admin.com' to include ROLE_ADMIN.\n";
    } else {
        echo "Admin user 'admin@admin.com' not found. Creating one...\n";
        // Create user if not exists (password: 'password')
        $hash = '$2y$13$E9.p.t6vL.t.t.t.t.t.t.t.t.t.t.t.t'; // Dummy hash or let user register
        // Actually better to just warn
        echo "Please register a user with email 'admin@admin.com' first.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
