<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/sanitize.php';

$db = new Database();
$conn = $db->connect();

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id < 1) {
    header('Location: /pages/products.php');
    exit();
}

// Buscar produto e vendedor
$stmt = $conn->prepare("
    SELECT p.*, u.nome as vendedor_nome, u.foto_perfil as vendedor_foto, u.email as vendedor_email
    FROM produtos p
    JOIN usuarios u ON p.vendedor_id = u.id
    WHERE p.id = ? AND p.ativo = 1
    LIMIT 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

$product_not_found = !$product;

// Imagens adicionais
$additional_images = [];
if (!$product_not_found && !empty($product['imagens_adicionais'])) {
    $additional_images = json_decode($product['imagens_adicionais'], true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title><?= !$product_not_found ? htmlspecialchars($product['nome']) . ' - Mac Cake' : 'Produto não encontrado - Mac Cake' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= !$product_not_found ? htmlspecialchars($product['descricao']) : 'Produto não encontrado' ?>">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/product_detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="../assets/js/review-stars.js"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="product-detail-page">
        <?php if ($product_not_found): ?>
            <div class="product-not-found">
                <h1>Produto não encontrado</h1>
                <p>Este produto não está disponível ou foi removido.</p>
                <a href="/pages/products.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Voltar para produtos</a>
            </div>
        <?php else: ?>
            <section class="product-main">
                <div class="product-gallery">
                    <div class="main-image">
                        <?php 
                        $main_image_path = '../assets/images/uploads/products/' . htmlspecialchars($product['imagem_principal']);
                        $default_image = '../assets/images/default-product.jpg';
                        ?>
                        <img src="<?= file_exists($main_image_path) ? $main_image_path : $default_image ?>" 
                             alt="<?= htmlspecialchars($product['nome']) ?>"
                             onerror="this.src='<?= $default_image ?>'">
                    </div>
                    <?php if ($additional_images): ?>
                        <div class="additional-images">
                            <?php foreach ($additional_images as $img): ?>
                                <img src="../assets/images/uploads/products/<?= htmlspecialchars($img) ?>" 
                                     alt="Imagem adicional de <?= htmlspecialchars($product['nome']) ?>"
                                     onclick="document.querySelector('.main-image img').src = this.src;">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h1><?= htmlspecialchars($product['nome']) ?></h1>
                    <div class="product-pricing">
                        <?php if (!empty($product['preco_promocional']) && $product['preco_promocional'] > 0): ?>
                            <span class="original-price">R$ <?= number_format($product['preco'], 2, ',', '.') ?></span>
                            <span class="promo-price">R$ <?= number_format($product['preco_promocional'], 2, ',', '.') ?></span>
                            <span class="discount-badge">
                                -<?= ceil(100 - ($product['preco_promocional'] / $product['preco'] * 100)) ?>%
                            </span>
                        <?php else: ?>
                            <span class="price">R$ <?= number_format($product['preco'], 2, ',', '.') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-details">
                        <p><strong>Categoria:</strong> <?= htmlspecialchars($product['categoria'] ?: 'Indefinida') ?></p>
                        <p><strong>Estoque disponível:</strong> 
                            <?php if ($product['estoque'] > 0): ?>
                                <span class="in-stock"><?= $product['estoque'] ?> unidade<?= $product['estoque'] > 1 ? 's' : '' ?></span>
                            <?php else: ?>
                                <span class="out-of-stock">Esgotado</span>
                            <?php endif; ?>
                        </p>
                        <?php if ($product['peso']): ?>
                            <p><strong>Peso:</strong> <?= number_format($product['peso'], 0) ?>g</p>
                        <?php endif; ?>
                        <?php if ($product['tempo_preparo']): ?>
                            <p><strong>Tempo de preparo:</strong> <?= $product['tempo_preparo'] ?> min</p>
                        <?php endif; ?>
                    </div>
                    <div class="product-actions">
                        <?php if ($product['estoque'] > 0): ?>
                            <button class="btn btn-primary add-to-cart-btn" data-id="<?= $product['id'] ?>">
                                <i class="fas fa-shopping-cart"></i> Adicionar ao carrinho
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-ban"></i> Indisponível
                            </button>
                        <?php endif; ?>
                        <a href="/pages/products.php" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Voltar para produtos
                        </a>
                    </div>
                </div>
            </section>
            <section class="product-description">
                <h2>Descrição</h2>
                <p><?= nl2br(htmlspecialchars($product['descricao'])) ?></p>
                <?php if ($product['ingredientes']): ?>
                    <h3>Ingredientes</h3>
                    <p><?= nl2br(htmlspecialchars($product['ingredientes'])) ?></p>
                <?php endif; ?>
            </section>
            <section class="seller-info">
                <h2>Informações do Vendedor</h2>
                <div class="seller-card">
                    <?php if ($product['vendedor_foto']): ?>
                        <img src="/assets/images/uploads/profiles/<?= htmlspecialchars($product['vendedor_foto']) ?>" 
                             alt="Foto de <?= htmlspecialchars($product['vendedor_nome']) ?>" class="seller-avatar">
                    <?php else: ?>
                        <i class="fas fa-user-circle seller-avatar"></i>
                    <?php endif; ?>
                    <div class="seller-details">
                        <span class="seller-name"><?= htmlspecialchars($product['vendedor_nome']) ?></span>
                        <span class="seller-email"><i class="fas fa-envelope"></i> <?= htmlspecialchars($product['vendedor_email']) ?></span>
                    </div>
                </div>
            </section>
            <section class="product-reviews">
                <h2>Avaliações dos clientes</h2>
                <div id="review-ajax-block"></div>
            </section>
        <?php endif; ?>
    </main>
    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
    // AJAX para avaliações
    function updateReviews() {
        const reviewBlock = document.getElementById('review-ajax-block');
        fetch('review_ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=get&product_id=<?= $product_id ?>'
        })
        .then(response => response.json())
        .then(data => {
            reviewBlock.innerHTML = data.html || '<p>Erro ao carregar avaliações.</p>';
            bindReviewForms();
            if (window.bindStarsInput) bindStarsInput();
        });
    }
    function bindReviewForms() {
        // Adicionar avaliação
        const addForm = document.querySelector('.add-review-form');
        if (addForm) {
            addForm.onsubmit = function(e) {
                e.preventDefault();
                const fd = new FormData(addForm);
                fd.append('action', 'add');
                fd.append('product_id', '<?= $product_id ?>');
                fetch('review_ajax.php', {method: 'POST', body: fd})
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    updateReviews();
                });
            };
        }
        // Editar avaliação
        const editForm = document.querySelector('.edit-review-form');
        if (editForm) {
            editForm.onsubmit = function(e) {
                e.preventDefault();
                const fd = new FormData(editForm);
                fd.append('action', 'edit');
                fd.append('review_id', editForm.dataset.reviewId);
                fd.append('product_id', '<?= $product_id ?>');
                fetch('review_ajax.php', {method: 'POST', body: fd})
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    updateReviews();
                });
            };
            // Apagar avaliação
            const delBtn = editForm.querySelector('.delete-review-btn');
            if (delBtn) {
                delBtn.onclick = function(e) {
                    e.preventDefault();
                    if (!confirm("Tem certeza que deseja apagar sua avaliação?")) return;
                    const fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('review_id', delBtn.dataset.reviewId);
                    fd.append('product_id', '<?= $product_id ?>');
                    fetch('review_ajax.php', {method: 'POST', body: fd})
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        updateReviews();
                    });
                }
            }
        }
    }
    document.addEventListener('DOMContentLoaded', updateReviews);
    </script>
</body>
</html>