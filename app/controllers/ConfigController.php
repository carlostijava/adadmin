<?php

require_once APP_PATH . '/controllers/AuthController.php';

/**
 * Controller para configurações
 */
class ConfigController
{
    /**
     * Página de configurações
     */
    public function index()
    {
        AuthController::checkAdmin();
        
        $title = 'Configurações - AD Manager';
        
        // Carregar configurações existentes
        $ldapConfig = [
            'host' => '',
            'port' => '389',
            'domain' => '',
            'base_dn' => '',
            'admin_user' => '',
            'use_tls' => false
        ];
        
        if (defined('LDAP_HOST')) {
            $ldapConfig = [
                'host' => LDAP_HOST,
                'port' => LDAP_PORT,
                'domain' => LDAP_DOMAIN,
                'base_dn' => LDAP_BASE_DN,
                'admin_user' => LDAP_ADMIN_USER,
                'use_tls' => LDAP_USE_TLS
            ];
        }
        
        include APP_PATH . '/views/config.php';
    }
    
    /**
     * Salvar configuração LDAP
     */
    public function saveLdap()
    {
        AuthController::checkAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /config');
            exit;
        }
        
        $host = trim($_POST['ldap_host'] ?? '');
        $port = intval($_POST['ldap_port'] ?? 389);
        $domain = trim($_POST['ldap_domain'] ?? '');
        $baseDn = trim($_POST['ldap_base_dn'] ?? '');
        $adminUser = trim($_POST['ldap_admin_user'] ?? '');
        $adminPassword = $_POST['ldap_admin_password'] ?? '';
        $useTls = isset($_POST['ldap_use_tls']) && $_POST['ldap_use_tls'] === '1';
        
        // Validações
        $errors = [];
        
        if (empty($host)) {
            $errors[] = 'Host LDAP é obrigatório';
        }
        
        if ($port < 1 || $port > 65535) {
            $errors[] = 'Porta deve estar entre 1 e 65535';
        }
        
        if (empty($domain)) {
            $errors[] = 'Domínio é obrigatório';
        }
        
        if (empty($baseDn)) {
            $errors[] = 'Base DN é obrigatório';
        }
        
        if (empty($adminUser)) {
            $errors[] = 'Usuário administrador é obrigatório';
        }
        
        if (empty($adminPassword)) {
            $errors[] = 'Senha do administrador é obrigatória';
        }
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode('<br>', $errors);
            header('Location: /config');
            exit;
        }
        
        // Testar conexão LDAP
        $testResult = $this->testLdapConnection($host, $port, $domain, $baseDn, $adminUser, $adminPassword, $useTls);
        
        if (!$testResult['success']) {
            $_SESSION['error'] = 'Erro ao conectar com LDAP: ' . $testResult['message'];
            header('Location: /config');
            exit;
        }
        
        // Criar arquivo de configuração
        $configContent = "<?php\n\n";
        $configContent .= "// Configuração LDAP gerada automaticamente\n";
        $configContent .= "// Data: " . date('d/m/Y H:i:s') . "\n\n";
        $configContent .= "define('LDAP_HOST', '" . addslashes($host) . "');\n";
        $configContent .= "define('LDAP_PORT', " . $port . ");\n";
        $configContent .= "define('LDAP_DOMAIN', '" . addslashes($domain) . "');\n";
        $configContent .= "define('LDAP_BASE_DN', '" . addslashes($baseDn) . "');\n";
        $configContent .= "define('LDAP_ADMIN_USER', '" . addslashes($adminUser) . "');\n";
        $configContent .= "define('LDAP_ADMIN_PASSWORD', '" . addslashes($adminPassword) . "');\n";
        $configContent .= "define('LDAP_USE_TLS', " . ($useTls ? 'true' : 'false') . ");\n";
        
        $configFile = CONFIG_PATH . '/ldap.php';
        
        if (file_put_contents($configFile, $configContent) === false) {
            $_SESSION['error'] = 'Erro ao salvar configuração. Verifique as permissões do diretório config/';
            header('Location: /config');
            exit;
        }
        
        $_SESSION['success'] = 'Configuração LDAP salva com sucesso!';
        header('Location: /config');
        exit;
    }
    
    /**
     * Testa conexão LDAP
     */
    private function testLdapConnection($host, $port, $domain, $baseDn, $adminUser, $adminPassword, $useTls)
    {
        try {
            $connection = ldap_connect($host, $port);
            
            if (!$connection) {
                return ['success' => false, 'message' => 'Não foi possível conectar ao servidor LDAP'];
            }
            
            ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($connection, LDAP_OPT_REFERRALS, 0);
            
            if ($useTls) {
                if (!ldap_start_tls($connection)) {
                    ldap_close($connection);
                    return ['success' => false, 'message' => 'Erro ao iniciar TLS'];
                }
            }
            
            $bind = ldap_bind($connection, $adminUser, $adminPassword);
            
            if (!$bind) {
                ldap_close($connection);
                return ['success' => false, 'message' => 'Credenciais inválidas'];
            }
            
            // Teste de busca básica
            $result = ldap_search($connection, $baseDn, '(objectClass=*)', [], 0, 1);
            
            if (!$result) {
                ldap_close($connection);
                return ['success' => false, 'message' => 'Base DN inválido'];
            }
            
            ldap_close($connection);
            return ['success' => true, 'message' => 'Conexão testada com sucesso'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}