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

$success_msg = '';
$error_msg = '';

// Cancelar pedido (POST seguro)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_pedido'])) {
    // Checa status do pedido e se pertence ao usuário
    $stmt = $conn->prepare("SELECT status FROM pedidos WHERE id = ? AND cliente_id = ? LIMIT 1");
    $stmt->execute([$pedido_id, $user_id]);
    $pedido_status = $stmt->fetchColumn();

    if (!$pedido_status) {
        $error_msg = "Pedido não encontrado.";
    } elseif (in_array(strtolower($pedido_status), ['cancelado', 'entregue'])) {
        $error_msg = "Este pedido não pode mais ser cancelado.";
    } else {
        // Cancela o pedido
        $stmt = $conn->prepare("UPDATE pedidos SET status = 'cancelado', data_atualizacao = NOW() WHERE id = ? AND cliente_id = ?");
        $stmt->execute([$pedido_id, $user_id]);
        // Cria log - compatível com tabela que NÃO tem pedido_id (só usuario_id, acao, descricao, ip, user_agent, data_log)
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $stmt = $conn->prepare("INSERT INTO logs (usuario_id, acao, descricao, ip, user_agent, data_log) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $user_id,
            'cancelamento',
            "Pedido #$pedido_id cancelado pelo cliente",
            $ip,
            $userAgent
        ]);
        $success_msg = "Pedido cancelado com sucesso!";
        // Redireciona para evitar repost POST
        header("Location: ?id=$pedido_id&success=1");
        exit();
    }
}

// Mensagem pós-cancelamento
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_msg = "Pedido cancelado com sucesso!";
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

    // Histórico/log do pedido: agora busca logs relacionados contendo o número do pedido na descrição
    $stmt_logs = $conn->prepare("SELECT l.*, u.nome AS usuario_nome FROM logs l LEFT JOIN usuarios u ON l.usuario_id = u.id WHERE l.descricao LIKE ? ORDER BY l.data_log DESC");
    $stmt_logs->execute(['%Pedido #'.$pedido_id.'%']);
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
</head>
<body>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<div class="order-container">
    <h1>Pedido #<?= htmlspecialchars($pedido_id) ?></h1>
    <?php if ($pedido_nao_encontrado): ?>
        <div class="alert alert-erro">Pedido não encontrado ou você não tem permissão para visualizar.</div>
        <a href="/pages/user/orders.php" class="btn btn-primario">Ver meus pedidos</a>
    <?php else: ?>
        <?php if ($success_msg): ?>
            <div class="alert alert-sucesso"><?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-erro"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

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
        <!-- Cancelar Pedido (apenas se possível) -->
        <?php
        $status_pode_cancelar = in_array(strtolower($pedido['status']), ['pendente', 'processando']);
        if ($status_pode_cancelar):
        ?>
            <form method="post" class="cancel-order-form" onsubmit="return confirm('Tem certeza que deseja cancelar este pedido?');" style="margin-bottom:24px;">
                <button type="submit" name="cancelar_pedido" class="btn btn-danger" style="background:#dc3545;color:#fff;">
                    <i class="fas fa-ban"></i> Cancelar Pedido
                </button>
            </form>
        <?php endif; ?>

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
                        <img src="/assets/images/uploads/products/<?= htmlspecialchars($item['imagem_produto']) ?>" width="40"
                             onerror="this.src='/assets/images/default-product.jpg'">
                        <?= htmlspecialchars($item['nome_produto']) ?>
                        <?php if (!empty($item['categoria'])): ?>
                            <br><span class="categoria">Categoria: <?= htmlspecialchars($item['categoria']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="vendedor"><?= htmlspecialchars($item['vendedor_nome']) ?></td>
                    <td class="preco-unitario">R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                    <td class="quantidade"><?= $item['quantidade'] ?></td>
                    <td class="subtotal">R$ <?= number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="order-summary-block">
            <div><strong>Subtotal:</strong> R$ <?= number_format($pedido['subtotal'], 2, ',', '.') ?></div>
            <div><strong>Taxa de entrega:</strong> R$ <?= number_format($pedido['taxa_entrega'], 2, ',', '.') ?></div>
            <?php if ($cupom_info): ?>
                <div class="cupom"><strong>Desconto Cupom:</strong> (<?= htmlspecialchars($cupom_info['codigo']) ?>) R$ <?= number_format($cupom_info['valor_desconto'], 2, ',', '.') ?></div>
            <?php endif; ?>
            <div class="total">
                <strong>Total:</strong> R$ <?= number_format($pedido['total'], 2, ',', '.') ?>
            </div>
        </div>
        <h2>Histórico do Pedido</h2>
        <?php if ($logs): ?>
            <div class="order-log-list">
                <ul>
                <?php foreach ($logs as $log): ?>
                    <li>
                        <span class="log-date"><?= date('d/m/Y H:i', strtotime($log['data_log'])) ?></span>
                        - <span class="log-action"><?= htmlspecialchars($log['acao']) ?></span>
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
        <div style="margin-top:22px;">
            <a href="/pages/user/orders.php" class="btn btn-secundario"><i class="fas fa-arrow-left"></i> Voltar para meus pedidos</a>
            <a href="/pages/products.php" class="btn btn-primario"><i class="fas fa-shopping-basket"></i> Continuar Comprando</a>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<script src="../../assets/js/main.js"></script>
</body>
</html>