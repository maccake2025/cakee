<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/sanitize.php';

$db = new Database();
$conn = $db->connect();

// Verifica login
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id || !is_numeric($user_id)) {
    header('Location: /pages/login.php');
    exit();
}

// Recupera pedido pelo GET
$pedido_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($pedido_id < 1) {
    header('Location: /pages/user/orders.php');
    exit();
}

// Busca pedido e dados do cliente
$stmt = $conn->prepare("
    SELECT p.*, u.nome AS cliente_nome, u.email AS cliente_email, u.telefone AS cliente_telefone
    FROM pedidos p
    JOIN usuarios u ON p.cliente_id = u.id
    WHERE p.id = ? AND p.cliente_id = ?
    LIMIT 1
");
$stmt->execute([$pedido_id, $user_id]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    $pedido_nao_encontrado = true;
} else {
    $pedido_nao_encontrado = false;
    // Busca itens do pedido
    $stmt_itens = $conn->prepare("
        SELECT pi.*, pr.categoria, pr.peso, pr.tempo_preparo, pr.vendedor_id, v.nome AS vendedor_nome
        FROM pedido_itens pi
        JOIN produtos pr ON pi.produto_id = pr.id
        JOIN usuarios v ON pr.vendedor_id = v.id
        WHERE pi.pedido_id = ?
    ");
    $stmt_itens->execute([$pedido_id]);
    $itens = $stmt_itens->fetchAll(PDO::FETCH_ASSOC);

    // Cupom utilizado
    $stmt_cupom = $conn->prepare("SELECT c.codigo, cu.valor_desconto FROM cupons_utilizados cu JOIN cupons c ON cu.cupom_id = c.id WHERE cu.pedido_id = ?");
    $stmt_cupom->execute([$pedido_id]);
    $cupom_info = $stmt_cupom->fetch(PDO::FETCH_ASSOC);

    // Histórico/log do pedido (corrigido para data_log)
    $stmt_logs = $conn->prepare("SELECT l.*, u.nome AS usuario_nome FROM logs l LEFT JOIN usuarios u ON l.usuario_id = u.id WHERE l.descricao LIKE ? ORDER BY l.data_log DESC");
    $stmt_logs->execute(["Pedido #$pedido_id%"]);
    $logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Detalhes do Pedido #<?= htmlspecialchars($pedido_id) ?> - Cakee Market</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/order_detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .order-container { max-width: 800px; margin: 0 auto; padding: 32px 0; }
        .order-table { width:100%; border-collapse:collapse; background:#fff; margin-bottom:24px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.04);}
        .order-table th,.order-table td{padding:9px 7px; border-bottom:1px solid #f0f0f0;}
        .order-table th {background:#f7f7f7;}
        .order-table tr:last-child td{border-bottom:none;}
        .order-summary-block{background:#fff; border-radius:7px; box-shadow:0 2px 8px rgba(0,0,0,0.03); padding:15px 20px; margin-bottom:20px;}
        .status-pendente{color:#c60; font-weight:600;}
        .status-processando{color:#06c; font-weight:600;}
        .status-enviado{color:#09c; font-weight:600;}
        .status-entregue{color:#090; font-weight:600;}
        .status-cancelado{color:#d00; font-weight:600;}
        .order-log-list{background:#fafafa; border-radius:7px; box-shadow:0 2px 8px rgba(0,0,0,0.03); padding:14px 18px; font-size:.98em;}
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="order-container">
    <h1>Pedido #<?= htmlspecialchars($pedido_id) ?></h1>
    <?php if ($pedido_nao_encontrado): ?>
        <div class="alert alert-erro">Pedido não encontrado ou você não tem permissão para visualizar.</div>
        <a href="/pages/user/orders.php" class="btn btn-primario">Ver meus pedidos</a>
    <?php else: ?>
        <div class="order-summary-block">
            <div><strong>Status:</strong>
                <?php
                $status = htmlspecialchars($pedido['status']);
                $status_class = 'status-' . strtolower($status);
                echo "<span class=\"$status_class\">" . ucfirst($status) . "</span>";
                ?>
            </div>
            <div><strong>Data do pedido:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></div>
            <div><strong>Cliente:</strong> <?= htmlspecialchars($pedido['cliente_nome']) ?> (<?= htmlspecialchars($pedido['cliente_email']) ?>, <?= htmlspecialchars($pedido['cliente_telefone']) ?>)</div>
            <div><strong>Endereço de entrega:</strong> <?= htmlspecialchars($pedido['endereco_entrega']) ?></div>
            <div><strong>Método de pagamento:</strong> <?= ucfirst(htmlspecialchars($pedido['metodo_pagamento'])) ?></div>
            <?php if (!empty($pedido['observacoes'])): ?>
                <div><strong>Observações:</strong> <?= nl2br(htmlspecialchars($pedido['observacoes'])) ?></div>
            <?php endif; ?>
        </div>
        <h2>Produtos do Pedido</h2>
        <table class="order-table">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Vendedor</th>
                    <th>Preço unitário</th>
                    <th>Quantidade</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($itens as $item): ?>
                <tr>
                    <td>
                        <img src="/assets/images/uploads/products/<?= htmlspecialchars($item['imagem_produto']) ?>" width="40">
                        <?= htmlspecialchars($item['nome_produto']) ?>
                        <?php if (!empty($item['categoria'])): ?>
                            <br><span style="font-size:.9em;color:#666;">Categoria: <?= htmlspecialchars($item['categoria']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['vendedor_nome']) ?></td>
                    <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                    <td><?= $item['quantidade'] ?></td>
                    <td>R$ <?= number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="order-summary-block">
            <div><strong>Subtotal:</strong> R$ <?= number_format($pedido['subtotal'], 2, ',', '.') ?></div>
            <div><strong>Taxa de entrega:</strong> R$ <?= number_format($pedido['taxa_entrega'], 2, ',', '.') ?></div>
            <?php if ($cupom_info): ?>
                <div><strong>Desconto Cupom:</strong> (<?= htmlspecialchars($cupom_info['codigo']) ?>) R$ <?= number_format($cupom_info['valor_desconto'], 2, ',', '.') ?></div>
            <?php endif; ?>
            <div style="font-size:1.1em; font-weight:600; margin-top:7px;">
                <strong>Total:</strong> R$ <?= number_format($pedido['total'], 2, ',', '.') ?>
            </div>
        </div>
        <h2>Histórico do Pedido</h2>
        <?php if ($logs): ?>
            <div class="order-log-list">
                <ul>
                <?php foreach ($logs as $log): ?>
                    <li>
                        <span><?= date('d/m/Y H:i', strtotime($log['data_log'])) ?></span>
                        - <span><?= htmlspecialchars($log['acao']) ?></span>
                        <?php if ($log['usuario_nome']): ?>
                            por <strong><?= htmlspecialchars($log['usuario_nome']) ?></strong>
                        <?php endif; ?>
                        <?php if ($log['descricao']): ?>
                            <br><span><?= htmlspecialchars($log['descricao']) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <p>Nenhum registro de histórico para este pedido.</p>
        <?php endif; ?>
        <a href="/pages/user/orders.php" class="btn btn-secundario"><i class="fas fa-arrow-left"></i> Voltar para meus pedidos</a>
        <a href="/pages/products.php" class="btn btn-primario">Continuar Comprando</a>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>