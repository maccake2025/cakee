<?php
session_start();
// Caminho correto para acessar o config/database.php a partir de /pages/auth/
require_once dirname(__DIR__, 2) . '/config/database.php';

function sanitize($str) {
    return htmlspecialchars(strip_tags(trim($str)));
}

$db = new Database();
$conn = $db->connect();

$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';
$email = isset($_GET['email']) ? sanitize($_GET['email']) : '';
$success = false;
$error = '';
$form_visible = true;

// Processa o POST (redefinição da senha)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = sanitize($_POST['token'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $senha1 = $_POST['senha1'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';

    if (!$senha1 || !$senha2) {
        $error = "Preencha todos os campos.";
    } elseif ($senha1 !== $senha2) {
        $error = "As senhas não coincidem.";
    } elseif (strlen($senha1) < 6) {
        $error = "A senha deve ter pelo menos 6 caracteres.";
    } elseif (!$token || !$email) {
        $error = "Link de redefinição inválido.";
    } else {
        // Busca usuário pelo e-mail, token e validade
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND token_reset_senha = ? AND token_expira >= NOW() AND ativo = 1 LIMIT 1");
        $stmt->execute([$email, $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = "Link inválido ou expirado. Solicite uma nova redefinição.";
            $form_visible = false;
        } else {
            // Atualiza senha e limpa o token
            $senha_hash = password_hash($senha1, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET senha = ?, token_reset_senha = NULL, token_expira = NULL WHERE id = ?");
            $stmt->execute([$senha_hash, $user['id']]);
            $success = true;
            $form_visible = false;
        }
    }
} else if ($token && $email) {
    // Checa se o token é válido ao abrir o link
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND token_reset_senha = ? AND token_expira >= NOW() AND ativo = 1 LIMIT 1");
    $stmt->execute([$email, $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $error = "Link inválido ou expirado. Solicite uma nova redefinição.";
        $form_visible = false;
    }
} else {
    $error = "Link de redefinição inválido.";
    $form_visible = false;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha - Mac Cake</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .senha-olho { cursor: pointer; position: absolute; right: 16px; top: 34px; color: #888; }
        .senha-group { position: relative; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1><i class="fas fa-lock"></i> Redefinir Senha</h1>
            <?php if ($success): ?>
                <div class="alert alert-sucesso">
                    Senha redefinida com sucesso!<br>
                    Agora você pode <a href="login.php">fazer login</a> normalmente.
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-erro"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($form_visible): ?>
                <form method="post" class="form-auth">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    <div class="senha-group">
                        <label for="senha1">Nova senha</label>
                        <input type="password" id="senha1" name="senha1" required minlength="6" autocomplete="new-password" placeholder="Nova senha">
                        <span class="senha-olho" onclick="toggleSenha('senha1', this)"><i class="fas fa-eye"></i></span>
                    </div>
                    <div class="senha-group">
                        <label for="senha2">Confirme a nova senha</label>
                        <input type="password" id="senha2" name="senha2" required minlength="6" autocomplete="new-password" placeholder="Confirme a nova senha">
                        <span class="senha-olho" onclick="toggleSenha('senha2', this)"><i class="fas fa-eye"></i></span>
                    </div>
                    <button type="submit" class="btn btn-primario"><i class="fas fa-save"></i> Redefinir senha</button>
                </form>
                <div class="auth-links">
                    <a href="login.php"><i class="fas fa-arrow-left"></i> Voltar ao login</a>
                </div>
            <?php else: ?>
                <div class="auth-links">
                    <a href="forgot_password.php"><i class="fas fa-undo"></i> Solicitar novo link</a>
                    <a href="login.php"><i class="fas fa-arrow-left"></i> Voltar ao login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    function toggleSenha(id, el) {
        const input = document.getElementById(id);
        if (input.type === "password") {
            input.type = "text";
            el.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
            input.type = "password";
            el.innerHTML = '<i class="fas fa-eye"></i>';
        }
    }
    </script>
</body>
</html>