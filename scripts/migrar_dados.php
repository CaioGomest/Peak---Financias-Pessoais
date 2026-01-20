<?php
/**
 * Script de Migração de Dados JSON para Banco de Dados
 * 
 * Este script migra os dados dos arquivos JSON existentes para o banco de dados MySQL.
 * Execute este script apenas uma vez após configurar o banco de dados.
 */

require_once __DIR__ . '/../config/database.php';

echo "=== SCRIPT DE MIGRAÇÃO DE DADOS ===\n";
echo "Iniciando migração dos dados JSON para o banco de dados...\n\n";

// Função para ler arquivo JSON
function lerArquivoJSON($caminho) {
    if (!file_exists($caminho)) {
        echo "Arquivo não encontrado: $caminho\n";
        return null;
    }
    
    $conteudo = file_get_contents($caminho);
    $dados = json_decode($conteudo, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Erro ao decodificar JSON: " . json_last_error_msg() . "\n";
        return null;
    }
    
    return $dados;
}

// Função para migrar categorias
function migrarCategorias($database) {
    echo "1. Migrando categorias...\n";
    
    $dados = lerArquivoJSON(__DIR__ . '/../dados/categorias.json');
    if (!$dados || !isset($dados['categorias'])) {
        echo "   Nenhuma categoria encontrada para migrar.\n";
        return;
    }
    
    $contador = 0;
    foreach ($dados['categorias'] as $categoria) {
        $sql = "INSERT INTO categorias (nome, tipo, icone, cor, ativa, criado_em) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    nome = VALUES(nome),
                    tipo = VALUES(tipo),
                    icone = VALUES(icone),
                    cor = VALUES(cor),
                    ativa = VALUES(ativa)";
        
        $params = [
            $categoria['nome'],
            $categoria['tipo'],
            $categoria['icone'] ?? 'fas fa-tag',
            $categoria['cor'] ?? '#666666',
            $categoria['ativa'] ? 1 : 0,
            $categoria['criada_em'] ?? date('Y-m-d H:i:s')
        ];
        
        if ($database->insert($sql, $params)) {
            $contador++;
        }
    }
    
    echo "   $contador categorias migradas com sucesso.\n\n";
}

// Função para migrar configurações
function migrarConfiguracoes($database) {
    echo "2. Migrando configurações...\n";
    
    $dados = lerArquivoJSON(__DIR__ . '/../dados/configuracoes.json');
    if (!$dados) {
        echo "   Nenhuma configuração encontrada para migrar.\n";
        return;
    }
    
    // Migrar usuário
    if (isset($dados['usuario'])) {
        $usuario = $dados['usuario'];
        $sql = "INSERT INTO usuarios (nome, email, foto_perfil, criado_em) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    nome = VALUES(nome),
                    email = VALUES(email),
                    foto_perfil = VALUES(foto_perfil)";
        
        $params = [
            $usuario['nome'] ?? 'Usuário',
            $usuario['email'] ?? 'usuario@exemplo.com',
            $usuario['foto_perfil'] ?? '',
            $usuario['data_cadastro'] ?? date('Y-m-d H:i:s')
        ];
        
        $usuario_id = $database->insert($sql, $params);
        if (!$usuario_id) {
            $usuario_id = 1; // Assume ID 1 se já existe
        }
        
        echo "   Usuário migrado (ID: $usuario_id).\n";
    }
    
    // Migrar contas
    if (isset($dados['contas'])) {
        $contador_contas = 0;
        foreach ($dados['contas'] as $conta) {
            $sql = "INSERT INTO contas (usuario_id, nome, tipo, banco, saldo_inicial, saldo_atual, cor, ativa, criado_em) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        nome = VALUES(nome),
                        tipo = VALUES(tipo),
                        banco = VALUES(banco),
                        cor = VALUES(cor),
                        ativa = VALUES(ativa)";
            
            $params = [
                1, // usuario_id
                $conta['nome'],
                $conta['tipo'],
                $conta['banco'] ?? '',
                floatval($conta['saldo_inicial'] ?? 0),
                floatval($conta['saldo_inicial'] ?? 0),
                $conta['cor'] ?? '#2196F3',
                $conta['ativa'] ? 1 : 0,
                date('Y-m-d H:i:s')
            ];
            
            if ($database->insert($sql, $params)) {
                $contador_contas++;
            }
        }
        echo "   $contador_contas contas migradas.\n";
    }
    
    // Migrar configurações do usuário
    if (isset($dados['preferencias'])) {
        $prefs = $dados['preferencias'];
        
        $sql = "INSERT INTO configuracoes_usuario (usuario_id, moeda, simbolo_moeda, formato_data, tema, mostrar_saldo, notificacoes_email, notificacoes_push, lembretes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    moeda = VALUES(moeda),
                    simbolo_moeda = VALUES(simbolo_moeda),
                    formato_data = VALUES(formato_data),
                    tema = VALUES(tema),
                    mostrar_saldo = VALUES(mostrar_saldo),
                    notificacoes_email = VALUES(notificacoes_email),
                    notificacoes_push = VALUES(notificacoes_push),
                    lembretes = VALUES(lembretes)";
        
        $params = [
            1, // usuario_id
            $prefs['moeda'] ?? 'BRL',
            $prefs['simbolo_moeda'] ?? 'R$',
            $prefs['formato_data'] ?? 'd/m/Y',
            $prefs['tema'] ?? 'escuro',
            isset($prefs['mostrar_saldo']) ? ($prefs['mostrar_saldo'] ? 1 : 0) : 1,
            isset($prefs['notificacoes_email']) ? ($prefs['notificacoes_email'] ? 1 : 0) : 1,
            isset($prefs['notificacoes_push']) ? ($prefs['notificacoes_push'] ? 1 : 0) : 0,
            isset($prefs['lembretes']) ? ($prefs['lembretes'] ? 1 : 0) : 1
        ];
        
        if ($database->insert($sql, $params)) {
            echo "   Configurações do usuário migradas.\n";
        }
    }
    
    echo "\n";
}

// Função para migrar transações
function migrarTransacoes($database) {
    echo "3. Migrando transações...\n";
    
    $dados = lerArquivoJSON(__DIR__ . '/../dados/transacoes.json');
    if (!$dados || !isset($dados['transacoes'])) {
        echo "   Nenhuma transação encontrada para migrar.\n";
        return;
    }
    
    $contador = 0;
    foreach ($dados['transacoes'] as $transacao) {
        // Mapear conta_origem_id para conta_id (compatibilidade)
        $conta_id = $transacao['conta_id'] ?? $transacao['conta_origem_id'] ?? 1;
        
        $sql = "INSERT INTO transacoes (usuario_id, conta_id, categoria_id, tipo, valor, descricao, data_transacao, criado_em) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    valor = VALUES(valor),
                    descricao = VALUES(descricao),
                    data_transacao = VALUES(data_transacao)";
        
        $params = [
            1, // usuario_id
            intval($conta_id),
            intval($transacao['categoria_id']),
            $transacao['tipo'],
            floatval($transacao['valor']),
            $transacao['descricao'] ?? '',
            $transacao['data'] ?? $transacao['data_transacao'] ?? date('Y-m-d'),
            $transacao['criada_em'] ?? date('Y-m-d H:i:s')
        ];
        
        if ($database->insert($sql, $params)) {
            $contador++;
        }
    }
    
    echo "   $contador transações migradas com sucesso.\n\n";
}

// Função para verificar dados migrados
function verificarMigracao($database) {
    echo "4. Verificando dados migrados...\n";
    
    // Contar registros
    $categorias = $database->select("SELECT COUNT(*) as total FROM categorias")[0]['total'];
    $usuarios = $database->select("SELECT COUNT(*) as total FROM usuarios")[0]['total'];
    $contas = $database->select("SELECT COUNT(*) as total FROM contas")[0]['total'];
    $transacoes = $database->select("SELECT COUNT(*) as total FROM transacoes")[0]['total'];
    $configuracoes = $database->select("SELECT COUNT(*) as total FROM configuracoes_usuario")[0]['total'];
    
    echo "   Categorias: $categorias\n";
    echo "   Usuários: $usuarios\n";
    echo "   Contas: $contas\n";
    echo "   Transações: $transacoes\n";
    echo "   Configurações: $configuracoes\n\n";
    
    // Verificar saldos das contas
    echo "   Saldos das contas:\n";
    $contas_saldos = $database->select("SELECT nome, saldo_atual FROM contas WHERE ativa = 1");
    foreach ($contas_saldos as $conta) {
        echo "   - {$conta['nome']}: R$ " . number_format($conta['saldo_atual'], 2, ',', '.') . "\n";
    }
    echo "\n";
}

// Função principal
function executarMigracao() {
    global $database;
    
    try {
        // Verificar conexão com o banco
        $database->select("SELECT 1");
        echo "Conexão com o banco de dados estabelecida.\n\n";
        
        // Executar migrações
        migrarCategorias($database);
        migrarConfiguracoes($database);
        migrarTransacoes($database);
        verificarMigracao($database);
        
        echo "=== MIGRAÇÃO CONCLUÍDA COM SUCESSO ===\n";
        echo "Todos os dados foram migrados para o banco de dados.\n";
        echo "Você pode agora usar o sistema com o banco de dados.\n\n";
        
        echo "IMPORTANTE:\n";
        echo "- Faça backup dos arquivos JSON antes de removê-los\n";
        echo "- Teste o sistema para garantir que tudo funciona corretamente\n";
        echo "- Os arquivos JSON podem ser removidos após a confirmação\n";
        
    } catch (Exception $e) {
        echo "ERRO durante a migração: " . $e->getMessage() . "\n";
        echo "Verifique a configuração do banco de dados e tente novamente.\n";
        return false;
    }
    
    return true;
}

// Executar migração se o script for chamado diretamente
if (php_sapi_name() === 'cli') {
    executarMigracao();
} else {
    echo "Este script deve ser executado via linha de comando.\n";
    echo "Execute: php scripts/migrar_dados.php\n";
}
?>