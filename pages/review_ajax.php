<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/sanitize.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Você precisa estar logado.']);
    exit();
}

$db = new Database();
$conn = $db->connect();

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id < 1) {
    echo json_encode(['success' => false, 'message' => 'Produto inválido.']);
    exit();
}

if ($action === 'add') {
    $nota = max(1, min(5, (int)$_POST['nota']));
    $comentario = sanitize($_POST['comentario']);
    if ($nota < 1 || $nota > 5 || strlen($comentario) < 5) {
        echo json_encode(['success' => false, 'message' => 'Nota/comentário inválido.']);
        exit();
    }
    // Impede múltiplas avaliações
    $stmt = $conn->prepare("SELECT id FROM avaliacoes WHERE usuario_id = ? AND produto_id = ?");
    $stmt->execute([$user_id, $product_id]);
    if ($stmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Você já avaliou este produto.']);
        exit();
    }
    $stmt = $conn->prepare("INSERT INTO avaliacoes (usuario_id, produto_id, nota, comentario, data_avaliacao) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$user_id, $product_id, $nota, $comentario]);
    echo json_encode(['success' => true, 'message' => 'Avaliação registrada com sucesso!']);
    exit();
}
if ($action === 'edit') {
    $review_id = (int)$_POST['review_id'];
    $nota = max(1, min(5, (int)$_POST['nota']));
    $comentario = sanitize($_POST['comentario']);
    if ($nota < 1 || $nota > 5 || strlen($comentario) < 5) {
        echo json_encode(['success' => false, 'message' => 'Nota/comentário inválido.']);
        exit();
    }
    $stmt = $conn->prepare("UPDATE avaliacoes SET nota = ?, comentario = ?, data_avaliacao = NOW() WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$nota, $comentario, $review_id, $user_id]);
    echo json_encode(['success' => true, 'message' => 'Avaliação editada com sucesso!']);
    exit();
}
if ($action === 'delete') {
    $review_id = (int)$_POST['review_id'];
    $stmt = $conn->prepare("DELETE FROM avaliacoes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$review_id, $user_id]);
    echo json_encode(['success' => true, 'message' => 'Avaliação apagada com sucesso!']);
    exit();
}
if ($action === 'get') {
    // Retorna bloco de avaliações HTML
    ob_start();
    require __DIR__ . '/review_block.php';
    $html = ob_get_clean();
    echo json_encode(['success' => true, 'html' => $html]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Ação inválida.']);