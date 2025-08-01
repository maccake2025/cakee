<?php
/**
 * Faz upload seguro de imagens para um diretório específico.
 * Aceita apenas arquivos de imagem (JPEG, PNG, GIF, WEBP).
 *
 * @param array $file Arquivo individual de $_FILES
 * @param string $folder Pasta dentro de /assets/images/uploads/
 * @param int $max_size_mb Tamanho máximo em MB
 * @return array ['success' => bool, 'file_name' => string, 'error' => string]
 */
function uploadImage($file, $folder = 'products', $max_size_mb = 5) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = $max_size_mb * 1024 * 1024;
    $uploads_dir = $_SERVER['DOCUMENT_ROOT'] . "/assets/images/uploads/$folder/";

    // Cria o diretório se não existir
    if (!is_dir($uploads_dir)) {
        if (!mkdir($uploads_dir, 0777, true)) {
            return ['success' => false, 'file_name' => '', 'error' => 'Falha ao criar o diretório de uploads.'];
        }
    }

    // Verificações iniciais
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'file_name' => '', 'error' => 'Parâmetro de upload inválido.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = 'Erro no upload.';
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            $msg = 'Arquivo muito grande.';
        }
        return ['success' => false, 'file_name' => '', 'error' => $msg];
    }
    if ($file['size'] > $max_size) {
        return ['success' => false, 'file_name' => '', 'error' => 'Arquivo muito grande. Máx: ' . $max_size_mb . 'MB'];
    }

    // Verifica o tipo MIME por segurança
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed_types)) {
        return ['success' => false, 'file_name' => '', 'error' => 'Tipo de arquivo inválido.'];
    }

    // Gera nome único seguro
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        return ['success' => false, 'file_name' => '', 'error' => 'Extensão de arquivo não permitida.'];
    }
    $basename = md5(uniqid((string)mt_rand(), true));
    $file_name = $basename . '.' . $ext;
    $dest_path = $uploads_dir . $file_name;

    // Move o arquivo
    if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
        return ['success' => false, 'file_name' => '', 'error' => 'Falha ao mover o arquivo enviado.'];
    }

    // Opcional: Redimensionamento/compressão pode ser feito aqui

    return ['success' => true, 'file_name' => $file_name, 'error' => ''];
}