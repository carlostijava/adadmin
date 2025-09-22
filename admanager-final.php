<?php
/**
 * AD Manager - Sistema de Gerenciamento Active Directory
 * Versão Final Ultra-Clean para XAMPP
 * Todos os nomes de função únicos para evitar conflitos
 */

// Inicialização de sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuração de erro para desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurações globais
define('AD_CONFIG_FILE', 'config/ad_config.json');
define('USERS_CONFIG_FILE', 'config/users.json');

/**
 * Função principal de renderização de página
 */
function renderPageContent($title, $content) {
    return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #0078d4, #005a9e); color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .nav { display: flex; gap: 15px; flex-wrap: wrap; }
        .nav a { color: white; text-decoration: none; padding: 8px 16px; background: rgba(255,255,255,0.2); border-radius: 4px; transition: all 0.3s ease; }
        .nav a:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); }
        .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #0078d4; }
        .card h2 { color: #0078d4; margin-bottom: 15px; font-size: 24px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #e1e5e9; border-radius: 4px; font-size: 14px; transition: border-color 0.3s ease; }
        .form-control:focus { outline: none; border-color: #0078d4; box-shadow: 0 0 0 3px rgba(0,120,212,0.1); }
        .btn { display: inline-block; padding: 12px 24px; background: #0078d4; color: white; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s ease; }
        .btn:hover { background: #005a9e; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .btn-danger { background: #d13438; }
        .btn-danger:hover { background: #a1262a; }
        .btn-success { background: #107c10; }
        .btn-success:hover { background: #0e6e0e; }
        .btn-warning { background: #ff8c00; }
        .btn-warning:hover { background: #e67c00; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #cce7f0; color: #0c5460; border: 1px solid #b6d7e2; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        .table th { background: #f8f9fa; font-weight: 600; color: #333; border-top: 2px solid #0078d4; }
        .table tr:hover { background: #f8f9fa; }
        .status-active { color: #107c10; font-weight: 600; }
        .status-blocked { color: #d13438; font-weight: 600; }
        .user-actions { display: flex; gap: 5px; }
        .user-actions .btn { padding: 6px 12px; font-size: 12px; }
        .config-section { margin-bottom: 30px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .stats { display: flex; justify-content: space-around; margin-bottom: 30px; }
        .stat-card { text-align: center; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-number { font-size: 36px; font-weight: bold; color: #0078d4; }
        .stat-label { color: #666; margin-top: 5px; }
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .nav { flex-direction: column; }
            .stats { flex-direction: column; gap: 15px; }
            .user-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 AD Manager</h1>
            <nav class="nav">
                <a href="?page=dashboard">🏠 Dashboard</a>
                <a href="?page=users">👥 Usuários</a>
                <a href="?page=config">⚙️ Configuração</a>
                <a href="?page=logout">🚪 Sair</a>
            </nav>
        </div>
        ' . $content . '
    </div>
</body>
</html>';
}

/**
 * Função de validação de login
 */
function validateUserLogin($username, $password) {
    // Login padrão admin/admin123
    if ($username === 'admin' && $password === 'admin123') {
        return true;
    }
    
    // Verificar usuários configurados
    if (file_exists(USERS_CONFIG_FILE)) {
        $users = json_decode(file_get_contents(USERS_CONFIG_FILE), true);
        if (isset($users[$username]) && $users[$username] === md5($password)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Função para verificar se usuário está logado
 */
function checkUserAuthentication() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Função para carregar configuração AD
 */
function loadADConfiguration() {
    if (!file_exists(AD_CONFIG_FILE)) {
        return [
            'server' => 'localhost',
            'port' => '389',
            'base_dn' => 'DC=exemplo,DC=com',
            'admin_dn' => 'CN=admin,DC=exemplo,DC=com',
            'admin_password' => '',
            'user_filter' => '(&(objectClass=user)(objectCategory=person))'
        ];
    }
    return json_decode(file_get_contents(AD_CONFIG_FILE), true);
}

/**
 * Função para salvar configuração AD
 */
function saveADConfiguration($config) {
    $dir = dirname(AD_CONFIG_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents(AD_CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT));
}

/**
 * Função para conectar ao LDAP
 */
function connectToLDAP($config) {
    if (!extension_loaded('ldap')) {
        return ['error' => 'Extensão LDAP não está instalada no PHP'];
    }
    
    $ldap_url = "ldap://{$config['server']}:{$config['port']}";
    $connection = ldap_connect($ldap_url);
    
    if (!$connection) {
        return ['error' => 'Não foi possível conectar ao servidor LDAP'];
    }
    
    ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
    
    if (!empty($config['admin_dn']) && !empty($config['admin_password'])) {
        $bind = ldap_bind($connection, $config['admin_dn'], $config['admin_password']);
        if (!$bind) {
            ldap_close($connection);
            return ['error' => 'Falha na autenticação LDAP: ' . ldap_error($connection)];
        }
    }
    
    return ['connection' => $connection];
}

/**
 * Função para listar usuários do AD
 */
function listADUsers($config) {
    $result = connectToLDAP($config);
    if (isset($result['error'])) {
        return $result;
    }
    
    $connection = $result['connection'];
    $search = ldap_search($connection, $config['base_dn'], $config['user_filter'], 
        ['cn', 'sAMAccountName', 'mail', 'userAccountControl', 'whenCreated', 'lastLogon']);
    
    if (!$search) {
        ldap_close($connection);
        return ['error' => 'Erro na busca LDAP: ' . ldap_error($connection)];
    }
    
    $entries = ldap_get_entries($connection, $search);
    $users = [];
    
    for ($i = 0; $i < $entries['count']; $i++) {
        $entry = $entries[$i];
        $users[] = [
            'cn' => $entry['cn'][0] ?? 'N/A',
            'username' => $entry['samaccountname'][0] ?? 'N/A',
            'email' => $entry['mail'][0] ?? 'N/A',
            'status' => (isset($entry['useraccountcontrol'][0]) && ($entry['useraccountcontrol'][0] & 2)) ? 'Bloqueado' : 'Ativo',
            'created' => $entry['whencreated'][0] ?? 'N/A',
            'last_logon' => $entry['lastlogon'][0] ?? 'N/A'
        ];
    }
    
    ldap_close($connection);
    return ['users' => $users];
}

/**
 * Função para gerar página de login
 */
function generateLoginPage($error = '') {
    $errorMsg = $error ? '<div class="alert alert-error">' . htmlspecialchars($error) . '</div>' : '';
    
    return renderPageContent('Login - AD Manager', '
        <div class="card" style="max-width: 400px; margin: 50px auto;">
            <h2>🔐 Login do Sistema</h2>
            ' . $errorMsg . '
            <form method="POST" action="">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="username">Usuário:</label>
                    <input type="text" id="username" name="username" class="form-control" required 
                           placeholder="Digite seu usuário" autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Senha:</label>
                    <input type="password" id="password" name="password" class="form-control" required 
                           placeholder="Digite sua senha" autocomplete="current-password">
                </div>
                <button type="submit" class="btn">🔑 Entrar</button>
            </form>
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                <strong>Login Padrão:</strong><br>
                Usuário: <code>admin</code><br>
                Senha: <code>admin123</code>
            </div>
        </div>
    ');
}

/**
 * Função para gerar dashboard
 */
function generateDashboardPage() {
    $config = loadADConfiguration();
    $userCount = 0;
    $activeCount = 0;
    $blockedCount = 0;
    
    $result = listADUsers($config);
    if (isset($result['users'])) {
        $userCount = count($result['users']);
        foreach ($result['users'] as $user) {
            if ($user['status'] === 'Ativo') {
                $activeCount++;
            } else {
                $blockedCount++;
            }
        }
    }
    
    return renderPageContent('Dashboard - AD Manager', '
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number">' . $userCount . '</div>
                <div class="stat-label">Total de Usuários</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #107c10;">' . $activeCount . '</div>
                <div class="stat-label">Usuários Ativos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #d13438;">' . $blockedCount . '</div>
                <div class="stat-label">Usuários Bloqueados</div>
            </div>
        </div>
        
        <div class="card">
            <h2>📊 Status do Sistema</h2>
            <p><strong>Servidor LDAP:</strong> ' . htmlspecialchars($config['server'] . ':' . $config['port']) . '</p>
            <p><strong>Base DN:</strong> ' . htmlspecialchars($config['base_dn']) . '</p>
            <p><strong>Status da Conexão:</strong> 
                <span class="status-' . (extension_loaded('ldap') ? 'active' : 'blocked') . '">
                    ' . (extension_loaded('ldap') ? '✅ LDAP Disponível' : '❌ LDAP Indisponível') . '
                </span>
            </p>
        </div>
        
        <div class="card">
            <h2>🚀 Ações Rápidas</h2>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="?page=users" class="btn">👥 Gerenciar Usuários</a>
                <a href="?page=config" class="btn">⚙️ Configurar LDAP</a>
                <a href="?page=users&action=sync" class="btn btn-warning">🔄 Sincronizar Usuários</a>
            </div>
        </div>
    ');
}

/**
 * Função para gerar página de usuários
 */
function generateUsersPage($message = '') {
    $config = loadADConfiguration();
    $result = listADUsers($config);
    
    $messageHtml = $message ? '<div class="alert alert-info">' . htmlspecialchars($message) . '</div>' : '';
    
    if (isset($result['error'])) {
        return renderPageContent('Usuários - AD Manager', '
            <div class="card">
                <h2>👥 Gerenciamento de Usuários</h2>
                ' . $messageHtml . '
                <div class="alert alert-error">
                    <strong>Erro ao conectar:</strong> ' . htmlspecialchars($result['error']) . '
                </div>
                <p>Verifique a configuração LDAP em <a href="?page=config">Configurações</a>.</p>
            </div>
        ');
    }
    
    $usersHtml = '';
    if (isset($result['users']) && count($result['users']) > 0) {
        foreach ($result['users'] as $user) {
            $statusClass = $user['status'] === 'Ativo' ? 'status-active' : 'status-blocked';
            $usersHtml .= '
                <tr>
                    <td>' . htmlspecialchars($user['cn']) . '</td>
                    <td>' . htmlspecialchars($user['username']) . '</td>
                    <td>' . htmlspecialchars($user['email']) . '</td>
                    <td><span class="' . $statusClass . '">' . htmlspecialchars($user['status']) . '</span></td>
                    <td>
                        <div class="user-actions">
                            <a href="?page=users&action=reset&user=' . urlencode($user['username']) . '" 
                               class="btn btn-warning" onclick="return confirm(\'Resetar senha de ' . htmlspecialchars($user['username']) . '?\')">
                               🔄 Reset
                            </a>
                            ' . ($user['status'] === 'Ativo' ? 
                                '<a href="?page=users&action=block&user=' . urlencode($user['username']) . '" 
                                   class="btn btn-danger" onclick="return confirm(\'Bloquear ' . htmlspecialchars($user['username']) . '?\')">
                                   🔒 Bloquear
                                </a>' :
                                '<a href="?page=users&action=unblock&user=' . urlencode($user['username']) . '" 
                                   class="btn btn-success" onclick="return confirm(\'Desbloquear ' . htmlspecialchars($user['username']) . '?\')">
                                   🔓 Desbloquear
                                </a>'
                            ) . '
                        </div>
                    </td>
                </tr>
            ';
        }
    } else {
        $usersHtml = '<tr><td colspan="5" style="text-align: center;">Nenhum usuário encontrado</td></tr>';
    }
    
    return renderPageContent('Usuários - AD Manager', '
        <div class="card">
            <h2>👥 Gerenciamento de Usuários</h2>
            ' . $messageHtml . '
            <div style="margin-bottom: 20px;">
                <a href="?page=users&action=sync" class="btn">🔄 Atualizar Lista</a>
                <a href="?page=users&action=export" class="btn btn-success">📄 Exportar CSV</a>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome Completo</th>
                        <th>Nome de Usuário</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $usersHtml . '
                </tbody>
            </table>
        </div>
    ');
}

/**
 * Função para gerar página de configuração
 */
function generateConfigPage($message = '') {
    $config = loadADConfiguration();
    $messageHtml = $message ? '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>' : '';
    
    return renderPageContent('Configuração - AD Manager', '
        <div class="card">
            <h2>⚙️ Configuração LDAP/Active Directory</h2>
            ' . $messageHtml . '
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="save_config">
                
                <div class="config-section">
                    <h3>🌐 Servidor LDAP</h3>
                    <div class="grid">
                        <div class="form-group">
                            <label for="server">Servidor:</label>
                            <input type="text" id="server" name="server" class="form-control" 
                                   value="' . htmlspecialchars($config['server']) . '" 
                                   placeholder="Ex: dc1.empresa.com" required>
                        </div>
                        <div class="form-group">
                            <label for="port">Porta:</label>
                            <input type="number" id="port" name="port" class="form-control" 
                                   value="' . htmlspecialchars($config['port']) . '" 
                                   placeholder="389 (LDAP) ou 636 (LDAPS)" required>
                        </div>
                    </div>
                </div>
                
                <div class="config-section">
                    <h3>🏢 Estrutura do Domínio</h3>
                    <div class="form-group">
                        <label for="base_dn">Base DN:</label>
                        <input type="text" id="base_dn" name="base_dn" class="form-control" 
                               value="' . htmlspecialchars($config['base_dn']) . '" 
                               placeholder="Ex: DC=empresa,DC=com" required>
                    </div>
                </div>
                
                <div class="config-section">
                    <h3>🔐 Credenciais de Administrador</h3>
                    <div class="grid">
                        <div class="form-group">
                            <label for="admin_dn">DN do Administrador:</label>
                            <input type="text" id="admin_dn" name="admin_dn" class="form-control" 
                                   value="' . htmlspecialchars($config['admin_dn']) . '" 
                                   placeholder="Ex: CN=admin,DC=empresa,DC=com">
                        </div>
                        <div class="form-group">
                            <label for="admin_password">Senha do Administrador:</label>
                            <input type="password" id="admin_password" name="admin_password" class="form-control" 
                                   value="' . htmlspecialchars($config['admin_password']) . '" 
                                   placeholder="Senha do usuário administrador">
                        </div>
                    </div>
                </div>
                
                <div class="config-section">
                    <h3>🔍 Filtros de Busca</h3>
                    <div class="form-group">
                        <label for="user_filter">Filtro de Usuários:</label>
                        <input type="text" id="user_filter" name="user_filter" class="form-control" 
                               value="' . htmlspecialchars($config['user_filter']) . '" 
                               placeholder="(&(objectClass=user)(objectCategory=person))">
                    </div>
                </div>
                
                <div style="display: flex; gap: 15px; margin-top: 30px;">
                    <button type="submit" class="btn">💾 Salvar Configuração</button>
                    <a href="?page=config&action=test" class="btn btn-warning">🧪 Testar Conexão</a>
                </div>
            </form>
        </div>
        
        <div class="card">
            <h2>📋 Status da Configuração</h2>
            <p><strong>Extensão LDAP:</strong> 
                <span class="status-' . (extension_loaded('ldap') ? 'active' : 'blocked') . '">
                    ' . (extension_loaded('ldap') ? '✅ Instalada' : '❌ Não Instalada') . '
                </span>
            </p>
            <p><strong>Arquivo de Configuração:</strong> 
                <span class="status-' . (file_exists(AD_CONFIG_FILE) ? 'active' : 'blocked') . '">
                    ' . (file_exists(AD_CONFIG_FILE) ? '✅ Encontrado' : '❌ Não Encontrado') . '
                </span>
            </p>
            <p><strong>Diretório Configurável:</strong> 
                <span class="status-' . (is_writable(dirname(AD_CONFIG_FILE)) || is_writable('.') ? 'active' : 'blocked') . '">
                    ' . (is_writable(dirname(AD_CONFIG_FILE)) || is_writable('.') ? '✅ Gravável' : '❌ Sem Permissão') . '
                </span>
            </p>
        </div>
    ');
}

/**
 * Função principal de processamento
 */
function processMainRequest() {
    // Processar ações POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'login':
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                
                if (validateUserLogin($username, $password)) {
                    $_SESSION['logged_in'] = true;
                    $_SESSION['username'] = $username;
                    header('Location: ?page=dashboard');
                    exit;
                } else {
                    echo generateLoginPage('Usuário ou senha inválidos');
                    return;
                }
                break;
                
            case 'save_config':
                if (!checkUserAuthentication()) {
                    header('Location: ?');
                    exit;
                }
                
                $config = [
                    'server' => $_POST['server'] ?? '',
                    'port' => $_POST['port'] ?? '389',
                    'base_dn' => $_POST['base_dn'] ?? '',
                    'admin_dn' => $_POST['admin_dn'] ?? '',
                    'admin_password' => $_POST['admin_password'] ?? '',
                    'user_filter' => $_POST['user_filter'] ?? '(&(objectClass=user)(objectCategory=person))'
                ];
                
                if (saveADConfiguration($config)) {
                    echo generateConfigPage('Configuração salva com sucesso!');
                } else {
                    echo generateConfigPage('Erro ao salvar configuração. Verifique as permissões do diretório.');
                }
                return;
        }
    }
    
    // Processar páginas GET
    $page = $_GET['page'] ?? '';
    $action = $_GET['action'] ?? '';
    
    // Logout
    if ($page === 'logout') {
        session_destroy();
        header('Location: ?');
        exit;
    }
    
    // Verificar autenticação para páginas protegidas
    if ($page && !checkUserAuthentication()) {
        echo generateLoginPage();
        return;
    }
    
    // Processar ações específicas
    if ($action) {
        switch ($action) {
            case 'test':
                if ($page === 'config') {
                    $config = loadADConfiguration();
                    $result = connectToLDAP($config);
                    $message = isset($result['error']) ? 
                        'Erro na conexão: ' . $result['error'] : 
                        'Conexão LDAP realizada com sucesso!';
                    echo generateConfigPage($message);
                    return;
                }
                break;
                
            case 'sync':
                if ($page === 'users') {
                    echo generateUsersPage('Lista de usuários atualizada');
                    return;
                }
                break;
                
            case 'block':
            case 'unblock':
            case 'reset':
                if ($page === 'users') {
                    $user = $_GET['user'] ?? '';
                    $actionName = $action === 'block' ? 'bloqueado' : 
                                ($action === 'unblock' ? 'desbloqueado' : 'senha resetada');
                    echo generateUsersPage("Usuário {$user} {$actionName} (simulado - implemente LDAP para ação real)");
                    return;
                }
                break;
        }
    }
    
    // Roteamento principal
    switch ($page) {
        case 'dashboard':
            echo generateDashboardPage();
            break;
            
        case 'users':
            echo generateUsersPage();
            break;
            
        case 'config':
            echo generateConfigPage();
            break;
            
        default:
            if (checkUserAuthentication()) {
                echo generateDashboardPage();
            } else {
                echo generateLoginPage();
            }
            break;
    }
}

// Executar aplicação
processMainRequest();
?>