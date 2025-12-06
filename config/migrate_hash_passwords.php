<?php
// Script to migrate plaintext passwords to password_hash() in the `user` table.
// Run once from CLI or web (prefer CLI): php migrate_hash_passwords.php

require __DIR__ . '/db.php';

try {
    $sql = 'SELECT id, mat_khau FROM user';
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_OBJ);

    $updated = 0;
    foreach ($users as $u) {
        $current = $u->mat_khau;
        // Skip if already looks like a password_hash() output (bcrypt starts with $2y$, $2a$, argon2 with $argon)
        if (is_string($current) && (strpos($current, '$2y$') === 0 || strpos($current, '$2a$') === 0 || strpos($current, '$argon2') === 0)) {
            // already hashed
            continue;
        }

        // Hash the existing plaintext password
        $newHash = password_hash($current, PASSWORD_DEFAULT);
        if ($newHash === false) continue;

        $upd = $connection->prepare('UPDATE user SET mat_khau = :hash WHERE id = :id');
        $ok = $upd->execute([':hash' => $newHash, ':id' => $u->id]);
        if ($ok) $updated++;
    }

    echo "Migration complete. Updated $updated users.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>