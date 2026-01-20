<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

if (!function_exists('aplicarMigracoes')) {
function aplicarMigracoes() {
    global $database;
    $resultado = [];

    try {
        $database->beginTransaction();

        $colPerfil = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'perfil'");
        if (empty($colPerfil)) {
            $database->query("ALTER TABLE usuarios ADD COLUMN perfil ENUM('admin','usuario') DEFAULT 'usuario' AFTER email_verificado");
            $resultado[] = 'usuarios.perfil';
        }

        $colStripePrice = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'planos' AND column_name = 'stripe_price_id'");
        if (empty($colStripePrice)) {
            $database->query("ALTER TABLE planos ADD COLUMN stripe_price_id VARCHAR(100) NULL AFTER preco");
            $resultado[] = 'planos.stripe_price_id';
        }

        $colStripeProduct = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'planos' AND column_name = 'stripe_product_id'");
        if (empty($colStripeProduct)) {
            $database->query("ALTER TABLE planos ADD COLUMN stripe_product_id VARCHAR(100) NULL AFTER stripe_price_id");
            $resultado[] = 'planos.stripe_product_id';
        }

        $colDuracao = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'planos' AND column_name = 'duracao_meses'");
        if (empty($colDuracao)) {
            $database->query("ALTER TABLE planos ADD COLUMN duracao_meses INT NOT NULL DEFAULT 1 AFTER preco");
            $resultado[] = 'planos.duracao_meses';
        }

        $colCustomerId = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'assinaturas' AND column_name = 'customer_id'");
        if (empty($colCustomerId)) {
            $database->query("ALTER TABLE assinaturas ADD COLUMN customer_id VARCHAR(100) NULL AFTER gateway_transacao_id");
            $resultado[] = 'assinaturas.customer_id';
        }

        $tblConfigSistema = $database->select("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'configuracoes_sistema'");
        if (empty($tblConfigSistema)) {
            $database->query("CREATE TABLE configuracoes_sistema (chave VARCHAR(100) PRIMARY KEY, valor TEXT NOT NULL, atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");
            $resultado[] = 'configuracoes_sistema';
        }

        $admins = $database->select("SELECT COUNT(*) AS total FROM usuarios WHERE perfil = 'admin'");
        $totalAdmins = !empty($admins) ? (int)$admins[0]['total'] : 0;
        if ($totalAdmins === 0) {
            $existeUsuario1 = $database->select("SELECT 1 FROM usuarios WHERE id = 1");
            if (!empty($existeUsuario1)) {
                $database->query("UPDATE usuarios SET perfil = 'admin' WHERE id = 1");
                $resultado[] = 'admin_inicial_id_1';
            }
        }

        $database->commit();
        return ['sucesso' => true, 'alteracoes' => $resultado];
    } catch (Exception $e) {
        $database->rollback();
        return ['sucesso' => false, 'erro' => $e->getMessage(), 'alteracoes' => $resultado];
    }
}
}

if (!function_exists('preverMigracoes')) {
function preverMigracoes() {
    global $database;
    $resp = [];
    try {
        $colPerfil = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'perfil'");
        $resp[] = ['nome' => 'usuarios.perfil', 'status' => empty($colPerfil) ? 'pendente' : 'ok'];

        $colStripePrice = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'planos' AND column_name = 'stripe_price_id'");
        $resp[] = ['nome' => 'planos.stripe_price_id', 'status' => empty($colStripePrice) ? 'pendente' : 'ok'];

        $colStripeProduct = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'planos' AND column_name = 'stripe_product_id'");
        $resp[] = ['nome' => 'planos.stripe_product_id', 'status' => empty($colStripeProduct) ? 'pendente' : 'ok'];

        $colCustomerId = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'assinaturas' AND column_name = 'customer_id'");
        $resp[] = ['nome' => 'assinaturas.customer_id', 'status' => empty($colCustomerId) ? 'pendente' : 'ok'];

        $tblConfigSistema = $database->select("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'configuracoes_sistema'");
        $resp[] = ['nome' => 'tabela.configuracoes_sistema', 'status' => empty($tblConfigSistema) ? 'pendente' : 'ok'];

        $admins = $database->select("SELECT COUNT(*) AS total FROM usuarios WHERE perfil = 'admin'");
        $totalAdmins = !empty($admins) ? (int)$admins[0]['total'] : 0;
        $resp[] = ['nome' => 'admin.existente', 'status' => $totalAdmins > 0 ? 'ok' : 'pendente'];
        return ['sucesso' => true, 'itens' => $resp];
    } catch (Exception $e) {
        return ['sucesso' => false, 'erro' => $e->getMessage()];
    }
}
}

if (isset($_GET['api']) && $_GET['api'] === 'admin_migracoes') {
    header('Content-Type: application/json');
    $acao = $_GET['acao'] ?? '';
    if ($acao === 'aplicar') {
        echo json_encode(aplicarMigracoes());
        exit;
    }
    if ($acao === 'prever') {
        echo json_encode(preverMigracoes());
        exit;
    }
    echo json_encode(['sucesso' => false, 'erro' => 'acao_invalida']);
    exit;
}

?>
        // Usuarios: garantir telefone, cpf e atualizado_em
        $colTel = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'telefone'");
        if (empty($colTel)) {
            $database->query("ALTER TABLE usuarios ADD COLUMN telefone VARCHAR(20) NULL AFTER email");
            $resultado[] = 'usuarios.telefone';
        }
        $colCpf = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'cpf'");
        if (empty($colCpf)) {
            $database->query("ALTER TABLE usuarios ADD COLUMN cpf VARCHAR(14) NULL AFTER telefone");
            $resultado[] = 'usuarios.cpf';
        }
        $colAtualizadoEm = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'atualizado_em'");
        if (empty($colAtualizadoEm)) {
            $database->query("ALTER TABLE usuarios ADD COLUMN atualizado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER cpf");
            $resultado[] = 'usuarios.atualizado_em';
        }
        $colTel = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'telefone'");
        $resp[] = ['nome' => 'usuarios.telefone', 'status' => empty($colTel) ? 'pendente' : 'ok'];
        $colCpf = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'cpf'");
        $resp[] = ['nome' => 'usuarios.cpf', 'status' => empty($colCpf) ? 'pendente' : 'ok'];
        $colAtualizadoEm = $database->select("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'atualizado_em'");
        $resp[] = ['nome' => 'usuarios.atualizado_em', 'status' => empty($colAtualizadoEm) ? 'pendente' : 'ok'];
