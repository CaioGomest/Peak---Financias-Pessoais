<?php
// Funções relacionadas às configurações - Versão com Banco de Dados

require_once __DIR__ . '/../config/database.php';

// Inicializar conexão com o banco de dados
$database = new Database();

/**
 * Obtém as configurações do usuário do banco de dados
 */
function lerConfiguracoes($usuario_id = 1) {
    global $database;
    
    $sql = "SELECT * FROM configuracoes_usuario WHERE usuario_id = ?";
    $resultado = $database->select($sql, [$usuario_id]);
    
    if (empty($resultado)) {
        return criarConfiguracoesPadrao($usuario_id);
    }
    
    $config = $resultado[0];
    
    // Criar estrutura compatível com o formato antigo
    $config['preferencias'] = [
        'moeda' => $config['moeda'],
        'simbolo_moeda' => $config['simbolo_moeda'],
        'formato_data' => $config['formato_data'],
        'tema' => $config['tema'],
        'mostrar_saldo' => (bool)$config['mostrar_saldo'],
        'notificacoes_email' => (bool)$config['notificacoes_email'],
        'notificacoes_push' => (bool)$config['notificacoes_push'],
        'lembretes' => (bool)$config['lembretes']
    ];
    
    $config['configuracoes_sistema'] = [];
    
    return $config;
}

/**
 * Salva as configurações do usuário no banco de dados
 */
function salvarConfiguracoes($dados, $usuario_id = 1) {
    global $database;
    
    $prefs = $dados['preferencias'] ?? [];
    
    $sql = "UPDATE configuracoes_usuario SET 
                moeda = ?,
                simbolo_moeda = ?,
                formato_data = ?,
                tema = ?,
                mostrar_saldo = ?,
                notificacoes_email = ?,
                notificacoes_push = ?,
                lembretes = ?,
                atualizado_em = NOW()
            WHERE usuario_id = ?";
    
    $params = [
        $prefs['moeda'] ?? 'BRL',
        $prefs['simbolo_moeda'] ?? 'R$',
        $prefs['formato_data'] ?? 'd/m/Y',
        $prefs['tema'] ?? 'escuro',
        isset($prefs['mostrar_saldo']) ? ($prefs['mostrar_saldo'] ? 1 : 0) : 1,
        isset($prefs['notificacoes_email']) ? ($prefs['notificacoes_email'] ? 1 : 0) : 1,
        isset($prefs['notificacoes_push']) ? ($prefs['notificacoes_push'] ? 1 : 0) : 0,
        isset($prefs['lembretes']) ? ($prefs['lembretes'] ? 1 : 0) : 1,
        $usuario_id
    ];
    
    return $database->update($sql, $params) > 0;
}

/**
 * Cria configurações padrão para um usuário
 */
function criarConfiguracoesPadrao($usuario_id = 1) {
    global $database;
    
    $sql = "INSERT INTO configuracoes_usuario (usuario_id, moeda, simbolo_moeda, formato_data, tema, mostrar_saldo, notificacoes_email, notificacoes_push, lembretes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                moeda = VALUES(moeda),
                simbolo_moeda = VALUES(simbolo_moeda),
                formato_data = VALUES(formato_data),
                tema = VALUES(tema),
                mostrar_saldo = VALUES(mostrar_saldo),
                notificacoes_email = VALUES(notificacoes_email),
                notificacoes_push = VALUES(notificacoes_push),
                lembretes = VALUES(lembretes)";
    
    $database->insert($sql, [
        $usuario_id,
        'BRL',
        'R$',
        'd/m/Y',
        'escuro',
        1,
        1,
        0,
        1
    ]);
    
    $preferencias = [
        'moeda' => 'BRL',
        'simbolo_moeda' => 'R$',
        'formato_data' => 'd/m/Y',
        'tema' => 'escuro',
        'mostrar_saldo' => true,
        'notificacoes_email' => true,
        'notificacoes_push' => false,
        'lembretes' => true
    ];
    
    return [
        'usuario_id' => $usuario_id,
        'preferencias' => $preferencias,
        'configuracoes_sistema' => []
    ];
}

/**
 * Obtém as preferências do usuário
 */
function obterPreferencias($usuario_id = 1) {
    $config = lerConfiguracoes($usuario_id);
    return $config['preferencias'];
}

/**
 * Atualiza as preferências do usuário
 */
function atualizarPreferencias($novas_preferencias, $usuario_id = 1) {
    $config = lerConfiguracoes($usuario_id);
    $config['preferencias'] = array_merge($config['preferencias'], $novas_preferencias);
    return salvarConfiguracoes($config, $usuario_id);
}

/**
 * Obtém todas as contas ativas do banco de dados
 */
function obterContas($usuario_id = 1) {
    global $database;
    
    $sql = "SELECT * FROM contas WHERE usuario_id = ? AND ativa = 1 ORDER BY nome ASC";
    return $database->select($sql, [$usuario_id]);
}

/**
 * Obtém uma conta por ID
 */
function obterContaPorId($id, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT * FROM contas WHERE id = ? AND usuario_id = ? AND ativa = 1";
    $resultado = $database->select($sql, [$id, $usuario_id]);
    
    return $resultado[0] ?? null;
}

/**
 * Adiciona uma nova conta
 */
function adicionarConta($nome, $tipo, $banco, $saldo_inicial = 0.00, $cor = '#2196F3', $usuario_id = 1) {
    global $database;
    
    $sql = "INSERT INTO contas (usuario_id, nome, tipo, banco, saldo_inicial, saldo_atual, cor, ativa) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
    
    $id = $database->insert($sql, [
        $usuario_id,
        $nome,
        $tipo,
        $banco,
        floatval($saldo_inicial),
        floatval($saldo_inicial),
        $cor
    ]);
    
    if ($id) {
        return obterContaPorId($id, $usuario_id);
    }
    
    return false;
}

/**
 * Edita uma conta existente
 */
function editarConta($id, $dados_atualizados, $usuario_id = 1) {
    global $database;
    
    $campos = [];
    $params = [];
    
    if (isset($dados_atualizados['nome'])) {
        $campos[] = "nome = ?";
        $params[] = $dados_atualizados['nome'];
    }
    if (isset($dados_atualizados['tipo'])) {
        $campos[] = "tipo = ?";
        $params[] = $dados_atualizados['tipo'];
    }
    if (isset($dados_atualizados['banco'])) {
        $campos[] = "banco = ?";
        $params[] = $dados_atualizados['banco'];
    }
    if (isset($dados_atualizados['cor'])) {
        $campos[] = "cor = ?";
        $params[] = $dados_atualizados['cor'];
    }
    if (isset($dados_atualizados['ativa'])) {
        $campos[] = "ativa = ?";
        $params[] = $dados_atualizados['ativa'] ? 1 : 0;
    }
    
    if (empty($campos)) {
        return false;
    }
    
    $campos[] = "atualizado_em = NOW()";
    $params[] = intval($id);
    $params[] = $usuario_id;
    
    $sql = "UPDATE contas SET " . implode(', ', $campos) . " WHERE id = ? AND usuario_id = ?";
    
    return $database->update($sql, $params) > 0;
}

/**
 * Exclui uma conta (marca como inativa)
 */
function excluirConta($id, $usuario_id = 1) {
    global $database;
    
    $sql = "UPDATE contas SET ativa = 0, atualizado_em = NOW() WHERE id = ? AND usuario_id = ?";
    return $database->update($sql, [$id, $usuario_id]) > 0;
}

/**
 * Obtém o saldo atual de uma conta
 */
function obterSaldoConta($conta_id, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT saldo_atual FROM contas WHERE id = ? AND usuario_id = ? AND ativa = 1";
    $resultado = $database->select($sql, [$conta_id, $usuario_id]);
    
    return floatval($resultado[0]['saldo_atual'] ?? 0);
}

/**
 * Atualiza o saldo de uma conta
 */
function atualizarSaldoConta($conta_id, $novo_saldo, $usuario_id = 1) {
    global $database;
    
    $sql = "UPDATE contas SET saldo_atual = ?, atualizado_em = NOW() WHERE id = ? AND usuario_id = ?";
    return $database->update($sql, [$novo_saldo, $conta_id, $usuario_id]) > 0;
}

/**
 * Obtém todas as contas (incluindo inativas) - para administração
 */
function obterTodasContas($usuario_id = 1) {
    global $database;
    
    $sql = "SELECT * FROM contas WHERE usuario_id = ? ORDER BY ativa DESC, nome ASC";
    return $database->select($sql, [$usuario_id]);
}

/**
 * Verifica se uma conta está sendo usada em transações
 */
function contaEstaEmUso($id, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT COUNT(*) as total FROM transacoes WHERE conta_id = ? AND usuario_id = ?";
    $resultado = $database->select($sql, [$id, $usuario_id]);
    
    return intval($resultado[0]['total'] ?? 0) > 0;
}

/**
 * Formata um valor monetário de acordo com as preferências do usuário
 */
function formatarMoeda($valor, $usuario_id = 1) {
    $preferencias = obterPreferencias($usuario_id);
    $simbolo = $preferencias['simbolo_moeda'];
    return $simbolo . ' ' . number_format($valor, 2, ',', '.');
}

/**
 * Formata uma data de acordo com as preferências do usuário
 */
function formatarData($data, $usuario_id = 1) {
    $preferencias = obterPreferencias($usuario_id);
    $formato = $preferencias['formato_data'];
    return date($formato, strtotime($data));
}

/**
 * Obtém informações do usuário
 */
function obterUsuario($usuario_id = 1) {
    global $database;
    
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $resultado = $database->select($sql, [$usuario_id]);
    
    return $resultado[0] ?? null;
}

/**
 * Atualiza informações do usuário
 */
function atualizarDadosUsuario($dados_atualizados, $usuario_id = 1) {
    global $database;
    
    $campos = [];
    $params = [];
    
    if (isset($dados_atualizados['nome'])) {
        $campos[] = "nome = ?";
        $params[] = $dados_atualizados['nome'];
    }
    if (isset($dados_atualizados['email'])) {
        $campos[] = "email = ?";
        $params[] = $dados_atualizados['email'];
    }
    if (isset($dados_atualizados['foto_perfil'])) {
        $campos[] = "foto_perfil = ?";
        $params[] = $dados_atualizados['foto_perfil'];
    }
    
    if (empty($campos)) {
        return false;
    }
    
    $campos[] = "atualizado_em = NOW()";
    $params[] = $usuario_id;
    
    $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
    
    return $database->update($sql, $params) > 0;
}

// Funções para compatibilidade com código antigo
function obterMetas($usuario_id = 1) {
    // Metas foram removidas do sistema, retorna array vazio para compatibilidade
    return [
        'economia_mensal' => 0.00,
        'limite_gastos' => 0.00,
        'meta_anual' => 0.00
    ];
}

function atualizarMetas($novas_metas, $usuario_id = 1) {
    // Metas foram removidas do sistema, retorna true para compatibilidade
    return true;
}
?>