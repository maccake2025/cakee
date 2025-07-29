<?php
// Sempre inicia a sessão antes de usar $_SESSION
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../../config/database.php';

// Verifica se está logado
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (!$user_id) {
    header('Location: /pages/login.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

$error = '';
$success = '';

// Busca dados do usuário
$stmt = $conn->prepare("SELECT id, nome, email, telefone, endereco, foto_perfil, tipo FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: /pages/login.php');
    exit();
}

// Atualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nome = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);
    $endereco = trim($_POST['endereco']);

    // Upload de foto de perfil (opcional)
    $foto_perfil = $user['foto_perfil'];
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $new_name = 'profile_' . $user_id . '_' . time() . '.' . $ext;
            $dest = __DIR__ . '/../../assets/images/uploads/profiles/' . $new_name;
            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $dest)) {
                $foto_perfil = $new_name;
            } else {
                $error = 'Falha ao fazer upload da foto.';
            }
        } else {
            $error = 'Formato de imagem inválido.';
        }
    }

    if (empty($error)) {
        $stmt = $conn->prepare("UPDATE usuarios SET nome = ?, telefone = ?, endereco = ?, foto_perfil = ? WHERE id = ?");
        $stmt->execute([$nome, $telefone, $endereco, $foto_perfil, $user_id]);
        $success = "Perfil atualizado com sucesso!";
        // Atualiza os dados exibidos
        $user['nome'] = $nome;
        $user['telefone'] = $telefone;
        $user['endereco'] = $endereco;
        $user['foto_perfil'] = $foto_perfil;
    }
}

// Alterar senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $hash = $stmt->fetchColumn();

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = 'Preencha todos os campos de senha.';
    } elseif (!password_verify($current, $hash)) {
        $error = 'Senha atual incorreta.';
    } elseif (strlen($new) < 6) {
        $error = 'A nova senha deve ter pelo menos 6 caracteres.';
    } elseif ($new !== $confirm) {
        $error = 'A confirmação da senha não corresponde.';
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $user_id]);
        $success = 'Senha alterada com sucesso!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil - Cakee Market</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .profile-container { max-width: 600px; margin: 0 auto; padding: 30px 0; }
        .profile-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 9px rgba(0,0,0,0.06); padding: 22px 25px; margin-bottom: 25px; }
        .profile-pic { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 2px solid #e77054; margin-bottom: 14px; }
        .alert { margin-bottom: 15px; padding: 10px 15px; border-radius: 5px; }
        .alert.success { background: #e6f9ef; color: #1c7e56; border: 1px solid #b3f1cd; }
        .alert.error { background: #ffeaea; color: #c00; border: 1px solid #f3bcbc; }
        .form-group { margin-bottom: 16px; }
        .profile-label { font-weight: bold; color: #444; margin-bottom: 3px; display:block;}
        .profile-value { color: #333; margin-bottom: 8px; }
        .btn { background: #e77054; color: #fff; padding: 8px 19px; border-radius: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #d3593e; }
        .profile-section { margin-bottom: 32px; }
        @media (max-width: 700px) {
            .profile-container { padding: 10px 2px; }
            .profile-card { padding: 14px 7px; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="profile-container">
    <h1>Meu Perfil</h1>
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <div class="profile-card profile-section">
        <h2>Informações Pessoais</h2>
        <form method="POST" enctype="multipart/form-data">
            <div style="text-align:center">
                <?php if (!empty($user['foto_perfil'])): ?>
                    <img src="/assets/images/uploads/profiles/<?= htmlspecialchars($user['foto_perfil']) ?>" alt="Foto de perfil" class="profile-pic">
                <?php else: ?>
                    <img src="/assets/images/default_profile.png" alt="Foto de perfil" class="profile-pic">
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="profile-label" for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($user['nome']) ?>" required>
            </div>
            <div class="form-group">
                <label class="profile-label" for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" readonly style="background:#f6f6f6">
            </div>
            <div class="form-group">
                <label class="profile-label" for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($user['telefone']) ?>">
            </div>
            <div class="form-group">
                <label class="profile-label" for="endereco">Endereço</label>
                <textarea id="endereco" name="endereco" rows="2"><?= htmlspecialchars($user['endereco']) ?></textarea>
            </div>
            <div class="form-group">
                <label class="profile-label" for="foto_perfil">Foto de Perfil</label>
                <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*">
            </div>
            <button type="submit" name="update_profile" class="btn">Atualizar Perfil</button>
        </form>
    </div>
    <div class="profile-card profile-section">
        <h2>Alterar Senha</h2>
        <form method="POST">
            <div class="form-group">
                <label class="profile-label" for="current_password">Senha Atual</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label class="profile-label" for="new_password">Nova Senha</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-group">
                <label class="profile-label" for="confirm_password">Confirmar Nova Senha</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" name="change_password" class="btn">Alterar Senha</button>
        </form>
    </div>
    <div style="text-align:center;margin:24px 0;">
        <a href="/pages/vendor/dashboard.php" class="btn" style="background:#888;">Voltar ao painel</a>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>