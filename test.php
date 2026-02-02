<?php
require_once 'config/database.php';

$conn = Database::getInstance()->getConnection();

// Ver el usuario admin
$stmt = $conn->query("SELECT id, email, password FROM usuarios WHERE email = 'admin@apartamentoscyl.es'");
$user = $stmt->fetch();

echo "<pre>";
echo "Usuario encontrado:\n";
print_r($user);

echo "\n\nVerificando contraseña 'Admin123!':\n";
if ($user) {
    $resultado = password_verify('Admin123!', $user['password']);
    echo $resultado ? "✅ CONTRASEÑA CORRECTA" : "❌ CONTRASEÑA INCORRECTA";
    
    echo "\n\nHash actual en BD:\n" . $user['password'];
    
    echo "\n\nHash nuevo generado:\n";
    echo password_hash('Admin123!', PASSWORD_BCRYPT, ['cost' => 12]);
} else {
    echo "❌ No existe el usuario admin";
}
echo "</pre>";