-- =====================================================
-- DADOS DE EXEMPLO PARA O SISTEMA DE GESTÃO FINANCEIRA
-- Baseado nos dados atuais do sistema JSON
-- =====================================================

USE gestao_financeira;

-- =====================================================
-- USUÁRIO DE EXEMPLO
-- =====================================================

-- Inserir usuário padrão (senha: 123456)
INSERT INTO usuarios (nome, email, senha_hash, plano_id, email_verificado) VALUES
('Usuário Exemplo', 'usuario@exemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, TRUE);

SET @usuario_id = LAST_INSERT_ID();

-- =====================================================
-- CONFIGURAÇÕES DO USUÁRIO
-- =====================================================

INSERT INTO configuracoes_usuario (usuario_id, moeda, simbolo_moeda, formato_data, tema, mostrar_saldo) VALUES
(@usuario_id, 'BRL', 'R$', 'd/m/Y', 'escuro', TRUE);

-- =====================================================
-- CONTAS BANCÁRIAS
-- =====================================================

INSERT INTO contas (usuario_id, nome, tipo, banco, saldo_inicial, saldo_atual, cor) VALUES
(@usuario_id, 'Conta Corrente', 'corrente', 'Banco Principal', 0.00, 0.00, '#2196F3'),
(@usuario_id, 'Conta Poupança', 'poupanca', 'Banco Principal', 0.00, 0.00, '#4CAF50'),
(@usuario_id, 'Cartão de Crédito', 'cartao_credito', 'Banco Principal', 0.00, 0.00, '#FF5722'),
(@usuario_id, 'Dinheiro', 'dinheiro', NULL, 0.00, 0.00, '#795548');

-- Obter IDs das contas
SET @conta_corrente = (SELECT id FROM contas WHERE usuario_id = @usuario_id AND nome = 'Conta Corrente');
SET @conta_poupanca = (SELECT id FROM contas WHERE usuario_id = @usuario_id AND nome = 'Conta Poupança');
SET @cartao_credito = (SELECT id FROM contas WHERE usuario_id = @usuario_id AND nome = 'Cartão de Crédito');
SET @dinheiro = (SELECT id FROM contas WHERE usuario_id = @usuario_id AND nome = 'Dinheiro');

-- =====================================================
-- CATEGORIAS DE RECEITAS
-- =====================================================

INSERT INTO categorias (usuario_id, nome, tipo, icone, cor) VALUES
(@usuario_id, 'Salário', 'receita', 'fas fa-money-bill-wave', '#4CAF50'),
(@usuario_id, 'Freelance', 'receita', 'fas fa-laptop-code', '#2196F3'),
(@usuario_id, 'Investimentos', 'receita', 'fas fa-chart-line', '#FF9800'),
(@usuario_id, 'Vendas', 'receita', 'fas fa-shopping-cart', '#9C27B0'),
(@usuario_id, 'Outros', 'receita', 'fas fa-plus-circle', '#607D8B');

-- =====================================================
-- CATEGORIAS DE DESPESAS
-- =====================================================

INSERT INTO categorias (usuario_id, nome, tipo, icone, cor) VALUES
(@usuario_id, 'Alimentação', 'despesa', 'fas fa-utensils', '#F44336'),
(@usuario_id, 'Moradia', 'despesa', 'fas fa-home', '#E91E63'),
(@usuario_id, 'Transporte', 'despesa', 'fas fa-car', '#673AB7'),
(@usuario_id, 'Saúde', 'despesa', 'fas fa-heartbeat', '#3F51B5'),
(@usuario_id, 'Educação', 'despesa', 'fas fa-graduation-cap', '#009688'),
(@usuario_id, 'Lazer', 'despesa', 'fas fa-gamepad', '#FF5722'),
(@usuario_id, 'Compras', 'despesa', 'fas fa-shopping-bag', '#795548'),
(@usuario_id, 'Serviços', 'despesa', 'fas fa-tools', '#607D8B'),
(@usuario_id, 'Outros', 'despesa', 'fas fa-minus-circle', '#9E9E9E');

-- Obter IDs das categorias
SET @cat_salario = (SELECT id FROM categorias WHERE usuario_id = @usuario_id AND nome = 'Salário');
SET @cat_freelance = (SELECT id FROM categorias WHERE usuario_id = @usuario_id AND nome = 'Freelance');
SET @cat_alimentacao = (SELECT id FROM categorias WHERE usuario_id = @usuario_id AND nome = 'Alimentação');
SET @cat_moradia = (SELECT id FROM categorias WHERE usuario_id = @usuario_id AND nome = 'Moradia');
SET @cat_transporte = (SELECT id FROM categorias WHERE usuario_id = @usuario_id AND nome = 'Transporte');
SET @cat_saude = (SELECT id FROM categorias WHERE usuario_id = @usuario_id AND nome = 'Saúde');
SET @cat_lazer = (SELECT id FROM categorias WHERE usuario_id = @usuario_id AND nome = 'Lazer');

-- =====================================================
-- TRANSAÇÕES DE EXEMPLO (BASEADAS NOS DADOS ATUAIS)
-- =====================================================

-- Receitas
INSERT INTO transacoes (usuario_id, tipo, descricao, valor, categoria_id, conta_id, data_transacao, observacoes) VALUES
(@usuario_id, 'receita', 'Salário', 5000.00, @cat_salario, @conta_corrente, '2024-01-15', 'Salário mensal'),
(@usuario_id, 'receita', 'Freelance', 1200.00, @cat_freelance, @conta_poupanca, '2024-01-18', 'Projeto web'),
(@usuario_id, 'receita', 'Salário', 5000.00, @cat_salario, @conta_corrente, '2024-02-15', 'Salário mensal'),
(@usuario_id, 'receita', 'Freelance', 800.00, @cat_freelance, @conta_corrente, '2024-02-20', 'Consultoria'),
(@usuario_id, 'receita', 'Freelance', 1500.00, @cat_freelance, @conta_poupanca, '2024-03-05', 'Desenvolvimento app');

-- Despesas
INSERT INTO transacoes (usuario_id, tipo, descricao, valor, categoria_id, conta_id, data_transacao, observacoes) VALUES
(@usuario_id, 'despesa', 'Supermercado', 350.50, @cat_alimentacao, @cartao_credito, '2024-01-16', 'Compras da semana'),
(@usuario_id, 'despesa', 'Aluguel', 1500.00, @cat_moradia, @conta_corrente, '2024-01-20', 'Aluguel mensal'),
(@usuario_id, 'despesa', 'Gasolina', 200.00, @cat_transporte, @cartao_credito, '2024-01-22', 'Abastecimento'),
(@usuario_id, 'despesa', 'Restaurante', 120.00, @cat_alimentacao, @cartao_credito, '2024-01-25', 'Jantar em família'),
(@usuario_id, 'despesa', 'Farmácia', 85.30, @cat_saude, @dinheiro, '2024-01-28', 'Medicamentos'),
(@usuario_id, 'despesa', 'Cinema', 60.00, @cat_lazer, @cartao_credito, '2024-01-30', 'Filme com amigos'),
(@usuario_id, 'despesa', 'Supermercado', 420.80, @cat_alimentacao, @cartao_credito, '2024-02-02', 'Compras mensais'),
(@usuario_id, 'despesa', 'Aluguel', 1500.00, @cat_moradia, @conta_corrente, '2024-02-20', 'Aluguel mensal'),
(@usuario_id, 'despesa', 'Uber', 45.50, @cat_transporte, @cartao_credito, '2024-02-22', 'Corridas do mês'),
(@usuario_id, 'despesa', 'Streaming', 29.90, @cat_lazer, @cartao_credito, '2024-02-25', 'Netflix'),
(@usuario_id, 'despesa', 'Supermercado', 380.20, @cat_alimentacao, @cartao_credito, '2024-03-01', 'Compras da semana'),
(@usuario_id, 'despesa', 'Gasolina', 180.00, @cat_transporte, @cartao_credito, '2024-03-03', 'Abastecimento'),
(@usuario_id, 'despesa', 'Aluguel', 1500.00, @cat_moradia, @conta_corrente, '2024-03-20', 'Aluguel mensal');

-- =====================================================
-- ORÇAMENTOS MENSAIS
-- =====================================================

INSERT INTO orcamentos (usuario_id, categoria_id, valor_limite, periodo, mes, ano) VALUES
(@usuario_id, @cat_alimentacao, 800.00, 'mensal', 3, 2024),
(@usuario_id, @cat_transporte, 400.00, 'mensal', 3, 2024),
(@usuario_id, @cat_lazer, 300.00, 'mensal', 3, 2024),
(@usuario_id, @cat_saude, 200.00, 'mensal', 3, 2024);

-- =====================================================
-- VERIFICAÇÃO DOS DADOS INSERIDOS
-- =====================================================

-- Mostrar resumo dos dados inseridos
SELECT 'USUÁRIO CRIADO' as info, nome, email FROM usuarios WHERE id = @usuario_id;

SELECT 'CONTAS CRIADAS' as info, COUNT(*) as total FROM contas WHERE usuario_id = @usuario_id;

SELECT 'CATEGORIAS CRIADAS' as info, tipo, COUNT(*) as total 
FROM categorias WHERE usuario_id = @usuario_id GROUP BY tipo;

SELECT 'TRANSAÇÕES CRIADAS' as info, tipo, COUNT(*) as total, SUM(valor) as total_valor
FROM transacoes WHERE usuario_id = @usuario_id GROUP BY tipo;

SELECT 'ORÇAMENTOS CRIADOS' as info, COUNT(*) as total FROM orcamentos WHERE usuario_id = @usuario_id;

-- Mostrar saldos atualizados das contas (pelos triggers)
SELECT 'SALDOS DAS CONTAS' as info, nome, saldo_atual FROM contas WHERE usuario_id = @usuario_id;