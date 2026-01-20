<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/usuario.php';

function obterConfigStripe() {
    global $database;
    $rows = $database->select("SELECT chave, valor FROM configuracoes_sistema WHERE chave IN ('gateway_padrao','stripe_api_key','stripe_webhook_secret')");
    $cfg = [];
    foreach ($rows as $r) { $cfg[$r['chave']] = $r['valor']; }
    return $cfg;
}

function criarCheckoutAssinatura($planoId) {
    global $database;
    if (!usuarioLogado()) return ['erro' => 'nao_autenticado'];
    $usuarioId = obterUsuarioId();
    $u = $database->select("SELECT id, email FROM usuarios WHERE id = ?", [$usuarioId]);
    $p = $database->select("SELECT id, nome, descricao, preco, duracao_meses, stripe_price_id, stripe_product_id FROM planos WHERE id = ?", [$planoId]);
    if (empty($u) || empty($p)) return ['erro' => 'dados_invalidos'];

    $assinaturaId = $database->insert("INSERT INTO assinaturas (usuario_id, plano_id, status, data_inicio, metodo_pagamento) VALUES (?, ?, 'pendente', CURDATE(), 'stripe')", [$usuarioId, $planoId]);

    $cfg = obterConfigStripe();
    if (($cfg['gateway_padrao'] ?? 'stripe') !== 'stripe' || empty($cfg['stripe_api_key'])) return ['erro' => 'gateway_nao_configurado'];
    $key = $cfg['stripe_api_key'];
    if (strpos($key, 'sk_test_') !== 0 && strpos($key, 'sk_live_') !== 0) {
        return ['erro' => 'chave_invalida', 'mensagem' => 'Use a chave secreta (sk_...), não a pública (pk_...)'];
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $base = dirname($scriptDir);
    if ($base === '\\' || $base === '/') { $base = ''; }
    $success = $scheme . '://' . $host . $base . "/paginas/assinatura.php?status=sucesso";
    $cancel = $scheme . '://' . $host . $base . "/paginas/assinatura.php?status=cancelado";

    // Criar Product/Price dinamicamente quando faltar stripe_price_id
    if (empty($p[0]['stripe_price_id'])) {
        if ((float)$p[0]['preco'] <= 0) {
            return ['erro' => 'plano_gratuito_indisponivel'];
        }
        $prodFields = [
            'name' => $p[0]['nome'] ?? ('Plano ' . $p[0]['id']),
            'description' => $p[0]['descricao'] ?? ''
        ];
        $ch1 = curl_init();
        curl_setopt($ch1, CURLOPT_URL, 'https://api.stripe.com/v1/products');
        curl_setopt($ch1, CURLOPT_POST, true);
        curl_setopt($ch1, CURLOPT_POSTFIELDS, http_build_query($prodFields));
        curl_setopt($ch1, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $key,
            "Content-Type: application/x-www-form-urlencoded"
        ]);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch1, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch1, CURLOPT_CONNECTTIMEOUT, 10);
        $resp1 = curl_exec($ch1);
        curl_close($ch1);
        $data1 = json_decode($resp1, true);
        if (!isset($data1['id'])) {
            $msg = isset($data1['error']['message']) ? $data1['error']['message'] : null;
            return ['erro' => 'falha_criar_produto', 'mensagem' => $msg];
        }
        $productId = $data1['id'];
        $unit = (int)round(((float)$p[0]['preco']) * 100);
        $intervalCount = max(1, (int)$p[0]['duracao_meses']);
        $priceFields = [
            'product' => $productId,
            'unit_amount' => $unit,
            'currency' => 'brl',
            'recurring[interval]' => 'month',
            'recurring[interval_count]' => $intervalCount
        ];
        $ch2 = curl_init();
        curl_setopt($ch2, CURLOPT_URL, 'https://api.stripe.com/v1/prices');
        curl_setopt($ch2, CURLOPT_POST, true);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query($priceFields));
        curl_setopt($ch2, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $key,
            "Content-Type: application/x-www-form-urlencoded"
        ]);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch2, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 10);
        $resp2 = curl_exec($ch2);
        curl_close($ch2);
        $data2 = json_decode($resp2, true);
        if (!isset($data2['id'])) {
            $msg = isset($data2['error']['message']) ? $data2['error']['message'] : null;
            return ['erro' => 'falha_criar_price', 'mensagem' => $msg];
        }
        $priceId = $data2['id'];
        $database->update("UPDATE planos SET stripe_product_id = ?, stripe_price_id = ?, atualizado_em = NOW() WHERE id = ?", [$productId, $priceId, $planoId]);
        $p[0]['stripe_price_id'] = $priceId;
    }

    $fields = [
        'mode' => 'subscription',
        'line_items[0][price]' => $p[0]['stripe_price_id'],
        'line_items[0][quantity]' => 1,
        'success_url' => $success,
        'cancel_url' => $cancel,
        'customer_email' => $u[0]['email'],
        'client_reference_id' => $assinaturaId
    ];

    $encoded = http_build_query($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $key,
        "Content-Type: application/x-www-form-urlencoded"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = ($resp === false) ? curl_error($ch) : null;
    curl_close($ch);
    $data = json_decode($resp, true);
    if (isset($data['url'])) {
        return ['url' => $data['url'], 'assinatura_id' => $assinaturaId];
    }
    $msg = isset($data['error']['message']) ? $data['error']['message'] : ($curlErr ?: null);
    // Fallback com file_get_contents se cURL falhou sem resposta
    if (!$data && $resp === false) {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer " . $key . "\r\nContent-Type: application/x-www-form-urlencoded",
                'content' => $encoded,
                'timeout' => 20
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ]);
        $resp2 = @file_get_contents('https://api.stripe.com/v1/checkout/sessions', false, $context);
        $data2 = json_decode($resp2, true);
        if (isset($data2['url'])) {
            return ['url' => $data2['url'], 'assinatura_id' => $assinaturaId];
        }
        $msg = isset($data2['error']['message']) ? $data2['error']['message'] : $msg;
    }
    return ['erro' => 'falha_checkout', 'mensagem' => $msg, 'http' => $httpCode ?: 0];
}

if (isset($_GET['api']) && $_GET['api'] === 'stripe') {
    header('Content-Type: application/json');
    $acao = $_GET['acao'] ?? '';
    switch ($acao) {
        case 'criar_checkout':
            $planoId = (int)($_GET['plano_id'] ?? 0);
            echo json_encode(criarCheckoutAssinatura($planoId));
            break;
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'acao_invalida']);
    }
    exit;
}

?>
