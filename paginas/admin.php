<?php
require_once __DIR__ . '/../funcoes/usuario.php';
verificarLogin();
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
    http_response_code(403);
    echo '<div class="container"><h2>Acesso negado</h2></div>';
    exit;
}
?>
<div class="container admin-container">
    <div class="dashboard-header">
        <div class="dashboard-background"></div>
        <div class="header-content">
            <div class="header-info">
                <h1><i class="fas fa-tools"></i> Painel Admin</h1>
                <p>Gerencie usuários, planos, assinaturas e integrações</p>
            </div>
        </div>
    </div>

    <div class="metrics-header">
        <select id="periodo_select" class="form-select">
            <option value="month" selected>Este mês</option>
            <option value="7d">7 dias</option>
            <option value="30d">30 dias</option>
            <option value="90d">90 dias</option>
            <option value="custom">Personalizado</option>
        </select>
        <div id="periodo_custom" class="form-linha" style="display:none">
            <input type="date" class="form-input" id="periodo_inicio">
            <input type="date" class="form-input" id="periodo_fim">
            <button class="botao botao-primario" onclick="aplicarPeriodoCustom()">Aplicar</button>
        </div>
    </div>
    <div id="metrics-container" class="admin-metrics resumo-transacoes"></div>

    <div class="admin-toolbar">
        <div class="filtros-lista" id="tabs-admin">
            <button class="filtro-btn" data-aba="usuarios" onclick="mostrarAba('usuarios')">Usuários</button>
            <button class="filtro-btn" data-aba="planos" onclick="mostrarAba('planos')">Planos</button>
            <button class="filtro-btn" data-aba="assinaturas" onclick="mostrarAba('assinaturas')">Assinaturas</button>
            <button class="filtro-btn" data-aba="gateway" onclick="mostrarAba('gateway')">Gateway</button>
            <button class="filtro-btn" data-aba="ia" onclick="mostrarAba('ia')">IA</button>
            <button class="filtro-btn" data-aba="migracoes" onclick="mostrarAba('migracoes')">Migrações</button>
        </div>
    </div>

    <div id="aba-usuarios" class="aba">
        <div class="card">
            <h3 class="titulo-secao">Usuários</h3>
            <form id="form-criar-usuario" class="form-grid">
                <input class="form-input" type="text" name="nome" placeholder="Nome" required>
                <input class="form-input" type="email" name="email" placeholder="Email" required>
                <input class="form-input" type="password" name="senha" placeholder="Senha" required>
                <select class="form-select" name="perfil">
                    <option value="usuario">Usuário</option>
                    <option value="admin">Admin</option>
                </select>
                <button class="botao botao-primario" type="submit">Criar</button>
            </form>
            <div id="lista-usuarios" class="lista-tabela"></div>
        </div>
    </div>

    <div id="aba-planos" class="aba" style="display:none">
        <div class="card">
            <h3 class="titulo-secao">Planos</h3>
            <div id="lista-planos" class="lista-tabela"></div>
        </div>
    </div>

    <div id="aba-assinaturas" class="aba" style="display:none">
        <div class="card">
            <h3 class="titulo-secao">Assinaturas</h3>
            <div id="lista-assinaturas" class="lista-tabela"></div>
        </div>
    </div>

    <div id="aba-gateway" class="aba" style="display:none">
        <div class="card">
            <h3 class="titulo-secao">Gateway</h3>
            <form id="form-gateway" class="form-linha">
                <select class="form-select" name="gateway_padrao">
                    <option value="stripe">Stripe</option>
                </select>
                <input class="form-input" type="text" name="stripe_api_key" placeholder="Chave secreta (ex: sk_test_...)">
                <input class="form-input" type="text" name="stripe_webhook_secret" placeholder="Webhook secret (ex: whsec_...)">
                <button class="botao botao-primario" type="submit">Salvar</button>
            </form>
            <div id="status-gateway"></div>
            <div style="margin-top:8px">
                <button class="botao botao-secundario" id="btn-testar-gateway">Testar Conexão</button>
            </div>
        </div>
    </div>

    <div id="aba-ia" class="aba" style="display:none">
        <div class="card">
            <h3 class="titulo-secao">Configuração de IA</h3>
            <p>Use a chave da API do GPT para classificar automaticamente categorias durante importações.</p>
            <form id="form-ia" class="form-linha">
                <input class="form-input" type="text" name="openai_api_key" placeholder="Chave da API OpenAI (ex: sk-...)" />
                <label style="display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" name="ai_auto_categorizar" />
                    <span>Ativar categorização automática</span>
                </label>
                <button class="botao botao-primario" type="submit">Salvar</button>
            </form>
            <div id="status-ia"></div>
        </div>
    </div>

    <div id="aba-migracoes" class="aba" style="display:none">
        <div class="card">
            <h3 class="titulo-secao">Migrações</h3>
            <div id="lista-migracoes" class="lista-tabela"></div>
            <div style="margin-top:10px; display:flex; gap:10px; align-items:center;">
                <button id="btn-aplicar-migracoes" class="botao botao-primario">Aplicar Migrações</button>
                <span id="status-migracoes"></span>
            </div>
            <pre id="resultado-migracoes" class="card" style="overflow:auto; margin-top:12px"></pre>
        </div>
    </div>
</div>

<script>
// Inicialização global segura
window.abaSelecionada = window.abaSelecionada || 'usuarios';
window.periodoSelecionado = window.periodoSelecionado || {inicio: null, fim: null, preset: 'month'};
function mostrarAba(nome) {
    var abas = ['usuarios','planos','assinaturas','gateway','migracoes'];
    abas.forEach(function(n){
        document.getElementById('aba-'+n).style.display = (n===nome)?'block':'none';
    });
    document.querySelectorAll('#tabs-admin .filtro-btn').forEach(function(el){
        el.classList.toggle('ativo', el.getAttribute('data-aba') === nome);
    });
    window.abaSelecionada = nome;
    carregarMetricasPeriodo();
    if (nome === 'usuarios') carregarUsuarios();
    if (nome === 'planos') carregarPlanos();
    if (nome === 'assinaturas') carregarAssinaturas();
    if (nome === 'migracoes') carregarPrevisaoMigracoes();
    if (nome === 'ia') carregarConfigIA();
}

document.getElementById('btn-aplicar-migracoes').addEventListener('click', function(){
    var btn = document.getElementById('btn-aplicar-migracoes');
    var st = document.getElementById('status-migracoes');
    btn.disabled = true;
    st.textContent = 'Aplicando...';
    fetch('funcoes/admin_migracoes.php?api=admin_migracoes&acao=aplicar')
        .then(r=>r.json()).then(d=>{
            document.getElementById('resultado-migracoes').textContent = JSON.stringify(d,null,2);
            st.textContent = d.sucesso ? 'Concluído' : 'Erro';
            carregarPrevisaoMigracoes();
        }).finally(function(){ btn.disabled = false; });
});

document.getElementById('form-criar-usuario').addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    fetch('funcoes/admin_usuarios.php?api=admin_usuarios&acao=criar', {method:'POST', body:fd})
        .then(r=>r.json()).then(()=>carregarUsuarios());
});

function carregarUsuarios(){
    fetch('funcoes/admin_usuarios.php?api=admin_usuarios&acao=listar')
        .then(r=>r.json()).then(d=>{
            var html = '<table class="tabela"><thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Perfil</th></tr></thead><tbody>';
            d.forEach(function(u){
                html += '<tr><td>'+u.id+'</td><td>'+u.nome+'</td><td>'+u.email+'</td><td>'+ (u.perfil||'usuario') +'</td></tr>';
            });
            html += '</tbody></table>';
            document.getElementById('lista-usuarios').innerHTML = html;
        });
}

    function carregarPlanos(){
    fetch('funcoes/admin_planos.php?api=admin_planos&acao=listar')
            .then(r=>r.json()).then(d=>{
            var html = '<table class="tabela"><thead><tr><th>ID</th><th>Nome</th><th>Preço</th><th>Duração (meses)</th><th>Price ID</th><th>Ações</th></tr></thead><tbody>';
            d.forEach(function(p){
                var preco = (p.preco||0);
                var dur = (p.duracao_meses||1);
                var price = (p.stripe_price_id||'');
                html += '<tr><td>'+p.id+'</td><td>'+p.nome+'</td><td>'+preco+'</td><td>'+dur+'</td><td>'+price+'</td>'+
                        '<td>'+
                        '<button class="icon-btn" title="Editar" onclick="abrirModalPlano('+p.id+',\''+preco+'\',\''+dur+'\',\''+price+'\')"><i class="fas fa-pencil-alt"></i></button> '+
                        '<button class="icon-btn danger" title="Excluir" onclick="inativarPlano('+p.id+')"><i class="fas fa-trash"></i></button>'+
                        '</td></tr>';
            });
            html += '</tbody></table>';
            document.getElementById('lista-planos').innerHTML = html;
        }).catch(function(){
            document.getElementById('lista-planos').innerHTML = '<div class="estado-vazio"><h3>Não foi possível carregar os planos</h3><p>Tente novamente mais tarde.</p></div>';
        });
    }

function abrirModalPlano(id, preco, duracao, priceId){
    var modal = document.getElementById('modal-editar-plano');
    modal.classList.add('ativo');
    document.getElementById('plano_id').value = id;
    document.getElementById('plano_preco').value = preco;
    document.getElementById('plano_duracao').value = duracao;
    document.getElementById('plano_price').value = priceId;
}

function fecharModalPlano(){
    document.getElementById('modal-editar-plano').classList.remove('ativo');
}

function salvarModalPlano(){
    var id = document.getElementById('plano_id').value;
    var preco = document.getElementById('plano_preco').value;
    var dur = document.getElementById('plano_duracao').value;
    var price = document.getElementById('plano_price').value;
    var fd1 = new FormData();
    fd1.append('id', id);
    fd1.append('preco', preco);
    fd1.append('duracao_meses', dur);
    fetch('funcoes/admin_planos.php?api=admin_planos&acao=atualizar_basico', {method:'POST', body:fd1})
        .then(()=>{
            var fd2 = new FormData();
            fd2.append('id', id);
            fd2.append('stripe_price_id', price);
            return fetch('funcoes/admin_planos.php?api=admin_planos&acao=atualizar_price', {method:'POST', body:fd2});
        })
        .then(()=>{ fecharModalPlano(); carregarPlanos(); });
}

function inativarPlano(id){
    if (!confirm('Deseja realmente excluir este plano?')) return;
    var fd = new FormData();
    fd.append('id', id);
    fetch('funcoes/admin_planos.php?api=admin_planos&acao=inativar', {method:'POST', body:fd})
        .then(r=>r.json()).then(()=>carregarPlanos());
}

function carregarAssinaturas(){
    fetch('funcoes/admin_assinaturas.php?api=admin_assinaturas&acao=listar')
        .then(r=>r.json()).then(d=>{
            var html = '<table class="tabela"><thead><tr><th>ID</th><th>Usuário</th><th>Plano</th><th>Status</th><th>Ações</th></tr></thead><tbody>';
            d.forEach(function(a){
                html += '<tr><td>'+a.id+'</td><td>'+a.usuario_id+'</td><td>'+a.plano_id+'</td><td>'+a.status+'</td>'+
                        '<td><button class="botao botao-secundario" onclick="revogar('+a.id+')">Revogar</button></td></tr>';
            });
            html += '</tbody></table>';
            document.getElementById('lista-assinaturas').innerHTML = html;
        });
}

function revogar(id){
    var fd = new FormData();
    fd.append('id', id);
    fetch('funcoes/admin_assinaturas.php?api=admin_assinaturas&acao=revogar', {method:'POST', body:fd})
        .then(r=>r.json()).then(()=>carregarAssinaturas());
}

document.getElementById('form-gateway').addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    const k = fd.get('stripe_api_key') || '';
    if (k.startsWith('pk_')) { alert('Informe a chave secreta (sk_...), não a pública (pk_...)'); return; }
    fetch('funcoes/admin_gateway.php?api=admin_gateway&acao=salvar', {method:'POST', body:fd})
        .then(r=>r.json()).then(d=>{
            document.getElementById('status-gateway').textContent = JSON.stringify(d);
        });
});

document.getElementById('btn-testar-gateway').addEventListener('click', function(){
    var st = document.getElementById('status-gateway');
    st.textContent = 'Testando...';
    fetch('funcoes/admin_gateway.php?api=admin_gateway&acao=testar')
        .then(r=>r.json()).then(d=>{
            st.textContent = d.conectado ? ('Conectado ✓ ('+(d.conta||'')+')') : ('Falha: '+(d.motivo||'erro')); 
        }).catch(function(){ st.textContent = 'Erro de rede ao testar'; });
});

document.getElementById('form-ia').addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    const k = fd.get('openai_api_key') || '';
    fetch('funcoes/admin_gateway.php?api=admin_gateway&acao=salvar', {method:'POST', body:fd})
        .then(r=>r.json()).then(d=>{
            document.getElementById('status-ia').textContent = d.sucesso ? 'Salvo' : 'Erro';
        });
});

function carregarConfigIA(){
    fetch('funcoes/admin_gateway.php?api=admin_gateway&acao=obter')
        .then(r=>r.json()).then(cfg=>{
            const f = document.getElementById('form-ia');
            if (!f) return;
            f.openai_api_key.value = cfg.openai_api_key || '';
            f.ai_auto_categorizar.checked = !!(cfg.ai_auto_categorizar && (cfg.ai_auto_categorizar==='1' || cfg.ai_auto_categorizar===1 || cfg.ai_auto_categorizar===true));
        });
}

mostrarAba('usuarios');
carregarUsuarios();
carregarPlanos();
carregarAssinaturas();
function carregarMetricas(){
    fetch('funcoes/admin_usuarios.php?api=admin_usuarios&acao=listar')
        .then(r=>r.json()).then(d=>{
            window.totalUsuarios = d.length || 0;
        });
    carregarMetricasPeriodo();
}
carregarMetricas();

function carregarPrevisaoMigracoes(){
    fetch('funcoes/admin_migracoes.php?api=admin_migracoes&acao=prever')
        .then(r=>r.json()).then(d=>{
            if (!d.sucesso) return;
            var html = '<table class="tabela"><thead><tr><th>Item</th><th>Status</th></tr></thead><tbody>';
            d.itens.forEach(function(i){
                var badge = i.status === 'ok' ? '<span class="badge status-ok">OK</span>' : '<span class="badge status-pending">Pendente</span>';
                html += '<tr><td>'+i.nome+'</td><td>'+badge+'</td></tr>';
            });
            html += '</tbody></table>';
            document.getElementById('lista-migracoes').innerHTML = html;
        });
}
carregarPrevisaoMigracoes();

var periodoSelecionado = window.periodoSelecionado;
function setFiltroPeriodo(preset){
    periodoSelecionado.preset = preset;
    document.getElementById('periodo_custom').style.display = (preset==='custom') ? 'flex' : 'none';
    carregarMetricasPeriodo();
}

function toggleCustomPeriodo(){
    periodoSelecionado.preset = 'custom';
    document.getElementById('periodo_custom').style.display = 'flex';
}

function aplicarPeriodoCustom(){
    var i = document.getElementById('periodo_inicio').value;
    var f = document.getElementById('periodo_fim').value;
    if (!i || !f) return;
    periodoSelecionado.inicio = i;
    periodoSelecionado.fim = f;
    carregarMetricasPeriodo();
}

function rangeAtual(){
    if (!periodoSelecionado) { periodoSelecionado = {inicio:null, fim:null, preset:'month'}; }
    if (periodoSelecionado.preset === 'custom' && periodoSelecionado.inicio && periodoSelecionado.fim) {
        return {inicio: periodoSelecionado.inicio, fim: periodoSelecionado.fim};
    }
    var hoje = new Date();
    var fim;
    var inicio;
    if (periodoSelecionado.preset === 'month') {
        var first = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
        var last = new Date(hoje.getFullYear(), hoje.getMonth()+1, 0);
        inicio = first.toISOString().slice(0,10);
        fim = last.toISOString().slice(0,10);
    } else {
        fim = hoje.toISOString().slice(0,10);
        var dias = periodoSelecionado.preset === '7d' ? 7 : (periodoSelecionado.preset === '90d' ? 90 : 30);
        var inicioDate = new Date(hoje.getTime() - dias*24*60*60*1000);
        inicio = inicioDate.toISOString().slice(0,10);
    }
    return {inicio: inicio, fim: fim};
}

function carregarMetricasPeriodo(){
    var r = rangeAtual();
    fetch('funcoes/admin_metrics.php?api=admin_metrics&acao=resumo&inicio='+r.inicio+'&fim='+r.fim)
        .then(r=>r.json()).then(d=>{ renderMetrics(d); }).catch(function(){ renderMetrics({}); });
}
document.getElementById('periodo_select').addEventListener('change', function(){ setFiltroPeriodo(this.value); });
setFiltroPeriodo('month');

function renderMetrics(d){
    var cont = document.getElementById('metrics-container');
    var fmt = new Intl.NumberFormat('pt-BR', {style:'currency', currency:'BRL'});
    var html = '';
    var aba = window.abaSelecionada || 'usuarios';
    if (aba === 'usuarios') {
        html += card('users','Novos usuários',''+(d.usuarios_novos||0));
        html += card('ban','Inativados',''+(d.usuarios_inativados||0));
    } else if (aba === 'assinaturas') {
        html += card('money-bill-wave','Receita', fmt.format(d.receita_total||0));
        html += card('ban','Cancelamentos',''+(d.cancelamentos||0));
    } else if (aba === 'gateway') {
        if (d.gateway_conectado) {
            html += card('plug','Gateway', (d.gateway_nome||'') + ' ✓');
        } else {
            html = '';
        }
    } else if (aba === 'planos') {
        html += card('layer-group','Planos ativos',''+(d.planos_ativos||0));
        html += card('layer-group','Planos totais',''+(d.planos_total||0));
    } else {
        html = '';
    }
    cont.innerHTML = html;
}

function card(icon, label, value){
    return '<div class="resumo-card">'+
           '<div class="resumo-icon"><i class="fas fa-'+icon+'"></i></div>'+
           '<div class="resumo-info"><span class="resumo-label">'+label+'</span>'+
           '<span class="resumo-valor">'+value+'</span></div></div>';
}
</script>

<div id="modal-editar-plano" class="modal">
  <div class="modal-conteudo">
    <div class="modal-cabecalho">
      <h2>Editar Plano</h2>
      <button class="fechar-modal" onclick="fecharModalPlano()">×</button>
    </div>
    <div class="modal-corpo">
      <input type="hidden" id="plano_id">
      <div class="form-grupo">
        <label class="form-label">Preço</label>
        <input class="form-input" type="number" step="0.01" min="0" id="plano_preco">
      </div>
      <div class="form-grupo">
        <label class="form-label">Duração (meses)</label>
        <input class="form-input" type="number" step="1" min="1" id="plano_duracao">
      </div>
      <div class="form-grupo">
        <label class="form-label">Price ID (Stripe)</label>
        <input class="form-input" type="text" id="plano_price">
      </div>
      <div style="display:flex; gap:10px;">
        <button class="botao botao-primario" onclick="salvarModalPlano()">Salvar</button>
        <button class="botao botao-secundario" onclick="fecharModalPlano()">Cancelar</button>
      </div>
    </div>
  </div>
</div>
