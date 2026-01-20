<?php
require_once __DIR__ . '/../config/database.php';

if (!function_exists('obterConfig')) {
function obterConfig() {
    global $database;
    $rows = $database->select("SELECT chave, valor FROM configuracoes_sistema");
    $cfg = [];
    foreach ($rows as $r) { $cfg[$r['chave']] = $r['valor']; }
    return $cfg;
}
}

if (!function_exists('salvarConfig')) {
function salvarConfig($dados) {
    global $database;
    foreach ($dados as $k => $v) {
        if ($v === null || $v === '') continue;
        $exist = $database->select("SELECT 1 FROM configuracoes_sistema WHERE chave = ?", [$k]);
        if (empty($exist)) {
            $database->insert("INSERT INTO configuracoes_sistema (chave, valor) VALUES (?, ?)", [$k, $v]);
        } else {
            $database->update("UPDATE configuracoes_sistema SET valor = ?, atualizado_em = NOW() WHERE chave = ?", [$v, $k]);
        }
    }
    return true;
}
}

if (!function_exists('testarGateway')) {
function testarGateway() {
    $cfg = obterConfig();
    $key = $cfg['stripe_api_key'] ?? '';
    $padrao = $cfg['gateway_padrao'] ?? 'stripe';
    if ($padrao !== 'stripe' || !$key) return ['conectado' => false, 'motivo' => 'nao_configurado'];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/account');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $key]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = ($resp === false) ? curl_error($ch) : null;
    curl_close($ch);
    $data = json_decode($resp, true);
    if ($http === 200 && isset($data['id'])) return ['conectado' => true, 'conta' => $data['id']];
    $msg = isset($data['error']['message']) ? $data['error']['message'] : $err;
    return ['conectado' => false, 'http' => $http ?: 0, 'motivo' => $msg];
}
}

if (isset($_GET['api']) && $_GET['api'] === 'admin_gateway') {
    header('Content-Type: application/json');
    $acao = $_GET['acao'] ?? '';
    switch ($acao) {
        case 'obter':
            echo json_encode(obterConfig());
            break;
        case 'salvar':
            $dados = [
                'gateway_padrao' => $_POST['gateway_padrao'] ?? 'stripe',
                'stripe_api_key' => $_POST['stripe_api_key'] ?? '',
                'stripe_webhook_secret' => $_POST['stripe_webhook_secret'] ?? ''
            ];
            echo json_encode(['sucesso' => salvarConfig($dados)]);
            break;
        case 'testar':
            echo json_encode(testarGateway());
            break;
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'acao_invalida']);
    }
    exit;
}

?>
