<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

// Função de sanitização
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

try {
    $db = new Database();
    $conn = $db->connect();

    // Paginação
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $page = max(1, $page); // Garante que a página não seja menor que 1
    $per_page = 12;
    $offset = ($page - 1) * $per_page;

    // Filtros
    $category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
    $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

    // Query base
    $query = "SELECT p.*, u.nome as vendedor_nome 
              FROM produtos p 
              JOIN usuarios u ON p.vendedor_id = u.id 
              WHERE p.ativo = 1";

    $params = [];

    // Adicionar filtros
    if (!empty($category)) {
        $query .= " AND p.categoria = ?";
        $params[] = $category;
    }

    if (!empty($search)) {
        $query .= " AND (p.nome LIKE ? OR p.descricao LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Contar total de produtos
    $count_query = "SELECT COUNT(*) as total FROM ($query) as total_query";
    $stmt = $conn->prepare($count_query);
    $stmt->execute($params);
    $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_products / $per_page);

    // Ajustar página se ultrapassar o total
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
        $offset = ($page - 1) * $per_page;
    }

    // Buscar produtos com paginação
    $query .= " ORDER BY p.data_cadastro DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;

    $stmt = $conn->prepare($query);
    foreach ($params as $index => $value) {
        $paramType = PDO::PARAM_STR;
        // Os dois últimos parâmetros são inteiros
        if ($index >= count($params) - 2) {
            $paramType = PDO::PARAM_INT;
        }
        $stmt->bindValue($index + 1, $value, $paramType);
    }

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar categorias para filtro
    $categories_query = $conn->prepare("SELECT DISTINCT categoria FROM produtos WHERE ativo = 1 AND categoria IS NOT NULL ORDER BY categoria");
    $categories_query->execute();
    $categories = $categories_query->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Erro ao conectar com o banco de dados. Por favor, tente novamente mais tarde.";
    $products = [];
    $categories = [];
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Mac Cake</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/produto.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="products-container">
        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <p><?= $error_message ?></p>
            </div>
        <?php endif; ?>

        <div class="filters-section">
            <h1>Nossos Produtos</h1>
            
            <form method="GET" class="search-form">
                <div class="search-input-container">
                    <input type="text" name="search" placeholder="Pesquisar produtos..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <?php if (!empty($search) || !empty($category)): ?>
                    <a href="products.php" class="btn-clear">Limpar filtros</a>
                <?php endif; ?>
            </form>
            
            <div class="category-filter">
                <h2>Categorias</h2>
                <ul>
                    <li><a href="products.php" class="<?= empty($category) ? 'active' : '' ?>">Todas as categorias</a></li>
                    <?php foreach ($categories as $cat): ?>
                        <li>
                            <a href="products.php?category=<?= urlencode($cat) ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                               class="<?= $category === $cat ? 'active' : '' ?>">
                                <?= htmlspecialchars($cat) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <div class="products-grid">
            <?php if (empty($products)): ?>
                <div class="no-products">
                    <p>Nenhum produto encontrado.</p>
                    <a href="products.php" class="btn-primary">Ver todos os produtos</a>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a href="product_detail.php?id=<?= $product['id'] ?>">
                            <div class="product-image">
                                <?php 
                                $imagePath = '../assets/images/uploads/products/' . htmlspecialchars($product['imagem_principal']);
                                $defaultImage = '../assets/images/default-product.jpg';
                                ?>
                                <img src="<?= file_exists($imagePath) ? $imagePath : $defaultImage ?>" 
                                     alt="<?= htmlspecialchars($product['nome']) ?>"
                                     onerror="this.src='<?= $defaultImage ?>'">
                                <?php if ($product['preco_promocional'] > 0): ?>
                                    <span class="discount-badge">-<?= 
                                        ceil(100 - ($product['preco_promocional'] / $product['preco'] * 100)) 
                                    ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h3><?= htmlspecialchars($product['nome']) ?></h3>
                                <div class="price-container">
                                    <?php if ($product['preco_promocional'] > 0): ?>
                                        <span class="original-price">R$ <?= number_format($product['preco'], 2, ',', '.') ?></span>
                                        <span class="promo-price">R$ <?= number_format($product['preco_promocional'], 2, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span class="price">R$ <?= number_format($product['preco'], 2, ',', '.') ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="seller">Vendedor: <?= htmlspecialchars($product['vendedor_nome']) ?></p>
                                <?php if ($product['estoque'] > 0): ?>
                                    <p class="stock">Disponível: <?= $product['estoque'] ?> un.</p>
                                <?php else: ?>
                                    <p class="stock out-of-stock">Esgotado</p>
                                <?php endif; ?>
                            </div>
                        </a>
                        <button class="add-to-cart" data-id="<?= $product['id'] ?>" <?= $product['estoque'] <= 0 ? 'disabled' : '' ?>>
                            <?= $product['estoque'] > 0 ? 'Adicionar ao Carrinho' : 'Indisponível' ?>
                            <?php if ($product['estoque'] > 0): ?>
                                <i class="fas fa-shopping-cart"></i>
                            <?php endif; ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?= !empty($category) ? '&category=' . urlencode($category) : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                       class="page-link first-page" title="Primeira página">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?page=<?= $page - 1 ?><?= !empty($category) ? '&category=' . urlencode($category) : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                       class="page-link" title="Página anterior">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php 
                // Mostrar até 5 páginas ao redor da atual
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                if ($start > 1) {
                    echo '<a href="?page=1'.(!empty($category) ? '&category='.urlencode($category) : '').(!empty($search) ? '&search='.urlencode($search) : '').'" class="page-link">1</a>';
                    if ($start > 2) echo '<span class="page-dots">...</span>';
                }
                
                for ($i = $start; $i <= $end; $i++): ?>
                    <a href="?page=<?= $i ?><?= !empty($category) ? '&category=' . urlencode($category) : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                       class="page-link <?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; 
                
                if ($end < $total_pages) {
                    if ($end < $total_pages - 1) echo '<span class="page-dots">...</span>';
                    echo '<a href="?page='.$total_pages.(!empty($category) ? '&category='.urlencode($category) : '').(!empty($search) ? '&search='.urlencode($search) : '').'" class="page-link">'.$total_pages.'</a>';
                }
                ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($category) ? '&category=' . urlencode($category) : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                       class="page-link" title="Próxima página">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?page=<?= $total_pages ?><?= !empty($category) ? '&category=' . urlencode($category) : '' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                       class="page-link last-page" title="Última página">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    <script>
        // Adicionar ao carrinho via AJAX
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-id');
                
                if (this.disabled) return;
                
                // Mostrar feedback visual
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adicionando...';
                this.disabled = true;
                
                fetch('../includes/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=1`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Feedback visual
                        this.innerHTML = '<i class="fas fa-check"></i> Adicionado!';
                        
                        // Atualizar contador do carrinho no header
                        const cartCountElement = document.getElementById('cart-count');
                        if (cartCountElement) {
                            cartCountElement.textContent = data.cart_count;
                            cartCountElement.classList.add('pulse');
                            setTimeout(() => {
                                cartCountElement.classList.remove('pulse');
                            }, 500);
                        }
                        
                        // Resetar o botão após 2 segundos
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.disabled = false;
                        }, 2000);
                    } else {
                        alert(data.message || 'Erro ao adicionar ao carrinho');
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao conectar com o servidor');
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
            });
        });
    </script>
</body>
</html>