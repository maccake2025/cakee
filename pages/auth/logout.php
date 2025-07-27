<?php
// pages/auth/logout.php

// Verifica o status da sessão antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Remove apenas dados de sessão do usuário
unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_type']);

// Remove o cookie "remember" se existir
if (isset($_COOKIE['remember'])) {
    // Remover do banco de dados (opcional, se usar token no banco)
    require_once __DIR__ . '/../../config/database.php';
    $db = new Database();
    $conn = $db->connect();

    // Tenta limpar o token do usuário logado ou pelo cookie
    $token = $_COOKIE['remember'];
    $stmt = $conn->prepare("UPDATE usuarios SET remember_token = NULL, token_expiry = NULL WHERE remember_token = ?");
    $stmt->execute([$token]);

    // Remove o cookie do navegador
    setcookie('remember', '', time() - 3600, '/', '', true, true);
}

// Limpa toda a sessão (se desejar remover tudo do usuário)
session_destroy();

// Redireciona para a página de login
header('Location: /pages/auth/login.php?success=logout');
exit();
?>