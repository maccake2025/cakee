<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /pages/cart.php');
    exit;
}

$codigo = isset($_POST['codigo_cupom']) ? trim($_POST['codigo_cupom']) : '';
if ($codigo === '') {
    $_SESSION['erro'] = 'Digite o código do cupom.';
    header('Location: /pages/cart.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Busca cupom válido
$stmt = $conn->prepare("SELECT * FROM cupons WHERE codigo = ? AND ativo = 1 AND (data_validade IS NULL OR data_validade >= NOW())");
$stmt->execute([$codigo]);
$cupom = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cupom) {
    $_SESSION['erro'] = 'Cupom inválido, expirado ou inativo.';
    header('Location: /pages/cart.php');
    exit;
}

// Checa usos máximos
if ($cupom['usos_maximos'] > 0 && $cupom['usos_atual'] >= $cupom['usos_maximos']) {
    $_SESSION['erro'] = 'Este cupom já atingiu o número máximo de usos.';
    header('Location: /pages/cart.php');
    exit;
}

// Checa valor mínimo do carrinho
require_once __DIR__ . '/../classes/Cart.php';
$cart = new Cart($conn);
$subtotal = $cart->getSubtotal();

if ($subtotal < $cupom['valor_minimo']) {
    $_SESSION['erro'] = 'Cupom válido apenas para compras acima de R$ ' . number_format($cupom['valor_minimo'], 2, ',', '.');
    header('Location: /pages/cart.php');
    exit;
}

// Calcula desconto
$desconto = 0;
if ($cupom['tipo_desconto'] === 'percentual') {
    $desconto = round($subtotal * ($cupom['desconto'] / 100), 2);
} else { // fixo
    $desconto = round($cupom['desconto'], 2);
}
if ($desconto > $subtotal) {
    $desconto = $subtotal;
}

// Salva cupom na sessão
$_SESSION['cupom'] = [
    'id'      => $cupom['id'],
    'codigo'  => $cupom['codigo'],
    'tipo'    => $cupom['tipo_desconto'],
    'desconto'=> $desconto,
    'texto'   => ($cupom['tipo_desconto'] === 'percentual' ? $cupom['desconto'].'%' : 'R$ '.number_format($cupom['desconto'],2,',','.')),
];

// Mensagem de sucesso
$_SESSION['sucesso'] = 'Cupom aplicado! Desconto de ' . ($_SESSION['cupom']['tipo'] === 'percentual'
    ? $_SESSION['cupom']['texto']
    : 'R$ ' . number_format($desconto, 2, ',', '.')) . ' no pedido.';

header('Location: /pages/cart.php');
exit;