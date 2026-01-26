<?php
require_once __DIR__ . '/../funcoes/usuario.php';
require_once __DIR__ . '/../funcoes/transacoes.php';
require_once __DIR__ . '/../funcoes/configuracoes.php';

$usuario_id = usuarioLogado() ? obterUsuarioId() : 1;
if (usuarioLogado()) {
    $usuario_atual = obterDadosUsuario();
} else {
    global $database;
    $sqlUsuario = "SELECT id, nome, email, foto_perfil FROM usuarios WHERE id = 1";
    $usuarios = $database->select($sqlUsuario);
    $usuario_atual = !empty($usuarios) ? $usuarios[0] : ['id' => 1, 'nome' => 'Usuário', 'email' => 'usuario@email.com'];
}

$saldo_total = calcularSaldoTotal($usuario_id);
$configuracoes = lerConfiguracoes($usuario_id);
$simbolo_moeda = $configuracoes['preferencias']['simbolo_moeda'] ?? 'R$';
function formatar_moeda_php($valor, $simbolo = 'R$')
{
    return $simbolo . ' ' . number_format((float) $valor, 2, ',', '.');
}
$nome_usuario = $usuario_atual['nome'] ?? 'Usuário';
$foto_usuario = $usuario_atual['foto_perfil'] ?? '';
$url_avatar_perfil = $foto_usuario ? $foto_usuario : ('https://ui-avatars.com/api/?name=' . urlencode($nome_usuario) . '&background=fbbf24&color=000');
?>
<div class="pagina-perfil">
    <div class="container">
        <div class="perfil-resumo card">
            <div class="perfil-resumo-left">
                <div class="perfil-resumo-avatar">
                    <img src="<?php echo htmlspecialchars($url_avatar_perfil); ?>" alt="Avatar"
                        class="perfil-resumo-avatar-img">
                </div>
                <div class="perfil-resumo-info">
                    <h1 id="nome-usuario"><?php echo htmlspecialchars($nome_usuario); ?></h1>
                    <p id="email-usuario">
                        <?php echo htmlspecialchars($usuario_atual['email'] ?? 'usuario@email.com'); ?>
                    </p>
                </div>
            </div>
            <div class="perfil-resumo-saldo">
                <div class="saldo-label">Saldo Total</div>
                <div class="saldo-valor <?php echo ($saldo_total >= 0) ? 'positivo' : 'negativo'; ?>" id="saldo-total">
                    <?php echo htmlspecialchars(formatar_moeda_php($saldo_total, $simbolo_moeda)); ?>
                </div>
            </div>
        </div>
        <div class="glass-panel p-6 rounded-2xl my-6">
            <!-- <h1 class="text-xl font-bold mb-4">Performance</h1> -->
            <div class="">
                <canvas id="grafico-performance" width="400" height="400"></canvas>
            </div>
        </div>

        <div class="perfil-section">
            <div class="perfil-section-title">Configurações</div>
            <div class="perfil-list">
                <button class="perfil-item" onclick="abrirModalEditarPerfil()">
                    <div class="perfil-item-icon"><i class="fas fa-user-edit"></i></div>
                    <div class="perfil-item-content">
                        <div class="perfil-item-title">Editar Perfil</div>
                        <div class="perfil-item-desc">Alterar dados pessoais</div>
                    </div>
                    <div class="perfil-item-arrow"><i class="fas fa-chevron-right"></i></div>
                </button>
                <button class="perfil-item" onclick="exportarDados()">
                    <div class="perfil-item-icon"><i class="fas fa-download"></i></div>
                    <div class="perfil-item-content">
                        <div class="perfil-item-title">Exportar Dados</div>
                        <div class="perfil-item-desc">Baixar informações</div>
                    </div>
                    <div class="perfil-item-arrow"><i class="fas fa-chevron-right"></i></div>
                </button>
                <button class="perfil-item" onclick="abrirAssinatura()">
                    <div class="perfil-item-icon"><i class="fas fa-receipt"></i></div>
                    <div class="perfil-item-content">
                        <div class="perfil-item-title">Assinatura</div>
                        <div class="perfil-item-desc">Ver e gerenciar</div>
                    </div>
                    <div class="perfil-item-arrow"><i class="fas fa-chevron-right"></i></div>
                </button>
            </div>
        </div>
        <div class="perfil-section">
            <div class="perfil-section-title">Conta</div>
            <div class="perfil-list">
                <button class="perfil-item perfil-item-danger" onclick="fazerLogout()">
                    <div class="perfil-item-icon"><i class="fas fa-sign-out-alt"></i></div>
                    <div class="perfil-item-content">
                        <div class="perfil-item-title">Sair</div>
                        <div class="perfil-item-desc">Encerrar sessão</div>
                    </div>
                    <div class="perfil-item-arrow"><i class="fas fa-chevron-right"></i></div>
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="perfil-footer">
            <div class="app-info">
                <i class="fas fa-mobile-alt"></i>
                <span>Versão 1.0.0</span>
            </div>
        </div>
    </div>
</div>

<script>
    // Variáveis globais para o perfil
    var dadosUsuario = <?php echo json_encode($usuario_atual, JSON_UNESCAPED_UNICODE); ?>;
    var temaEscuro = false;

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

    // Carregar dados do perfil quando a página for carregada
    function carregarDadosPerfil() {
        // Atualizar elementos com dados já fornecidos pelo servidor
        var nomeElement = document.getElementById('nome-usuario');
        var emailElement = document.getElementById('email-usuario');
        if (nomeElement) nomeElement.textContent = dadosUsuario.nome || 'Usuário';
        if (emailElement) emailElement.textContent = dadosUsuario.email || 'usuario@email.com';

        // Carregar saldo total real via API (opcional para manter atualizado)
        carregarSaldoTotal();

        // Verificar tema salvo
        var temaSalvo = localStorage.getItem('temaEscuro');
        var toggleElement = document.getElementById('toggle-tema');

        if (temaSalvo === 'true') {
            temaEscuro = true;
            if (toggleElement) { toggleElement.classList.add('ativo'); }
            document.body.classList.add('tema-escuro');
            document.body.classList.remove('tema-claro');
        } else {
            temaEscuro = false;
            if (toggleElement) { toggleElement.classList.remove('ativo'); }
            document.body.classList.remove('tema-escuro');
            document.body.classList.add('tema-claro');
        }
    }

    // Carregar saldo total
    function carregarSaldoTotal() {
        var saldoElement = document.getElementById('saldo-total');
        if (!saldoElement) return;

        fetch(obterUrl('funcoes/transacoes.php?api=transacoes&acao=saldo_total'))
            .then(function (response) {
                if (!response.ok) throw new Error('Erro ao obter saldo total');
                return response.json();
            })
            .then(function (data) {
                var saldo = (data && typeof data.saldo === 'number') ? data.saldo : <?php echo json_encode($saldo_total); ?>;
                saldoElement.textContent = formatarMoeda(saldo);
                saldoElement.className = 'saldo-valor ' + (saldo >= 0 ? 'positivo' : 'negativo');
            })
            .catch(function (err) {
                console.error('Erro saldo total:', err);
            });
    }

    // Função para formatar moeda
    function formatarMoeda(valor) {
        return 'R$ ' + valor.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Abrir modal para editar perfil
    function abrirModalEditarPerfil() {
        var tel = dadosUsuario.telefone || '';
        var cpf = dadosUsuario.cpf || '';
        var modalHTML = `
        <div class="modal" id="modal-editar-perfil" style="opacity:1;pointer-events:all;position:fixed;top:0;left:0;width:100%;height:100%;display:flex;justify-content:center;align-items:center;z-index:1000;background:rgba(0,0,0,0.5)">
            <div class="modal-conteudo" style="background:var(--cor-fundo-secundario);padding:20px;border-radius:12px;width:95%;max-width:520px;color:var(--cor-texto)">
                <div class="modal-cabecalho" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <h3>Editar Perfil</h3>
                    <button onclick="fecharModalPerfil()" style="background:transparent;border:none;color:var(--cor-texto);font-size:20px;cursor:pointer">×</button>
                </div>
                <div class="form-grid" style="display:grid;grid-template-columns:1fr;gap:12px">
                    <div>
                        <label>Nome</label>
                        <input type="text" id="edit-nome" value="${dadosUsuario.nome || ''}" style="width:100%;padding:10px;border:1px solid var(--cor-borda);border-radius:6px;background:var(--cor-fundo);color:var(--cor-texto)">
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="email" id="edit-email" value="${dadosUsuario.email || ''}" style="width:100%;padding:10px;border:1px solid var(--cor-borda);border-radius:6px;background:var(--cor-fundo);color:var(--cor-texto)">
                    </div>
                    <div>
                        <label>Número</label>
                        <input type="text" id="edit-telefone" value="${tel}" placeholder="(00) 00000-0000" style="width:100%;padding:10px;border:1px solid var(--cor-borda);border-radius:6px;background:var(--cor-fundo);color:var(--cor-texto)">
                    </div>
                    <div>
                        <label>CPF</label>
                        <input type="text" id="edit-cpf" value="${cpf}" placeholder="000.000.000-00" style="width:100%;padding:10px;border:1px solid var(--cor-borda);border-radius:6px;background:var(--cor-fundo);color:var(--cor-texto)">
                    </div>
                </div>
                <div style="display:flex;gap:10px;margin-top:16px">
                    <button onclick="salvarPerfil()" style="flex:1;padding:10px;background:var(--cor-destaque);color:#fff;border:none;border-radius:6px;cursor:pointer">Salvar</button>
                    <button onclick="fecharModalPerfil()" style="flex:1;padding:10px;background:var(--cor-borda);color:var(--cor-texto);border:none;border-radius:6px;cursor:pointer">Cancelar</button>
                </div>
            </div>
        </div>`;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    // Salvar alterações do perfil
    function salvarPerfil() {
        var novoNome = document.getElementById('edit-nome').value;
        var novoEmail = document.getElementById('edit-email').value;
        var novoTelefone = document.getElementById('edit-telefone').value;
        var novoCpf = document.getElementById('edit-cpf').value;
        var dados = { nome: novoNome.trim(), email: novoEmail.trim(), telefone: novoTelefone.trim(), cpf: novoCpf.trim() };
        fetch(obterUrl('funcoes/usuario.php?api=usuario&acao=atualizar'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        }).then(function (resp) {
            return resp.text().then(function (texto) {
                var ok = resp.ok;
                var retorno;
                try { retorno = texto ? JSON.parse(texto) : {}; } catch (e) { retorno = { erro: 'Resposta inválida', detalhe: e.message }; }
                return { ok: ok, dados: retorno, status: resp.status };
            });
        }).then(function (r) {
            if (r.ok && r.dados && r.dados.sucesso) {
                dadosUsuario.nome = dados.nome;
                dadosUsuario.email = dados.email;
                dadosUsuario.telefone = dados.telefone;
                dadosUsuario.cpf = dados.cpf;
                carregarDadosPerfil();
                fecharModalPerfil();
                alert('Perfil atualizado com sucesso!');
            } else {
                var msg = (r.dados && (r.dados.erro || r.dados.detalhe)) ? (r.dados.erro + (r.dados.detalhe ? ' - ' + r.dados.detalhe : '')) : ('Erro HTTP ' + r.status);
                alert(msg);
            }
        }).catch(function (e) { alert('Erro: ' + (e && e.message ? e.message : 'Falha desconhecida')); });
    }

    function fecharModalPerfil() {
        var modal = document.getElementById('modal-editar-perfil');
        if (modal) modal.remove();
    }

    function construirGraficoPerformance() {
        var canvas = document.getElementById('grafico-performance');
        if (!canvas) {
            console.log("Canvas de performance não encontrado");
            return;
        }

        const ctx = canvas.getContext("2d");

        // 🔹 Labels (pilares de performance)
        const labels = [
            "Capital",
            "Controle",
            "Eficiência",
            "Alocação",
            "Disciplina",
            "Ascensão"
        ];

        // 🔹 Dados de performance (0 a 100)
        // depois você pode calcular isso dinamicamente
        const performanceData = [
            75, // Capital
            68, // Controle
            82, // Eficiência
            70, // Alocação
            65, // Disciplina
            78  // Ascensão
        ];

        // console.log("Dados de performance:", performanceData);
        const gradient = ctx.createRadialGradient(
            canvas.width / 2,
            canvas.height / 2,
            20,
            canvas.width / 2,
            canvas.height / 2,
            canvas.width / 1.2
        );
        gradient.addColorStop(0, "rgba(251, 191, 36, 0.35)");
        gradient.addColorStop(1, "rgba(251, 191, 36, 0.05)");

        if (window.graficoPerformance) {
            window.graficoPerformance.destroy();
        }

        window.graficoPerformance = new Chart(ctx, {
            type: "radar",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Performance Geral",
                        data: performanceData,
                        backgroundColor: gradient,
                        borderColor: "#fbbf24",
                        borderWidth: 2,
                        pointBackgroundColor: "#fbbf24",
                        pointBorderColor: "#000",
                        pointHoverBackgroundColor: "#fff",
                        pointHoverBorderColor: "#fbbf24",
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        min: 0,
                        max: 100,
                        ticks: {
                            stepSize: 20,
                            color: "#aaa",
                            backdropColor: "transparent",
                            font: {
                                size: 10,
                            },
                            callback: (value) => value + "%",
                        },
                        grid: {
                            color: "rgba(255,255,255,0.08)",
                        },
                        angleLines: {
                            color: "rgba(255,255,255,0.12)",
                        },
                        pointLabels: {
                            color: "#e5e7eb",
                            font: {
                                size: 12,
                                weight: "500",
                            },
                        },
                    },
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: "rgba(0, 0, 0, 0.8)",
                        padding: 12,
                        cornerRadius: 6,
                        callbacks: {
                            label: function (context) {
                                return `${context.label}: ${context.parsed.r}%`;
                            },
                        },
                    },
                },
                animation: {
                    duration: 1200,
                    easing: "easeOutQuart",
                },
            },
        });
    }

    // Exportar dados
    function exportarDados() {
        // Criar objeto com todos os dados
        var dados = {
            usuario: dadosUsuario,
            exportadoEm: new Date().toISOString(),
            versao: '1.0.0'
        };

        // Converter para JSON
        var dadosJSON = JSON.stringify(dados, null, 2);

        // Criar link para download
        var blob = new Blob([dadosJSON], { type: 'application/json' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'financas_pessoais_backup.json';
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        alert('Dados exportados com sucesso!');
    }

    // Fazer logout
    function fazerLogout() {
        if (!confirm('Tem certeza que deseja sair?')) return;
        try { localStorage.clear(); } catch (e) { }
        try { sessionStorage.clear(); } catch (e) { }
        fetch(obterUrl('funcoes/usuario.php'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'acao=logout'
        })
            .then(function () { window.location.href = obterUrl('login.php'); })
            .catch(function () { window.location.href = obterUrl('login.php'); });
    }

    function abrirAssinatura() {
        window.location.href = obterUrl('paginas/assinatura.php');
    }

    // Função para inicializar a página de perfil
    window.inicializarPerfil = function () {
        carregarDadosPerfil();
        construirGraficoPerformance();  
    };

    // Carregar dados quando a página for exibida
    if (typeof carregarDadosPerfil === 'function') {
        // Aguardar um pouco para garantir que os elementos estejam no DOM
        setTimeout(function () {
            window.inicializarPerfil();
        }, 100);
    }

    // Se a página já estiver carregada, executar imediatamente
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.inicializarPerfil);
    } else {
        // DOM já está carregado
        setTimeout(window.inicializarPerfil, 100);
    }

    // Também executar quando a página de perfil for carregada via AJAX
    window.addEventListener('load', function () {
        if (document.querySelector('.pagina-perfil')) {
            setTimeout(window.inicializarPerfil, 100);
        }
    });


</script>