<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <title>Mac Cake</title>
</head>
<body>
    <!-- Cabeçalho -->
    <header>
        <!-- Ícone de Menu para Dispositivos Móveis -->
        <div class="menu-icon" onclick="toggleMenu()" aria-label="Abrir menu">&#9776;</div>

        <!-- Logos -->
        <div class="logo-container">
            <a href="/index.html">
                <img src="/assets/images/MAC CAKE LOGO.png" alt="Logo da Mac Cake" class="primeira-logo">
            </a>
            <a href="/quemsomos.php">
                <img src="/assets/images/Mac Cake_2_base.png" alt="Segunda Logo da Mac Cake" class="segunda-logo">
            </a>
        </div>

        <!-- Navegação -->
        <nav id="mainNav">
            <ul class="menu-list">
                <li><a href="/contato.php" class="sem-sublinhado" onclick="closeMenu()">Contato</a></li>
                <li><a href="/servicos.php" class="sem-sublinhado" onclick="closeMenu()">Serviços</a></li>
            </ul>

            <!-- Carrinho de Compras -->
            <div class="carrinho-container">
                <a href="/carrinho.php">
                    <img src="/assets/images/carrinho de compras.png" alt="Ícone do Carrinho de Compras" class="imagem-pequena">
                </a>
            </div>
        </nav>
    </header>

    <!-- Toldo -->
    <div class="toldo">
        <img src="/assets/images/toldo_base site div.png" alt="Toldo decorativo do site">
    </div>
