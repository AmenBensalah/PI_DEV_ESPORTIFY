<?php
// fix_user_roles.php
$dsn = 'mysql:host=127.0.0.1;dbname=esportify;charset=utf8mb4';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Check for users with invalid or empty roles
    $stmt = $pdo->query("SELECT * FROM user WHERE role = '' OR role IS NULL");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($users) . " users with invalid roles.\n";

    foreach ($users as $user) {
        echo "Fixing user ID: " . $user['id'] . " (" . $user['email'] . ")\n";
        
        // Default to ROLE_JOUEUR
        $updateStmt = $pdo->prepare("UPDATE user SET role = 'ROLE_JOUEUR' WHERE id = :id");
        $updateStmt->execute(['id' => $user['id']]);
        
        echo "  -> Set role to ROLE_JOUEUR\n";
    }

    // 2. Also check if there are other roles not in the Enum (just in case)
    // Valid roles: ROLE_ADMIN, ROLE_JOUEUR, ROLE_MANAGER, ROLE_ORGANISATEUR
    $validRoles = ['ROLE_ADMIN', 'ROLE_JOUEUR', 'ROLE_MANAGER', 'ROLE_ORGANISATEUR'];
    $placeholders = implode(',', array_fill(0, count($validRoles), '?'));
    
    $stmt = $pdo->prepare("SELECT * FROM user WHERE role NOT IN ($placeholders) AND role != '' AND role IS NOT NULL");
    $stmt->execute($validRoles);
    $weirdUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($weirdUsers) > 0) {
        echo "Found " . count($weirdUsers) . " users with unknown values (not empty, but not standard):\n";
        foreach ($weirdUsers as $user) {
            echo "  User ID: " . $user['id'] . " has role: '" . $user['role'] . "'\n";
             // Optional: Force fix these too? Maybe strictly safer to leave them or set to JOUEUR.
             // Let's set them to JOUEUR to avoid hydration errors.
             $updateStmt = $pdo->prepare("UPDATE user SET role = 'ROLE_JOUEUR' WHERE id = :id");
             $updateStmt->execute(['id' => $user['id']]);
             echo "  -> Fixed to ROLE_JOUEUR\n";
        }
    }

    echo "Done.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
