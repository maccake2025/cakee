<?php
require_once('../src/config/database.php');
require_once('../src/models/Produto.php');

$produtos = Produto::listarTodos();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mac Cake - Produtos</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- Cabeçalho -->
<header>
    <div class="menu-icon" onclick="toggleMenu()" aria-label="Abrir menu">&#9776;</div>

    <div class="logo-container">
        <a href="index.html">
            <img src="assets/images/MAC CAKE LOGO.png" alt="Logo da Mac Cake" class="primeira-logo">
        </a>
        <a href="quemsomos.php">
            <img src="assets/images/Mac Cake_2_base.png" alt="Segunda Logo" class="segunda-logo">
        </a>
    </div>

    <nav id="mainNav">
        <ul class="menu-list">
            <li><a href="contato.php" class="sem-sublinhado" onclick="closeMenu()">Contato</a></li>
            <li><a href="servicos.php" class="sem-sublinhado" onclick="closeMenu()">Serviços</a></li>
            <li><a href="login.php" class="sem-sublinhado" onclick="closeMenu()">Login</a></li>
            <li><a href="cadastro.php" class="sem-sublinhado" onclick="closeMenu()">Cadastrar</a></li>
        </ul>
        <div class="carrinho-container">
            <a href="carrinho.php">
                <img src="assets/images/carrinho de compras.png.png" alt="Carrinho" class="imagem-pequena">
            </a>
        </div>
    </nav>
</header>

<!-- Toldo -->
<div class="toldo">
    <img src="assets/images/toldo_base site div.png" alt="Toldo decorativo do site">
</div>

<!-- Conteúdo -->
<main>
    <h1>Nossos Produtos</h1>

    <div class="produtos-grid">
        <?php foreach ($produtos as $produto): ?>
            <div class="produto-card">
                <img src="<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                <h2><?= htmlspecialchars($produto['nome']) ?></h2>
                <p>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                <button onclick="adicionarAoCarrinho(<?= $produto['id'] ?>)">Adicionar ao Carrinho</button>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<!-- Rodapé -->
<footer class="footer">
    <div class="copyright">
        <a href="https://www.facebook.com/profile.php?id=61573112745752" target="_blank">
            <img src="assets/images/icons8-facebook-logo-80.png">
        </a>
        <a href="https://github.com/maccake2025" target="_blank">
            <img src="assets/images/icons8-github-80x80.png">
        </a>
        <a href="https://www.instagram.com/macc.ake/" target="_blank">
            <img src="assets/images/icons8-instagram-logo-80.png">
        </a>
        <p>&copy; 2025 Mac Cake. Todos os direitos reservados.</p>
    </div>
</footer>

<!-- Scripts -->
<script src="assets/js/main.js"></script>
<script>
function adicionarAoCarrinho(produtoId) {
    alert("Produto " + produtoId + " adicionado ao carrinho!");
    // Aqui você pode fazer uma requisição AJAX ou redirecionar
}
</script>

</body>
</html>
