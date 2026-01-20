<!-- Dashboard - Página principal -->
<?php
// Incluir funções necessárias
require_once __DIR__ . '/../funcoes/transacoes.php';
require_once __DIR__ . '/../funcoes/categorias.php';
require_once __DIR__ . '/../funcoes/configuracoes.php';

// Obter dados dinâmicos já alinhados ao mês atual
$usuario_id = usuarioLogado() ? obterUsuarioId() : 0;
$mes_atual = date('Y-m');
$totais_mes = calcularTotaisMes($usuario_id, $mes_atual);
$resumo = [
    'total_receitas' => $totais_mes['receitas'],
    'total_despesas' => $totais_mes['despesas'],
    'saldo_atual' => $totais_mes['saldo']
];
$configuracoes = lerConfiguracoes();
$simbolo_moeda = $configuracoes['preferencias']['simbolo_moeda'];


?>
<div class="pagina-dashboard">
    <!-- Header da Página -->
    <div class="dashboard-header">
        <div class="dashboard-background"></div>
        <div class="header-content">
            <div class="header-info">
                <h1>
                    <i class="fas fa-chart-line"></i>
                    Dashboard
                </h1>
                <p id="descricao-periodo">Visão geral das suas finanças</p>
            </div>
            <div class="header-actions">
                <div class="controle-periodo">
                    <div class="seletor-bonito" id="seletor-bonito-periodo">
                        <button class="seletor-botao" id="btn-seletor-periodo">
                            <span id="texto-seletor-periodo">Mês atual</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="seletor-painel" id="painel-seletor-periodo" style="display: none;">
                            <div class="painel-topo">
                                <button class="painel-nav" id="painel-prev"><i class="fas fa-chevron-left"></i></button>
                                <button class="mes-nav-botao" id="mes-nav-botao">
                                    <span id="mes-nav-texto"></span>
                                </button>
                                <button class="painel-nav" id="painel-next"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <div class="painel-calendario" id="painel-calendario">
                                <div class="calendario-cabecalho">
                                    <span>Dom</span><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span>
                                </div>
                                <div class="calendario-dias" id="calendario-dias"></div>
                                <div class="painel-acoes">
                                    <button class="acao-cancelar" id="acao-cancelar-calendario">Cancelar</button>
                                    <button class="acao-padrao" id="acao-aplicar-calendario">Aplicar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="botao-ocultar" onclick="app.toggleOcultarValores()">
                    <i class="fas" id="icone-ocultar"></i>
                    <span>Ocultar</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Card de resumo financeiro completo -->
    <div class="card card-resumo-completo">
        <!-- Saldo principal -->
        <div class="saldo-principal">
            <div class="titulo-saldo">Saldo em contas</div>
            <div class="valor-saldo-principal" id="valor-saldo"><?php echo $simbolo_moeda . ' ' . number_format($resumo['saldo_atual'], 2, ',', '.'); ?></div>
            <div class="valor-saldo-principal" id="valor-oculto" style="display: none;">••••••</div>
        </div>
        
        <!-- Receitas e Despesas -->
        <div class="entradas-saidas">
            <div class="entrada-item">
                <div class="entrada-icone">
                    <i class="fas fa-arrow-up"></i>
                </div>
                <div class="entrada-info">
                    <div class="entrada-titulo">Receitas</div>
                    <div class="entrada-valor valor-receita" id="valor-receitas"><?php echo $simbolo_moeda . ' ' . number_format($resumo['total_receitas'], 2, ',', '.'); ?></div>
                    <div class="entrada-valor" id="receitas-oculto" style="display: none;">••••••</div>
                </div>
            </div>
            
            <div class="saida-item">
                <div class="saida-icone">
                    <i class="fas fa-arrow-down"></i>
                </div>
                <div class="saida-info">
                    <div class="saida-titulo">Despesas</div>
                    <div class="saida-valor valor-despesa" id="valor-despesas"><?php echo $simbolo_moeda . ' ' . number_format($resumo['total_despesas'], 2, ',', '.'); ?></div>
                    <div class="saida-valor" id="despesas-oculto" style="display: none;">••••••</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de linhas (entradas e saídas ao longo do tempo) -->
    <div class="card">
        <div class="titulo-secao">
            <i class="fas fa-chart-line"></i>
            Entradas e Saídas
        </div>
        <div class="container-grafico">
            <canvas id="grafico-linhas"></canvas>
        </div>
    </div>

    <!-- Gráfico de barras mensal (Entradas x Saídas) -->
    <div class="card">
        <div class="titulo-secao">
            <i class="fas fa-chart-bar"></i>
            Entradas x Saídas (Mensal)
        </div>
        <div class="container-grafico">
            <canvas id="grafico-mensal"></canvas>
        </div>
    </div>

    <div class="cards-row">
        <div class="card">
            <div class="titulo-secao">
                <i class="fas fa-chart-pie"></i>
                Categorias de Receitas
            </div>
            <div class="container-grafico">
                <canvas id="grafico-donut-receitas"></canvas>
            </div>
            <div class="listagem-categorias">
                <div id="lista-categorias-receitas"></div>
            </div>
        </div>

        <div class="card">
            <div class="titulo-secao">
                <i class="fas fa-chart-pie"></i>
                Categorias de Despesas
            </div>
            <div class="container-grafico">
                <canvas id="grafico-donut-despesas"></canvas>
            </div>
            <div class="listagem-categorias">
                <div id="lista-categorias-despesas"></div>
            </div>
        </div>
    </div>


</div>
