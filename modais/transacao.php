<div class="modal" id="modalTransacao">
    <div class="modal-conteudo">
        <div class="modal-cabecalho">
            <h3 id="modal-titulo">Nova Transação</h3>
            <span class="fechar" onclick="fecharModalTransacao()">&times;</span>
        </div>
        <div class="modal-corpo">
            <form id="form-transacao" onsubmit="salvarTransacao(event)">
                <input type="hidden" id="transacao-id" value="">
                <input type="hidden" id="transacao-tipo" value="receita">
                
                <div class="campo-formulario">
                    <label for="transacao-descricao">Descrição</label>
                    <input type="text" id="transacao-descricao" required>
                </div>
                
                <div class="campo-formulario">
                    <label for="transacao-valor">Valor</label>
                    <input type="number" id="transacao-valor" step="0.01" min="0.01" required>
                </div>
                
                <div class="campo-formulario">
                    <label for="transacao-data">Data</label>
                    <input type="date" id="transacao-data" required>
                </div>
                
                <div class="campo-formulario">
                    <label for="transacao-categoria">Categoria</label>
                    <select id="transacao-categoria" required>
                        <!-- Categorias serão carregadas via JavaScript -->
                    </select>
                </div>
                
                <div class="campo-formulario">
                    <label for="transacao-conta">Conta</label>
                    <select id="transacao-conta" onchange="atualizarContaDestino()" required>
                        <!-- Contas serão carregadas via JavaScript -->
                    </select>
                </div>
                
                <div class="campo-formulario" id="campo-conta-destino" style="display: none;">
                    <label for="transacao-conta-destino">Conta de Destino</label>
                    <select id="transacao-conta-destino" required>
                        <!-- Contas serão carregadas via JavaScript -->
                    </select>
                </div>
                
                <div class="acoes-formulario">
                    <button type="button" class="botao secundario" onclick="fecharModalTransacao()">Cancelar</button>
                    <button type="submit" class="botao primario">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Variáveis globais
var categorias = [];
var contas = [];

// Helper para compor URLs relativas ao diretório da aplicação
function obterUrl(caminho) {
    var caminhoNormalizado = (caminho || '').replace(/^\//, '');
    var path = window.location.pathname || '';
    var base = path;
    if (base.endsWith('/index.php')) {
        base = base.replace('/index.php', '');
    }
    if (base.includes('/paginas/')) {
        base = base.split('/paginas/')[0];
    }
    if (base.includes('/modais/')) {
        base = base.split('/modais/')[0];
    }
    if (!base.endsWith('/')) base += '/';
    return base + caminhoNormalizado;
}

window.abrirModalTransacao = function(tipo, transacao = null) {
    // Resetar formulário
    document.getElementById('form-transacao').reset();
    document.getElementById('transacao-id').value = '';
    
    // Definir tipo de transação
    document.getElementById('transacao-tipo').value = tipo || 'receita';
    
    // Mostrar/esconder campo de conta destino
    if (tipo === 'transferencia') {
        document.getElementById('campo-conta-destino').style.display = 'block';
    } else {
        document.getElementById('campo-conta-destino').style.display = 'none';
    }
    
    // Carregar categorias e contas
    carregarCategoriasParaTransacao();
    carregarContasParaTransacao();
    
    // Definir título do modal
    if (transacao) {
        document.getElementById('modal-titulo').textContent = 'Editar Transação';
        document.getElementById('transacao-id').value = transacao.id;
        document.getElementById('transacao-descricao').value = transacao.descricao;
        document.getElementById('transacao-valor').value = transacao.valor;
        document.getElementById('transacao-data').value = transacao.data;
        
        // Selecionar categoria e conta após carregamento
        setTimeout(function() {
            document.getElementById('transacao-categoria').value = transacao.categoria_id;
            document.getElementById('transacao-conta').value = transacao.conta_id;
            if (tipo === 'transferencia') {
                document.getElementById('transacao-conta-destino').value = transacao.conta_destino_id;
            }
        }, 500);
    } else {
        document.getElementById('modal-titulo').textContent = 'Nova Transação';
        // Definir data atual usando fuso horário local (sem UTC)
        function formatarDataInputLocal(data) {
            var ano = data.getFullYear();
            var mes = String(data.getMonth() + 1).padStart(2, '0');
            var dia = String(data.getDate()).padStart(2, '0');
            return ano + '-' + mes + '-' + dia;
        }
        var hoje = new Date();
        document.getElementById('transacao-data').value = formatarDataInputLocal(hoje);
    }
    
    // Exibir modal usando a classe 'ativo'
    document.getElementById('modalTransacao').classList.add('ativo');
}

function fecharModalTransacao() {
    var modal = document.getElementById('modalTransacao');
    if (modal) modal.classList.remove('ativo');
    var menu = document.getElementById('menu-circular');
    var overlay = document.getElementById('menu-overlay');
    var botao = document.querySelector('.botao-adicionar-central');
    if (menu) menu.classList.remove('ativo');
    if (overlay) overlay.classList.remove('ativo');
    if (botao) botao.classList.remove('ativo');
}

function carregarCategoriasParaTransacao() {
    var tipo = document.getElementById('transacao-tipo').value;
    var select = document.getElementById('transacao-categoria');
    select.innerHTML = '<option value="">Carregando...</option>';
    
    // Carregar categorias via AJAX
    var xhr = new XMLHttpRequest();
    // Para transferências, listar todas as categorias (sem filtro por tipo)
    var urlCategorias = 'funcoes/transacoes.php?api=categorias&acao=listar' + (tipo === 'transferencia' ? '' : ('&tipo=' + tipo));
    xhr.open('GET', obterUrl(urlCategorias), true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                categorias = JSON.parse(xhr.responseText);
                select.innerHTML = '';
                
                if (categorias.length === 0) {
                    select.innerHTML = '<option value="">Nenhuma categoria encontrada</option>';
                } else {
                    categorias.forEach(function(categoria) {
                        var option = document.createElement('option');
                        option.value = categoria.id;
                        option.textContent = categoria.nome;
                        select.appendChild(option);
                    });
                }
            } catch (e) {
                select.innerHTML = '<option value="">Erro ao carregar categorias</option>';
            }
        } else {
            select.innerHTML = '<option value="">Erro ao carregar categorias</option>';
        }
    };
    xhr.send();
}

function carregarContasParaTransacao() {
    var selectConta = document.getElementById('transacao-conta');
    var selectContaDestino = document.getElementById('transacao-conta-destino');
    selectConta.innerHTML = '<option value="">Carregando...</option>';
    selectContaDestino.innerHTML = '<option value="">Carregando...</option>';
    
    // Carregar contas via AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('GET', obterUrl('api/contas.php'), true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                contas = JSON.parse(xhr.responseText);
                selectConta.innerHTML = '';
                selectContaDestino.innerHTML = '';
                
                if (contas.length === 0) {
                    selectConta.innerHTML = '<option value="">Nenhuma conta encontrada</option>';
                    selectContaDestino.innerHTML = '<option value="">Nenhuma conta encontrada</option>';
                } else {
                    contas.forEach(function(conta) {
                        // Adicionar ao select de conta origem
                        var option1 = document.createElement('option');
                        option1.value = conta.id;
                        option1.textContent = conta.nome;
                        selectConta.appendChild(option1);
                        
                        // Adicionar ao select de conta destino
                        var option2 = document.createElement('option');
                        option2.value = conta.id;
                        option2.textContent = conta.nome;
                        selectContaDestino.appendChild(option2);
                    });
                    
                    // Atualizar conta destino para desabilitar a conta selecionada
                    atualizarContaDestino();
                }
            } catch (e) {
                selectConta.innerHTML = '<option value="">Erro ao carregar contas</option>';
                selectContaDestino.innerHTML = '<option value="">Erro ao carregar contas</option>';
            }
        } else {
            selectConta.innerHTML = '<option value="">Erro ao carregar contas</option>';
            selectContaDestino.innerHTML = '<option value="">Erro ao carregar contas</option>';
        }
    };
    xhr.send();
}

function atualizarContaDestino() {
    var contaOrigem = document.getElementById('transacao-conta').value;
    var selectContaDestino = document.getElementById('transacao-conta-destino');
    
    // Habilitar todas as opções
    for (var i = 0; i < selectContaDestino.options.length; i++) {
        selectContaDestino.options[i].disabled = false;
    }
    
    // Desabilitar a conta de origem
    for (var i = 0; i < selectContaDestino.options.length; i++) {
        if (selectContaDestino.options[i].value === contaOrigem) {
            selectContaDestino.options[i].disabled = true;
            
            // Se a conta destino for igual à origem, selecionar outra
            if (selectContaDestino.value === contaOrigem) {
                for (var j = 0; j < selectContaDestino.options.length; j++) {
                    if (!selectContaDestino.options[j].disabled) {
                        selectContaDestino.value = selectContaDestino.options[j].value;
                        break;
                    }
                }
            }
            
            break;
        }
    }
}

function salvarTransacao(event) {
    event.preventDefault();
    
    // Obter dados do formulário
    var id = document.getElementById('transacao-id').value;
    var tipo = document.getElementById('transacao-tipo').value;
    var descricao = document.getElementById('transacao-descricao').value;
    var valor = document.getElementById('transacao-valor').value;
    var data = document.getElementById('transacao-data').value;
    var categoria_id = document.getElementById('transacao-categoria').value;
    var conta_id = document.getElementById('transacao-conta').value;
    var conta_destino_id = tipo === 'transferencia' ? document.getElementById('transacao-conta-destino').value : '';
    
    // Validar formulário
    if (!descricao || !valor || !data || !categoria_id || !conta_id || (tipo === 'transferencia' && !conta_destino_id)) {
        alert('Preencha todos os campos obrigatórios');
        return;
    }
    
    // Preparar dados para envio
    var dados = {
        tipo: tipo,
        descricao: descricao,
        valor: parseFloat(valor),
        data_transacao: data,
        categoria_id: parseInt(categoria_id),
        conta_id: parseInt(conta_id)
    };
    
    if (id) {
        dados.id = parseInt(id);
    }
    
    if (tipo === 'transferencia') {
        dados.conta_destino_id = parseInt(conta_destino_id);
    }
    
    // Enviar requisição AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', obterUrl('funcoes/transacoes.php?api=transacoes&acao=salvar'), true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var resposta = JSON.parse(xhr.responseText);
                if (resposta.sucesso) {
                    // Fechar modal
                    fecharModalTransacao();
                    
                    // Recarregar transações
                    if (typeof carregarTransacoes === 'function') {
                        carregarTransacoes();
                    } else if (typeof app !== 'undefined' && app.atualizarGraficos) {
                        app.atualizarGraficos();
                    } else {
                        // Recarregar página se não houver função de carregar transações
                        location.reload();
                    }
                } else {
                    alert('Erro ao salvar transação: ' + (resposta.erro || 'Erro desconhecido'));
                }
            } catch (e) {
                alert('Erro ao processar resposta do servidor');
            }
        } else {
            alert('Erro ao salvar transação');
        }
    };
    xhr.send(JSON.stringify(dados));
}
</script>
