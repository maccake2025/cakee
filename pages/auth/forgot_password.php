<?php
session_start();
// Caminho correto para acessar o config/database.php a partir de /pages/auth/
require_once dirname(__DIR__, 2) . '/config/database.php';

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

$db = new Database();
$conn = $db->connect();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Por favor, informe um e-mail válido.";
    } else {
        // Busca usuário ativo
        $stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Por segurança, sempre retorna sucesso
            $success = true;
        } else {
            // Gera token seguro
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Salva token e expiração
            $stmt = $conn->prepare("UPDATE usuarios SET token_reset_senha = ?, token_expira = ? WHERE id = ?");
            $stmt->execute([$token, $expira, $user['id']]);

            // Monta link de redefinição
            $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $link = $protocolo . "://" . $_SERVER['HTTP_HOST'] . "/pages/auth/reset_password.php?token=$token&email=" . urlencode($email);

            // Em ambiente de desenvolvimento, exibe o link ao invés de enviar o e-mail
            $_SESSION['reset_link'] = $link;
            // Em produção, use a linha abaixo (descomente):
            // @mail($to, $subject, $message, $headers);

            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha - Mac Cake</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1><i class="fas fa-unlock-alt"></i> Recuperar Senha</h1>
            <?php if ($success): ?>
                <div class="alert alert-sucesso">
                    Se o e-mail informado existir em nosso sistema, você receberá um link para redefinir sua senha em instantes.<br>
                    Não esqueça de verificar sua caixa de spam!
                </div>
                <?php if (isset($_SESSION['reset_link'])): ?>
                    <div style="background:#fffbe5;padding:15px;margin:15px 0 0 0;border-radius:8px;color:#d48200;">
                        <b>Ambiente de desenvolvimento:</b><br>
                        <small>Link de redefinição de senha:</small><br>
                        <a href="<?= $_SESSION['reset_link'] ?>" style="word-break:break-all;"><?= $_SESSION['reset_link'] ?></a>
                    </div>
                    <?php unset($_SESSION['reset_link']); ?>
                <?php endif; ?>
                <a href="login.php" class="btn btn-primario" style="margin-top:20px;">Voltar ao login</a>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-erro"><?= $error ?></div>
                <?php endif; ?>
                <form method="post" class="form-auth">
                    <label for="email">E-mail cadastrado</label>
                    <input type="email" id="email" name="email" required autocomplete="email" placeholder="seu@email.com">
                    <button type="submit" class="btn btn-primario"><i class="fas fa-envelope"></i> Enviar link de recuperação</button>
                </form>
                <div class="auth-links">
                    <a href="login.php"><i class="fas fa-arrow-left"></i> Voltar ao login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>