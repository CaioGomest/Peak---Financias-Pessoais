<?php
require_once __DIR__ . '/../funcoes/usuario.php';
require_once __DIR__ . '/../config/database.php';
verificarLogin();
global $database;
$planos = $database->select("SELECT id, nome, descricao, preco FROM planos WHERE ativo = 1 ORDER BY id");
$usuarioId = obterUsuarioId();
$assin = $database->select("SELECT a.id, a.status, a.plano_id, a.metodo_pagamento, a.gateway_transacao_id, p.nome FROM assinaturas a LEFT JOIN planos p ON p.id=a.plano_id WHERE a.usuario_id = ? ORDER BY a.id DESC LIMIT 1", [$usuarioId]);
$cfgRows = $database->select("SELECT chave, valor FROM configuracoes_sistema WHERE chave IN ('gateway_padrao','stripe_api_key')");
$cfg = [];
foreach ($cfgRows as $r) { $cfg[$r['chave']] = $r['valor']; }
$gatewayPadrao = $cfg['gateway_padrao'] ?? 'stripe';
$status = $_GET['status'] ?? '';
?>
<?php
// Fallback quando aberto fora do index
$script_atual = basename($_SERVER['SCRIPT_NAME']);
$esta_no_index = ($script_atual === 'index.php');
$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$uri_sem_query = strtok($uri, '?');
$base_app = $uri_sem_query;
if (substr($base_app, -10) === '/index.php') { $base_app = substr($base_app, 0, -10); }
$pos_paginas = strpos($base_app, '/paginas/');
if ($pos_paginas !== false) { $base_app = substr($base_app, 0, $pos_paginas); }
if (substr($base_app, -1) !== '/') { $base_app .= '/'; }
?>
<?php if (!$esta_no_index): ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_app; ?>assets/css/style.css">
</head>
<body>
    <script>
    (function(){
        try{var escuro=localStorage.getItem('temaEscuro');if(escuro==='true'){document.body.classList.add('tema-escuro');}else{document.body.classList.add('tema-claro');}}
        catch(e){document.body.classList.add('tema-escuro');}
    })();
    </script>
<?php endif; ?>
<?php if (!$esta_no_index) echo '<div class="conteudo">'; ?>
<div class="container">
    <div class="dashboard-header">
        <div class="dashboard-background"></div>
        <div class="header-content">
            <div class="header-info">
                <h1><i class="fas fa-receipt"></i> Assinatura</h1>
                <p>Gerencie seu plano e pagamentos</p>
            </div>
        </div>
    </div>
    <?php if ($status === 'sucesso') { echo '<div class="card">Pagamento confirmado.</div>'; } elseif ($status === 'cancelado') { echo '<div class="card">Pagamento cancelado.</div>'; } ?>
    <div class="card">
        <div><strong>Sua assinatura</strong></div>
        <?php if (!empty($assin)): ?>
            <div class="form-linha">
                <span>Plano: <?php echo htmlspecialchars($assin[0]['nome'] ?? ''); ?></span>
                <span>Status: <?php echo htmlspecialchars($assin[0]['status'] ?? ''); ?></span>
                <span>Gateway: <?php echo htmlspecialchars($assin[0]['metodo_pagamento'] ?? $gatewayPadrao); ?></span>
            </div>
        <?php else: ?>
            <div class="estado-vazio" style="margin:0;">
                <div class="icone-vazio"><i class="fas fa-receipt"></i></div>
                <h3>Nenhuma assinatura ativa</h3>
                <p>Escolha um plano abaixo para começar</p>
            </div>
        <?php endif; ?>
        <div style="margin-top:10px;">Gateway selecionado: <strong><?php echo htmlspecialchars($gatewayPadrao); ?></strong></div>
    </div>
    <div>
        <?php foreach ($planos as $p): ?>
            <?php 
                $nomeMin = strtolower($p['nome']);
                $classeIcone = 'plano-icone';
                if (strpos($nomeMin, 'grat') !== false) { $classeIcone .= ' plano-gratuito'; $fa = 'fas fa-seedling'; }
                elseif (strpos($nomeMin, 'prem') !== false) { $classeIcone .= ' plano-premium'; $fa = 'fas fa-crown'; }
                elseif (strpos($nomeMin, 'empre') !== false) { $classeIcone .= ' plano-empresarial'; $fa = 'fas fa-building'; }
                else { $fa = 'fas fa-receipt'; }
            ?>
            <div class="card">
                <div class="form-linha" style="justify-content:space-between; align-items:center;">
                    <div style="display:flex; gap:12px; align-items:center;">
                        <div class="<?php echo $classeIcone; ?>"><i class="<?php echo $fa; ?>"></i></div>
                        <strong><?php echo htmlspecialchars($p['nome']); ?></strong> - R$ <?php echo number_format($p['preco'],2,',','.'); ?>
                        <div class="titulo-secao"><?php echo htmlspecialchars($p['descricao']); ?></div>
                    </div>
                    <div>
                <?php if ((float)$p['preco'] == 0): ?>
                    <button class="botao botao-primario" onclick="assinarGratuito(<?php echo (int)$p['id']; ?>)"><i class="fas fa-check"></i> Ativar</button>
                <?php elseif ($gatewayPadrao === 'stripe' && (float)$p['preco'] > 0): ?>
                    <button class="botao botao-primario" onclick="assinar(<?php echo (int)$p['id']; ?>)"><i class="fas fa-check"></i> Assinar</button>
                <?php else: ?>
                    <span class="badge status-error">Gateway não configurado</span>
                <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php if (!$esta_no_index) echo '</div>'; ?>
<?php if (!$esta_no_index): ?>
    <div class="menu-overlay" id="menu-overlay"></div>
    <a href="#" class="botao-adicionar-central" onclick="toggleMenuCircular()"><i class="fas fa-plus"></i></a>
    <div class="menu-circular" id="menu-circular">
        <a href="#" class="opcao-menu receita" onclick="abrirModalTransacao('receita')"><i class="fas fa-arrow-up"></i><span>Receita</span></a>
        <a href="#" class="opcao-menu despesa" onclick="abrirModalTransacao('despesa')"><i class="fas fa-arrow-down"></i><span>Despesa</span></a>
        <a href="#" class="opcao-menu transferencia" onclick="abrirModalTransacao('transferencia')"><i class="fas fa-exchange-alt"></i><span>Transferência</span></a>
    </div>
    <script>
    function toggleMenuCircular(){var m=document.getElementById('menu-circular');var o=document.getElementById('menu-overlay');if(!m||!o)return;m.classList.toggle('ativo');o.classList.toggle('ativo');}
    </script>
<?php endif; ?>
<script>
function obterUrl(caminho) {
    var caminhoNormalizado = (caminho || '').replace(/^\//, '');
    var path = window.location.pathname || '';
    var base = path;
    if (base.endsWith('/index.php')) base = base.replace('/index.php', '');
    if (base.includes('/paginas/')) base = base.split('/paginas/')[0];
    if (!base.endsWith('/')) base += '/';
    return base + caminhoNormalizado;
}
function assinar(planoId){
    fetch(obterUrl('funcoes/stripe.php?api=stripe&acao=criar_checkout&plano_id='+planoId))
        .then(function(r){ if(!r.ok) throw new Error('Falha na solicitação'); return r.json(); })
        .then(function(d){ 
            if(d.url){ 
                window.location.href = d.url; 
            } else { 
                var msg = d.mensagem ? ('\nMotivo: '+d.mensagem) : '';
                alert('Não foi possível iniciar o checkout'+msg);
            } 
        })
        .catch(function(e){ alert('Erro ao iniciar checkout. Verifique o gateway.'); console.error(e); });
}
function assinarGratuito(planoId){
    fetch(obterUrl('funcoes/admin_assinaturas.php?api=admin_assinaturas&acao=criar_gratuita'), {
        method: 'POST',
        body: new URLSearchParams({ plano_id: String(planoId) })
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d && d.sucesso) {
            alert('Assinatura gratuita ativada');
            window.location.reload();
        } else {
            alert('Não foi possível ativar: ' + (d.erro || 'erro'));
        }
    })
    .catch(function(){ alert('Erro de rede ao ativar assinatura gratuita'); });
}
</script>
<?php if (!$esta_no_index): ?>
</body>
<?php endif; ?>
