<?php
// Funções relacionadas às transações - Versão com Banco de Dados

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/usuario.php';

// Inicializar conexão com o banco de dados
$database = new Database();

/**
 * Obtém todas as transações do banco de dados
 */
function obterTransacoes($usuario_id = 1) {
    global $database;
    
    $sql = "SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                   ct.nome as conta_nome, ct.tipo as conta_tipo,
                   CASE WHEN t.observacoes LIKE 'TRANSFERENCIA:%' THEN 1 ELSE 0 END as eh_transferencia
            FROM transacoes t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            LEFT JOIN contas ct ON t.conta_id = ct.id
            WHERE t.usuario_id = ? 
            ORDER BY t.data_transacao DESC, t.criado_em DESC";
    
    return $database->select($sql, [$usuario_id]);
}

/**
 * Adiciona uma nova transação
 */
function adicionarTransacao($tipo, $descricao, $valor, $categoria_id, $data, $conta_id, $observacoes = '', $usuario_id = 1) {
    global $database;
    
    $sql = "INSERT INTO transacoes (usuario_id, tipo, descricao, valor, categoria_id, conta_id, data_transacao, observacoes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [$usuario_id, $tipo, $descricao, floatval($valor), intval($categoria_id), intval($conta_id), $data, $observacoes];
    
    $id = $database->insert($sql, $params);
    
    if ($id) {
        // Retorna a transação criada
        $sql_select = "SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                              ct.nome as conta_nome, ct.tipo as conta_tipo
                       FROM transacoes t 
                       LEFT JOIN categorias c ON t.categoria_id = c.id 
                       LEFT JOIN contas ct ON t.conta_id = ct.id
                       WHERE t.id = ?";
        
        $resultado = $database->select($sql_select, [$id]);
        return $resultado[0] ?? null;
    }
    
    return false;
}

/**
 * Edita uma transação existente
 */
function editarTransacao($id, $dados_atualizados, $usuario_id = 1) {
    global $database;
    
    $campos = [];
    $params = [];
    
    if (isset($dados_atualizados['tipo'])) {
        $campos[] = "tipo = ?";
        $params[] = $dados_atualizados['tipo'];
    }
    if (isset($dados_atualizados['descricao'])) {
        $campos[] = "descricao = ?";
        $params[] = $dados_atualizados['descricao'];
    }
    if (isset($dados_atualizados['valor'])) {
        $campos[] = "valor = ?";
        $params[] = floatval($dados_atualizados['valor']);
    }
    if (isset($dados_atualizados['categoria_id'])) {
        $campos[] = "categoria_id = ?";
        $params[] = intval($dados_atualizados['categoria_id']);
    }
    if (isset($dados_atualizados['conta_id'])) {
        $campos[] = "conta_id = ?";
        $params[] = intval($dados_atualizados['conta_id']);
    }
    if (isset($dados_atualizados['data_transacao']) || isset($dados_atualizados['data'])) {
        $campos[] = "data_transacao = ?";
        $params[] = $dados_atualizados['data_transacao'] ?? $dados_atualizados['data'];
    }
    if (isset($dados_atualizados['observacoes'])) {
        $campos[] = "observacoes = ?";
        $params[] = $dados_atualizados['observacoes'];
    }
    
    if (empty($campos)) {
        return false;
    }
    
    $campos[] = "atualizado_em = NOW()";
    $params[] = intval($id);
    $params[] = $usuario_id;
    
    $sql = "UPDATE transacoes SET " . implode(', ', $campos) . " WHERE id = ? AND usuario_id = ?";
    
    return $database->update($sql, $params) > 0;
}

/**
 * Exclui uma transação
 */
function excluirTransacao($id, $usuario_id = 1) {
    global $database;
    
    $sql = "DELETE FROM transacoes WHERE id = ? AND usuario_id = ?";
    return $database->delete($sql, [$id, $usuario_id]) > 0;
}

/**
 * Calcula resumo financeiro
 */
function calcularResumo($usuario_id = 1) {
    global $database;
    
    $sql = "SELECT 
                SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) as total_receitas,
                SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END) as total_despesas,
                (SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END) - 
                 SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END)) as saldo_atual
            FROM transacoes 
            WHERE usuario_id = ? AND (observacoes IS NULL OR observacoes NOT LIKE 'TRANSFERENCIA:%')";
    
    $resultado = $database->select($sql, [$usuario_id]);
    
    if (!empty($resultado)) {
        return [
            'total_receitas' => floatval($resultado[0]['total_receitas'] ?? 0),
            'total_despesas' => floatval($resultado[0]['total_despesas'] ?? 0),
            'saldo_atual' => floatval($resultado[0]['saldo_atual'] ?? 0),
            'ultima_atualizacao' => date('Y-m-d H:i:s')
        ];
    }
    
    return [
        'total_receitas' => 0,
        'total_despesas' => 0,
        'saldo_atual' => 0,
        'ultima_atualizacao' => date('Y-m-d H:i:s')
    ];
}

/**
 * Obtém transações por período
 */
function obterTransacoesPorPeriodo($data_inicio, $data_fim, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                   ct.nome as conta_nome, ct.tipo as conta_tipo,
                   CASE WHEN t.observacoes LIKE 'TRANSFERENCIA:%' THEN 1 ELSE 0 END as eh_transferencia
            FROM transacoes t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            LEFT JOIN contas ct ON t.conta_id = ct.id
            WHERE t.usuario_id = ? AND t.data_transacao BETWEEN ? AND ?
            ORDER BY t.data_transacao DESC, t.criado_em DESC";
    
    return $database->select($sql, [$usuario_id, $data_inicio, $data_fim]);
}

/**
 * Obtém transações por categoria
 */
function obterTransacoesPorCategoria($categoria_id, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                   ct.nome as conta_nome, ct.tipo as conta_tipo,
                   CASE WHEN t.observacoes LIKE 'TRANSFERENCIA:%' THEN 1 ELSE 0 END as eh_transferencia
            FROM transacoes t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            LEFT JOIN contas ct ON t.conta_id = ct.id
            WHERE t.usuario_id = ? AND t.categoria_id = ?
            ORDER BY t.data_transacao DESC, t.criado_em DESC";
    
    return $database->select($sql, [$usuario_id, $categoria_id]);
}

/**
 * Calcula total de receitas por mês
 */
function calcularTotalReceitas($mes, $ano, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT COALESCE(SUM(valor), 0) as total
            FROM transacoes 
            WHERE usuario_id = ? AND tipo = 'receita' 
            AND MONTH(data_transacao) = ? AND YEAR(data_transacao) = ?
            AND (observacoes IS NULL OR observacoes NOT LIKE 'TRANSFERENCIA:%')";
    
    $resultado = $database->select($sql, [$usuario_id, $mes, $ano]);
    return floatval($resultado[0]['total'] ?? 0);
}

/**
 * Calcula total de despesas por mês
 */
function calcularTotalDespesas($mes, $ano, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT COALESCE(SUM(valor), 0) as total
            FROM transacoes 
            WHERE usuario_id = ? AND tipo = 'despesa' 
            AND MONTH(data_transacao) = ? AND YEAR(data_transacao) = ?
            AND (observacoes IS NULL OR observacoes NOT LIKE 'TRANSFERENCIA:%')";
    
    $resultado = $database->select($sql, [$usuario_id, $mes, $ano]);
    return floatval($resultado[0]['total'] ?? 0);
}

/**
 * Obtém despesas por categoria
 */
function obterDespesasPorCategoria($mes, $ano, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT c.id as categoria_id, c.nome as categoria_nome, c.cor as categoria_cor,
                   COALESCE(SUM(t.valor), 0) as total,
                   COUNT(t.id) as quantidade_transacoes
            FROM categorias c
            LEFT JOIN transacoes t ON c.id = t.categoria_id 
                AND t.tipo = 'despesa' 
                AND t.usuario_id = ?
                AND MONTH(t.data_transacao) = ? 
                AND YEAR(t.data_transacao) = ?
                AND (t.observacoes IS NULL OR t.observacoes NOT LIKE 'TRANSFERENCIA:%')
            WHERE c.tipo = 'despesa' AND c.ativa = 1
            GROUP BY c.id, c.nome, c.cor
            HAVING total > 0
            ORDER BY total DESC";
    
    return $database->select($sql, [$usuario_id, $mes, $ano]);
}

/**
 * Obtém transações por mês
 */
function obterTransacoesPorMes($mes, $ano, $usuario_id = 1) {
    global $database;
    
    $sql = "SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                   ct.nome as conta_nome, ct.tipo as conta_tipo,
                   CASE WHEN t.observacoes LIKE 'TRANSFERENCIA:%' THEN 1 ELSE 0 END as eh_transferencia
            FROM transacoes t 
            LEFT JOIN categorias c ON t.categoria_id = c.id 
            LEFT JOIN contas ct ON t.conta_id = ct.id
            WHERE t.usuario_id = ? 
            AND MONTH(t.data_transacao) = ? AND YEAR(t.data_transacao) = ?
            ORDER BY t.data_transacao DESC, t.criado_em DESC";
    
    return $database->select($sql, [$usuario_id, $mes, $ano]);
}

/**
 * Calcula saldo total
 */
function calcularSaldoTotal($usuario_id = 1) {
    $resumo = calcularResumo($usuario_id);
    return $resumo['saldo_atual'];
}

/**
 * Salva transação (adicionar ou editar)
 */
function salvarTransacao($dados, $usuario_id = 1) {
    if (isset($dados['id']) && $dados['id'] > 0) {
        // Editar transação existente
        return editarTransacao($dados['id'], $dados, $usuario_id);
    } else {
        // Adicionar nova transação
        $tipo = $dados['tipo'] ?? '';
        $descricao = $dados['descricao'] ?? '';
        $valor = $dados['valor'] ?? 0;
        $categoria_id = $dados['categoria_id'] ?? null;
        $data = $dados['data_transacao'] ?? $dados['data'] ?? date('Y-m-d');
        $conta_origem_id = $dados['conta_id'] ?? $dados['conta'] ?? null; // Compatibilidade
        $conta_destino_id = $dados['conta_destino_id'] ?? null;

        // Caso especial: transferência — criar duas transações atômicas (despesa + receita)
        // Somente quando houver conta de destino válida e diferente da origem
        if ($tipo === 'transferencia' && $conta_origem_id && $conta_destino_id && intval($conta_origem_id) !== intval($conta_destino_id)) {
            global $database;
            try {
                $database->beginTransaction();
                // Saída da conta de origem (despesa)
                $saida = adicionarTransacao(
                    'despesa',
                    $descricao,
                    $valor,
                    $categoria_id,
                    $data,
                    $conta_origem_id,
                    'TRANSFERENCIA:SAIDA',
                    $usuario_id
                );

                // Entrada na conta de destino (receita)
                $entrada = adicionarTransacao(
                    'receita',
                    $descricao,
                    $valor,
                    $categoria_id,
                    $data,
                    $conta_destino_id,
                    'TRANSFERENCIA:ENTRADA',
                    $usuario_id
                );

                if ($saida === false || $entrada === false) {
                    $database->rollback();
                    return false;
                }

                $database->commit();
                // Retornar ambos para eventual consumo
                return ['transferencia' => ['saida' => $saida, 'entrada' => $entrada]];
            } catch (Exception $e) {
                // Em caso de erro, garantir rollback
                try { $database->rollback(); } catch (Exception $ignored) {}
                throw $e;
            }
        }

        // Demais tipos (receita/despesa) OU transferência inválida (sem conta destino distinta)
        $tipo_final = ($tipo === 'transferencia') ? 'despesa' : $tipo;
        return adicionarTransacao(
            $tipo_final,
            $descricao,
            $valor,
            $categoria_id,
            $data,
            $conta_origem_id,
            $dados['observacoes'] ?? '',
            $usuario_id
        );
    }
}

/**
 * Calcula totais do mês (receitas, despesas, saldo)
 */
function calcularTotaisMes($usuario_id, $mes_ano) {
    $partes = explode('-', $mes_ano);
    $ano = intval($partes[0]);
    $mes = intval($partes[1]);
    
    $receitas = calcularTotalReceitas($mes, $ano, $usuario_id);
    $despesas = calcularTotalDespesas($mes, $ano, $usuario_id);
    $saldo = $receitas - $despesas;
    
    return [
        'receitas' => $receitas,
        'despesas' => $despesas,
        'saldo' => $saldo
    ];
}

/**
 * API para salvar transação (POST) - deve vir antes da API GET
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['api']) && $_GET['api'] == 'transacoes') {
    header('Content-Type: application/json');
    
    $acao = $_GET['acao'] ?? '';
    // Usar o usuário logado; se não houver, não usar usuário padrão
    $usuario_id = usuarioLogado() ? obterUsuarioId() : 0;
    
    // Debug: log dos dados recebidos
    error_log("POST API transacoes - Acao: " . $acao);
    $input = file_get_contents('php://input');
    error_log("POST API transacoes - Input: " . $input);
    
    switch ($acao) {
        case 'salvar':
            $dados = json_decode($input, true);
            error_log("POST API transacoes - Dados decodificados: " . print_r($dados, true));
            
            if (!$dados) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados JSON inválidos']);
                exit;
            }
            
            try {
                $resultado = salvarTransacao($dados, $usuario_id);
                echo json_encode(['sucesso' => $resultado !== false, 'resultado' => $resultado]);
            } catch (Exception $e) {
                error_log("Erro ao salvar transação: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['erro' => $e->getMessage()]);
            }
            break;
            
        case 'excluir':
            $dados = json_decode($input, true);
            if (!$dados) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados JSON inválidos']);
                exit;
            }
            
            try {
                $resultado = excluirTransacao($dados['id'], $usuario_id);
                echo json_encode(['sucesso' => $resultado]);
            } catch (Exception $e) {
                error_log("Erro ao excluir transação: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['erro' => $e->getMessage()]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Ação não reconhecida: ' . $acao]);
    }
    
    exit;
}

/**
 * API para obter transações (GET)
 */
if (isset($_GET['api']) && $_GET['api'] == 'transacoes') {
    header('Content-Type: application/json');
    
    // Verificar se o usuário está logado; sem sessão não há dados
    $usuario_id = usuarioLogado() ? obterUsuarioId() : 0;
    
    $acao = $_GET['acao'] ?? '';
    
    switch ($acao) {
        case 'listar':
            $transacoes = obterTransacoes($usuario_id);
            echo json_encode($transacoes);
            break;
            
        case 'obter_por_mes':
            $mes_ano = $_GET['mes'] ?? date('Y-m');
            $partes = explode('-', $mes_ano);
            $ano = intval($partes[0]);
            $mes = intval($partes[1]);
            $transacoes = obterTransacoesPorMes($mes, $ano, $usuario_id);
            echo json_encode($transacoes);
            break;
            
        case 'saldo_total':
            $saldo = calcularSaldoTotal($usuario_id);
            echo json_encode(['saldo' => $saldo]);
            break;
            
        case 'totais_mes':
            $mes = $_GET['mes'] ?? date('Y-m');
            $totais = calcularTotaisMes($usuario_id, $mes);
            echo json_encode($totais);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Ação não reconhecida']);
    }
    
    exit;
}

/**
 * API para salvar categorias (POST)
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_GET['api']) && $_GET['api'] == 'categorias') {
    require_once 'categorias.php';
    header('Content-Type: application/json');
    
    $acao = $_GET['acao'] ?? '';
    $usuario_id = usuarioLogado() ? obterUsuarioId() : 0;
    
    switch ($acao) {
        case 'salvar':
            $dados = json_decode(file_get_contents('php://input'), true);
            
            if (!$dados) {
                http_response_code(400);
                echo json_encode(['erro' => 'Dados JSON inválidos']);
                exit;
            }
            
            try {
                $nome = $dados['nome'] ?? '';
                $tipo = $dados['tipo'] ?? '';
                $icone = $dados['icone'] ?? 'fas fa-tag';
                $cor = $dados['cor'] ?? '#666666';
                $id_categoria = isset($dados['id']) ? intval($dados['id']) : 0;

                if (empty($nome) || empty($tipo)) {
                    http_response_code(400);
                    echo json_encode(['erro' => 'Nome e tipo são obrigatórios']);
                    exit;
                }

                if ($id_categoria > 0) {
                    // Atualizar categoria existente
                    $atualizado = editarCategoria($id_categoria, [
                        'nome' => $nome,
                        'tipo' => $tipo,
                        'icone' => $icone,
                        'cor' => $cor
                    ]);
                    echo json_encode(['sucesso' => $atualizado !== false, 'id' => $id_categoria]);
                } else {
                    // Verificar duplicidade antes de inserir
                    try {
                        $verificacao = $database->select(
                            "SELECT COUNT(*) AS total FROM categorias WHERE usuario_id = ? AND nome = ? AND tipo = ? AND ativa = 1",
                            [$usuario_id, $nome, $tipo]
                        );
                        $jaExiste = intval($verificacao[0]['total'] ?? 0) > 0;
                        if ($jaExiste) {
                            http_response_code(409);
                            echo json_encode(['erro' => 'Categoria já existe para este usuário e tipo']);
                            exit;
                        }
                    } catch (Exception $e) {
                        // Se der erro na verificação, seguimos com o insert e tratamos no catch externo
                    }

                    $resultado = adicionarCategoria($nome, $tipo, $icone, $cor, $usuario_id);
                    echo json_encode(['sucesso' => $resultado !== false, 'id' => $resultado]);
                }
            } catch (Exception $e) {
                error_log("Erro ao salvar categoria: " . $e->getMessage());
                // Detectar violação de chave única
                $mensagem = strpos($e->getMessage(), 'SQLSTATE[23000]') !== false
                    ? 'Categoria já existe para este usuário e tipo'
                    : $e->getMessage();
                http_response_code(strpos($e->getMessage(), 'SQLSTATE[23000]') !== false ? 409 : 500);
                echo json_encode(['erro' => $mensagem]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Ação não reconhecida: ' . $acao]);
    }
    
    exit;
}

/**
 * API para obter categorias (GET)
 */
if (isset($_GET['api']) && $_GET['api'] == 'categorias') {
    require_once 'categorias.php';
    header('Content-Type: application/json');
    
    // Verificar se o usuário está logado; sem sessão não há dados
    $usuario_id = usuarioLogado() ? obterUsuarioId() : 0;
    
    $acao = $_GET['acao'] ?? '';
    
    switch ($acao) {
        case 'listar':
            // Permitir filtro por tipo via query string
            $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
            if ($tipo) {
                echo json_encode(obterCategorias($tipo, $usuario_id));
            } else {
                echo json_encode(obterCategoriasPorUsuario($usuario_id));
            }
            break;
            
        case 'obter_por_id':
            $id = $_GET['id'] ?? 0;
            echo json_encode(obterCategoriaPorId($id));
            break;
    }
    
    exit;
}



// Função para compatibilidade com código antigo
function lerTransacoes() {
    // Usar usuário da sessão quando disponível; se não houver, não carregar dados
    $usuario_id = usuarioLogado() ? obterUsuarioId() : 0;

    if ($usuario_id > 0) {
        $transacoes = obterTransacoes($usuario_id);
        $resumo = calcularResumo($usuario_id);
    } else {
        // Sem usuário logado: retornar listas vazias e totais zerados
        $transacoes = [];
        $resumo = [
            'total_receitas' => 0,
            'total_despesas' => 0,
            'saldo_atual' => 0,
            'ultima_atualizacao' => date('Y-m-d H:i:s')
        ];
    }

    return [
        'transacoes' => $transacoes,
        'proximo_id' => count($transacoes) + 1,
        'resumo' => $resumo
    ];
}
?>
