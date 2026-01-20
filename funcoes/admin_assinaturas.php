<?php
require_once __DIR__ . '/../config/database.php';

function cfg() {
    global $database;
    $rows = $database->select("SELECT chave, valor FROM configuracoes_sistema");
    $m = [];
    foreach ($rows as $r) { $m[$r['chave']] = $r['valor']; }
    return $m;
}

if (!function_exists('listarAssinaturas')) {
function listarAssinaturas() {
    global $database;
    $sql = "SELECT id, usuario_id, plano_id, status, gateway_transacao_id FROM assinaturas ORDER BY id DESC";
    return $database->select($sql);
}
}

if (!function_exists('revogarAssinatura')) {
function revogarAssinatura($id) {
    global $database;
    $ass = $database->select("SELECT id, gateway_transacao_id FROM assinaturas WHERE id = ?", [$id]);
    if (empty($ass)) return false;
    $subscriptionId = $ass[0]['gateway_transacao_id'];
    $c = cfg();
    if (!empty($subscriptionId) && ($c['gateway_padrao'] ?? 'stripe') === 'stripe' && !empty($c['stripe_api_key'])) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/subscriptions/" . urlencode($subscriptionId));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $c['stripe_api_key']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch);
        curl_close($ch);
    }
    $database->update("UPDATE assinaturas SET status = 'cancelada', atualizado_em = NOW() WHERE id = ?", [$id]);
    return true;
}
}

if (isset($_GET['api']) && $_GET['api'] === 'admin_assinaturas') {
    header('Content-Type: application/json');
    $acao = $_GET['acao'] ?? '';
    switch ($acao) {
        case 'listar':
            echo json_encode(listarAssinaturas());
            break;
        case 'revogar':
            $id = (int)($_POST['id'] ?? 0);
            echo json_encode(['sucesso' => $id ? revogarAssinatura($id) : false]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'acao_invalida']);
    }
    exit;
}

?>
