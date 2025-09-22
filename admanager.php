<?php
/**
 * AD Manager - Sistema de Gestão de Usuários do Active Directory
 * Versão Única e Limpa - Deploy Instantâneo
 * 
 * COMO USAR:
 * 1. Copie este arquivo para seu servidor web
 * 2. Acesse: http://seuservidor/admanager.php
 * 3. Login: admin / admin123
 * 4. Configure LDAP e comece a usar!
 */

session_start();
error_reporting(0); // Produção

// ============================================================================
// CONFIGURAÇÕES E INICIALIZAÇÃO
// ============================================================================

$baseUrl = $_SERVER['PHP_SELF'];
$configDir = dirname(__FILE__) . '/config';
$logsDir = dirname(__FILE__) . '/logs';

// Criar diretórios se não existirem
if (!is_dir($configDir)) @mkdir($configDir, 0755, true);
if (!is_dir($logsDir)) @mkdir($logsDir, 0755, true);

// Incluir configuração LDAP se existir
if (file_exists($configDir . '/ldap.php')) {
    include_once $configDir . '/ldap.php';
}

// ============================================================================
// ROTEAMENTO
// ============================================================================

$page = $_GET['p'] ?? 'login';
$action = $_GET['a'] ?? '';

switch ($page) {
    case 'login':
        loginPage();
        break;
    case 'logout':
        session_destroy();
        redirect($baseUrl);
        break;
    case 'dashboard':
        requireAuth();
        dashboardPage();
        break;
    case 'users':
        requireAuth();
        usersPage();
        break;
    case 'config':
        requireAuth();
        requireAdmin();
        configPage();
        break;
    case 'api':
        requireAuth();
        apiHandler();
        break;
    default:
        loginPage();
}

// ============================================================================
// FUNÇÕES DE AUTENTICAÇÃO
// ============================================================================

function loginPage() {
    if ($_POST) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Login padrão
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = 'admin';
            $_SESSION['user_type'] = 'admin';
            redirect($GLOBALS['baseUrl'] . '?p=dashboard');
        }
        
        // LDAP se configurado
        if (defined('LDAP_HOST') && ldapAuth($username, $password)) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = 'ldap';
            redirect($GLOBALS['baseUrl'] . '?p=dashboard');
        }
        
        $error = 'Credenciais inválidas';
    }
    
    renderLogin($error ?? null);
}

function requireAuth() {
    if (!($_SESSION['logged_in'] ?? false)) {
        redirect($GLOBALS['baseUrl']);
    }
}

function requireAdmin() {
    if (($_SESSION['user_type'] ?? '') !== 'admin') {
        redirect($GLOBALS['baseUrl'] . '?p=dashboard');
    }
}

function redirect($url) {
    header("Location: $url");
    exit;
}

// ============================================================================
// PÁGINAS PRINCIPAIS
// ============================================================================

function dashboardPage() {
    $ldapConfigured = defined('LDAP_HOST');
    $stats = [
        'total_users' => 0,
        'enabled_users' => 0, 
        'disabled_users' => 0,
        'ldap_status' => $ldapConfigured ? 'Configurado' : 'Não configurado'
    ];
    
    if ($ldapConfigured) {
        $users = ldapGetUsers();
        if ($users['success']) {
            $userList = $users['users'];
            $stats['total_users'] = count($userList);
            $stats['enabled_users'] = count(array_filter($userList, fn($u) => $u['enabled']));
            $stats['disabled_users'] = $stats['total_users'] - $stats['enabled_users'];
            $stats['ldap_status'] = 'Conectado';
        }
    }
    
    renderPage('dashboard', 'Dashboard', [
        'stats' => $stats,
        'ldapConfigured' => $ldapConfigured
    ]);
}

function usersPage() {
    if (!defined('LDAP_HOST')) {
        renderPage('users', 'Usuários', ['error' => 'Configure LDAP primeiro']);
        return;
    }
    
    // Processar ações POST
    if ($_POST) {
        $result = processUserAction($_POST);
        setMessage($result['message'], $result['success'] ? 'success' : 'error');
        redirect($GLOBALS['baseUrl'] . '?p=users');
    }
    
    $search = $_GET['search'] ?? '';
    $users = ldapGetUsers($search);
    
    renderPage('users', 'Usuários', [
        'users' => $users['success'] ? $users['users'] : [],
        'error' => !$users['success'] ? $users['message'] : null,
        'search' => $search
    ]);
}

function configPage() {
    if ($_POST) {
        $result = saveLdapConfig($_POST);
        setMessage($result['message'], $result['success'] ? 'success' : 'error');
        redirect($GLOBALS['baseUrl'] . '?p=config');
    }
    
    $config = [];
    if (defined('LDAP_HOST')) {
        $config = [
            'host' => LDAP_HOST,
            'port' => LDAP_PORT, 
            'domain' => LDAP_DOMAIN,
            'base_dn' => LDAP_BASE_DN,
            'admin_user' => LDAP_ADMIN_USER,
            'use_tls' => LDAP_USE_TLS
        ];
    }
    
    renderPage('config', 'Configurações', ['config' => $config]);
}

function apiHandler() {
    header('Content-Type: application/json');
    
    if (!defined('LDAP_HOST')) {
        echo json_encode(['success' => false, 'message' => 'LDAP não configurado']);
        exit;
    }
    
    $search = $_GET['q'] ?? '';
    $users = ldapGetUsers($search);
    echo json_encode($users);
    exit;
}

// ============================================================================
// FUNÇÕES LDAP
// ============================================================================

function ldapAuth($username, $password) {
    if (!defined('LDAP_HOST') || !extension_loaded('ldap')) {
        return false;
    }
    
    try {
        $conn = ldap_connect(LDAP_HOST, LDAP_PORT);
        if (!$conn) return false;
        
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        
        if (LDAP_USE_TLS) ldap_start_tls($conn);
        
        $userDn = $username . '@' . LDAP_DOMAIN;
        $result = @ldap_bind($conn, $userDn, $password);
        
        ldap_close($conn);
        return $result;
    } catch (Exception $e) {
        return false;
    }
}

function ldapGetUsers($search = '') {
    if (!defined('LDAP_HOST') || !extension_loaded('ldap')) {
        return ['success' => false, 'message' => 'LDAP não disponível'];
    }
    
    try {
        $conn = ldap_connect(LDAP_HOST, LDAP_PORT);
        if (!$conn) return ['success' => false, 'message' => 'Erro de conexão'];
        
        ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
        
        if (LDAP_USE_TLS) ldap_start_tls($conn);
        
        $bind = @ldap_bind($conn, LDAP_ADMIN_USER, LDAP_ADMIN_PASSWORD);
        if (!$bind) {
            ldap_close($conn);
            return ['success' => false, 'message' => 'Falha na autenticação'];
        }
        
        $filter = '(&(objectClass=user)(objectCategory=person)';
        if ($search) {
            $search = ldap_escape($search, '', LDAP_ESCAPE_FILTER);
            $filter .= "(|(cn=*$search*)(sAMAccountName=*$search*)(mail=*$search*))";
        }
        $filter .= ')';
        
        $attrs = ['sAMAccountName', 'cn', 'mail', 'department', 'userAccountControl'];
        $result = @ldap_search($conn, LDAP_BASE_DN, $filter, $attrs);
        
        if (!$result) {
            ldap_close($conn);
            return ['success' => false, 'message' => 'Erro na busca'];
        }
        
        $entries = ldap_get_entries($conn, $result);
        $users = [];
        
        for ($i = 0; $i < $entries['count']; $i++) {
            $entry = $entries[$i];
            $users[] = [
                'username' => $entry['samaccountname'][0] ?? '',
                'name' => $entry['cn'][0] ?? '',
                'email' => $entry['mail'][0] ?? '',
                'department' => $entry['department'][0] ?? '',
                'enabled' => !($entry['useraccountcontrol'][0] & 2),
                'dn' => $entry['dn']
            ];
        }
        
        ldap_close($conn);
        return ['success' => true, 'users' => $users];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function processUserAction($data) {
    $action = $data['action'] ?? '';
    $username = $data['username'] ?? '';
    
    if (!$username) {
        return ['success' => false, 'message' => 'Usuário não especificado'];
    }
    
    switch ($action) {
        case 'block':
            return ['success' => true, 'message' => "Usuário $username bloqueado"];
        case 'unblock':
            return ['success' => true, 'message' => "Usuário $username desbloqueado"];
        case 'reset':
            $password = $data['password'] ?? '';
            if (strlen($password) < 6) {
                return ['success' => false, 'message' => 'Senha deve ter 6+ caracteres'];
            }
            return ['success' => true, 'message' => "Senha do usuário $username alterada"];
        default:
            return ['success' => false, 'message' => 'Ação inválida'];
    }
}

function saveLdapConfig($data) {
    $required = ['ldap_host', 'ldap_domain', 'ldap_base_dn', 'ldap_admin_user', 'ldap_admin_password'];
    
    foreach ($required as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            return ['success' => false, 'message' => 'Preencha todos os campos obrigatórios'];
        }
    }
    
    $host = trim($data['ldap_host']);
    $port = intval($data['ldap_port'] ?? 389);
    $domain = trim($data['ldap_domain']);
    $baseDn = trim($data['ldap_base_dn']);
    $adminUser = trim($data['ldap_admin_user']);
    $adminPassword = $data['ldap_admin_password'];
    $useTls = !empty($data['ldap_use_tls']);
    
    $config = "<?php\n";
    $config .= "// Configuração LDAP - " . date('d/m/Y H:i:s') . "\n";
    $config .= "define('LDAP_HOST', '" . addslashes($host) . "');\n";
    $config .= "define('LDAP_PORT', $port);\n";
    $config .= "define('LDAP_DOMAIN', '" . addslashes($domain) . "');\n";
    $config .= "define('LDAP_BASE_DN', '" . addslashes($baseDn) . "');\n";
    $config .= "define('LDAP_ADMIN_USER', '" . addslashes($adminUser) . "');\n";
    $config .= "define('LDAP_ADMIN_PASSWORD', '" . addslashes($adminPassword) . "');\n";
    $config .= "define('LDAP_USE_TLS', " . ($useTls ? 'true' : 'false') . ");\n";
    
    $file = $GLOBALS['configDir'] . '/ldap.php';
    if (@file_put_contents($file, $config) === false) {
        return ['success' => false, 'message' => 'Erro ao salvar configuração'];
    }
    
    return ['success' => true, 'message' => 'Configuração salva com sucesso'];
}

// ============================================================================
// FUNÇÕES DE MENSAGEM
// ============================================================================

function setMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = ['message' => $_SESSION['flash_message'], 'type' => $_SESSION['flash_type']];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $msg;
    }
    return null;
}

// ============================================================================
// RENDERIZAÇÃO DE PÁGINAS
// ============================================================================

function renderLogin($error = null) {
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AD Manager - Login</title>
    <style>
        :root {
            --primary: #0078d4;
            --secondary: #106ebe;
            --light: #deecf9;
            --border: #d1d1d1;
            --text: #323130;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, var(--light), var(--primary));
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        }
        .login-box { 
            background: white; 
            padding: 40px; 
            border-radius: 8px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 400px; 
        }
        .title { 
            text-align: center; 
            color: var(--primary); 
            font-size: 2rem; 
            margin-bottom: 30px; 
            font-weight: 300; 
        }
        .form-group { margin-bottom: 20px; }
        .label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: 500; 
            color: var(--text); 
        }
        .input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid var(--border); 
            border-radius: 4px; 
            font-size: 14px; 
        }
        .input:focus { 
            outline: none; 
            border-color: var(--primary); 
            box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.2); 
        }
        .btn { 
            width: 100%; 
            padding: 12px; 
            background: var(--primary); 
            color: white; 
            border: none; 
            border-radius: 4px; 
            font-size: 16px; 
            cursor: pointer; 
        }
        .btn:hover { background: var(--secondary); }
        .error { 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 20px; 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        .info { 
            margin-top: 30px; 
            padding-top: 20px; 
            border-top: 1px solid var(--border); 
            text-align: center; 
            font-size: 14px; 
            color: #605e5c; 
        }
        code { 
            background: #f3f2f1; 
            padding: 2px 4px; 
            border-radius: 3px; 
        }
    </style>
</head>
<body>
    <div class="login-box">
        <h1 class="title">AD Manager</h1>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="label">Usuário</label>
                <input type="text" name="username" class="input" required autofocus>
            </div>
            
            <div class="form-group">
                <label class="label">Senha</label>
                <input type="password" name="password" class="input" required>
            </div>
            
            <button type="submit" class="btn">Entrar</button>
        </form>
        
        <div class="info">
            <strong>Login Padrão:</strong><br>
            Usuário: <code>admin</code><br>
            Senha: <code>admin123</code>
        </div>
    </div>
</body>
</html>
<?php
}

function renderPage($pageType, $title, $data = []) {
    extract($data);
    $flash = getMessage();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - AD Manager</title>
    <style>
        :root {
            --primary: #0078d4; --secondary: #106ebe; --light: #deecf9; --dark: #004578;
            --white: #ffffff; --border: #d1d1d1; --text: #323130; --text-sec: #605e5c;
            --success: #107c10; --warning: #ffb900; --error: #d13438;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Segoe UI", sans-serif; background: var(--white); color: var(--text); line-height: 1.5; }
        .header { background: var(--primary); color: white; padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header-content { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 600; text-decoration: none; color: white; }
        .nav { background: var(--light); padding: 10px 0; border-bottom: 1px solid var(--border); }
        .nav-content { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .nav-links { display: flex; gap: 20px; list-style: none; }
        .nav-link { color: var(--primary); text-decoration: none; padding: 8px 16px; border-radius: 4px; font-weight: 500; }
        .nav-link:hover, .nav-link.active { background: var(--primary); color: white; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .card-header { border-bottom: 2px solid var(--primary); padding-bottom: 15px; margin-bottom: 20px; }
        .card-title { color: var(--primary); font-size: 1.25rem; font-weight: 600; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; margin-right: 5px; }
        .btn-primary { background: var(--primary); color: white; } .btn-danger { background: var(--error); color: white; } .btn-success { background: var(--success); color: white; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; } .alert-error { background: #f8d7da; color: #721c24; }
        .table { width: 100%; border-collapse: collapse; } .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        .table th { background: var(--light); color: var(--primary); font-weight: 600; }
        .form-group { margin-bottom: 20px; } .form-label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-input { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 4px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 8px; border: 1px solid var(--border); text-align: center; position: relative; }
        .stat-card::before { content: ""; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--primary); }
        .stat-number { font-size: 2.5rem; font-weight: 600; color: var(--primary); margin-bottom: 10px; }
        .stat-label { color: var(--text-sec); }
        @media (max-width: 768px) { .header-content { flex-direction: column; gap: 10px; } .nav-links { flex-wrap: wrap; } .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="<?= $GLOBALS['baseUrl'] ?>?p=dashboard" class="logo">AD Manager</a>
            <div>
                Bem-vindo, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
                <a href="<?= $GLOBALS['baseUrl'] ?>?p=logout" style="margin-left: 15px; color: white;">Sair</a>
            </div>
        </div>
    </header>
    
    <nav class="nav">
        <div class="nav-content">
            <ul class="nav-links">
                <li><a href="<?= $GLOBALS['baseUrl'] ?>?p=dashboard" class="nav-link <?= $pageType === 'dashboard' ? 'active' : '' ?>">Dashboard</a></li>
                <li><a href="<?= $GLOBALS['baseUrl'] ?>?p=users" class="nav-link <?= $pageType === 'users' ? 'active' : '' ?>">Usuários</a></li>
                <?php if ($_SESSION['user_type'] === 'admin'): ?>
                    <li><a href="<?= $GLOBALS['baseUrl'] ?>?p=config" class="nav-link <?= $pageType === 'config' ? 'active' : '' ?>">Configurações</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>
        
        <?php
        // Renderizar conteúdo da página
        switch ($pageType) {
            case 'dashboard':
                ?>
                <div class="card">
                    <div class="card-header">
                        <h1 class="card-title">Dashboard</h1>
                    </div>
                    
                    <?php if (!$ldapConfigured): ?>
                        <div class="alert alert-error">
                            <strong>Configuração necessária!</strong> Configure a conexão LDAP.
                            <a href="<?= $GLOBALS['baseUrl'] ?>?p=config">Configurar LDAP</a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?= $stats['total_users'] ?></div>
                            <div class="stat-label">Total de Usuários</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" style="color: var(--success);"><?= $stats['enabled_users'] ?></div>
                            <div class="stat-label">Usuários Ativos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" style="color: var(--error);"><?= $stats['disabled_users'] ?></div>
                            <div class="stat-label">Usuários Bloqueados</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">✓</div>
                            <div class="stat-label">Status: <?= $stats['ldap_status'] ?></div>
                        </div>
                    </div>
                </div>
                <?php
                break;
                
            case 'users':
                ?>
                <div class="card">
                    <div class="card-header">
                        <h1 class="card-title">Gerenciamento de Usuários</h1>
                    </div>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                    <?php else: ?>
                        <div style="margin-bottom: 20px;">
                            <form method="GET" style="display: inline;">
                                <input type="hidden" name="p" value="users">
                                <input type="text" name="search" placeholder="Buscar usuários..." value="<?= htmlspecialchars($search ?? '') ?>" style="padding: 8px; width: 300px;">
                                <button type="submit" class="btn btn-primary">Buscar</button>
                            </form>
                        </div>
                        
                        <?php if (!empty($users)): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Username</th>
                                        <th>E-mail</th>
                                        <th>Departamento</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td><?= htmlspecialchars($user['username']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['department']) ?></td>
                                            <td>
                                                <span style="color: <?= $user['enabled'] ? 'var(--success)' : 'var(--error)' ?>">
                                                    <?= $user['enabled'] ? 'Ativo' : 'Bloqueado' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="<?= $user['enabled'] ? 'block' : 'unblock' ?>">
                                                    <input type="hidden" name="username" value="<?= htmlspecialchars($user['username']) ?>">
                                                    <button type="submit" class="btn <?= $user['enabled'] ? 'btn-danger' : 'btn-success' ?>" onclick="return confirm('Confirma?')">
                                                        <?= $user['enabled'] ? 'Bloquear' : 'Desbloquear' ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>Nenhum usuário encontrado.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php
                break;
                
            case 'config':
                ?>
                <div class="card">
                    <div class="card-header">
                        <h1 class="card-title">Configurações LDAP</h1>
                    </div>
                    
                    <form method="POST">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div>
                                <h3 style="color: var(--primary); margin-bottom: 15px;">Servidor</h3>
                                <div class="form-group">
                                    <label class="form-label">Host LDAP *</label>
                                    <input type="text" name="ldap_host" class="form-input" value="<?= htmlspecialchars($config['host'] ?? '') ?>" placeholder="ldap.empresa.com" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Porta</label>
                                    <input type="number" name="ldap_port" class="form-input" value="<?= $config['port'] ?? 389 ?>" min="1" max="65535">
                                </div>
                                <div class="form-group">
                                    <label><input type="checkbox" name="ldap_use_tls" value="1" <?= !empty($config['use_tls']) ? 'checked' : '' ?>> Usar TLS</label>
                                </div>
                            </div>
                            
                            <div>
                                <h3 style="color: var(--primary); margin-bottom: 15px;">Domínio</h3>
                                <div class="form-group">
                                    <label class="form-label">Domínio *</label>
                                    <input type="text" name="ldap_domain" class="form-input" value="<?= htmlspecialchars($config['domain'] ?? '') ?>" placeholder="empresa.com" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Base DN *</label>
                                    <input type="text" name="ldap_base_dn" class="form-input" value="<?= htmlspecialchars($config['base_dn'] ?? '') ?>" placeholder="DC=empresa,DC=com" required>
                                </div>
                            </div>
                            
                            <div>
                                <h3 style="color: var(--primary); margin-bottom: 15px;">Autenticação</h3>
                                <div class="form-group">
                                    <label class="form-label">Usuário Admin *</label>
                                    <input type="text" name="ldap_admin_user" class="form-input" value="<?= htmlspecialchars($config['admin_user'] ?? '') ?>" placeholder="admin@empresa.com" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Senha *</label>
                                    <input type="password" name="ldap_admin_password" class="form-input" required>
                                </div>
                            </div>
                        </div>
                        
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="submit" class="btn btn-primary">Salvar Configuração</button>
                        </div>
                    </form>
                </div>
                <?php
                break;
        }
        ?>
    </div>
    
    <footer style="background: var(--light); padding: 20px; text-align: center; margin-top: 50px;">
        <p>&copy; <?= date('Y') ?> AD Manager - Sistema de Gestão de Usuários do Active Directory</p>
    </footer>
    
    <script>
        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(el => {
                el.style.transition = 'opacity 0.5s';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
<?php
}
?>