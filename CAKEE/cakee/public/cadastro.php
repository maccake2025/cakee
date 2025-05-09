<?php
session_start();
require_once('src/models/Usuario.php');
require_once('src/views/templates/header.php');

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $usuario = new Usuario($nome, $email, $senha);
    if (Usuario::cadastrar($usuario)) {
        header("Location: login.php");
        exit;
    } else {
        $erro = 'Ocorreu um erro ao cadastrar. Tente novamente.';
    }
}
?>
<main>
    <h1>Cadastrar</h1>
    <form action="cadastro.php" method="POST">
        <input type="text" name="nome" placeholder="Nome completo" required><br>
        <input type="email" name="email" placeholder="E-mail" required><br>
        <input type="password" name="senha" placeholder="Senha" required><br>
        <button type="submit">Cadastrar</button>
    </form>
    <?php if ($erro): ?>
        <p class="erro"><?= $erro; ?></p>
    <?php endif; ?>
</main>

<?php
require_once('src/views/templates/footer.php');
