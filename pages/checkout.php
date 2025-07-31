<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Cart.php';

$db = new Database();
$conn = $db->connect();
$cart = new Cart($conn);

// Verifica login
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id || !is_numeric($user_id)) {
    header('Location: /pages/auth/login.php');
    exit();
}

// Recupera itens do carrinho
$cart_items = $cart->getItems();
$subtotal = $cart->getSubtotal();

// Cupom
$cupom = $_SESSION['cupom'] ?? null;
$desconto = $cupom ? $cupom['desconto'] : 0;
$total = max(0, $subtotal - $desconto);

// Taxa de entrega (pode ser dinâmica)
$taxa_entrega = 15.00;
$total_final = $total + $taxa_entrega;

// Busca dados do usuário
$stmt = $conn->prepare("SELECT nome, email, telefone, endereco FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar'])) {
    $endereco_entrega = trim($_POST['endereco_entrega']);
    $metodo_pagamento = $_POST['metodo_pagamento'] ?? '';
    $observacoes = trim($_POST['observacoes']);

    if (empty($cart_items)) {
        $error = 'Seu carrinho está vazio.';
    } elseif (empty($endereco_entrega)) {
        $error = 'Informe o endereço de entrega.';
    } elseif (!in_array($metodo_pagamento, ['cartao','pix','boleto','dinheiro'])) {
        $error = 'Selecione um método de pagamento válido.';
    } else {
        try {
            $conn->beginTransaction();

            // Cria pedido (sem cupom_id, pois não existe mais)
            $stmt = $conn->prepare("
                INSERT INTO pedidos (cliente_id, data_pedido, status, total, subtotal, taxa_entrega, endereco_entrega, metodo_pagamento, observacoes)
                VALUES (?, NOW(), 'pendente', ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $total_final,
                $subtotal,
                $taxa_entrega,
                $endereco_entrega,
                $metodo_pagamento,
                $observacoes
            ]);
            $pedido_id = $conn->lastInsertId();

            // Insere itens do pedido
            $stmt_item = $conn->prepare("
                INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario, nome_produto, imagem_produto)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($cart_items as $item) {
                if ($item['quantity'] > $item['estoque']) {
                    $conn->rollBack();
                    $error = 'Produto "' . htmlspecialchars($item['nome']) . '" com estoque insuficiente.';
                    break;
                }
                $preco_unitario = $item['preco_promocional'] > 0 ? $item['preco_promocional'] : $item['preco'];
                $stmt_item->execute([
                    $pedido_id,
                    $item['id'],
                    $item['quantity'],
                    $preco_unitario,
                    $item['nome'],
                    $item['imagem_principal']
                ]);
                $conn->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ?")
                    ->execute([$item['quantity'], $item['id']]);
            }

            if (empty($error)) {
                // Atualiza uso do cupom (e salva em cupons_utilizados)
                if ($cupom) {
                    $conn->prepare("UPDATE cupons SET usos_atual = usos_atual + 1 WHERE id = ?")
                        ->execute([$cupom['id']]);

                    $conn->prepare("INSERT INTO cupons_utilizados (cupom_id, usuario_id, pedido_id, valor_desconto)
                                    VALUES (?, ?, ?, ?)")
                        ->execute([$cupom['id'], $user_id, $pedido_id, $desconto]);

                    unset($_SESSION['cupom']);
                }

                // Limpa carrinho
                $cart->clear();

                // Registra log
                $conn->prepare("INSERT INTO logs (usuario_id, acao, descricao)
                                VALUES (?, 'pedido_criado', ?)")
                    ->execute([$user_id, "Pedido #$pedido_id criado"]);

                $conn->commit();
                $success = "Pedido realizado com sucesso! <a href='/pages/user/order_detail.php?id={$pedido_id}'>Ver pedido</a>";
            }

        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Erro ao finalizar pedido: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Cakee Market</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .checkout-container { max-width: 700px; margin: 0 auto; padding: 32px 0; }
        .cart-table { width:100%; border-collapse:collapse; background:#fff; margin-bottom:24px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.04);}
        .cart-table th,.cart-table td{padding:9px 7px; border-bottom:1px solid #f0f0f0;}
        .cart-table th {background:#f7f7f7;}
        .cart-table tr:last-child td{border-bottom:none;}
        .total-block{background:#fff; border-radius:7px; box-shadow:0 2px 8px rgba(0,0,0,0.03); padding:15px 20px; margin-bottom:20px;}
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="checkout-container">
    <h1>Finalizar Pedido</h1>
    <?php if ($error): ?>
        <div class="alert alert-erro"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-sucesso"><?= $success ?></div>
    <?php endif; ?>
    <?php if (!empty($cart_items) && !$success): ?>
        <h2>Resumo do Carrinho</h2>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Preço</th>
                    <th>Qtd</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($cart_items as $item): ?>
                <tr>
                    <td>
                        <img src="../assets/images/uploads/products/<?= htmlspecialchars($item['imagem_principal']) ?>" width="40">
                        <?= htmlspecialchars($item['nome']) ?>
                    </td>
                    <td>
                        <?php if ($item['preco_promocional'] > 0): ?>
                            <span class="preco-original">R$ <?= number_format($item['preco'], 2, ',', '.') ?></span>
                            <span class="preco-promocional">R$ <?= number_format($item['preco_promocional'], 2, ',', '.') ?></span>
                        <?php else: ?>
                            <span class="preco">R$ <?= number_format($item['preco'], 2, ',', '.') ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= $item['quantity'] ?></td>
                    <td>
                        R$ <?= number_format(
                            ($item['preco_promocional'] > 0 ? $item['preco_promocional'] : $item['preco']) * $item['quantity'],
                            2, ',', '.'
                        ) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="total-block">
            <div><strong>Subtotal:</strong> R$ <?= number_format($subtotal, 2, ',', '.') ?></div>
            <div><strong>Desconto Cupom:</strong> R$ <?= number_format($desconto, 2, ',', '.') ?></div>
            <div><strong>Taxa de Entrega:</strong> R$ <?= number_format($taxa_entrega, 2, ',', '.') ?></div>
            <div style="font-size:1.1em; font-weight:600; margin-top:7px;">
                <strong>Total:</strong> R$ <?= number_format($total_final, 2, ',', '.') ?>
            </div>
        </div>
        <form method="POST">
            <h2>Informações de Entrega</h2>
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" id="nome" value="<?= htmlspecialchars($user['nome']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="text" id="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" value="<?= htmlspecialchars($user['telefone']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="endereco_entrega">Endereço de entrega</label>
                <textarea id="endereco_entrega" name="endereco_entrega" rows="2" required><?= htmlspecialchars($user['endereco']) ?></textarea>
            </div>
            <div class="form-group">
                <label for="metodo_pagamento">Método de pagamento</label>
                <select id="metodo_pagamento" name="metodo_pagamento" required>
                    <option value="">Selecione...</option>
                    <option value="cartao">Cartão de crédito</option>
                    <option value="pix">Pix</option>
                    <option value="boleto">Boleto</option>
                    <option value="dinheiro">Dinheiro na entrega</option>
                </select>
            </div>
            <div class="form-group">
                <label for="observacoes">Observações (opcional)</label>
                <textarea id="observacoes" name="observacoes" rows="2"><?= isset($_POST['observacoes']) ? htmlspecialchars($_POST['observacoes']) : '' ?></textarea>
            </div>
            <button type="submit" name="finalizar" class="btn btn-primario">
                <i class="fas fa-credit-card"></i> Finalizar Compra
            </button>
        </form>
    <?php elseif ($success): ?>
        <a href="/pages/products.php" class="btn btn-primario">Continuar Comprando</a>
    <?php else: ?>
        <p>Seu carrinho está vazio. <a href="/pages/products.php">Ver produtos</a></p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>