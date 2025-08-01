<?php
// pages/auth/login.php

// Verifica o status da sessão antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redireciona usuários já logados
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit();
}

// Inclui arquivos necessários
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/sanitize.php';

// Inicializa a conexão com o banco de dados
$db = new Database();
$conn = $db->connect();

// Variáveis para controle do formulário
$error = '';
$email = '';
$success = '';

// Processa o formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    try {
        // Busca o usuário no banco de dados
        $stmt = $conn->prepare("SELECT id, nome, email, senha, tipo, ativo FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Verifica se a conta está ativa
            if (!$user['ativo']) {
                $error = 'Sua conta está desativada. Entre em contato com o suporte.';
            } 
            // Verifica a senha
            elseif (password_verify($password, $user['senha'])) {
                // Login bem-sucedido
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nome'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['tipo'];
                
                // Cookie de "Lembrar de mim"
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + 60 * 60 * 24 * 30; // 30 dias
                    
                    // Armazena o token no banco de dados
                    $stmt = $conn->prepare("UPDATE usuarios SET remember_token = ?, token_expiry = ? WHERE id = ?");
                    $stmt->execute([$token, date('Y-m-d H:i:s', $expiry), $user['id']]);
                    
                    // Define o cookie
                    setcookie('remember', $token, $expiry, '/', '', true, true);
                }
                
                // Redireciona para a página inicial
                header('Location: ../../index.php');
                exit();
            } else {
                $error = 'Senha incorreta.';
            }
        } else {
            $error = 'Nenhuma conta encontrada com este email.';
        }
    } catch (PDOException $e) {
        $error = 'Erro ao processar login. Tente novamente mais tarde.';
        // Em produção, registrar o erro em um log
        // error_log('Login error: ' . $e->getMessage());
    }
}

// Verifica se há mensagem de sucesso (como após registro ou recuperação de senha)
if (isset($_GET['success'])) {
    $success = match($_GET['success']) {
        'registered' => 'Cadastro realizado com sucesso! Faça login para continuar.',
        'password_reset' => 'Senha redefinida com sucesso! Faça login com sua nova senha.',
        default => ''
    };
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cakee Market</title>
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="/melhorcakee/assets/images/favicon.ico">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-brand">
                <img src="../../assets/images/logo.png" alt="Cakee Market" class="auth-logo">
                <h1>Cakee Market</h1>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">E-mail</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" required 
                               value="<?= htmlspecialchars($email) ?>" 
                               placeholder="seu@email.com">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required 
                               placeholder="Sua senha">
                        <button type="button" class="password-toggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Lembrar de mim</span>
                    </label>
                    <a href="/pages/auth/forgot_password.php" class="text-link">Esqueceu a senha?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
                
                <div class="auth-divider">ou</div>
                
                <a href="/pages/auth/register.php" class="btn btn-outline btn-block">
                    <i class="fas fa-user-plus"></i> Criar nova conta
                </a>
            </form>
            
            <div class="auth-footer">
                <p>Ao continuar, você concorda com nossos <a href="/melhorcakee/pages/terms.php">Termos de Serviço</a> e <a href="/melhorcakee/pages/privacy.php">Política de Privacidade</a>.</p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.querySelector('.password-toggle').addEventListener('click', function() {
            const icon = this.querySelector('i');
            const passwordInput = document.getElementById('password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!email || !password) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos.');
            }
        });

        // Check for error messages and focus on the first error field
        window.addEventListener('DOMContentLoaded', () => {
            <?php if ($error && $email): ?>
                document.getElementById('password').focus();
            <?php elseif ($error): ?>
                document.getElementById('email').focus();
            <?php endif; ?>
        });
    </script>
</body>
</html>