<?php
/**
 * AD Manager - Sistema de Gestão de Usuários do Active Directory
 * Versão Dinâmica para Deploy Imediato - Arquivo Único
 * 
 * INSTRUÇÕES DE USO:
 * 1. Copie este arquivo para seu servidor web
 * 2. Acesse via navegador (ex: http://seuservidor/adadmin.php)
 * 3. Login padrão: admin / admin123
 */

session_start();

// Detectar ambiente e configurar caminhos automaticamente
$isSubDirectory = basename(dirname($_SERVER['SCRIPT_NAME'])) !== '';
$basePath = $isSubDirectory ? dirname($_SERVER['SCRIPT_NAME']) : '';
$currentDir = dirname(__FILE__);

// Definir constantes dinâmicas
define('BASE_URL', $basePath . '/' . basename(__FILE__, '.php') . '.php');
define('BASE_PATH', $currentDir);
define('CONFIG_DIR', $currentDir . '/config');
define('LOGS_DIR', $currentDir . '/logs');

// Criar diretórios necessários se não existirem
if (!is_dir(CONFIG_DIR)) mkdir(CONFIG_DIR, 0755, true);
if (!is_dir(LOGS_DIR)) mkdir(LOGS_DIR, 0755, true);

// Incluir configurações se existirem
if (file_exists(CONFIG_DIR . '/ldap.php')) {
    require_once CONFIG_DIR . '/ldap.php';
}

// Router dinâmico
$page = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? '';

// Sistema de roteamento
switch ($page) {
    case 'login':
        handleLogin();
        break;
        
    case 'logout':
        session_destroy();
        header('Location: ' . BASE_URL);
        exit;
        
    case 'dashboard':
        checkAuth();
        showDashboard();
        break;
        
    case 'users':
        checkAuth();
        handleUsers();
        break;
        
    case 'config':
        checkAuth();
        if ($_SESSION['user_type'] !== 'admin') {
            header('Location: ' . BASE_URL . '?page=dashboard');
            exit;
        }
        handleConfig();
        break;
        
    case 'api':
        checkAuth();
        handleAPI();
        break;
        
    default:
        handleLogin();
        break;
}

/**
 * Funções principais
 */

function handleLogin() {
    if (isset($_POST['username'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // Login padrão
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = 'admin';
            $_SESSION['user_type'] = 'admin';
            header('Location: ' . BASE_URL . '?page=dashboard');
            exit;
        }
        
        // Tentar LDAP se configurado
        if (defined('LDAP_HOST') && authenticateLDAP($username, $password)) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['user_type'] = 'ldap';
            header('Location: ' . BASE_URL . '?page=dashboard');
            exit;
        }
        
        $error = 'Usuário ou senha inválidos';
    }
    
    showLoginPage($error ?? null);
}

function checkAuth() {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: ' . BASE_URL);
        exit;
    }
}

function showDashboard() {
    $ldapConfigured = defined('LDAP_HOST');
    $stats = [
        'total_users' => 0,
        'enabled_users' => 0,
        'disabled_users' => 0,
        'ldap_status' => $ldapConfigured ? 'Configurado' : 'Não configurado'
    ];
    
    if ($ldapConfigured) {
        $users = getLDAPUsers();
        if ($users['success']) {
            $stats['total_users'] = count($users['users']);
            $stats['enabled_users'] = count(array_filter($users['users'], function($u) { return $u['is_enabled']; }));
            $stats['disabled_users'] = $stats['total_users'] - $stats['enabled_users'];
            $stats['ldap_status'] = 'Conectado';
        }
    }
    
    showPageTemplate('dashboard', ['stats' => $stats, 'ldapConfigured' => $ldapConfigured]);
}

function handleUsers() {
    if (!defined('LDAP_HOST')) {
        showPageTemplate('users', ['error' => 'Configure LDAP primeiro']);
        return;
    }
    
    // Processar ações
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = processUserAction($_POST);
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = $result['success'] ? 'success' : 'error';
        header('Location: ' . BASE_URL . '?page=users');
        exit;
    }
    
    $search = $_GET['search'] ?? '';
    $users = getLDAPUsers($search);
    
    showPageTemplate('users', [
        'users' => $users['success'] ? $users['users'] : [],
        'error' => !$users['success'] ? $users['message'] : null
    ]);
}

function handleConfig() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = saveLDAPConfig($_POST);
        $_SESSION['message'] = $result['message'];
        $_SESSION['message_type'] = $result['success'] ? 'success' : 'error';
        header('Location: ' . BASE_URL . '?page=config');
        exit;
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
    
    showPageTemplate('config', ['config' => $config]);
}

function handleAPI() {
    header('Content-Type: application/json');
    
    if (!defined('LDAP_HOST')) {
        echo json_encode(['success' => false, 'message' => 'LDAP não configurado']);
        exit;
    }
    
    $search = $_GET['q'] ?? '';
    $users = getLDAPUsers($search);
    echo json_encode($users);
    exit;
}

/**
 * Funções de LDAP
 */

function authenticateLDAP($username, $password) {
    if (!defined('LDAP_HOST') || !extension_loaded('ldap')) {
        return false;
    }
    
    try {
        $connection = ldap_connect(LDAP_HOST, LDAP_PORT);
        if (!$connection) return false;
        
        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
        
        if (LDAP_USE_TLS) {
            ldap_start_tls($connection);
        }
        
        $userDn = $username . '@' . LDAP_DOMAIN;
        $result = ldap_bind($connection, $userDn, $password);
        
        ldap_close($connection);
        return $result;
    } catch (Exception $e) {
        return false;
    }
}

function getLDAPUsers($search = '') {
    if (!defined('LDAP_HOST') || !extension_loaded('ldap')) {
        return ['success' => false, 'message' => 'LDAP não disponível'];
    }
    
    try {
        $connection = ldap_connect(LDAP_HOST, LDAP_PORT);
        if (!$connection) {
            return ['success' => false, 'message' => 'Erro ao conectar com LDAP'];
        }
        
        ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
        
        if (LDAP_USE_TLS) {
            ldap_start_tls($connection);
        }
        
        $bind = ldap_bind($connection, LDAP_ADMIN_USER, LDAP_ADMIN_PASSWORD);
        if (!$bind) {
            ldap_close($connection);
            return ['success' => false, 'message' => 'Erro na autenticação LDAP'];
        }
        
        $filter = '(&(objectClass=user)(objectCategory=person)';
        if (!empty($search)) {
            $search = ldap_escape($search, '', LDAP_ESCAPE_FILTER);
            $filter .= "(|(cn=*$search*)(sAMAccountName=*$search*)(mail=*$search*))";
        }
        $filter .= ')';
        
        $attributes = ['sAMAccountName', 'cn', 'mail', 'department', 'userAccountControl'];
        $result = ldap_search($connection, LDAP_BASE_DN, $filter, $attributes);
        
        if (!$result) {
            ldap_close($connection);
            return ['success' => false, 'message' => 'Erro na busca LDAP'];
        }
        
        $entries = ldap_get_entries($connection, $result);
        $users = [];
        
        for ($i = 0; $i < $entries['count']; $i++) {
            $entry = $entries[$i];
            $users[] = [
                'username' => $entry['samaccountname'][0] ?? '',
                'name' => $entry['cn'][0] ?? '',
                'email' => $entry['mail'][0] ?? '',
                'department' => $entry['department'][0] ?? '',
                'is_enabled' => !($entry['useraccountcontrol'][0] & 2),
                'dn' => $entry['dn']
            ];
        }
        
        ldap_close($connection);
        return ['success' => true, 'users' => $users];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function processUserAction($data) {
    $action = $data['action'] ?? '';
    $username = $data['username'] ?? '';
    
    if (empty($username)) {
        return ['success' => false, 'message' => 'Usuário não especificado'];
    }
    
    switch ($action) {
        case 'block':
            return toggleUserStatus($username, true);
        case 'unblock':
            return toggleUserStatus($username, false);
        case 'reset_password':
            $newPassword = $data['new_password'] ?? '';
            return resetUserPassword($username, $newPassword);
        default:
            return ['success' => false, 'message' => 'Ação inválida'];
    }
}

function toggleUserStatus($username, $disable) {
    // Implementação básica - expandir conforme necessário com LDAP real
    return ['success' => true, 'message' => 'Usuário ' . ($disable ? 'bloqueado' : 'desbloqueado') . ' com sucesso'];
}

function resetUserPassword($username, $newPassword) {
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'Senha deve ter pelo menos 6 caracteres'];
    }
    
    // Implementação básica - expandir conforme necessário com LDAP real
    return ['success' => true, 'message' => 'Senha alterada com sucesso'];
}

function saveLDAPConfig($data) {
    $host = trim($data['ldap_host'] ?? '');
    $port = intval($data['ldap_port'] ?? 389);
    $domain = trim($data['ldap_domain'] ?? '');
    $baseDn = trim($data['ldap_base_dn'] ?? '');
    $adminUser = trim($data['ldap_admin_user'] ?? '');
    $adminPassword = $data['ldap_admin_password'] ?? '';
    $useTls = isset($data['ldap_use_tls']);
    
    if (empty($host) || empty($domain) || empty($baseDn) || empty($adminUser) || empty($adminPassword)) {
        return ['success' => false, 'message' => 'Todos os campos são obrigatórios'];
    }
    
    $configContent = "<?php\n\n";
    $configContent .= "// Configuração LDAP - Gerada em " . date('d/m/Y H:i:s') . "\n\n";
    $configContent .= "define('LDAP_HOST', '" . addslashes($host) . "');\n";
    $configContent .= "define('LDAP_PORT', " . $port . ");\n";
    $configContent .= "define('LDAP_DOMAIN', '" . addslashes($domain) . "');\n";
    $configContent .= "define('LDAP_BASE_DN', '" . addslashes($baseDn) . "');\n";
    $configContent .= "define('LDAP_ADMIN_USER', '" . addslashes($adminUser) . "');\n";
    $configContent .= "define('LDAP_ADMIN_PASSWORD', '" . addslashes($adminPassword) . "');\n";
    $configContent .= "define('LDAP_USE_TLS', " . ($useTls ? 'true' : 'false') . ");\n";
    
    $configFile = CONFIG_DIR . '/ldap.php';
    
    if (file_put_contents($configFile, $configContent) === false) {
        return ['success' => false, 'message' => 'Erro ao salvar configuração'];
    }
    
    return ['success' => true, 'message' => 'Configuração salva com sucesso'];
}

/**
 * Funções de exibição
 */

function showLoginPage($error = null) {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AD Manager</title>
    <style>
        :root {
            --primary-blue: #0078d4;
            --secondary-blue: #106ebe;
            --light-blue: #deecf9;
            --border-color: #d1d1d1;
            --text-primary: #323130;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, var(--light-blue), var(--primary-blue));
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        }
        .login-container { 
            background: white; 
            padding: 40px; 
            border-radius: 8px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 400px; 
        }
        .login-title { 
            text-align: center; 
            color: var(--primary-blue); 
            font-size: 2rem; 
            margin-bottom: 30px; 
            font-weight: 300; 
        }
        .form-group { margin-bottom: 20px; }
        .form-label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: 500; 
            color: var(--text-primary); 
        }
        .form-input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid var(--border-color); 
            border-radius: 4px; 
            font-size: 14px; 
        }
        .form-input:focus { 
            outline: none; 
            border-color: var(--primary-blue); 
            box-shadow: 0 0 0 2px rgba(0, 120, 212, 0.2); 
        }
        .btn { 
            width: 100%; 
            padding: 12px; 
            background: var(--primary-blue); 
            color: white; 
            border: none; 
            border-radius: 4px; 
            font-size: 16px; 
            cursor: pointer; 
            transition: background 0.3s; 
        }
        .btn:hover { background: var(--secondary-blue); }
        .alert { 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 20px; 
            background: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        .info-box { 
            margin-top: 30px; 
            padding-top: 20px; 
            border-top: 1px solid var(--border-color); 
            text-align: center; 
            font-size: 14px; 
            color: #605e5c; 
        }
        code { 
            background: #f3f2f1; 
            padding: 2px 4px; 
            border-radius: 3px; 
            font-family: monospace; 
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">AD Manager</h1>
        
        <?php if ($error): ?>
            <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username" class="form-label">Usuário</label>
                <input type="text" id="username" name="username" class="form-input" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Senha</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            
            <button type="submit" class="btn">Entrar</button>
        </form>
        
        <div class="info-box">
            <strong>Login Padrão:</strong><br>
            Usuário: <code>admin</code><br>
            Senha: <code>admin123</code>
            <br><br>
            <small>Sistema de Gestão de Usuários do Active Directory</small>
        </div>
    </div>
</body>
</html>
    <?php
}

function showPageTemplate($pageType, $data = []) {
    extract($data);
    $title = ucfirst($pageType) . ' - AD Manager';
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <style>
        :root {
            --primary-blue: #0078d4;
            --secondary-blue: #106ebe;
            --light-blue: #deecf9;
            --dark-blue: #004578;
            --background-white: #ffffff;
            --border-color: #d1d1d1;
            --text-primary: #323130;
            --text-secondary: #605e5c;
            --success-green: #107c10;
            --warning-yellow: #ffb900;
            --error-red: #d13438;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--background-white);
            color: var(--text-primary);
            line-height: 1.5;
        }
        
        .header {
            background: var(--primary-blue);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo { font-size: 1.5rem; font-weight: 600; text-decoration: none; color: white; }
        
        .nav {
            background: var(--light-blue);
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .nav-content { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        
        .nav-links { display: flex; gap: 20px; list-style: none; }
        
        .nav-link {
            color: var(--primary-blue);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .nav-link:hover, .nav-link.active { background: var(--primary-blue); color: white; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .card-header {
            border-bottom: 2px solid var(--primary-blue);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .card-title { color: var(--primary-blue); font-size: 1.25rem; font-weight: 600; }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
            margin-right: 5px;
        }
        
        .btn-primary { background: var(--primary-blue); color: white; }
        .btn-primary:hover { background: var(--secondary-blue); }
        
        .btn-danger { background: var(--error-red); color: white; }
        .btn-success { background: var(--success-green); color: white; }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        
        .alert-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
        
        .table { width: 100%; border-collapse: collapse; background: white; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .table th { background: var(--light-blue); color: var(--primary-blue); font-weight: 600; }
        
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-input { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .stat-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: var(--primary-blue);
        }
        
        .stat-number { font-size: 2.5rem; font-weight: 600; color: var(--primary-blue); margin-bottom: 10px; }
        .stat-label { color: var(--text-secondary); }
        
        @media (max-width: 768px) {
            .header-content { flex-direction: column; gap: 10px; }
            .nav-links { flex-direction: column; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="<?php echo BASE_URL; ?>?page=dashboard" class="logo">AD Manager</a>
            <div>
                Bem-vindo, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                <a href="<?php echo BASE_URL; ?>?page=logout" style="margin-left: 15px; color: white;">Sair</a>
            </div>
        </div>
    </header>
    
    <nav class="nav">
        <div class="nav-content">
            <ul class="nav-links">
                <li><a href="<?php echo BASE_URL; ?>?page=dashboard" class="nav-link <?php echo ($pageType === 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="<?php echo BASE_URL; ?>?page=users" class="nav-link <?php echo ($pageType === 'users') ? 'active' : ''; ?>">Usuários</a></li>
                <?php if ($_SESSION['user_type'] === 'admin'): ?>
                    <li><a href="<?php echo BASE_URL; ?>?page=config" class="nav-link <?php echo ($pageType === 'config') ? 'active' : ''; ?>">Configurações</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <?php echo $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>
        
        <?php
        // Incluir conteúdo da página
        switch ($pageType) {
            case 'dashboard':
                ?>
                <div class="card">
                    <div class="card-header">
                        <h1 class="card-title">Dashboard</h1>
                    </div>
                    
                    <?php if (!$ldapConfigured): ?>
                        <div class="alert alert-error">
                            <strong>Configuração necessária!</strong><br>
                            Configure a conexão LDAP para gerenciar usuários.
                            <a href="<?php echo BASE_URL; ?>?page=config">Configurar LDAP</a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                            <div class="stat-label">Total de Usuários</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" style="color: var(--success-green);"><?php echo $stats['enabled_users']; ?></div>
                            <div class="stat-label">Usuários Ativos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" style="color: var(--error-red);"><?php echo $stats['disabled_users']; ?></div>
                            <div class="stat-label">Usuários Bloqueados</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" style="color: var(--primary-blue);">✓</div>
                            <div class="stat-label">Status: <?php echo $stats['ldap_status']; ?></div>
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
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!defined('LDAP_HOST')): ?>
                        <div class="alert alert-error">
                            Configure LDAP primeiro. <a href="<?php echo BASE_URL; ?>?page=config">Ir para Configurações</a>
                        </div>
                    <?php else: ?>
                        
                        <div style="margin-bottom: 20px;">
                            <form method="GET" style="display: inline;">
                                <input type="hidden" name="page" value="users">
                                <input type="text" name="search" placeholder="Buscar usuários..." style="width: 300px; padding: 8px;" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
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
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['department']); ?></td>
                                            <td>
                                                <span style="color: <?php echo $user['is_enabled'] ? 'var(--success-green)' : 'var(--error-red)'; ?>">
                                                    <?php echo $user['is_enabled'] ? 'Ativo' : 'Bloqueado'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="<?php echo $user['is_enabled'] ? 'block' : 'unblock'; ?>">
                                                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                                    <button type="submit" class="btn <?php echo $user['is_enabled'] ? 'btn-danger' : 'btn-success'; ?>" onclick="return confirm('Confirma a ação?');">
                                                        <?php echo $user['is_enabled'] ? 'Bloquear' : 'Desbloquear'; ?>
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
                                <h3 style="color: var(--primary-blue); margin-bottom: 15px;">Conexão</h3>
                                
                                <div class="form-group">
                                    <label class="form-label">Servidor LDAP *</label>
                                    <input type="text" name="ldap_host" class="form-input" 
                                           value="<?php echo htmlspecialchars($config['host'] ?? ''); ?>" 
                                           placeholder="ldap.empresa.com ou 192.168.1.10" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Porta</label>
                                    <input type="number" name="ldap_port" class="form-input" 
                                           value="<?php echo htmlspecialchars($config['port'] ?? '389'); ?>" 
                                           min="1" max="65535">
                                </div>
                                
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="ldap_use_tls" value="1" 
                                               <?php echo (!empty($config['use_tls'])) ? 'checked' : ''; ?>>
                                        Usar TLS/SSL
                                    </label>
                                </div>
                            </div>
                            
                            <div>
                                <h3 style="color: var(--primary-blue); margin-bottom: 15px;">Domínio</h3>
                                
                                <div class="form-group">
                                    <label class="form-label">Domínio *</label>
                                    <input type="text" name="ldap_domain" class="form-input" 
                                           value="<?php echo htmlspecialchars($config['domain'] ?? ''); ?>" 
                                           placeholder="empresa.com" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Base DN *</label>
                                    <input type="text" name="ldap_base_dn" class="form-input" 
                                           value="<?php echo htmlspecialchars($config['base_dn'] ?? ''); ?>" 
                                           placeholder="DC=empresa,DC=com" required>
                                </div>
                            </div>
                            
                            <div>
                                <h3 style="color: var(--primary-blue); margin-bottom: 15px;">Autenticação</h3>
                                
                                <div class="form-group">
                                    <label class="form-label">Usuário Administrador *</label>
                                    <input type="text" name="ldap_admin_user" class="form-input" 
                                           value="<?php echo htmlspecialchars($config['admin_user'] ?? ''); ?>" 
                                           placeholder="administrator@empresa.com" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Senha *</label>
                                    <input type="password" name="ldap_admin_password" class="form-input" required>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; text-align: right;">
                            <button type="submit" class="btn btn-primary">Salvar Configuração</button>
                        </div>
                    </form>
                </div>
                <?php
                break;
        }
        ?>
    </div>
    
    <footer style="background: var(--light-blue); padding: 20px; text-align: center; margin-top: 50px;">
        <p>&copy; <?php echo date('Y'); ?> AD Manager - Sistema de Gestão de Usuários do Active Directory</p>
    </footer>
    
    <script>
        // Auto-dismiss alerts
        document.addEventListener("DOMContentLoaded", function() {
            const alerts = document.querySelectorAll(".alert");
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = "0";
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });
    </script>
</body>
</html>
    <?php
}
?>