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

// Pega o ID do produto da URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header('Location: /pages/vendor/products.php');
    exit();
}

// Busca dados do produto
$stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ? AND vendedor_id = ?");
$stmt->execute([$product_id, $user_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: /pages/vendor/products.php');
    exit();
}

// Função para gerar slug "amigável" e único
function slugify($text) {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'produto' : $text;
}
function generateUniqueSlug($conn, $baseSlug, $excludeProductId = null) {
    $slug = $baseSlug;
    $i = 1;
    do {
        if ($excludeProductId) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM produtos WHERE slug = ? AND id <> ?");
            $stmt->execute([$slug, $excludeProductId]);
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM produtos WHERE slug = ?");
            $stmt->execute([$slug]);
        }
        $count = $stmt->fetchColumn();
        if ($count == 0) break;
        $slug = $baseSlug . '-' . $i;
        $i++;
    } while (true);
    return $slug;
}

$error = '';
$success = '';

// EDITAR PRODUTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
    $category = sanitize($_POST['category'] ?? '');
    $ingredients = sanitize($_POST['ingredients'] ?? '');
    $weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
    $prep_time = isset($_POST['prep_time']) && $_POST['prep_time'] !== '' ? (int)$_POST['prep_time'] : null;
    $stock = isset($_POST['stock']) && $_POST['stock'] !== '' ? (int)$_POST['stock'] : 1;
    $destaque = isset($_POST['destaque']) ? 1 : 0;
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    // Slug novo (se mudou o nome)
    $baseSlug = slugify($name);
    $slug = generateUniqueSlug($conn, $baseSlug, $product_id);

    // Upload da nova imagem principal (se enviada)
    $main_image = $product['imagem_principal'];
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadImage($_FILES['main_image'], 'products');
        if ($upload['success']) {
            $main_image = $upload['file_name'];
        } else {
            $error = $upload['error'];
        }
    }

    // Upload de novas imagens adicionais (adiciona às existentes)
    $additional_images = [];
    if (!empty($product['imagens_adicionais'])) {
        $additional_images = json_decode($product['imagens_adicionais'], true) ?: [];
    }
    if (!empty($_FILES['additional_images']['name'][0])) {
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

    // Remover imagens adicionais selecionadas via checkboxes
    if (!empty($_POST['remove_images'])) {
        foreach ($_POST['remove_images'] as $imgToRemove) {
            $additional_images = array_filter($additional_images, function($img) use ($imgToRemove) {
                return $img !== $imgToRemove;
            });
        }
        $additional_images = array_values($additional_images); // reindexa o array
    }

    if (empty($error)) {
        try {
            $stmt = $conn->prepare("
                UPDATE produtos SET
                    nome = ?, slug = ?, descricao = ?, preco = ?, categoria = ?, imagem_principal = ?, imagens_adicionais = ?, estoque = ?, ingredientes = ?, peso = ?, tempo_preparo = ?, destaque = ?, ativo = ?, data_atualizacao = NOW()
                WHERE id = ? AND vendedor_id = ?
            ");
            $stmt->execute([
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
                $prep_time,
                $destaque,
                $ativo,
                $product_id,
                $user_id
            ]);
            $success = 'Produto atualizado com sucesso!';

            // Atualiza os dados do produto para exibir no formulário após update
            $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ? AND vendedor_id = ?");
            $stmt->execute([$product_id, $user_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao editar produto: " . $e->getMessage());
            if(stripos($e->getMessage(), 'Duplicate entry') !== false && stripos($e->getMessage(), 'slug') !== false){
                $error = 'Já existe um produto com um slug semelhante. Tente alterar o nome do produto.';
            } else {
                $error = 'Falha ao editar produto. Tente novamente.';
            }
        }
    }
}

// Remove imagem principal
if (isset($_POST['remove_main_image'])) {
    $stmt = $conn->prepare("UPDATE produtos SET imagem_principal = '' WHERE id = ? AND vendedor_id = ?");
    $stmt->execute([$product_id, $user_id]);
    $product['imagem_principal'] = '';
    $success = 'Imagem principal removida!';
}

// Remove imagem adicional via GET
if (isset($_GET['remove_img']) && !empty($_GET['remove_img'])) {
    $imgToRemove = $_GET['remove_img'];
    $additional_images = json_decode($product['imagens_adicionais'], true) ?: [];
    $additional_images = array_filter($additional_images, function($img) use ($imgToRemove) {
        return $img !== $imgToRemove;
    });
    $stmt = $conn->prepare("UPDATE produtos SET imagens_adicionais = ? WHERE id = ? AND vendedor_id = ?");
    $stmt->execute([json_encode(array_values($additional_images)), $product_id, $user_id]);

    // Atualiza os dados do produto para exibir no formulário após update
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ? AND vendedor_id = ?");
    $stmt->execute([$product_id, $user_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    $success = 'Imagem adicional removida!';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Produto - Painel do Vendedor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        body { background: #fff6f3;}
        .container { max-width: 700px; margin: 32px auto; background: #fff; border-radius: 12px; box-shadow: 0 3px 18px rgba(231,112,84,0.07); padding: 28px;}
        h1 { color: #e77054; font-size:2.1em;}
        .alert-sucesso { background: #e7ffe9; color: #1d7a32; border: 1px solid #98e3a7; padding: 10px 13px; margin-bottom: 14px; border-radius: 7px;}
        .alert-erro { background: #fff0f0; color: #c00; border: 1.5px solid #f5b1b1; padding: 10px 13px; margin-bottom: 14px; border-radius: 7px;}
        .form-group { margin-bottom: 17px; }
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
        .img-preview { margin-bottom: 14px;}
        .img-preview img { max-width: 120px; border-radius: 10px; margin-right: 7px; border: 1px solid #eee; vertical-align: middle;}
        .img-remove { color: #c00; font-weight: bold; margin-left: 8px; cursor: pointer; text-decoration: underline;}
        .btn { background: #e77054; color: #fff; border: none; border-radius: 6px; padding: 8px 16px; font-weight: 600; cursor: pointer; transition: background .2s;}
        .btn:hover { background: #cd6249;}
        .flex-row { display: flex; gap: 22px;}
        .checkbox-row { display: flex; gap: 22px; align-items: center;}
        @media (max-width:700px) {
            .container { padding: 10px;}
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="container">
    <h1>Editar Produto</h1>
    <?php if ($error): ?>
        <div class="alert alert-erro"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-sucesso"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Nome do Produto <span style="color:#d14e2d;">*</span></label>
            <input type="text" id="name" name="name" maxlength="80" required value="<?= htmlspecialchars($product['nome']) ?>">
        </div>
        <div class="form-group">
            <label for="description">Descrição</label>
            <textarea id="description" name="description" rows="4" maxlength="400"><?= htmlspecialchars($product['descricao'] ?? '') ?></textarea>
        </div>
        <div class="flex-row">
            <div class="form-group">
                <label for="price">Preço <span style="color:#d14e2d;">*</span></label>
                <input type="number" id="price" name="price" step="0.01" min="0" required value="<?= htmlspecialchars($product['preco']) ?>">
            </div>
            <div class="form-group">
                <label for="category">Categoria</label>
                <input type="text" id="category" name="category" maxlength="40" value="<?= htmlspecialchars($product['categoria']) ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="ingredients">Ingredientes</label>
            <textarea id="ingredients" name="ingredients" rows="2" maxlength="250"><?= htmlspecialchars($product['ingredientes'] ?? '') ?></textarea>
        </div>
        <div class="flex-row">
            <div class="form-group">
                <label for="weight">Peso (g)</label>
                <input type="number" id="weight" name="weight" step="0.1" min="0" value="<?= htmlspecialchars($product['peso']) ?>">
            </div>
            <div class="form-group">
                <label for="prep_time">Tempo de Preparo (min)</label>
                <input type="number" id="prep_time" name="prep_time" min="0" value="<?= htmlspecialchars($product['tempo_preparo']) ?>">
            </div>
            <div class="form-group">
                <label for="stock">Estoque</label>
                <input type="number" id="stock" name="stock" min="0" value="<?= htmlspecialchars($product['estoque']) ?>">
            </div>
        </div>
        <div class="checkbox-row">
            <div class="form-group">
                <input type="checkbox" id="destaque" name="destaque" value="1" <?= $product['destaque'] ? 'checked' : '' ?>>
                <label for="destaque">Produto em destaque</label>
            </div>
            <div class="form-group">
                <input type="checkbox" id="ativo" name="ativo" value="1" <?= $product['ativo'] ? 'checked' : '' ?>>
                <label for="ativo">Ativo</label>
            </div>
        </div>
        <div class="form-group">
            <label>Imagem Principal</label>
            <div class="img-preview">
                <?php if (!empty($product['imagem_principal'])): ?>
                    <img src="/assets/images/uploads/products/<?= htmlspecialchars($product['imagem_principal']) ?>" alt="Imagem principal">
                    <form method="POST" style="display:inline;">
                        <button type="submit" name="remove_main_image" class="btn btn-small img-remove" onclick="return confirm('Remover imagem principal?');">Remover</button>
                    </form>
                <?php else: ?>
                    <span style="color:#aaa;">Sem imagem principal</span>
                <?php endif; ?>
            </div>
            <input type="file" name="main_image" accept="image/*">
        </div>
        <div class="form-group">
            <label>Imagens Adicionais</label>
            <div class="img-preview">
                <?php
                $additional_images = json_decode($product['imagens_adicionais'], true) ?: [];
                if (!empty($additional_images)):
                    foreach($additional_images as $img):
                ?>
                    <img src="/assets/images/uploads/products/<?= htmlspecialchars($img) ?>" alt="Imagem adicional">
                    <a href="edit_product.php?id=<?= $product_id ?>&remove_img=<?= urlencode($img) ?>" class="img-remove" onclick="return confirm('Remover esta imagem?');">Remover</a>
                <?php
                    endforeach;
                else:
                ?>
                    <span style="color:#aaa;">Nenhuma imagem adicional</span>
                <?php endif; ?>
            </div>
            <input type="file" name="additional_images[]" accept="image/*" multiple>
        </div>
        <button type="submit" name="edit_product" class="btn"><i class="fas fa-check"></i> Salvar Alterações</button>
        <a href="/pages/vendor/products.php" class="btn" style="background:#888;margin-left:10px;">Voltar</a>
    </form>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<script src="../../assets/js/main.js"></script>
</body>
</html>