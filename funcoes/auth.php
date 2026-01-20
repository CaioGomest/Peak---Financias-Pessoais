<?php
/**
 * Middleware de autenticação
 * Inclua este arquivo no início de páginas que precisam de autenticação
 */

require_once __DIR__ . '/usuario.php';

// Verificar se o usuário está logado
verificarLogin();

// Função para obter dados do usuário atual para uso nas páginas
function obterUsuarioAtual() {
    return obterDadosUsuario();
}

// Função para obter ID do usuário atual
function obterIdUsuarioAtual() {
    return obterUsuarioId();
}
?>