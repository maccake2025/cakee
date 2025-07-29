<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Verificação do login e perfil vendedor usando o campo correto do banco (tipo)
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    header('Location: /pages/auth/login.php');
    exit();
}

$db = new Database();
$conn = $db->connect();
$user_id = intval($_SESSION['user_id']);

// Busca o usuário e verifica se é vendedor
$stmt = $conn->prepare("SELECT id, nome, email, foto_perfil, tipo FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || strtolower($usuario['tipo']) !== 'vendedor') {
    header('Location: /pages/login.php');
    exit();
}

// Estatísticas
$stmt = $conn->prepare("SELECT COUNT(*) FROM produtos WHERE vendedor_id = ? AND ativo = 1");
$stmt->execute([$user_id]);
$total_products = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT p.id)
    FROM pedidos p
    JOIN pedido_itens pi ON pi.pedido_id = p.id
    JOIN produtos pr ON pi.produto_id = pr.id
    WHERE pr.vendedor_id = ?
");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetchColumn();

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

// Últimos pedidos (corrigido GROUP BY, pega o total corretamente)
$stmt = $conn->prepare("
    SELECT p.id, p.data_pedido, p.status, p.total, u.nome as cliente
    FROM pedidos p
    JOIN pedido_itens pi ON pi.pedido_id = p.id
    JOIN produtos pr ON pi.produto_id = pr.id
    JOIN usuarios u ON p.cliente_id = u.id
    WHERE pr.vendedor_id = ?
    GROUP BY p.id, p.data_pedido, p.status, p.total, u.nome
    ORDER BY p.data_pedido DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Últimas avaliações
$stmt = $conn->prepare("
    SELECT a.*, pr.nome AS produto_nome, u.nome AS cliente_nome
    FROM avaliacoes a
    JOIN produtos pr ON a.produto_id = pr.id
    JOIN usuarios u ON a.usuario_id = u.id
    WHERE pr.vendedor_id = ?
    ORDER BY a.data_avaliacao DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Média de avaliação
$stmt = $conn->prepare("
    SELECT AVG(a.nota)
    FROM avaliacoes a
    JOIN produtos pr ON a.produto_id = pr.id
    WHERE pr.vendedor_id = ?
");
$stmt->execute([$user_id]);
$media_avaliacao = $stmt->fetchColumn();
$media_avaliacao = $media_avaliacao ? round($media_avaliacao, 2) : '--';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel do Vendedor - Cakee Market</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/vendor_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            <div class="stat-card">
                <span class="stat-title">Média Avaliação</span>
                <span class="stat-value">
                    <?= $media_avaliacao ?>
                    <?php if ($media_avaliacao !== '--'): ?>
                        <i class="fas fa-star" style="color:#ffd200;font-size:1.1em"></i>
                    <?php endif; ?>
                </span>
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

        <h2>Últimas Avaliações</h2>
        <?php if (empty($recent_reviews)): ?>
            <p>Seus produtos ainda não possuem avaliações.</p>
        <?php else: ?>
            <table class="table-list">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Cliente</th>
                        <th>Nota</th>
                        <th>Comentário</th>
                        <th>Data</th>
                        <th>Resposta</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent_reviews as $review): ?>
                    <tr>
                        <td><?= htmlspecialchars($review['produto_nome']) ?></td>
                        <td><?= htmlspecialchars($review['cliente_nome']) ?></td>
                        <td>
                            <?php
                                $stars = intval($review['nota']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo '<i class="fas fa-star'.($i <= $stars ? ' filled' : '').'" style="color:'.($i <= $stars ? '#ffd200' : '#ccc').'"></i>';
                                }
                            ?>
                        </td>
                        <td><?= nl2br(htmlspecialchars($review['comentario'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($review['data_avaliacao'])) ?></td>
                        <td>
                            <?php if (!empty($review['resposta_vendedor'])): ?>
                                <span title="<?= htmlspecialchars($review['resposta_vendedor']) ?>"><i class="fas fa-reply" style="color:#4caf50"></i></span>
                            <?php else: ?>
                                <span style="color:#888">---</span>
                            <?php endif; ?>
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