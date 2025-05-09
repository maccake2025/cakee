<?php

/**
 * Arquivo auth.php - API de Autenticação
 * Responsável por: login, logout e verificação de sessão
 * Endpoints:
 * - POST /auth.php (login)
 * - DELETE /auth.php (logout)
 * - GET /auth.php (verificar autenticação)
 */

// Configura o cabeçalho para retornar JSON
header('Content-Type: application/json');

// Inclui o arquivo de configuração do banco de dados
require_once '../config/db.php';

// Inicia a sessão PHP para gerenciar estado de autenticação
session_start();

// Obtém o método HTTP da requisição (GET, POST, DELETE, etc.)
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Tratamento do Login (POST)
 */
if ($method == 'POST') {
    // Lê os dados JSON do corpo da requisição
    $data = json_decode(file_get_contents('php://input'), true);

    // Validação dos campos obrigatórios
    if (empty($data['email']) || empty($data['senha'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Email e senha são obrigatórios']);
        exit;
    }

    try {
        // Prepara e executa query para buscar usuário
        $sql = "SELECT id, nome, foto_perfil, senha FROM usuarios WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$data['email']]);
        $usuario = $stmt->fetch();

        /**
         * Verificação de credenciais
         * ATENÇÃO: Em produção, usar password_verify() com senhas hasheadas
         */
        if ($usuario && $usuario['senha'] == $data['senha']) {
            // Cria/atualiza a sessão com dados do usuário
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_foto'] = $usuario['foto_perfil'];

            // Retorna sucesso com dados do usuário (sem a senha)
            echo json_encode([
                'success' => true,
                'usuario' => [
                    'id' => $usuario['id'],
                    'nome' => $usuario['nome'],
                    'foto_perfil' => $usuario['foto_perfil']
                ]
            ]);
        } else {
            http_response_code(401); // Unauthorized
            echo json_encode(['error' => 'Credenciais inválidas']);
        }
    } catch (PDOException $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(['error' => 'Erro no servidor']);
        error_log("Erro de login: " . $e->getMessage()); // Log interno
    }
}

/**
 * Tratamento do Logout (DELETE)
 */
elseif ($method == 'DELETE') {
    // Limpa todos os dados da sessão
    session_unset();
    // Destrói a sessão completamente
    session_destroy();
    // Retorna confirmação
    echo json_encode(['success' => true]);
}

/**
 * Verificação de Autenticação (GET)
 */
elseif ($method == 'GET') {
    if (isset($_SESSION['usuario_id'])) {
        // Usuário autenticado - retorna dados da sessão
        echo json_encode([
            'authenticated' => true,
            'usuario' => [
                'id' => $_SESSION['usuario_id'],
                'nome' => $_SESSION['usuario_nome'],
                'foto_perfil' => $_SESSION['usuario_foto']
            ]
        ]);
    } else {
        // Sessão não autenticada
        echo json_encode(['authenticated' => false]);
    }
}

/**
 * Métodos não implementados
 */
else {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Método não permitido']);
}

/**
 * Funcionamento Geral:
 * 
 * 1. POST /auth.php (Login):
 *    - Recebe email e senha via JSON
 *    - Valida no banco de dados
 *    - Cria sessão PHP se válido
 *    - Retorna dados do usuário (sem senha)
 * 
 * 2. DELETE /auth.php (Logout):
 *    - Destrói a sessão atual
 *    - Retorna confirmação
 * 
 * 3. GET /auth.php (Verificar autenticação):
 *    - Verifica se há sessão ativa
 *    - Retorna status e dados do usuário se autenticado
 * 
 * Segurança:
 * - Sempre retorna códigos HTTP apropriados
 * - Nunca expõe a senha, mesmo hasheada
 * - Em produção, implementar:
 *   - Hash de senha com password_hash()
 *   - Proteção contra brute-force
 *   - HTTPS obrigatório
 *   - CORS restrito
 * 
 * Fluxo do Frontend:
 * 1. Login: POST com credenciais → armazena token/sessão
 * 2. Requests subsequentes: inclui token/sessão
 * 3. Logout: DELETE → limpa token/sessão
 */