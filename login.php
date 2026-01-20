<?php
require_once __DIR__ . '/config/config.php';
// Incluir funções de usuário e iniciar sessão
require_once __DIR__ . '/funcoes/usuario.php';

// Se já estiver logado, envia para dashboard
if (usuarioLogado()) {
    header('Location: index.php');
    exit;
}

// Processar login localmente
$erro_login = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'login') {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    if (fazerLogin($email, $senha)) {
        header('Location: index.php');
        exit;
    } else {
        $erro_login = 'credenciais';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Financeiro</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .login-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .demo-info {
            background-color: #e8f4fd;
            color: #0066cc;
            border: 1px solid #b3d9ff;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .demo-info strong {
            display: block;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <script>
    (function(){
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
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>💰 Sistema Financeiro</h1>
                <p>Faça login para acessar sua conta</p>
            </div>
            
            <?php if ($erro_login || isset($_GET['erro'])): ?>
                <div class="alert alert-error">
                    <?php 
                    $erro_code = $erro_login ?? ($_GET['erro'] ?? '');
                    switch($erro_code) {
                        case 'credenciais':
                            echo 'Email ou senha incorretos.';
                            break;
                        case 'acesso':
                            echo 'Você precisa fazer login para acessar esta página.';
                            break;
                        default:
                            echo 'Erro no login. Tente novamente.';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <input type="hidden" name="acao" value="login">
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? 'usuario@email.com'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                    <span class="link-esqueci" onclick="abrirEsqueciSenha()">Esqueci minha senha</span>
                </div>
                
                <button type="submit" class="btn-login">Entrar</button>
            </form>
            
            <div class="demo-info">
                <strong>Dados para teste:</strong>
                Email: usuario@email.com<br>
                Senha: 123456
            </div>
        </div>
    </div>

    <div id="modal-esqueci-senha" class="modal">
        <div class="modal-conteudo">
            <div class="modal-cabecalho">
                <h3>Recuperar Senha</h3>
                <button class="btn btn-secundario" onclick="fecharEsqueciSenha()">Fechar</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="rec-email" placeholder="seu@email.com">
                </div>
                <div class="form-group" style="display:flex; gap:10px; align-items:center">
                    <button class="btn btn-primario" onclick="enviarCodigoRecuperacao()">Enviar código</button>
                    <span id="rec-info" style="color: var(--cor-texto-secundario, #666)"></span>
                </div>
                <div class="form-group">
                    <label>Código</label>
                    <input type="text" id="rec-codigo" placeholder="000000">
                </div>
                <div class="form-group">
                    <label>Nova Senha</label>
                    <input type="password" id="rec-nova">
                </div>
                <div class="form-group">
                    <label>Confirmar Senha</label>
                    <input type="password" id="rec-confirmar">
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn btn-secundario" onclick="fecharEsqueciSenha()">Cancelar</button>
                <button class="btn btn-primario" onclick="salvarRecuperacao()">Salvar</button>
            </div>
        </div>
    </div>

    <script>
    function abrirEsqueciSenha(){
        var m = document.getElementById('modal-esqueci-senha');
        var emailLogin = document.getElementById('email');
        var rec = document.getElementById('rec-email');
        if (rec && emailLogin) rec.value = emailLogin.value || '';
        if (m) m.classList.add('ativo');
    }
    function fecharEsqueciSenha(){
        var m = document.getElementById('modal-esqueci-senha');
        if (m) m.classList.remove('ativo');
        document.getElementById('rec-info').textContent = '';
    }
    function enviarCodigoRecuperacao(){
        var email = document.getElementById('rec-email').value.trim();
        if (!email) { alert('Informe o email'); return; }
        document.getElementById('rec-info').textContent = 'Enviando...';
        fetch('funcoes/usuario.php?api=usuario&acao=enviar_codigo_senha_login', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ email: email }) })
        .then(r=>r.json()).then(j=>{ document.getElementById('rec-info').textContent = j.sucesso ? 'Código enviado' : (j.erro || 'Falha'); })
        .catch(()=>{ document.getElementById('rec-info').textContent = 'Erro'; });
    }
    function salvarRecuperacao(){
        var email = document.getElementById('rec-email').value.trim();
        var codigo = document.getElementById('rec-codigo').value.trim();
        var nova = document.getElementById('rec-nova').value;
        var conf = document.getElementById('rec-confirmar').value;
        if (!email || !codigo || !nova || nova !== conf) { alert('Verifique os campos'); return; }
        fetch('funcoes/usuario.php?api=usuario&acao=trocar_senha_login', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ email: email, codigo: codigo, nova_senha: nova }) })
        .then(r=>r.json()).then(j=>{ if (j.sucesso) { alert('Senha atualizada'); fecharEsqueciSenha(); } else { alert('Erro: ' + (j.erro || 'Falha')); } })
        .catch(()=>{ alert('Erro ao salvar'); });
    }
    </script>
</body>
</html>
        .link-esqueci {
            display: inline-block;
            margin-top: 0.5rem;
            color: #667eea;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .modal { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:1000; }
        .modal.ativo { display:flex; }
        .modal-conteudo { background: var(--cor-fundo-secundario, #fff); color: var(--cor-texto, #111); border-radius: 12px; width: 95%; max-width: 480px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-cabecalho { display:flex; align-items:center; justify-content:space-between; padding: 16px 20px; border-bottom: 1px solid var(--cor-borda, #eee); }
        .modal-body { padding: 16px 20px; }
        .modal-actions { display:flex; gap:10px; padding: 0 20px 16px; }
        .btn { padding: 10px 12px; border-radius: 8px; border:none; cursor:pointer; }
        .btn-primario { background: var(--cor-destaque, #667eea); color:#fff; }
        .btn-secundario { background: var(--cor-borda, #eee); color: var(--cor-texto, #111); }
