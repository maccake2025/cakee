<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/vendor_check.php';
require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'];

// Buscar estatísticas
// Total de produtos
$stmt = $conn->prepare("SELECT COUNT(*) FROM produtos WHERE vendedor_id = ?");
$stmt->execute([$user_id]);
$total_products = $stmt->fetchColumn();

// Total de pedidos recebidos
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM pedidos p
    JOIN pedido_itens pi ON pi.pedido_id = p.id
    JOIN produtos pr ON pi.produto_id = pr.id
    WHERE pr.vendedor_id = ?
");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetchColumn();

// Total vendido (somatório dos itens vendidos deste vendedor)
$stmt = $conn->prepare("
    SELECT SUM(pi.preco_unitario * pi.quantidade)
    FROM pedidos p
    JOIN pedido_itens pi ON pi.pedido_id = p.id
    JOIN produtos pr ON pi.produto_id = pr.id
    WHERE pr.vendedor_id = ? AND p.status != 'cancelado'
");
$stmt->execute([$user_id]);
$total_sold = $stmt->fetchColumn();
$total_sold = $total_sold ? $total_sold : 0.00;

// Últimos pedidos
$stmt = $conn->prepare("
    SELECT p.id, p.data_pedido, p.status, p.total, u.nome as cliente
    FROM pedidos p
    JOIN pedido_itens pi ON pi.pedido_id = p.id
    JOIN produtos pr ON pi.produto_id = pr.id
    JOIN usuarios u ON p.cliente_id = u.id
    WHERE pr.vendedor_id = ?
    GROUP BY p.id
    ORDER BY p.data_pedido DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel do Vendedor - Cakee Market</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="vendor-dashboard">
    <aside class="sidebar">
        <nav>
            <ul>
                <li class="active"><a href="/pages/vendor/dashboard.php">Dashboard</a></li>
                <li><a href="/pages/vendor/products.php">Meus Produtos</a></li>
                <li><a href="/pages/vendor/orders.php">Pedidos Recebidos</a></li>
                <li><a href="/pages/user/profile.php">Meu Perfil</a></li>
                <li><a href="/pages/auth/logout.php">Sair</a></li>
            </ul>
        </nav>
    </aside>
    <main class="content">
        <h1>Painel do Vendedor</h1>
        <div class="stats">
            <div class="stat-card">
                <span class="stat-title">Produtos Ativos</span>
                <span class="stat-value"><?= $total_products ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-title">Pedidos Recebidos</span>
                <span class="stat-value"><?= $total_orders ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-title">Total Vendido</span>
                <span class="stat-value">R$ <?= number_format($total_sold, 2, ',', '.') ?></span>
            </div>
        </div>
        <h2>Últimos Pedidos</h2>
        <?php if (empty($recent_orders)): ?>
            <p>Você ainda não recebeu pedidos.</p>
        <?php else: ?>
            <table class="table-list">
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
                <?php foreach ($recent_orders as $order): ?>
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
    </main>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>