<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Cart.php';

// Função para sanitizar
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Requisição inválida.'
    ]);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Validar entrada
if ($product_id < 1 || $quantity < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Produto ou quantidade inválida.'
    ]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    $cart = new Cart($conn);

    // Buscar produto para validação
    $stmt = $conn->prepare("
        SELECT p.*, u.nome as vendedor_nome 
        FROM produtos p 
        JOIN usuarios u ON p.vendedor_id = u.id 
        WHERE p.id = ? AND p.ativo = 1
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Produto não encontrado ou inativo.'
        ]);
        exit;
    }

    $estoque = (int)$product['estoque'];

    // Checar estoque atual no carrinho
    $atual_no_carrinho = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0;
    if ($atual_no_carrinho + $quantity > $estoque) {
        echo json_encode([
            'success' => false,
            'message' => "Quantidade solicitada excede o estoque disponível ({$estoque})."
        ]);
        exit;
    }

    // Adicionar ao carrinho
    $cart->addItem($product_id, $quantity);

    // Contar itens no carrinho
    $cart_count = $cart->countItems();

    echo json_encode([
        'success' => true,
        'cart_count' => $cart_count,
        'message' => 'Produto adicionado ao carrinho!'
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>