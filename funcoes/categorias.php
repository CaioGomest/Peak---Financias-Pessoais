<?php
// Funções relacionadas às categorias - Versão com Banco de Dados

require_once __DIR__ . '/../config/database.php';

// Inicializar conexão com o banco de dados
$database = new Database();

/**
 * Obtém todas as categorias ativas do banco de dados
 */
function obterCategorias($tipo = null, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT * FROM categorias WHERE ativa = 1 AND usuario_id = ?";
    $params = [$usuario_id];
    
    if ($tipo) {
        $sql .= " AND tipo = ?";
        $params[] = $tipo;
    }
    
    $sql .= " ORDER BY nome ASC";
    
    return $database->select($sql, $params);
}

/**
 * Obtém categorias por usuário
 */
function obterCategoriasPorUsuario($usuario_id) {
    global $database;
    
    $sql = "SELECT * FROM categorias WHERE usuario_id = ? AND ativa = 1 ORDER BY nome";
    return $database->select($sql, [$usuario_id]);
}

/**
 * Obtém uma categoria por ID
 */
function obterCategoriaPorId($id) {
    global $database;
    
    $sql = "SELECT * FROM categorias WHERE id = ? AND ativa = 1";
    $resultado = $database->select($sql, [$id]);
    
    return $resultado[0] ?? null;
}

/**
 * Adiciona uma nova categoria
 */
function adicionarCategoria($nome, $tipo, $icone = 'fas fa-tag', $cor = '#666666', $usuario_id = 1) {
    global $database;
    
    $sql = "INSERT INTO categorias (usuario_id, nome, tipo, icone, cor, ativa) VALUES (?, ?, ?, ?, ?, 1)";
    $params = [$usuario_id, $nome, $tipo, $icone, $cor];
    
    $id = $database->insert($sql, $params);
    
    if ($id) {
        // Retorna o ID da categoria criada
        return $id;
    }
    
    return false;
}

/**
 * Edita uma categoria existente
 */
function editarCategoria($id, $dados_atualizados) {
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
    if (isset($dados_atualizados['icone'])) {
        $campos[] = "icone = ?";
        $params[] = $dados_atualizados['icone'];
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
    
    $sql = "UPDATE categorias SET " . implode(', ', $campos) . " WHERE id = ?";
    
    return $database->update($sql, $params) > 0;
}

/**
 * Exclui uma categoria (marca como inativa)
 */
function excluirCategoria($id) {
    global $database;
    
    $sql = "UPDATE categorias SET ativa = 0, atualizado_em = NOW() WHERE id = ?";
    return $database->update($sql, [$id]) > 0;
}

/**
 * Obtém categorias de receitas
 */
function obterCategoriasReceitas() {
    return obterCategorias('receita');
}

/**
 * Obtém categorias de despesas
 */
function obterCategoriasDespesas() {
    return obterCategorias('despesa');
}

/**
 * Obtém todas as categorias (incluindo inativas) - para administração
 */
function obterTodasCategorias($tipo = null) {
    global $database;
    
    $sql = "SELECT * FROM categorias";
    $params = [];
    
    if ($tipo) {
        $sql .= " WHERE tipo = ?";
        $params[] = $tipo;
    }
    
    $sql .= " ORDER BY ativa DESC, nome ASC";
    
    return $database->select($sql, $params);
}

/**
 * Verifica se uma categoria está sendo usada em transações
 */
function categoriaEstaEmUso($id) {
    global $database;
    
    $sql = "SELECT COUNT(*) as total FROM transacoes WHERE categoria_id = ?";
    $resultado = $database->select($sql, [$id]);
    
    return intval($resultado[0]['total'] ?? 0) > 0;
}

/**
 * Obtém estatísticas de uso de uma categoria
 */
function obterEstatisticasCategoria($id, $mes = null, $ano = null) {
    global $database;
    
    $sql = "SELECT 
                COUNT(*) as total_transacoes,
                SUM(valor) as total_valor,
                AVG(valor) as valor_medio,
                MIN(valor) as menor_valor,
                MAX(valor) as maior_valor
            FROM transacoes 
            WHERE categoria_id = ?";
    
    $params = [$id];
    
    if ($mes && $ano) {
        $sql .= " AND MONTH(data_transacao) = ? AND YEAR(data_transacao) = ?";
        $params[] = $mes;
        $params[] = $ano;
    }
    
    $resultado = $database->select($sql, $params);
    
    if (!empty($resultado)) {
        return [
            'total_transacoes' => intval($resultado[0]['total_transacoes'] ?? 0),
            'total_valor' => floatval($resultado[0]['total_valor'] ?? 0),
            'valor_medio' => floatval($resultado[0]['valor_medio'] ?? 0),
            'menor_valor' => floatval($resultado[0]['menor_valor'] ?? 0),
            'maior_valor' => floatval($resultado[0]['maior_valor'] ?? 0)
        ];
    }
    
    return [
        'total_transacoes' => 0,
        'total_valor' => 0,
        'valor_medio' => 0,
        'menor_valor' => 0,
        'maior_valor' => 0
    ];
}

// Função para compatibilidade com código antigo
function lerCategorias() {
    $categorias = obterCategorias();
    
    return [
        'categorias' => $categorias,
        'proximo_id' => count($categorias) + 1
    ];
}

// Função para compatibilidade com código antigo
function salvarCategorias($dados) {
    // Esta função não é mais necessária com banco de dados
    // Mantida apenas para compatibilidade
    return true;
}
?>