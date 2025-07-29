<?php
// Corrige os avisos de sessão: sempre inicie a sessão!
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/vendor_check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/sanitize.php';
require_once __DIR__ . '/../../functions/upload.php';

// Garante que $_SESSION está definido e user_id está presente após os checks
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
if (!$user_id) {
    header('Location: /pages/login.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

$error = '';
$success = '';

// Ativar/Inativar produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'], $_POST['product_id'])) {
    $pid = (int)$_POST['product_id'];
    $stmt = $conn->prepare("SELECT ativo FROM produtos WHERE id = ? AND vendedor_id = ?");
    $stmt->execute([$pid, $user_id]);
    $current = $stmt->fetchColumn();
    if ($current !== false) {
        $newStatus = $current ? 0 : 1;
        $stmt = $conn->prepare("UPDATE produtos SET ativo = ? WHERE id = ? AND vendedor_id = ?");
        $stmt->execute([$newStatus, $pid, $user_id]);
        $success = $newStatus ? 'Produto ativado!' : 'Produto desativado!';
    }
}

// Adicionar novo produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $category = sanitize($_POST['category']);
    $ingredients = sanitize($_POST['ingredients']);
    $weight = (float)$_POST['weight'];
    $prep_time = (int)$_POST['prep_time'];
    $stock = (int)$_POST['stock'];

    // Validações
    if (empty($name) || empty($price)) {
        $error = 'Nome e preço são obrigatórios.';
    } elseif ($price <= 0) {
        $error = 'O preço deve ser maior que zero.';
    } else {
        // Upload da imagem principal
        $main_image = '';
        if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            $upload = uploadImage($_FILES['main_image'], 'products');
            if ($upload['success']) {
                $main_image = $upload['file_name'];
            } else {
                $error = $upload['error'];
            }
        } else {
            $error = 'Imagem principal é obrigatória.';
        }

        // Upload de imagens adicionais
        $additional_images = [];
        if (empty($error) && !empty($_FILES['additional_images']['name'][0])) {
            foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['additional_images']['name'][$key],
                        'type' => $_FILES['additional_images']['type'][$key],
                        'tmp_name' => $tmp_name,
                        'error' => $_FILES['additional_images']['error'][$key],
                        'size' => $_FILES['additional_images']['size'][$key]
                    ];
                    $upload = uploadImage($file, 'products');
                    if ($upload['success']) {
                        $additional_images[] = $upload['file_name'];
                    }
                }
            }
        }

        if (empty($error)) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO produtos 
                    (vendedor_id, nome, descricao, preco, categoria, imagem_principal, imagens_adicionais, estoque, ingredientes, peso, tempo_preparo)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id,
                    $name,
                    $description,
                    $price,
                    $category,
                    $main_image,
                    json_encode($additional_images),
                    $stock,
                    $ingredients,
                    $weight,
                    $prep_time
                ]);
                $success = 'Produto adicionado com sucesso!';
            } catch (PDOException $e) {
                error_log("Erro ao adicionar produto: " . $e->getMessage());
                $error = 'Falha ao adicionar produto. Tente novamente.';
            }
        }
    }
}

// Buscar produtos do vendedor
$stmt = $conn->prepare("
    SELECT * FROM produtos 
    WHERE vendedor_id = ? 
    ORDER BY data_cadastro DESC
");
$stmt->execute([$user_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Meus Produtos - Painel do Vendedor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="vendor-dashboard">
    <aside class="sidebar">
        <nav>
            <ul>
                <li><a href="/pages/vendor/dashboard.php">Dashboard</a></li>
                <li class="active"><a href="/pages/vendor/products.php">Meus Produtos</a></li>
                <li><a href="/pages/vendor/orders.php">Pedidos Recebidos</a></li>
                <li><a href="/pages/user/profile.php">Meu Perfil</a></li>
                <li><a href="/pages/auth/logout.php">Sair</a></li>
            </ul>
        </nav>
    </aside>
    <main class="content">
        <h1>Meus Produtos</h1>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <button id="toggle-form" class="btn">Adicionar Novo Produto</button>
        <div id="product-form" style="display: none;">
            <h2>Cadastrar Produto</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Nome do Produto</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Descrição</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Preço</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="category">Categoria</label>
                    <input type="text" id="category" name="category">
                </div>
                <div class="form-group">
                    <label for="ingredients">Ingredientes</label>
                    <textarea id="ingredients" name="ingredients" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="weight">Peso (g)</label>
                        <input type="number" id="weight" name="weight" step="0.1" min="0">
                    </div>
                    <div class="form-group">
                        <label for="prep_time">Tempo de Preparo (min)</label>
                        <input type="number" id="prep_time" name="prep_time" min="0">
                    </div>
                    <div class="form-group">
                        <label for="stock">Estoque</label>
                        <input type="number" id="stock" name="stock" min="0" value="1">
                    </div>
                </div>
                <div class="form-group">
                    <label for="main_image">Imagem Principal (obrigatória)</label>
                    <input type="file" id="main_image" name="main_image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="additional_images">Imagens Adicionais (opcional)</label>
                    <input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple>
                </div>
                <button type="submit" name="add_product" class="btn">Cadastrar Produto</button>
            </form>
        </div>
        <div class="products-list">
            <?php if (empty($products)): ?>
                <p>Você ainda não cadastrou produtos.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Imagem</th>
                            <th>Nome</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <img src="/assets/images/uploads/products/<?= htmlspecialchars($product['imagem_principal']) ?>" alt="<?= htmlspecialchars($product['nome']) ?>" width="50">
                            </td>
                            <td><?= htmlspecialchars($product['nome']) ?></td>
                            <td>R$ <?= number_format($product['preco'], 2, ',', '.') ?></td>
                            <td><?= $product['estoque'] ?></td>
                            <td><?= $product['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                            <td>
                                <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-small">Editar</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-small">
                                        <?= $product['ativo'] ? 'Desativar' : 'Ativar' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach;?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>
<script>
    document.getElementById('toggle-form').addEventListener('click', function() {
        const form = document.getElementById('product-form');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        this.textContent = form.style.display === 'none' ? 'Adicionar Novo Produto' : 'Ocultar Formulário';
    });
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>