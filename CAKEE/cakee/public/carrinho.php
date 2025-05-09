<?php
session_start();
require_once('../src/config/database.php');
require_once('../src/models/Produto.php');

// Inicializa o carrinho na sessão, se ainda não estiver definido
if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

// Função para remover produto do carrinho
if (isset($_GET['remover'])) {
    $idRemover = $_GET['remover'];
    unset($_SESSION['carrinho'][$idRemover]);
}

// Lista de produtos no carrinho
$produtosNoCarrinho = [];
$total = 0;

foreach ($_SESSION['carrinho'] as $id => $quantidade) {
    $produto = Produto::buscarPorId($id);
    if ($produto) {
        $produto['quantidade'] = $quantidade;
        $produto['subtotal'] = $produto['preco'] * $quantidade;
        $total += $produto['subtotal'];
        $produtosNoCarrinho[] = $produto;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mac Cake - Carrinho</title>
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
    <h1>Seu Carrinho</h1>

    <?php if (count($produtosNoCarrinho) > 0): ?>
        <table class="carrinho-tabela">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Preço</th>
                    <th>Quantidade</th>
                    <th>Subtotal</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtosNoCarrinho as $produto): ?>
                    <tr>
                        <td><?= htmlspecialchars($produto['nome']) ?></td>
                        <td>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                        <td><?= $produto['quantidade'] ?></td>
                        <td>R$ <?= number_format($produto['subtotal'], 2, ',', '.') ?></td>
                        <td><a href="?remover=<?= $produto['id'] ?>" class="btn-remover">Remover</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="carrinho-total">
            <h3>Total: R$ <?= number_format($total, 2, ',', '.') ?></h3>
            <button onclick="finalizarCompra()">Finalizar Compra</button>
        </div>
    <?php else: ?>
        <p>Seu carrinho está vazio.</p>
    <?php endif; ?>
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
function finalizarCompra() {
    alert('Compra finalizada! Obrigado por escolher a Mac Cake.');
    // Aqui você pode limpar o carrinho e redirecionar
}
</script>

</body>
</html>
