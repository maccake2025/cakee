<?php
// Inicia a sessão de forma segura
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/sanitize.php';
require_once __DIR__ . '/../../functions/upload.php';

// Garante que o usuário está autenticado
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: /pages/auth/login.php');
    exit();
}

// Busca o tipo do usuário para garantir que é vendedor
$db = new Database();
$conn = $db->connect();
$stmt = $conn->prepare("SELECT tipo FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$tipo = $stmt->fetchColumn();

if (!$tipo || strtolower($tipo) !== 'vendedor') {
    header('Location: /pages/auth/login.php');
    exit();
}

$error = '';
$success = '';

// Função para gerar slug "amigável" e único
function slugify($text) {
    // Remove acentos, converte para minúsculas e substitui espaços/char especiais por hífen
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'produto' : $text;
}
function generateUniqueSlug($conn, $baseSlug) {
    $slug = $baseSlug;
    $i = 1;
    do {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM produtos WHERE slug = ?");
        $stmt->execute([$slug]);
        $count = $stmt->fetchColumn();
        if ($count == 0) break;
        $slug = $baseSlug . '-' . $i;
        $i++;
    } while (true);
    return $slug;
}

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
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
    $category = sanitize($_POST['category'] ?? '');
    $ingredients = sanitize($_POST['ingredients'] ?? '');
    $weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
    $prep_time = isset($_POST['prep_time']) && $_POST['prep_time'] !== '' ? (int)$_POST['prep_time'] : null;
    $stock = isset($_POST['stock']) && $_POST['stock'] !== '' ? (int)$_POST['stock'] : 1;

    // Validações
    if (empty($name) || $price === 0.0) {
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

        // Gera slug único
        $baseSlug = slugify($name);
        $slug = generateUniqueSlug($conn, $baseSlug);

        if (empty($error)) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO produtos 
                    (vendedor_id, nome, slug, descricao, preco, categoria, imagem_principal, imagens_adicionais, estoque, ingredientes, peso, tempo_preparo, ativo, data_cadastro)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([
                    $user_id,
                    $name,
                    $slug,
                    $description,
                    $price,
                    $category,
                    $main_image,
                    json_encode($additional_images ?: []),
                    $stock,
                    $ingredients,
                    $weight,
                    $prep_time
                ]);
                $success = 'Produto adicionado com sucesso!';
            } catch (PDOException $e) {
                error_log("Erro ao adicionar produto: " . $e->getMessage());
                if(stripos($e->getMessage(), 'Duplicate entry') !== false && stripos($e->getMessage(), 'slug') !== false){
                    $error = 'Já existe um produto com um slug semelhante. Tente alterar o nome do produto.';
                } else {
                    $error = 'Falha ao adicionar produto. Tente novamente.';
                }
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
    <style>
        .products-list img {
            width: 52px;
            height: 52px;
            border-radius: 7px;
            object-fit: cover;
            background: #f7f7f7;
            border: 1px solid #eee;
        }
        .products-list table {
            width: 100%;
            margin-top: 16px;
            background: #fff;
            border-radius: 10px;
            border-collapse: separate;
            border-spacing: 0;
            box-shadow: 0 2px 10px rgba(231,112,84,0.06);
            font-size: 1em;
        }
        .products-list th, .products-list td {
            padding: 11px 8px;
            border-bottom: 1.5px solid #f3dbd4;
        }
        .products-list th {
            background: #fff6f3;
            color: #e77054;
            border-bottom: 2.5px solid #e77054;
            font-weight: 700;
        }
        .products-list tr:last-child td { border-bottom: none; }
        .products-list tr:hover td { background: #fff9f7; transition: background .1s; }
        .btn.btn-small { margin: 0 2px 0 0;}
        #product-form { background: #fffaf7; border-radius: 10px; margin: 22px 0 36px 0; padding: 24px 22px 18px 22px; box-shadow: 0 2px 12px rgba(231,112,84,0.04);}
        #toggle-form { margin-bottom: 16px; }
        .form-row { display: flex; gap: 24px; }
        .form-group { margin-bottom: 15px; flex: 1; }
        .form-group label { font-weight: 600; color: #e77054; margin-bottom: 4px; display: block; }
        .form-group input, .form-group textarea {
            border: 1.5px solid #ffe3db;
            border-radius: 7px;
            padding: 8px 11px;
            font-size: 1.02em;
            background: #fff;
            color: #333;
            width: 100%;
        }
        .form-group input[type="file"] { background: #f7f7f7; }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #e77054;
            background: #fff8f5;
        }
        @media (max-width:900px) {
            .form-row { flex-direction: column; gap: 6px; }
            .products-list th, .products-list td { padding: 7px 4px; font-size: .98em; }
        }
        .alert-sucesso { background: #e7ffe9; color: #1d7a32; border: 1px solid #98e3a7; padding: 10px 13px; margin-bottom: 14px; border-radius: 7px;}
        .alert-erro { background: #fff0f0; color: #c00; border: 1.5px solid #f5b1b1; padding: 10px 13px; margin-bottom: 14px; border-radius: 7px;}
        .btn { background: #e77054; color: #fff; border: none; border-radius: 6px; padding: 8px 16px; font-weight: 600; cursor: pointer; transition: background .2s;}
        .btn:hover { background: #cd6249;}
        .btn-small { font-size: 0.95em; padding: 6px 12px;}
        .dash-root { display: flex; }
        .dash-sidebar { width: 220px; background: #fff6f3; min-height: 100vh; padding: 25px 0 0 0;}
        .dash-sidebar ul { list-style: none; padding: 0;}
        .dash-sidebar li { margin-bottom: 14px; }
        .dash-sidebar a { display: block; color: #e77054; text-decoration: none; font-weight: 600; font-size: 1.1em; padding: 8px 22px;}
        .dash-sidebar li.active a, .dash-sidebar a:hover { background: #ffe3db; border-radius: 6px;}
        .dash-content { flex: 1; padding: 36px; background: #fff;}
        @media (max-width:800px) {
            .dash-root { flex-direction: column;}
            .dash-sidebar { width: 100%; min-height: unset;}
            .dash-content { padding: 20px;}
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="dash-root">
    <aside class="dash-sidebar">
        <nav>
            <ul>
                <li><a href="/pages/vendor/dashboard.php"><i class="fas fa-home menu-icon"></i>Dashboard</a></li>
                <li class="active"><a href="/pages/vendor/products.php"><i class="fas fa-box menu-icon"></i>Meus Produtos</a></li>
                <li><a href="/pages/vendor/orders.php"><i class="fas fa-clipboard-list menu-icon"></i>Pedidos Recebidos</a></li>
                <li><a href="/pages/user/profile.php"><i class="fas fa-user menu-icon"></i>Meu Perfil</a></li>
                <li><a href="/pages/auth/logout.php"><i class="fas fa-sign-out-alt menu-icon"></i>Sair</a></li>
            </ul>
        </nav>
    </aside>
    <main class="dash-content">
        <h1>Meus Produtos</h1>
        <?php if ($error): ?>
            <div class="alert alert-erro"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-sucesso"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <button id="toggle-form" class="btn"><i class="fas fa-plus"></i> Adicionar Novo Produto</button>
        <div id="product-form" style="display: none;">
            <h2>Cadastrar Produto</h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Nome do Produto <span style="color:#d14e2d;">*</span></label>
                    <input type="text" id="name" name="name" maxlength="80" required>
                </div>
                <div class="form-group">
                    <label for="description">Descrição</label>
                    <textarea id="description" name="description" rows="4" maxlength="400"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Preço <span style="color:#d14e2d;">*</span></label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Categoria</label>
                        <input type="text" id="category" name="category" maxlength="40">
                    </div>
                </div>
                <div class="form-group">
                    <label for="ingredients">Ingredientes</label>
                    <textarea id="ingredients" name="ingredients" rows="2" maxlength="250"></textarea>
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
                    <label for="main_image">Imagem Principal <span style="color:#d14e2d;">*</span></label>
                    <input type="file" id="main_image" name="main_image" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label for="additional_images">Imagens Adicionais</label>
                    <input type="file" id="additional_images" name="additional_images[]" accept="image/*" multiple>
                </div>
                <button type="submit" name="add_product" class="btn"><i class="fas fa-check"></i> Cadastrar Produto</button>
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
                            <th style="min-width:110px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <?php if (!empty($product['imagem_principal'])): ?>
                                    <img src="/assets/images/uploads/products/<?= htmlspecialchars($product['imagem_principal']) ?>" alt="<?= htmlspecialchars($product['nome']) ?>">
                                <?php else: ?>
                                    <span style="color:#aaa;">Sem imagem</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($product['nome']) ?></td>
                            <td>R$ <?= number_format($product['preco'], 2, ',', '.') ?></td>
                            <td><?= $product['estoque'] ?></td>
                            <td>
                                <?php if ($product['ativo']): ?>
                                    <span style="color:#25ad5d;font-weight:bold;">Ativo</span>
                                <?php else: ?>
                                    <span style="color:#c00;font-weight:bold;">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-small"><i class="fas fa-edit"></i> Editar</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-small" style="background:<?= $product['ativo'] ? '#888' : '#25ad5d' ?>;">
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
<script>
    document.getElementById('toggle-form').addEventListener('click', function() {
        const form = document.getElementById('product-form');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        this.innerHTML = form.style.display === 'none'
            ? '<i class="fas fa-plus"></i> Adicionar Novo Produto'
            : '<i class="fas fa-minus"></i> Ocultar Formulário';
        if(form.style.display === 'block') form.scrollIntoView({behavior: "smooth"});
    });
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>