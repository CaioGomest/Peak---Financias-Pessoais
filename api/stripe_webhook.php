<?php
require_once __DIR__ . '/../config/database.php';

function cfgStripe() {
    global $database;
    $rows = $database->select("SELECT chave, valor FROM configuracoes_sistema WHERE chave IN ('stripe_webhook_secret')");
    $m = [];
    foreach ($rows as $r) { $m[$r['chave']] = $r['valor']; }
    return $m;
}

function verificarAssinatura($payload, $sigHeader, $secret) {
    if (empty($sigHeader) || empty($secret)) return false;
    $parts = [];
    foreach (explode(',', $sigHeader) as $p) {
        $kv = explode('=', trim($p), 2);
        if (count($kv) === 2) $parts[$kv[0]] = $kv[1];
    }
    if (!isset($parts['t']) || !isset($parts['v1'])) return false;
    $signed = $parts['t'] . '.' . $payload;
    $calc = hash_hmac('sha256', $signed, $secret);
    return hash_equals($calc, $parts['v1']);
}

$payload = file_get_contents('php://input');
$sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$cfg = cfgStripe();
if (!verificarAssinatura($payload, $sig, $cfg['stripe_webhook_secret'] ?? '')) {
    http_response_code(400);
    echo 'invalid signature';
    exit;
}

$event = json_decode($payload, true);
if (!$event) { http_response_code(400); echo 'invalid payload'; exit; }

global $database;
switch ($event['type'] ?? '') {
    case 'checkout.session.completed':
        $obj = $event['data']['object'] ?? [];
        $ref = $obj['client_reference_id'] ?? null;
        $sub = $obj['subscription'] ?? null;
        $cust = $obj['customer'] ?? null;
        if ($ref) {
            $database->update("UPDATE assinaturas SET status='ativa', gateway_transacao_id = ?, customer_id = ?, atualizado_em = NOW() WHERE id = ?", [$sub, $cust, $ref]);
        }
        break;
    case 'invoice.payment_succeeded':
        $obj = $event['data']['object'] ?? [];
        $sub = $obj['subscription'] ?? null;
        $amount = isset($obj['amount_paid']) ? ($obj['amount_paid']/100.0) : null;
        if ($sub && $amount !== null) {
            $database->update("UPDATE assinaturas SET valor_pago = ?, atualizado_em = NOW() WHERE gateway_transacao_id = ?", [$amount, $sub]);
        }
        break;
    case 'customer.subscription.deleted':
        $obj = $event['data']['object'] ?? [];
        $sub = $obj['id'] ?? null;
        if ($sub) {
            $database->update("UPDATE assinaturas SET status='cancelada', data_fim = CURDATE(), atualizado_em = NOW() WHERE gateway_transacao_id = ?", [$sub]);
        }
        break;
}

echo json_encode(['received' => true]);
?>
