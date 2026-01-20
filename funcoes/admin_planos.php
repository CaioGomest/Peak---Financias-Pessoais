<?php
require_once __DIR__ . '/../config/database.php';

if (!function_exists('colunaExiste')) {
function colunaExiste($tabela, $coluna) {
    global $database;
    $sql = "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?";
    $res = $database->select($sql, [$tabela, $coluna]);
    return !empty($res);
}
}

if (!function_exists('listarPlanos')) {
function listarPlanos() {
    global $database;
    $temDuracao = colunaExiste('planos', 'duracao_meses');
    if ($temDuracao) {
        $sql = "SELECT id, nome, descricao, preco, duracao_meses, stripe_price_id, stripe_product_id, ativo FROM planos WHERE ativo = 1 ORDER BY id";
    } else {
        $sql = "SELECT id, nome, descricao, preco, 1 AS duracao_meses, stripe_price_id, stripe_product_id, ativo FROM planos WHERE ativo = 1 ORDER BY id";
    }
    return $database->select($sql);
}
}

if (!function_exists('atualizarPriceId')) {
function atualizarPriceId($id, $priceId) {
    global $database;
    $sql = "UPDATE planos SET stripe_price_id = ?, atualizado_em = NOW() WHERE id = ?";
    return $database->update($sql, [$priceId, $id]) > 0;
}
}

if (!function_exists('atualizarBasicoPlano')) {
function atualizarBasicoPlano($id, $preco, $duracaoMeses) {
    global $database;
    $temDuracao = colunaExiste('planos', 'duracao_meses');
    if ($temDuracao) {
        $sql = "UPDATE planos SET preco = ?, duracao_meses = ?, atualizado_em = NOW() WHERE id = ?";
        return $database->update($sql, [$preco, $duracaoMeses, $id]) > 0;
    } else {
        $sql = "UPDATE planos SET preco = ?, atualizado_em = NOW() WHERE id = ?";
        return $database->update($sql, [$preco, $id]) > 0;
    }
}
}

if (!function_exists('inativarPlano')) {
function inativarPlano($id) {
    global $database;
    $sql = "UPDATE planos SET ativo = 0, atualizado_em = NOW() WHERE id = ?";
    return $database->update($sql, [$id]) > 0;
}
}

if (isset($_GET['api']) && $_GET['api'] === 'admin_planos') {
    header('Content-Type: application/json');
    $acao = $_GET['acao'] ?? '';
    switch ($acao) {
        case 'listar':
            echo json_encode(listarPlanos());
            break;
        case 'atualizar_price':
            $id = (int)($_POST['id'] ?? 0);
            $priceId = $_POST['stripe_price_id'] ?? '';
            if (!$id || !$priceId) {
                http_response_code(400);
                echo json_encode(['erro' => 'dados_invalidos']);
                break;
            }
            echo json_encode(['sucesso' => atualizarPriceId($id, $priceId)]);
            break;
        case 'atualizar_basico':
            $id = (int)($_POST['id'] ?? 0);
            $preco = isset($_POST['preco']) ? floatval($_POST['preco']) : null;
            $duracao = (int)($_POST['duracao_meses'] ?? 0);
            if (!$id || $preco === null || $duracao <= 0) {
                http_response_code(400);
                echo json_encode(['erro' => 'dados_invalidos']);
                break;
            }
            echo json_encode(['sucesso' => atualizarBasicoPlano($id, $preco, $duracao)]);
            break;
        case 'inativar':
            $id = (int)($_POST['id'] ?? 0);
            echo json_encode(['sucesso' => $id ? inativarPlano($id) : false]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'acao_invalida']);
    }
    exit;
}

?>
