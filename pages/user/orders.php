<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inicia sessão e verifica login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/auth/login.php?success=login_required');
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'];
$orders = [];
$error_message = '';

// Buscar pedidos do usuário (corrigido: cliente_id)
try {
    $stmt = $conn->prepare("
        SELECT id, data_pedido, status, total, endereco_entrega
        FROM pedidos
        WHERE cliente_id = ?
        ORDER BY data_pedido DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar itens de cada pedido
    $order_items = [];
    if ($orders) {
        $order_ids = array_column($orders, 'id');
        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
        $stmt = $conn->prepare("
            SELECT oi.pedido_id, oi.produto_id, oi.quantidade, oi.preco_unitario, oi.nome_produto, oi.imagem_produto
            FROM pedido_itens oi
            WHERE oi.pedido_id IN ($placeholders)
        ");
        $stmt->execute($order_ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $order_items[$item['pedido_id']][] = $item;
        }
    }
} catch (PDOException $e) {
    $error_message = "Erro ao buscar seus pedidos. Detalhes: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Meus Pedidos - Cakee Market</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/orders.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <?php require_once __DIR__ . '/../../includes/header.php'; ?>

    <main class="orders-page">
        <h1>Meus Pedidos</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <img src="../../assets/images/orders-empty.png" alt="Sem pedidos">
                <p>Você ainda não fez nenhum pedido.</p>
                <a href="/pages/products.php" class="btn btn-primary">
                    <i class="fas fa-shopping-basket"></i> Ver produtos
                </a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">Pedido #<?= $order['id'] ?></span>
                            <span class="order-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?= date('d/m/Y H:i', strtotime($order['data_pedido'])) ?>
                            </span>
                            <span class="order-status status-<?= strtolower($order['status']) ?>">
                                <i class="fas fa-info-circle"></i>
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                        <div class="order-body">
                            <div class="order-items">
                                <?php foreach (($order_items[$order['id']] ?? []) as $item): ?>
                                    <div class="order-item">
                                        <img src="../../assets/images/uploads/products/<?= htmlspecialchars($item['imagem_produto']) ?>"
                                             alt="<?= htmlspecialchars($item['nome_produto']) ?>"
                                             onerror="this.src='../../assets/images/default-product.jpg'">
                                        <div class="item-info">
                                            <h3><?= htmlspecialchars($item['nome_produto']) ?></h3>
                                            <span>Qtd: <?= $item['quantidade'] ?></span>
                                            <span>Preço: R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="order-summary">
                                <span><strong>Total:</strong> R$ <?= number_format($order['total'], 2, ',', '.') ?></span>
                                <span><strong>Entrega:</strong> <?= htmlspecialchars($order['endereco_entrega']) ?></span>
                            </div>
                        </div>
                        <div class="order-footer">
                            <?php if (strtolower($order['status']) === 'entregue'): ?>
                                <a href="/pages/user/order_detail.php?id=<?= $order['id'] ?>" class="btn btn-outline">
                                    <i class="fas fa-star"></i> Avaliar pedido
                                </a>
                            <?php endif; ?>
                            <a href="/pages/user/order_detail.php?id=<?= $order['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Ver detalhes
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php require_once __DIR__ . '/../../includes/footer.php'; ?>
    <script src="../../assets/js/main.js"></script>
</body>
</html>