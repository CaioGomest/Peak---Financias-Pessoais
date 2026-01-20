<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/usuario.php';
require_once __DIR__ . '/categorias.php';
require_once __DIR__ . '/admin_gateway.php';

function obterConfigIA() {
    $cfg = obterConfig();
    return [
        'api_key' => $cfg['openai_api_key'] ?? '',
        'auto' => isset($cfg['ai_auto_categorizar']) ? (bool)$cfg['ai_auto_categorizar'] : false
    ];
}

function heuristicaCategoria($descricao, $valor, $tipo) {
    $d = mb_strtolower($descricao ?? '');
    $palavras = [
        'mercado' => ['supermercado','mercado','carrefour','extra','pao de acucar','atacad'],
        'alimentação' => ['restaurante','lanch','pizza','ifood','uber eats','bar','café'],
        'transporte' => ['uber','99','metro','onibus','gasolina','ipva','estacion'],
        'moradia' => ['aluguel','condominio','energia','agua','gás','internet','vivo','claro','oi'],
        'saúde' => ['farmacia','drog','consulta','exame','plano de saude'],
        'salário' => ['salario','pagamento','folha','pro labore'],
        'investimentos' => ['rend','cdi','tesouro','bolsa','dividend']
    ];
    foreach ($palavras as $nome => $lista) {
        foreach ($lista as $kw) {
            if (strpos($d, $kw) !== false) {
                return ['nome' => $nome, 'tipo' => $tipo ?: (($valor<0)?'despesa':'receita'), 'criar' => true];
            }
        }
    }
    return ['nome' => ($tipo==='receita'?'outros (receitas)':'outros (despesas)'), 'tipo' => $tipo ?: (($valor<0)?'despesa':'receita'), 'criar' => true];
}

function categorizarComOpenAI($itens, $usuarioId) {
    $cfg = obterConfigIA();
    $key = $cfg['api_key'];
    if (!$key) {
        $sugestoes = [];
        foreach ($itens as $i) {
            $sugestoes[] = heuristicaCategoria($i['descricao'] ?? '', floatval($i['valor'] ?? 0), $i['tipo'] ?? null);
        }
        return ['sugestoes' => $sugestoes, 'origem' => 'heuristica'];
    }
    $existentes = obterCategoriasPorUsuario($usuarioId);
    $nomesExistentes = array_map(function($c){ return $c['nome'].'|'.$c['tipo']; }, $existentes);
    $prompt = "Você é um classificador financeiro. Para cada item, sugira uma categoria e tipo ('receita' ou 'despesa'). ".
              "Se nenhuma categoria existente combinar, proponha um nome simples. Responda em JSON puro: ".
              "[{\"nome\":\"...\",\"tipo\":\"receita|despesa\"}, ...]. Categorias existentes: ".implode(', ', $nomesExistentes).".";
    $messages = [
        ['role' => 'system', 'content' => 'Você classifica transações financeiras em categorias claras e consistentes.'],
        ['role' => 'user', 'content' => $prompt."\nItens:\n".json_encode($itens, JSON_UNESCAPED_UNICODE)]
    ];
    $payload = json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => $messages,
        'temperature' => 0.2
    ]);
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer '.$key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = ($resp === false) ? curl_error($ch) : null;
    curl_close($ch);
    $data = json_decode($resp, true);
    if ($http !== 200 || !isset($data['choices'][0]['message']['content'])) {
        $sugestoes = [];
        foreach ($itens as $i) {
            $sugestoes[] = heuristicaCategoria($i['descricao'] ?? '', floatval($i['valor'] ?? 0), $i['tipo'] ?? null);
        }
        return ['sugestoes' => $sugestoes, 'origem' => 'heuristica', 'erro_openai' => $err ?: ($data['error']['message'] ?? null), 'http' => $http];
    }
    $content = $data['choices'][0]['message']['content'];
    $parsed = json_decode($content, true);
    if (!is_array($parsed)) {
        $sugestoes = [];
        foreach ($itens as $i) {
            $sugestoes[] = heuristicaCategoria($i['descricao'] ?? '', floatval($i['valor'] ?? 0), $i['tipo'] ?? null);
        }
        return ['sugestoes' => $sugestoes, 'origem' => 'heuristica', 'erro_openai' => 'parse_fail'];
    }
    return ['sugestoes' => $parsed, 'origem' => 'openai'];
}

function materializarCategorias($sugestoes, $usuarioId) {
    $result = [];
    foreach ($sugestoes as $s) {
        $nome = trim($s['nome'] ?? '');
        $tipo = ($s['tipo'] === 'receita') ? 'receita' : 'despesa';
        if ($nome === '') $nome = $tipo === 'receita' ? 'outros (receitas)' : 'outros (despesas)';
        $existentes = obterCategorias($tipo, $usuarioId);
        $id = null;
        foreach ($existentes as $c) {
            if (mb_strtolower($c['nome']) === mb_strtolower($nome)) { $id = $c['id']; break; }
        }
        if (!$id) {
            $id = adicionarCategoria($nome, $tipo, 'fas fa-tag', $tipo==='receita'?'#4CAF50':'#F44336', $usuarioId);
        }
        $result[] = ['id' => $id, 'nome' => $nome, 'tipo' => $tipo];
    }
    return $result;
}

if (isset($_GET['api']) && $_GET['api'] === 'ia') {
    header('Content-Type: application/json');
    $acao = $_GET['acao'] ?? '';
    if ($acao === 'config') {
        echo json_encode(obterConfigIA());
        exit;
    }
    if ($acao === 'categorizar') {
        $body = file_get_contents('php://input');
        $json = json_decode($body, true);
        if (!is_array($json)) { http_response_code(400); echo json_encode(['erro'=>'body_invalido']); exit; }
        $usuarioId = usuarioLogado() ? obterUsuarioId() : 1;
        $resp = categorizarComOpenAI($json['itens'] ?? [], $usuarioId);
        $material = materializarCategorias($resp['sugestoes'] ?? [], $usuarioId);
        echo json_encode(['categorias' => $material, 'origem' => $resp['origem'] ?? '']);
        exit;
    }
    http_response_code(400);
    echo json_encode(['erro' => 'acao_invalida']);
    exit;
}
?>
