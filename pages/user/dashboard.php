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

// Busca dados do usuário
$stmt = $conn->prepare("SELECT id, nome, email, foto_perfil, tipo FROM usuarios WHERE id = ?");
$stmt->execute([$user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Estatísticas
$stmt = $conn->prepare("SELECT COUNT(*) FROM pedidos WHERE cliente_id = ?");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT SUM(total) FROM pedidos WHERE cliente_id = ? AND status != 'cancelado'");
$stmt->execute([$user_id]);
$total_spent = $stmt->fetchColumn();
$total_spent = $total_spent ? $total_spent : 0;

$stmt = $conn->prepare("SELECT COUNT(*) FROM avaliacoes WHERE usuario_id = ?");
$stmt->execute([$user_id]);
$total_reviews = $stmt->fetchColumn();

$stmt = $conn->prepare("
    SELECT id, data_pedido, status, total
    FROM pedidos
    WHERE cliente_id = ?
    ORDER BY data_pedido DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$last_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT a.*, p.nome AS produto_nome
    FROM avaliacoes a
    JOIN produtos p ON a.produto_id = p.id
    WHERE a.usuario_id = ?
    ORDER BY a.data_avaliacao DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$last_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT f.*, p.nome, p.imagem_principal
    FROM favoritos f
    JOIN produtos p ON f.produto_id = p.id
    WHERE f.usuario_id = ?
    ORDER BY f.data_adicionado DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Painel do Cliente - Cakee Market</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .client-dashboard { max-width: 1100px; margin: 0 auto; padding: 28px 10px; }
        .profile-panel { display:flex; align-items:center; gap:26px; background:#fff; border-radius:8px; box-shadow:0 2px 9px rgba(0,0,0,0.06); padding:16px 18px; margin-bottom:30px;}
        .profile-pic { width:70px; height:70px; border-radius:50%; object-fit:cover; border:2px solid #e77054;}
        .stats-block { display:flex; gap:30px; flex-wrap:wrap; margin-bottom:33px; }
        .stat-card { background:#fff; border-radius:7px; box-shadow:0 2px 8px rgba(0,0,0,0.04); padding:20px 19px; text-align:center; min-width:120px; }
        .stat-value { font-size:2.2em; color:#e77054; font-weight:bold; }
        .stat-label { font-size:1em; color:#444; }
        .section { margin-bottom:38px; }
        .table-list { width:100%; border-collapse:collapse; background:#fff; border-radius:6px;}
        .table-list th, .table-list td { padding:9px 7px; border-bottom:1px solid #eee;}
        .table-list th { background:#f7f7f7;}
        .table-list tr:last-child td { border-bottom:none;}
        .btn-small { padding:3px 10px; font-size:.95em; border-radius:4px; border:none; background:#e77054; color:#fff; cursor:pointer; text-decoration:none;}
        .btn-small:hover { background:#d3593e;}
        .fav-img { width:44px; height:44px; border-radius:6px; object-fit:cover;}
        @media (max-width:900px) {
            .stats-block { flex-direction:column; gap:12px;}
            .profile-panel { flex-direction:column; gap:12px; }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../includes/header.php'; ?>
<div class="client-dashboard">
    <div class="stats-block">
        <div class="stat-card">
            <span class="stat-value"><?= $total_orders ?></span>
            <span class="stat-label">Pedidos</span>
        </div>
        <div class="stat-card">
            <span class="stat-value">R$ <?= number_format($total_spent, 2, ',', '.') ?></span>
            <span class="stat-label">Total gasto</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $total_reviews ?></span>
            <span class="stat-label">Avaliações</span>
        </div>
    </div>
    <div class="section">
        <h2>Últimos Pedidos</h2>
        <?php if (empty($last_orders)): ?>
            <p>Você ainda não fez pedidos.</p>
        <?php else: ?>
            <table class="table-list">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($last_orders as $order): ?>
                    <tr>
                        <td>#<?= $order['id'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($order['data_pedido'])) ?></td>
                        <td><?= ucfirst($order['status']) ?></td>
                        <td>R$ <?= number_format($order['total'], 2, ',', '.') ?></td>
                        <td>
                            <a href="/pages/user/order_detail.php?id=<?= $order['id'] ?>" class="btn-small">Ver Detalhes</a>
                        </td>
                    </tr>
                <?php endforeach;?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <div class="section">
        <h2>Últimas Avaliações</h2>
        <?php if (empty($last_reviews)): ?>
            <p>Você ainda não fez avaliações.</p>
        <?php else: ?>
            <table class="table-list">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Nota</th>
                        <th>Comentário</th>
                        <th>Data</th>
                        <th>Resposta do Vendedor</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($last_reviews as $review): ?>
                    <tr>
                        <td><?= htmlspecialchars($review['produto_nome']) ?></td>
                        <td>
                            <?php
                                for ($i = 1; $i <= 5; $i++) {
                                    echo '<i class="fas fa-star'.($i <= intval($review['nota']) ? ' filled' : '').'" style="color:'.($i <= intval($review['nota']) ? '#ffd200' : '#ccc').'"></i>';
                                }
                            ?>
                        </td>
                        <td><?= nl2br(htmlspecialchars($review['comentario'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($review['data_avaliacao'])) ?></td>
                        <td>
                            <?= !empty($review['resposta_vendedor']) ? nl2br(htmlspecialchars($review['resposta_vendedor'])) : '<span style="color:#888">---</span>' ?>
                        </td>
                    </tr>
                <?php endforeach;?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
<!-- FontAwesome -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
</body>
</html>