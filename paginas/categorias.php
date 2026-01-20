<?php
require_once __DIR__ . '/../funcoes/categorias.php';
require_once __DIR__ . '/../funcoes/usuario.php';

$usuario_id = usuarioLogado() ? obterUsuarioId() : 1;
$tipo_parametro = isset($_GET['tipo']) ? $_GET['tipo'] : null;
$tipo_filtro = in_array($tipo_parametro, ['receita', 'despesa']) ? $tipo_parametro : null;
$todas_categorias_servidor = obterCategorias($tipo_filtro, $usuario_id);
$filtro_categorias_atual_servidor = $tipo_filtro ? $tipo_filtro : 'todos';

// Definir base do link de forma robusta
$script_atual = basename($_SERVER['SCRIPT_NAME']);
$esta_no_index = ($script_atual === 'index.php');

// Construir base absoluta da aplicação a partir da URI atual
$uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
$uri_sem_query = strtok($uri, '?');
$base_app = $uri_sem_query;
// Remover /index.php do final, se presente
if (substr($base_app, -10) === '/index.php') {
    $base_app = substr($base_app, 0, -10);
}
// Remover subcaminhos como /paginas/ e /modais/
$pos_paginas = strpos($base_app, '/paginas/');
if ($pos_paginas !== false) {
    $base_app = substr($base_app, 0, $pos_paginas);
}
$pos_modais = strpos($base_app, '/modais/');
if ($pos_modais !== false) {
    $base_app = substr($base_app, 0, $pos_modais);
}
if (substr($base_app, -1) !== '/') {
    $base_app .= '/';
}

// Link base absoluto
$base_link = $esta_no_index 
    ? ($base_app . 'index.php?pagina=categorias') 
    : ($base_app . 'paginas/categorias.php');

// Helper para adicionar o parâmetro de tipo respeitando ? ou &
function adicionarParametroTipo($base, $tipo) {
    return $base . (strpos($base, '?') !== false ? '&' : '?') . 'tipo=' . $tipo;
}
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
    (function() {
        try {
            var escuro = localStorage.getItem('temaEscuro');
            if (escuro === 'true') {
                document.body.classList.add('tema-escuro');
            } else {
                document.body.classList.add('tema-claro');
            }
        } catch (e) {
            document.body.classList.add('tema-escuro');
        }
    })();
    </script>
    <!-- Navegação inferior (fallback quando fora do index.php) -->
    <nav class="menu-inferior">
        <a href="<?php echo $base_app; ?>index.php?pagina=dashboard" class="menu-item">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?php echo $base_app; ?>index.php?pagina=transacoes" class="menu-item">
            <i class="fas fa-exchange-alt"></i>
            <span>Transações</span>
        </a>
        <div class="espaco-central"></div>
        <a href="<?php echo $base_app; ?>index.php?pagina=categorias" class="menu-item">
            <i class="fas fa-tags"></i>
            <span>Categorias</span>
        </a>
        <a href="<?php echo $base_app; ?>index.php?pagina=perfil" class="menu-item">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </a>
    </nav>

    <!-- Overlay escuro e botão central (fallback) -->
    <div class="menu-overlay" id="menu-overlay"></div>
    <a href="#" class="botao-adicionar-central" onclick="toggleMenuCircular()">
        <i class="fas fa-plus"></i>
    </a>
    <!-- Menu circular (fallback) -->
    <div class="menu-circular" id="menu-circular">
        <a href="#" class="opcao-menu receita" onclick="abrirModalTransacao('receita')" title="Nova Receita">
            <i class="fas fa-arrow-up"></i>
            <span>Receita</span>
        </a>
        <a href="#" class="opcao-menu despesa" onclick="abrirModalTransacao('despesa')" title="Nova Despesa">
            <i class="fas fa-arrow-down"></i>
            <span>Despesa</span>
        </a>
        <a href="#" class="opcao-menu transferencia" onclick="abrirModalTransacao('transferencia')" title="Transferência">
            <i class="fas fa-exchange-alt"></i>
            <span>Transferência</span>
        </a>
    </div>
    <script>
        // Alternar exibição do menu circular no fallback
        function toggleMenuCircular() {
            var menu = document.getElementById('menu-circular');
            var overlay = document.getElementById('menu-overlay');
            if (!menu || !overlay) return;
            menu.classList.toggle('ativo');
            overlay.classList.toggle('ativo');
        }
        // Fechar menu ao clicar no overlay
        (function(){
            var overlay = document.getElementById('menu-overlay');
            if (overlay) {
                overlay.addEventListener('click', function(){
                    document.getElementById('menu-circular').classList.remove('ativo');
                    overlay.classList.remove('ativo');
                });
            }
        })();
    </script>
<?php endif; ?>
<!-- Página de Categorias Reestruturada -->
<div class="pagina-categorias">
    <!-- Header da Página -->
    <div class="categorias-header">
        <div class="categorias-background"></div>
        <div class="header-content">
            <div class="header-info">
                <h1>
                    <i class="fas fa-tags"></i>
                    Categorias
                </h1>
                <p>Organize suas transações por categorias</p>
            </div>
            <div class="header-actions">
                <button class="btn-nova-categoria" onclick="abrirModalCategoria()">
                    <i class="fas fa-plus"></i>
                    <span>Nova Categoria</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="categorias-filtros">
        <div class="filtros-container">
            <a class="filtro-btn <?php echo ($filtro_categorias_atual_servidor === 'todos') ? 'ativo' : ''; ?>" data-tipo="todos" href="<?php echo $base_link; ?>">
                <i class="fas fa-list"></i>
                Todas
            </a>
            <a class="filtro-btn <?php echo ($filtro_categorias_atual_servidor === 'receita') ? 'ativo' : ''; ?>" data-tipo="receita" href="<?php echo adicionarParametroTipo($base_link, 'receita'); ?>">
                <i class="fas fa-arrow-up"></i>
                Receitas
            </a>
            <a class="filtro-btn <?php echo ($filtro_categorias_atual_servidor === 'despesa') ? 'ativo' : ''; ?>" data-tipo="despesa" href="<?php echo adicionarParametroTipo($base_link, 'despesa'); ?>">
                <i class="fas fa-arrow-down"></i>
                Despesas
            </a>
        </div>
    </div>

    <!-- Lista de Categorias -->
    <div class="categorias-content">
        <div class="categorias-grid" id="lista-categorias-container">
            <!-- As categorias serão carregadas aqui via JavaScript -->
        </div>
        
        <!-- Estado Vazio (oculto inicialmente) -->
        <div id="mensagem-vazia" class="estado-vazio" style="display: none;">
            <div class="vazio-ilustracao">
                <div class="vazio-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="vazio-decoracao">
                    <div class="circulo circulo-1"></div>
                    <div class="circulo circulo-2"></div>
                    <div class="circulo circulo-3"></div>
                </div>
            </div>
            <div class="vazio-content">
                <h3>Nenhuma categoria encontrada</h3>
                <p>Crie sua primeira categoria para organizar melhor suas transações financeiras</p>
                <button class="btn-criar-primeira" onclick="abrirModalCategoria()">
                    <i class="fas fa-plus"></i>
                    Criar Primeira Categoria
                </button>
            </div>
        </div>
        
        
    </div>
</div>

<script>
    // Funções para manipulação de categorias - definidas no escopo global
    console.log('Funções de categoria carregadas...');

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

    window.editarCategoria = function(categoria) {
        window.abrirModalCategoria(categoria);
    };

    // Removido salvarCategoria duplicado para evitar conflito com o modal
    
    // Dados vindos do servidor (evita flash e chamadas extras)
    var todasCategorias = <?php echo json_encode($todas_categorias_servidor, JSON_UNESCAPED_UNICODE); ?>;
    var filtroCategoriasAtual = '<?php echo $filtro_categorias_atual_servidor; ?>';
    // Controlar estados de carregamento na UI
    // (Sem necessidade de variável: usaremos elementos de DOM)
    
    // Função para carregar as categorias do banco de dados
    function carregarCategorias() {
        console.log('Carregando categorias do banco de dados...');
        
        // Fazer requisição para obter categorias reais
        fetch(obterUrl('funcoes/transacoes.php?api=categorias&acao=listar'))
            .then(response => {

                console.log('---------------------> Response categorias:', response);




                console.log('Response categorias:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error(`Erro ao buscar categorias: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Texto da resposta categorias:', text.substring(0, 200));
                try {
                    todasCategorias = JSON.parse(text);
                    console.log('Categorias carregadas do banco:', todasCategorias.length, 'categorias');
                    renderizarCategorias();
                } catch (e) {
                    console.error('Erro ao fazer parse do JSON de categorias:', e);
                    console.error('Texto recebido:', text);
                    // Em caso de erro, mostrar mensagem vazia
                    todasCategorias = [];
                    renderizarCategorias();
                }
            })
            .catch(error => {
                console.error('Erro ao carregar categorias:', error);
                // Em caso de erro, mostrar mensagem vazia
                todasCategorias = [];
                renderizarCategorias();
            });
    }
    
    // Função para renderizar as categorias
    function renderizarCategorias() {
        var container = document.getElementById('lista-categorias-container');
        var mensagemVazia = document.getElementById('mensagem-vazia');
        
        // Filtrar categorias baseado no filtro atual
        var categoriasFiltradas = todasCategorias.filter(function(categoria) {
            return filtroCategoriasAtual === 'todos' || categoria.tipo === filtroCategoriasAtual;
        });
        
        // Limpar o container
        container.innerHTML = '';
        
        if (categoriasFiltradas.length > 0) {
            mensagemVazia.style.display = 'none';
            container.style.display = 'grid';
            
            // Adicionar cada categoria ao container
            categoriasFiltradas.forEach(function(categoria, index) {
                var card = document.createElement('div');
                card.className = 'categoria-card';
                card.style.animationDelay = (index * 0.1) + 's';
                card.onclick = function() { editarCategoria(categoria); };
                
                var cardHeader = document.createElement('div');
                cardHeader.className = 'categoria-header';
                
                var corIndicador = document.createElement('div');
                corIndicador.className = 'categoria-cor-indicador';
                corIndicador.style.backgroundColor = categoria.cor;
                // Ícone dentro da bolinha de cor
                var iconeClasse = (categoria.icone || '').includes('fa-') || (categoria.icone || '').includes('fas')
                    ? categoria.icone
                    : ('fas fa-' + (categoria.icone || 'tag'));
                corIndicador.innerHTML = '<i class="' + iconeClasse + '"></i>';
                
                var tipoIcon = document.createElement('div');
                tipoIcon.className = 'categoria-tipo-icon';
                tipoIcon.innerHTML = categoria.tipo === 'receita' 
                    ? '<i class="fas fa-arrow-up"></i>' 
                    : '<i class="fas fa-arrow-down"></i>';
                tipoIcon.style.color = categoria.tipo === 'receita' ? '#27ae60' : '#e74c3c';
                
                cardHeader.appendChild(corIndicador);
                cardHeader.appendChild(tipoIcon);
                
                var cardBody = document.createElement('div');
                cardBody.className = 'categoria-body';
                
                var nome = document.createElement('h3');
                nome.className = 'categoria-nome';
                nome.textContent = categoria.nome;
                
                var tipo = document.createElement('p');
                tipo.className = 'categoria-tipo';
                tipo.textContent = categoria.tipo === 'receita' ? 'Receita' : 'Despesa';
                
                cardBody.appendChild(nome);
                cardBody.appendChild(tipo);
                
                var cardFooter = document.createElement('div');
                cardFooter.className = 'categoria-footer';
                cardFooter.innerHTML = '<i class="fas fa-edit"></i> Editar';
                
                card.appendChild(cardHeader);
                card.appendChild(cardBody);
                card.appendChild(cardFooter);
                
                container.appendChild(card);
            });
        } else {
            mensagemVazia.style.display = 'flex';
            container.style.display = 'none';
        }
    }
    
    // Função para filtrar categorias
    // Filtragem agora é feita via navegação (links); função não é necessária
    
    // Função para inicializar a página de categorias
    window.inicializarCategorias = function() {
        renderizarCategorias();
    };
    
    // Carregar categorias ao iniciar a página
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.inicializarCategorias);
    } else {
        // DOM já está carregado
        setTimeout(window.inicializarCategorias, 100);
    }

    // Removido carregamento via fetch: dados já estão disponíveis do servidor
</script>
<?php 
// Incluir modal de categoria para garantir que as funções estejam disponíveis
// tanto ao acessar via index.php quanto diretamente esta página.
include __DIR__ . '/../modais/categoria.php';
// Incluir modal de transação no fallback para habilitar o botão central
if (!$esta_no_index) {
    include __DIR__ . '/../modais/transacao.php';
}
?>
<?php if (!$esta_no_index): ?>
</body>
<?php endif; ?>
