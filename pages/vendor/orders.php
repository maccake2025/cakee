<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/vendor_check.php';
require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

$order_detail = null;
// Ver detalhes do pedido
if (isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
    // Busca pedido e itens do vendedor
    $stmt = $conn->prepare("
        SELECT p.id, p.data_pedido, p.status, p.total, u.nome as cliente, p.endereco_entrega
        FROM pedidos p
        JOIN usuarios u ON p.cliente_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$order_id]);
    $order_detail = $stmt->fetch(PDO::FETCH_ASSOC);

    // Itens deste vendedor no pedido
    $stmt = $conn->prepare("
        SELECT pi.*, pr.nome, pr.imagem_principal
        FROM pedido_itens pi
        JOIN produtos pr ON pi.produto_id = pr.id
        WHERE pi.pedido_id = ? AND pr.vendedor_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

else {
    // Listar pedidos recebidos pelo vendedor
    $stmt = $conn->prepare("
        SELECT DISTINCT p.id, p.data_pedido, p.status, p.total, u.nome as cliente
        FROM pedidos p
        JOIN pedido_itens pi ON pi.pedido_id = p.id
        JOIN produtos pr ON pi.produto_id = pr.id
        JOIN usuarios u ON p.cliente_id = u.id
        WHERE pr.vendedor_id = ?
        ORDER BY p.data_pedido DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Pedidos Recebidos - Painel do Vendedor</title>
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
                <li><a href="/pages/vendor/products.php">Meus Produtos</a></li>
                <li class="active"><a href="/pages/vendor/orders.php">Pedidos Recebidos</a></li>
                <li><a href="/pages/user/profile.php">Meu Perfil</a></li>
                <li><a href="/pages/auth/logout.php">Sair</a></li>
            </ul>
        </nav>
    </aside>
    <main class="content">
        <h1>Pedidos Recebidos</h1>
        <?php if ($order_detail): ?>
            <h2>Pedido #<?= $order_detail['id'] ?></h2>
            <p><strong>Cliente:</strong> <?= htmlspecialchars($order_detail['cliente']) ?></p>
            <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($order_detail['data_pedido'])) ?></p>
            <p><strong>Status:</strong> <?= ucfirst($order_detail['status']) ?></p>
            <p><strong>Endereço:</strong> <?= htmlspecialchars($order_detail['endereco_entrega']) ?></p>
            <p><strong>Total do Pedido:</strong> R$ <?= number_format($order_detail['total'], 2, ',', '.') ?></p>
            <h3>Itens do Pedido</h3>
            <table>
                <thead>
                    <tr>
                        <th>Imagem</th>
                        <th>Produto</th>
                        <th>Qtd</th>
                        <th>Preço Unit.</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><img src="/assets/images/uploads/products/<?= htmlspecialchars($item['imagem_principal']) ?>" alt="<?= htmlspecialchars($item['nome']) ?>" width="40"></td>
                        <td><?= htmlspecialchars($item['nome']) ?></td>
                        <td><?= $item['quantidade'] ?></td>
                        <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach;?>
                </tbody>
            </table>
            <br>
            <a href="/pages/vendor/orders.php" class="btn">Voltar à lista</a>
        <?php else: ?>
            <?php if (empty($orders)): ?>
                <p>Você ainda não recebeu pedidos.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($order['data_pedido'])) ?></td>
                            <td><?= ucfirst($order['status']) ?></td>
                            <td><?= htmlspecialchars($order['cliente']) ?></td>
                            <td>R$ <?= number_format($order['total'], 2, ',', '.') ?></td>
                            <td>
                                <a href="/pages/vendor/orders.php?id=<?= $order['id'] ?>" class="btn btn-small">Ver Detalhes</a>
                            </td>
                        </tr>
                    <?php endforeach;?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>