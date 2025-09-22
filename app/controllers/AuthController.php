<?php

/**
 * Controller para autenticação
 */
class AuthController
{
    /**
     * Exibe página de login
     */
    public function login()
    {
        if (isset($_POST['username'])) {
            $this->handleLogin();
            return;
        }
        
        // Se já estiver logado, redirecionar para dashboard
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
            header('Location: /dashboard');
            exit;
        }
        
        $title = 'Login - AD Manager';
        include APP_PATH . '/views/login.php';
    }
    
    /**
     * Processa login
     */
    private function handleLogin()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Verificar credenciais padrão do administrador
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = 'admin';
            $_SESSION['user_type'] = 'admin';
            
            header('Location: /dashboard');
            exit;
        }
        
        // Se LDAP estiver configurado, tentar autenticação via AD
        if (defined('LDAP_HOST')) {
            require_once APP_PATH . '/models/User.php';
            $userModel = new User();
            
            if ($userModel->authenticate($username, $password)) {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['user_type'] = 'ldap';
                
                header('Location: /dashboard');
                exit;
            }
        }
        
        $error = 'Usuário ou senha inválidos';
        $title = 'Login - AD Manager';
        include APP_PATH . '/views/login.php';
    }
    
    /**
     * Logout
     */
    public function logout()
    {
        session_destroy();
        header('Location: /login');
        exit;
    }
    
    /**
     * Verifica se usuário está logado
     */
    public static function checkAuth()
    {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            header('Location: /login');
            exit;
        }
    }
    
    /**
     * Verifica se é administrador
     */
    public static function checkAdmin()
    {
        self::checkAuth();
        
        if ($_SESSION['user_type'] !== 'admin') {
            header('Location: /dashboard');
            exit;
        }
    }
}