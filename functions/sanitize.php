<?php
/**
 * Sanitiza uma string ou array para uso seguro no sistema.
 * Remove tags HTML, espaços desnecessários e converte caracteres especiais.
 *
 * @param mixed $input A string ou array a ser sanitizada.
 * @return mixed A string ou array sanitizado.
 */
function sanitize($input) {
    if (is_array($input)) {
        // Sanitiza recursivamente cada elemento do array
        return array_map('sanitize', $input);
    }
    // Garante que seja string e aplica as sanitizações
    return htmlspecialchars(
        trim(strip_tags((string)($input ?? ''))),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
}