<?php
session_start();
require_once('src/models/Usuario.php');
require_once('src/views/templates/header.php');

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $usuario = Usuario::autenticar($email, $senha);
    if ($usuario) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        header("Location: index.php");
        exit;
    } else {
        $erro = 'E-mail ou senha inválidos.';
    }
}
?>
<main>
    <h1>Login</h1>
    <form action="login.php" method="POST">
        <input type="email" name="email" placeholder="E-mail" required><br>
        <input type="password" name="senha" placeholder="Senha" required><br>
        <button type="submit">Entrar</button>
    </form>
    <?php if ($erro): ?>
        <p class="erro"><?= $erro; ?></p>
    <?php endif; ?>
</main>

<?php
require_once('src/views/templates/footer.php');
