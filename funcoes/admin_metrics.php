<?php
require_once __DIR__ . '/../config/database.php';

if (!function_exists('resumoMetrics')) {
function resumoMetrics($inicio, $fim) {
    global $database;
    $inicio = $inicio ?: date('Y-m-01');
    $fim = $fim ?: date('Y-m-t');

    $receita = $database->select(
        "SELECT COALESCE(SUM(valor_pago),0) AS total FROM assinaturas WHERE atualizado_em BETWEEN ? AND ?",
        [$inicio . ' 00:00:00', $fim . ' 23:59:59']
    );

    $cancelamentos = $database->select(
        "SELECT COUNT(*) AS total FROM assinaturas WHERE status = 'cancelada' AND data_fim BETWEEN ? AND ?",
        [$inicio, $fim]
    );

    $novas = $database->select(
        "SELECT COUNT(*) AS total FROM assinaturas WHERE data_inicio BETWEEN ? AND ?",
        [$inicio, $fim]
    );

    $ativas = $database->select(
        "SELECT COUNT(*) AS total FROM assinaturas WHERE status = 'ativa'",
        []
    );

    $usuariosNovos = $database->select(
        "SELECT COUNT(*) AS total FROM usuarios WHERE data_cadastro BETWEEN ? AND ?",
        [$inicio . ' 00:00:00', $fim . ' 23:59:59']
    );

    $usuariosInativados = $database->select(
        "SELECT COUNT(*) AS total FROM usuarios WHERE status = 'inativo' AND atualizado_em BETWEEN ? AND ?",
        [$inicio . ' 00:00:00', $fim . ' 23:59:59']
    );

    $cfgRows = $database->select("SELECT chave, valor FROM configuracoes_sistema WHERE chave IN ('gateway_padrao','stripe_api_key')");
    $cfg = [];
    foreach ($cfgRows as $r) { $cfg[$r['chave']] = $r['valor']; }
    $gwNome = isset($cfg['gateway_padrao']) ? strtoupper($cfg['gateway_padrao']) : '—';
    $gwCon = !empty($cfg['stripe_api_key']);
    
    $planosTot = $database->select("SELECT COUNT(*) AS total FROM planos", []);
    $planosAtivos = $database->select("SELECT COUNT(*) AS total FROM planos WHERE ativo = 1", []);

    return [
        'sucesso' => true,
        'inicio' => $inicio,
        'fim' => $fim,
        'receita_total' => (float)$receita[0]['total'],
        'cancelamentos' => (int)$cancelamentos[0]['total'],
        'novas_assinaturas' => (int)$novas[0]['total'],
        'assinaturas_ativas' => (int)$ativas[0]['total'],
        'usuarios_novos' => (int)$usuariosNovos[0]['total'],
        'usuarios_inativados' => (int)$usuariosInativados[0]['total'],
        'gateway_nome' => $gwNome,
        'gateway_conectado' => $gwCon,
        'planos_total' => (int)$planosTot[0]['total'],
        'planos_ativos' => (int)$planosAtivos[0]['total'],
    ];
}
}

if (isset($_GET['api']) && $_GET['api'] === 'admin_metrics') {
    header('Content-Type: application/json');
    $acao = $_GET['acao'] ?? '';
    switch ($acao) {
        case 'resumo':
            $inicio = $_GET['inicio'] ?? null;
            $fim = $_GET['fim'] ?? null;
            echo json_encode(resumoMetrics($inicio, $fim));
            break;
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'acao_invalida']);
    }
    exit;
}

?>
<?php
