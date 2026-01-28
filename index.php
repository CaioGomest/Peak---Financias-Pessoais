<?php
require_once __DIR__ . '/config/config.php';
require_once 'funcoes/usuario.php';

if (!usuarioLogado()) {
    header('Location: login.php');
    exit;
}

require_once 'funcoes/transacoes.php';
require_once 'funcoes/categorias.php';

if (usuarioLogado()) {
    $usuario_atual = obterDadosUsuario();
} else {
    $usuario_atual = [
        'id' => 1,
        'nome' => 'Usuário Teste',
        'email' => 'teste@teste.com'
    ];
}

// Lógica de Roteamento inicial
$pagina_inicial = isset($_GET['pagina']) ? preg_replace('/[^a-z_]/', '', $_GET['pagina']) : '';

if ($pagina_inicial === '' && isset($_COOKIE['paginaAtual'])) {
    $cookiePagina = preg_replace('/[^a-z_]/', '', $_COOKIE['paginaAtual']);
    $paginasPermitidas = ['dashboard', 'transacoes', 'categorias', 'perfil', 'admin'];
    if (in_array($cookiePagina, $paginasPermitidas, true)) {
        if ($cookiePagina === 'admin' && (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin')) {
            $pagina_inicial = 'dashboard';
        } else {
            $pagina_inicial = $cookiePagina;
        }
    }
}

if ($pagina_inicial === '') {
    $pagina_inicial = 'dashboard';
}

$arquivo_pagina_inicial = 'paginas/' . $pagina_inicial . '.php';
if (!file_exists($arquivo_pagina_inicial)) {
    $pagina_inicial = 'dashboard';
    $arquivo_pagina_inicial = 'paginas/dashboard.php';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <?php
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    $uri_sem_query = strtok($uri, '?');
    $caminho_base = $uri_sem_query;
    if (substr($caminho_base, -10) === '/index.php') {
        $caminho_base = substr($caminho_base, 0, -10);
    }
    $pos_paginas = strpos($caminho_base, '/paginas/');
    if ($pos_paginas !== false) {
        $caminho_base = substr($caminho_base, 0, $pos_paginas);
    }
    $pos_modais = strpos($caminho_base, '/modais/');
    if ($pos_modais !== false) {
        $caminho_base = substr($caminho_base, 0, $pos_modais);
    }
    if (substr($caminho_base, -1) !== '/') {
        $caminho_base .= '/';
    }
    ?>
    <base href="<?= htmlspecialchars($caminho_base) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanças Pessoais</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // PDF.js loader (mantido original)
        (function () {
            function loadScript(src, worker) {
                return new Promise(function (resolve, reject) {
                    var s = document.createElement('script');
                    s.src = src;
                    s.onload = function () {
                        try {
                            if (window.pdfjsLib && window.pdfjsLib.GlobalWorkerOptions) {
                                window.pdfjsLib.GlobalWorkerOptions.workerSrc = worker;
                                resolve(window.pdfjsLib);
                            } else {
                                reject(new Error('pdfjsLib não disponível'));
                            }
                        } catch (e) { reject(e); }
                    };
                    s.onerror = function () { reject(new Error('Falha ao carregar ' + src)); };
                    document.head.appendChild(s);
                });
            }
            window.__loadPdfJs = {
                ensure: async function () {
                    if (window.pdfjsLib && window.pdfjsLib.getDocument) return window.pdfjsLib;
                    var fontes = [
                        { lib: 'assets/libs/pdfjs/pdf.min.js', worker: 'assets/libs/pdfjs/pdf.worker.min.js' },
                        { lib: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js', worker: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js' }
                    ];
                    for (var i = 0; i < fontes.length; i++) {
                        try {
                            return await loadScript(fontes[i].lib, fontes[i].worker);
                        } catch (e) { console.warn(e); }
                    }
                    return null;
                }
            };
        })();
    </script>
</head>

<body>
    <script>
        // Tema (mantido original)
        (function () {
            try {
                var escuro = localStorage.getItem('temaEscuro');
                document.body.classList.add(escuro === 'true' ? 'tema-escuro' : 'tema-claro');
            } catch (e) { document.body.classList.add('tema-escuro'); }
        })();
    </script>

    <div id="app">
        <header class="app-header">
            <?php
            $dados_usuario = isset($usuario_atual) ? $usuario_atual : obterDadosUsuario();
            $nome_header = $dados_usuario['nome'] ?? 'Usuário';
            $foto_header = $dados_usuario['foto_perfil'] ?? '';
            $url_avatar_header = $foto_header ?: ('https://ui-avatars.com/api/?name=' . urlencode($nome_header) . '&background=fbbf24&color=000');
            ?>
            <div class="app-header-left">
                <div class="app-logo"><i class="fas fa-tree"></i></div>
                <div class="app-titles">
                    <span class="app-title">PEAK</span>
                    <span class="app-subtitle">Otimização Financeira</span>
                </div>
            </div>
            <div class="app-header-right">
                <div class="flex items-center mr-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="checkbox-tema" class="sr-only peer" onclick="alternarTema()">
                        <div
                            class="w-14 h-7 bg-amber-400 rounded-full flex items-center justify-between px-2 peer-checked:bg-slate-600">
                            <i class="fas fa-sun text-white text-xs"></i>
                            <i class="fas fa-moon text-white text-xs"></i>
                        </div>
                    </label>
                </div>
                <img src="<?= htmlspecialchars($url_avatar_header) ?>" class="app-avatar" alt="Avatar">
            </div>
        </header>

        <div class="conteudo" id="conteudo-principal">
            <?php include $arquivo_pagina_inicial; ?>
        </div>

        <div class="fixed bottom-8 left-1/2 -translate-x-1/2 w-auto min-w-[20rem] max-w-[95vw] 
            bg-[#121214]/80 backdrop-blur-xl border border-white/10 p-2 rounded-full 
            flex items-center justify-center gap-1 shadow-2xl shadow-black/50 z-50 menu-inferior">

            <div class="flex items-center gap-1">
                <a href="javascript:void(0)" onclick="carregarPagina('dashboard')"
                    class="relative w-12 h-12 rounded-full flex items-center justify-center group transition-all hover:bg-white/5">
                    <i class="fas fa-home text-lg menu-item"></i>
                </a>
                <a href="javascript:void(0)" onclick="carregarPagina('transacoes')"
                    class="w-12 h-12 rounded-full flex items-center justify-center text-neutral-500 hover:bg-white/5 transition-all">
                    <i class="fas fa-exchange-alt text-lg menu-item"></i>
                </a>
            </div>

            <div class="px-2">
                <a href="javascript:void(0)" onclick="toggleMenuCircular()"
                    class="w-14 h-14 bg-amber-500 hover:bg-amber-600 text-black rounded-full flex items-center justify-center shadow-lg transition-all transform hover:scale-110 active:scale-95">
                    <i class="fas fa-plus text-xl"></i>
                </a>
            </div>

            <div class="flex items-center gap-1">
                <a href="javascript:void(0)" onclick="carregarPagina('categorias')"
                    class="w-12 h-12 rounded-full flex items-center justify-center text-neutral-500 hover:bg-white/5 transition-all">
                    <i class="fas fa-tags text-lg menu-item"></i>
                </a>
                <a href="javascript:void(0)" onclick="carregarPagina('perfil')"
                    class="w-12 h-12 rounded-full flex items-center justify-center text-neutral-500 hover:bg-white/5 transition-all">
                    <i class="fas fa-user text-lg menu-item"></i>
                </a>
                <?php
                $perfil_menu_admin = (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin')
                    || (isset($usuario_atual['perfil']) && $usuario_atual['perfil'] === 'admin');
                if ($perfil_menu_admin): ?>
                    <a href="javascript:void(0)" onclick="carregarPagina('admin')"
                        class="w-12 h-12 rounded-full flex items-center justify-center text-neutral-500 hover:bg-white/5 transition-all">
                        <i class="fas fa-tools text-lg menu-item"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="menu-overlay" id="menu-overlay" onclick="toggleMenuCircular()"></div>
        <div class="menu-circular" id="menu-circular">
            <a href="javascript:void(0)" class="opcao-menu receita" onclick="abrirModalTransacao('receita')">
                <i class="fas fa-arrow-up"></i><span>Receita</span>
            </a>
            <a href="javascript:void(0)" class="opcao-menu despesa" onclick="abrirModalTransacao('despesa')">
                <i class="fas fa-arrow-down"></i><span>Despesa</span>
            </a>
            <a href="javascript:void(0)" class="opcao-menu transferencia"
                onclick="abrirModalTransacao('transferencia')">
                <i class="fas fa-exchange-alt"></i><span>Transferência</span>
            </a>
        </div>

        <div id="container-modais">
            <?php
            include 'modais/transacao.php';
            include 'modais/categoria.php';
            include 'modais/importar_extrato.php';
            ?>
        </div>
    </div>

    <script src="assets/js/app.js"></script>

    <script>
        var paginaAtual = '<?= $pagina_inicial ?>';

        function definirItemAtivo(pagina) {
            document.querySelectorAll('.menu-item').forEach(i => {
                i.classList.remove('text-amber-500');
                i.classList.add('text-neutral-500');
            });
            var link = document.querySelector(`a[onclick*="'${pagina}'"]`);
            if (link) {
                var icon = link.querySelector('.menu-item');
                if (icon) { icon.classList.replace('text-neutral-500', 'text-amber-500'); }
            }
        }

        function carregarPagina(pagina, push = true) {
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function () {
                if (this.readyState == 4 && this.status == 200) {
                    var conteudo = document.getElementById('conteudo-principal');
                    var temp = document.createElement('div');
                    temp.innerHTML = this.responseText;

                    var scripts = temp.querySelectorAll('script');
                    conteudo.innerHTML = '';
                    temp.childNodes.forEach(no => {
                        if (no.tagName !== 'SCRIPT') conteudo.appendChild(no.cloneNode(true));
                    });

                    scripts.forEach(s => {
                        var novo = document.createElement('script');
                        if (s.src) novo.src = s.src; else novo.textContent = s.innerHTML;
                        document.body.appendChild(novo);
                    });

                    if (push) history.pushState({ pagina: pagina }, "", "?pagina=" + pagina);

                    definirItemAtivo(pagina);
                    paginaAtual = pagina;
                    localStorage.setItem('paginaAtual', pagina);
                    var conteudo = document.getElementById('conteudo-principal');
                    if (conteudo) conteudo.classList.remove('sem-padding');

                    // Inicializadores automáticos
                    setTimeout(() => {
                        if (pagina === 'dashboard' && window.app) window.app.inicializarGraficos();
                        if (pagina === 'transacoes' && window.inicializarTransacoes) window.inicializarTransacoes();
                        if (pagina === 'categorias' && window.inicializarCategorias) window.inicializarCategorias();
                        if (pagina === 'perfil' && window.inicializarPerfil) window.inicializarPerfil();
                    }, 100);
                }
            };
            xhttp.open("GET", "paginas/" + pagina + ".php", true);
            xhttp.send();
        }

        // Evento para quando o usuário usa as setas do navegador
        window.onpopstate = function (e) {
            if (e.state && e.state.pagina) carregarPagina(e.state.pagina, false);
        };

        // Inicialização
        document.addEventListener('DOMContentLoaded', function () {
            definirItemAtivo(paginaAtual);
            // Salva o estado inicial na History API para o F5 funcionar
            if (!history.state) history.replaceState({ pagina: paginaAtual }, "", window.location.search);
            var conteudo = document.getElementById('conteudo-principal');
            if (conteudo) conteudo.classList.remove('sem-padding');
        });

        // Funções de Tema e Menu (mantidas)
        function toggleMenuCircular() {
            document.getElementById('menu-circular').classList.toggle('ativo');
            document.getElementById('menu-overlay').classList.toggle('ativo');
        }

        function alternarTema() {
            var isDark = document.body.classList.toggle('tema-escuro');
            document.body.classList.toggle('tema-claro', !isDark);
            localStorage.setItem('temaEscuro', isDark);
        }
    </script>
</body>

</html>
