<div class="modal" id="modalImportacao">
    <div class="modal-conteudo">
        <div class="modal-cabecalho">
            <h2>Importar Extrato</h2>
            <span class="fechar" onclick="fecharModalImportacao()">&times;</span>
        </div>
        <div class="modal-corpo">
            <div class="importacao-controles">
                <div class="controle-col">
                    <label>Conta</label>
                    <select id="import-conta"></select>
                </div>
                <div class="controle-col">
                    <label>Categoria padrão (Receitas)</label>
                    <select id="import-cat-receita"></select>
                </div>
                <div class="controle-col">
                    <label>Categoria padrão (Despesas)</label>
                    <select id="import-cat-despesa"></select>
                </div>
            </div>
            <div class="upload-area">
                <i class="fas fa-file-upload"></i>
                <input type="file" id="arquivo-importacao" accept=".csv,text/csv,application/pdf" />
            </div>
            <div class="aviso-pdf">Aceita apenas CSV ou PDF. O tipo é detectado automaticamente.</div>
            <div id="import-preview" class="preview-tabela"></div>
            <div class="acoes-formulario">
                <button type="button" class="botao secundario" onclick="fecharModalImportacao()">Cancelar</button>
                <button type="button" class="botao primario" id="botao-importar" disabled>Importar</button>
            </div>
            <div id="import-status" class="import-status"></div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.162/pdf.min.js"></script>
<script>
// Estado
var importDados = [];
var importMapeamento = { data: null, descricao: null, valor: null, tipo: null };

// Utilidade de log
function log() { try { console.log.apply(console, arguments); } catch(e) {} }

function abrirModalImportacao() {
    carregarContasImport();
    carregarCategoriasImport();
    document.getElementById('modalImportacao').classList.add('ativo');
}
function fecharModalImportacao() {
    document.getElementById('modalImportacao').classList.remove('ativo');
    importDados = [];
    document.getElementById('import-preview').innerHTML = '';
    var input = document.getElementById('arquivo-importacao'); if (input) input.value='';
    document.getElementById('botao-importar').disabled = true;
    document.getElementById('import-status').textContent = '';
}

// Detecção automática do tipo de arquivo
function detectarTipoArquivo(arquivo) {
    var nome = (arquivo && arquivo.name || '').toLowerCase();
    var tipo = (arquivo && arquivo.type || '').toLowerCase();
    if (tipo.indexOf('pdf') > -1 || nome.endsWith('.pdf')) return 'pdf';
    if (tipo.indexOf('csv') > -1 || nome.endsWith('.csv')) return 'csv';
    return null;
}

// Carregar selects
function carregarContasImport() {
    var select = document.getElementById('import-conta');
    select.innerHTML = '<option>Carregando...</option>';
    fetch(obterUrl('api/contas.php'))
        .then(r=>r.json())
        .then(contas=>{
            select.innerHTML = '';
            contas.forEach(c=>{
                var opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.nome;
                select.appendChild(opt);
            });
        }).catch(()=> select.innerHTML = '<option>Erro ao carregar</option>');
}
function carregarCategoriasImport() {
    var rec = document.getElementById('import-cat-receita');
    var des = document.getElementById('import-cat-despesa');
    rec.innerHTML = des.innerHTML = '<option>Carregando...</option>';
    Promise.all([
        fetch(obterUrl('funcoes/transacoes.php?api=categorias&acao=listar&tipo=receita')).then(r=>r.json()),
        fetch(obterUrl('funcoes/transacoes.php?api=categorias&acao=listar&tipo=despesa')).then(r=>r.json())
    ]).then(([receitas, despesas])=>{
        rec.innerHTML = '';
        des.innerHTML = '';
        receitas.forEach(c=>{
            var opt = document.createElement('option');
            opt.value = c.id; opt.textContent = c.nome; rec.appendChild(opt);
        });
        despesas.forEach(c=>{
            var opt = document.createElement('option');
            opt.value = c.id; opt.textContent = c.nome; des.appendChild(opt);
        });
    }).catch(()=>{
        rec.innerHTML = '<option>Erro</option>';
        des.innerHTML = '<option>Erro</option>';
    });
}

// Upload único: CSV ou PDF
document.getElementById('arquivo-importacao').addEventListener('change', async function(){
    var arquivo = this.files[0];
    if (!arquivo) return;
    var tipo = detectarTipoArquivo(arquivo);
    if (!tipo) { alert('Formato inválido. Use apenas CSV ou PDF.'); this.value=''; return; }
    if (tipo === 'csv') {
        var leitor = new FileReader();
        leitor.onload = function(e){ processarCSV(e.target.result); };
        leitor.readAsText(arquivo, 'UTF-8');
    } else {
        await processarPDFArquivo(arquivo);
    }
});
function processarCSV(texto){
    var linhas = texto.split(/\r?\n/).filter(l=>l.trim().length>0);
    var separador = texto.indexOf(';') > -1 ? ';' : ',';
    var cab = linhas[0].split(separador).map(h=>h.trim().toLowerCase());
    importMapeamento.data = cab.findIndex(h=>/data/.test(h));
    importMapeamento.descricao = cab.findIndex(h=>/descricao|descrição|historico|histórico/.test(h));
    importMapeamento.valor = cab.findIndex(h=>/valor|amount/.test(h));
    importMapeamento.tipo = cab.findIndex(h=>/tipo|debito|crédito|credito/.test(h));
    importDados = [];
    for (var i=1;i<linhas.length;i++){
        var cols = linhas[i].split(separador);
        if (cols.length < 2) continue;
        var dataStr = cols[importMapeamento.data] || '';
        var desc = cols[importMapeamento.descricao] || '';
        var valStr = cols[importMapeamento.valor] || '';
        var tipoStr = (importMapeamento.tipo>=0 ? (cols[importMapeamento.tipo]||'') : '');
        var valor = parseFloat(valStr.replace(/\./g,'').replace(',','.'));
        var tipo = tipoStr ? (/[D]/i.test(tipoStr) ? 'despesa' : 'receita') : (valor<0 ? 'despesa' : 'receita');
        valor = Math.abs(valor);
        var dataISO = normalizarData(dataStr);
        if (!dataISO) continue;
        importDados.push({data: dataISO, descricao: desc, valor: valor, tipo: tipo});
    }
    renderPreview(importDados, 'import-preview');
}

// PDF
async function processarPDFArquivo(file){
    try {
        log('PDF selecionado:', { nome: file.name, tamanho: file.size });
        const loader = window.__loadPdfJs && window.__loadPdfJs.ensure ? window.__loadPdfJs : null;
        const pdfjsLib = loader ? await loader.ensure() : (window.pdfjsLib || window['pdfjsLib']);
        if (!pdfjsLib || !pdfjsLib.getDocument) {
            document.getElementById('import-preview').innerHTML = '<p class="sem-dados">Biblioteca PDF.js não está disponível. Verifique conectividade com CDN.</p>';
            log('pdfjsLib ausente:', window.pdfjsLib);
            return;
        }
        try { pdfjsLib.GlobalWorkerOptions.workerSrc = pdfjsLib.GlobalWorkerOptions.workerSrc || 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.162/pdf.worker.min.js'; } catch(e) { log('Falha ao definir workerSrc', e); }
        const arrayBuffer = await file.arrayBuffer();
        log('ArrayBuffer bytes:', arrayBuffer.byteLength);
        const pdf = await pdfjsLib.getDocument({
            data: arrayBuffer,
            cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.162/cmaps/',
            cMapPacked: true,
            standardFontDataUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.162/standard_fonts/'
        }).promise;
        log('PDF carregado. Páginas:', pdf.numPages);
        let linhas = [];
        for (let p=1; p<=pdf.numPages; p++){
            const page = await pdf.getPage(p);
            const content = await page.getTextContent();
            log('Conteúdo página', p, 'itens:', (content.items||[]).length);
            linhas = linhas.concat(agruparLinhasPorY(content));
        }
        if (!linhas || linhas.length === 0) {
            document.getElementById('import-preview').innerHTML = '<p class="sem-dados">Texto não encontrado no PDF (possivelmente escaneado). Prefira CSV.</p>';
            return;
        }
        processarPDFLinhas(linhas);
        log('Linhas agregadas:', linhas.length, 'Registros reconhecidos:', importDados.length);
    } catch(e){
        const msg = (e && e.message) ? e.message : 'Falha ao ler PDF. Prefira CSV.';
        document.getElementById('import-preview').innerHTML = '<p class="sem-dados">'+msg+'</p>';
        log('Erro leitura PDF:', e);
    }
}
function agruparLinhasPorY(content){
    const map = new Map();
    (content.items || []).forEach(i=>{
        const tr = i.transform || [1,0,0,1,0,0];
        const y = Math.round(tr[5]);
        const x = tr[4] || 0;
        if (!map.has(y)) map.set(y, []);
        map.get(y).push({ x, s: i.str || '' });
    });
    const linhas = [];
    Array.from(map.entries())
        .sort((a,b)=>b[0]-a[0])
        .forEach(([_, arr])=>{
            arr.sort((a,b)=>a.x-b.x);
            linhas.push(arr.map(t=>t.s).join(' ').trim());
        });
    return linhas;
}
function processarPDFLinhas(linhas){
    importDados = [];
    linhas.forEach(l=>{
        // dd/mm/aaaa ... valor (com possível R$ e sinal)
        var m = l.match(/(\d{2}\/\d{2}\/\d{4}).*?(?:R\$\s*)?(-?\d{1,3}(?:\.\d{3})*,\d{2})/);
        if (!m) return;
        var dataISO = normalizarData(m[1]);
        var valor = parseFloat(m[2].replace(/\./g,'').replace(',','.'));
        var tipo = valor<0 ? 'despesa' : 'receita';
        var desc = l.replace(m[1],'').replace(m[2],'').replace(/R\$\s*/,'').trim();
        // Remover possíveis colunas extras comuns do Itaú (ex: "DEB", "CR")
        desc = desc.replace(/\b(DEB|CR|D|C)\b/g,'').replace(/\s{2,}/g,' ').trim();
        importDados.push({ data: dataISO, descricao: desc, valor: Math.abs(valor), tipo: tipo });
    });
    renderPreview(importDados, 'import-preview');
}

function normalizarData(str){
    var m = str.match(/(\d{2})\/(\d{2})\/(\d{4})/);
    if (!m) return null;
    return m[3] + '-' + m[2] + '-' + m[1];
}

function renderPreview(dados, containerId){
    var cont = document.getElementById(containerId);
    if (!dados || dados.length===0){ cont.innerHTML = '<p class="sem-dados">Nenhum dado reconhecido</p>'; document.getElementById('botao-importar').disabled = true; return; }
    var html = '<table class="tabela-preview"><thead><tr><th>Data</th><th>Descrição</th><th>Tipo</th><th>Valor</th></tr></thead><tbody>';
    dados.slice(0,50).forEach(d=>{
        html += '<tr><td>'+d.data+'</td><td>'+escapeHtml(d.descricao)+'</td><td>'+d.tipo+'</td><td>R$ '+d.valor.toLocaleString('pt-BR',{minimumFractionDigits:2})+'</td></tr>';
    });
    html += '</tbody></table>';
    cont.innerHTML = html;
    document.getElementById('botao-importar').disabled = false;
}
function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }

document.getElementById('botao-importar').addEventListener('click', async function(){
    if (!importDados || importDados.length===0) return;
    var contaId = parseInt(document.getElementById('import-conta').value||0);
    var catRec = parseInt(document.getElementById('import-cat-receita').value||0);
    var catDes = parseInt(document.getElementById('import-cat-despesa').value||0);
    if (!contaId || !catRec || !catDes){ alert('Selecione conta e categorias padrão'); return; }
    var status = document.getElementById('import-status');
    status.textContent = 'Importando...';
    let ok=0, falha=0;
    // Tentar IA para sugerir categorias se configurada
    let sugestoes = null;
    try {
        const cfg = await fetch(obterUrl('funcoes/ia.php?api=ia&acao=config')).then(r=>r.json());
        if (cfg && cfg.auto && cfg.api_key) {
            const body = JSON.stringify({itens: importDados.map(d=>({descricao: d.descricao, valor: d.valor, tipo: d.tipo}))});
            const respIA = await fetch(obterUrl('funcoes/ia.php?api=ia&acao=categorizar'), {method:'POST', headers:{'Content-Type':'application/json'}, body});
            const dataIA = await respIA.json();
            if (dataIA && Array.isArray(dataIA.categorias)) {
                sugestoes = dataIA.categorias;
            }
        }
    } catch(e) { /* silenciar */ }
    for (let idx=0; idx<importDados.length; idx++){
        const d = importDados[idx];
        let categoriaId = d.tipo==='receita' ? catRec : catDes;
        if (sugestoes && sugestoes[idx] && sugestoes[idx].id) {
            categoriaId = sugestoes[idx].id;
        }
        const payload = {
            tipo: d.tipo,
            descricao: d.descricao,
            valor: d.valor,
            data_transacao: d.data,
            categoria_id: categoriaId,
            conta_id: contaId,
            observacoes: 'IMPORTADO:EXTRATO'
        };
        try {
            const resp = await fetch(obterUrl('funcoes/transacoes.php?api=transacoes&acao=salvar'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            if (resp.ok){ ok++; } else { falha++; }
        } catch(e){ falha++; }
    }
    status.textContent = 'Concluído: '+ok+' importadas, '+falha+' falhas';
    if (typeof carregarTransacoes === 'function') {
        carregarTransacoes();
    } else if (typeof app !== 'undefined' && app.atualizarGraficos) {
        app.atualizarGraficos();
    }
    setTimeout(()=> fecharModalImportacao(), 1200);
});
</script>

