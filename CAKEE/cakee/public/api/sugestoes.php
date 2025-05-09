<?php
// Cabeçalhos para permitir acesso CORS e definir o tipo de conteúdo
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once('../../src/config/database.php');
require_once('../../src/models/Produto.php');

try {
    $sugestoes = Produto::listarSugestoes();

    if ($sugestoes) {
        echo json_encode([
            'status' => 'success',
            'data' => $sugestoes
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status' => 'empty',
            'message' => 'Nenhuma sugestão encontrada.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao acessar a API: ' . $e->getMessage()
    ]);
}
