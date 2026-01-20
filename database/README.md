# 📊 Estrutura do Banco de Dados - Sistema de Gestão Financeira

## 🎯 Visão Geral

Este documento descreve a arquitetura completa do banco de dados MySQL para o sistema de gestão financeira pessoal, incluindo as funcionalidades atuais e futuras de autenticação e assinatura.

## 🏗️ Arquitetura do Banco

### 📋 Tabelas Principais

#### 1. **Autenticação e Usuários**
- `planos` - Planos de assinatura disponíveis
- `usuarios` - Dados dos usuários do sistema
- `assinaturas` - Controle de assinaturas ativas
- `tokens_auth` - Tokens para autenticação e recuperação

#### 2. **Sistema Financeiro**
- `contas` - Contas bancárias e carteiras do usuário
- `categorias` - Categorias de receitas e despesas
- `transacoes` - Todas as transações financeiras

#### 3. **Configurações e Recursos Avançados**
- `configuracoes_usuario` - Preferências pessoais
- `orcamentos` - Orçamentos por categoria

---

## 📊 Detalhamento das Tabelas

### 🔐 **PLANOS**
```sql
planos (
    id, nome, descricao, preco, 
    limite_transacoes, limite_categorias, 
    recursos, ativo, criado_em, atualizado_em
)
```
**Propósito:** Define os diferentes planos de assinatura (Gratuito, Premium, Empresarial)

**Recursos por Plano:**
- **Gratuito:** 100 transações, 10 categorias, relatórios básicos
- **Premium:** Ilimitado, relatórios avançados, metas, orçamentos
- **Empresarial:** Multi-usuários, API, suporte dedicado

### 👤 **USUARIOS**
```sql
usuarios (
    id, nome, email, senha_hash, foto_perfil,
    plano_id, status, email_verificado,
    data_cadastro, ultimo_acesso, criado_em, atualizado_em
)
```
**Propósito:** Armazena dados dos usuários e controla acesso ao sistema

**Status Possíveis:** `ativo`, `inativo`, `suspenso`

### 💳 **ASSINATURAS**
```sql
assinaturas (
    id, usuario_id, plano_id, status,
    data_inicio, data_fim, valor_pago,
    metodo_pagamento, gateway_transacao_id,
    criado_em, atualizado_em
)
```
**Propósito:** Controla o histórico e status das assinaturas

### 🔑 **TOKENS_AUTH**
```sql
tokens_auth (
    id, usuario_id, token, tipo,
    expira_em, usado, criado_em
)
```
**Tipos de Token:** `login`, `reset_senha`, `verificacao_email`

### 🏦 **CONTAS**
```sql
contas (
    id, usuario_id, nome, tipo, banco,
    saldo_inicial, saldo_atual, cor, ativa,
    criado_em, atualizado_em
)
```
**Tipos de Conta:** `corrente`, `poupanca`, `cartao_credito`, `cartao_debito`, `dinheiro`, `investimento`

### 🏷️ **CATEGORIAS**
```sql
categorias (
    id, usuario_id, nome, tipo, icone, cor, ativa,
    criado_em, atualizado_em
)
```
**Tipos:** `receita`, `despesa`

### 💰 **TRANSACOES**
```sql
transacoes (
    id, usuario_id, tipo, descricao, valor,
    categoria_id, conta_origem_id, conta_destino_id,
    data_transacao, observacoes, anexos,
    recorrente, recorrencia_config, transacao_pai_id,
    criado_em, atualizado_em
)
```
**Tipos:** `receita`, `despesa`, `transferencia`

**Recursos Especiais:**
- Registra todas as movimentações financeiras
- Suporta receitas e despesas
- Transferências são registradas como entrada e saída separadas
- Vinculação com conta e categoria específicas

### ⚙️ **CONFIGURACOES_USUARIO**
```sql
configuracoes_usuario (
    id, usuario_id, moeda, simbolo_moeda,
    formato_data, tema, mostrar_saldo,
    notificacoes_email, notificacoes_push, lembretes,
    criado_em, atualizado_em
)
```

### 📊 **ORCAMENTOS**
```sql
orcamentos (
    id, usuario_id, categoria_id, valor_limite,
    periodo, mes, ano, ativo,
    criado_em, atualizado_em
)
```
**Períodos:** `mensal`, `anual`

---

## 🔗 Relacionamentos

### **Relacionamentos Principais:**
1. `usuarios` ← `planos` (N:1)
2. `usuarios` → `assinaturas` (1:N)
3. `usuarios` → `contas` (1:N)
4. `usuarios` → `categorias` (1:N)
5. `usuarios` → `transacoes` (1:N)
6. `categorias` ← `transacoes` (N:1)
7. `contas` ← `transacoes` (N:1)

### **Relacionamentos de Configuração:**
- `usuarios` → `configuracoes_usuario` (1:1)
- `usuarios` → `metas` (1:N)
- `usuarios` → `orcamentos` (1:N)

---

## ⚡ Performance e Otimização

### **Índices Criados:**
- `idx_transacoes_usuario_data` - Consultas por usuário e período
- `idx_transacoes_categoria` - Relatórios por categoria
- `idx_transacoes_conta_origem` - Consultas por conta
- `idx_categorias_usuario_tipo` - Listagem de categorias
- `idx_tokens_expiracao` - Limpeza de tokens expirados

### **Triggers Automáticos:**
- **Atualização de Saldo:** Triggers que mantêm o `saldo_atual` das contas sempre atualizado
- **Suporte a Transferências:** Debita origem e credita destino automaticamente

---

## 🚀 Funcionalidades Implementadas

### ✅ **Atuais (Baseadas no Sistema Existente):**
- Gestão completa de transações
- Categorização de receitas e despesas
- Múltiplas contas/carteiras
- Configurações personalizadas
- Transferências como registros separados

### 🔮 **Futuras (Preparadas na Estrutura):**
- Sistema de login e autenticação
- Planos de assinatura (Gratuito/Premium/Empresarial)
- Orçamentos por categoria

---

## 🛡️ Segurança

### **Medidas Implementadas:**
- Senhas com hash seguro
- Tokens com expiração
- Soft delete para dados críticos
- Validação de integridade referencial
- Isolamento de dados por usuário

### **Controle de Acesso:**
- Todos os dados são isolados por `usuario_id`
- Verificação de limites por plano
- Status de usuário controlado

---

## 📈 Escalabilidade

### **Preparado Para:**
- Multi-tenancy (isolamento por usuário)
- Grandes volumes de transações
- Diferentes moedas e formatos
- Expansão de funcionalidades
- Integração com gateways de pagamento

### **Limites por Plano:**
- **Gratuito:** 100 transações, 10 categorias
- **Premium/Empresarial:** Ilimitado

---

## 🔧 Instalação

1. Execute o script `schema.sql` no MySQL
2. O banco será criado com dados iniciais dos planos
3. Triggers serão configurados automaticamente
4. Índices otimizados serão criados

```bash
mysql -u root -p < database/schema.sql
```

---

## 📝 Notas de Migração

Para migrar os dados atuais (JSON) para o banco:
1. Criar usuário padrão
2. Importar categorias existentes
3. Importar transações com referências corretas
4. Configurar contas baseadas nos dados atuais

O sistema foi projetado para manter compatibilidade com a estrutura atual enquanto prepara para as funcionalidades futuras.