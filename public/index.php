<?php
/**
 * Sistema de Gestão de Usuários do Active Directory
 * Ponto de entrada da aplicação
 */

session_start();

// Configurações gerais
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Autoloader simples
spl_autoload_register(function ($class) {
    $file = APP_PATH . '/' . strtolower(str_replace('\\', '/', $class)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Incluir configurações
if (file_exists(CONFIG_PATH . '/ldap.php')) {
    require_once CONFIG_PATH . '/ldap.php';
}

// Router simples
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$path = str_replace('/index.php', '', $path);

// Remover query string
$path = strtok($path, '?');

// Definir rota padrão
if ($path === '/' || $path === '') {
    $path = '/login';
}

// Sistema de roteamento
switch ($path) {
    case '/login':
        require_once APP_PATH . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->login();
        break;
        
    case '/logout':
        require_once APP_PATH . '/controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;
        
    case '/dashboard':
        require_once APP_PATH . '/controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;
        
    case '/users':
        require_once APP_PATH . '/controllers/UserController.php';
        $controller = new UserController();
        $controller->index();
        break;
        
    case '/users/block':
        require_once APP_PATH . '/controllers/UserController.php';
        $controller = new UserController();
        $controller->block();
        break;
        
    case '/users/unblock':
        require_once APP_PATH . '/controllers/UserController.php';
        $controller = new UserController();
        $controller->unblock();
        break;
        
    case '/users/reset-password':
        require_once APP_PATH . '/controllers/UserController.php';
        $controller = new UserController();
        $controller->resetPassword();
        break;
        
    case '/users/bulk-action':
        require_once APP_PATH . '/controllers/UserController.php';
        $controller = new UserController();
        $controller->bulkAction();
        break;
        
    case '/config':
        require_once APP_PATH . '/controllers/ConfigController.php';
        $controller = new ConfigController();
        $controller->index();
        break;
        
    case '/config/ldap':
        require_once APP_PATH . '/controllers/ConfigController.php';
        $controller = new ConfigController();
        $controller->saveLdap();
        break;
        
    case '/api/users/search':
        require_once APP_PATH . '/controllers/api/UserApiController.php';
        $controller = new UserApiController();
        $controller->search();
        break;
        
    default:
        http_response_code(404);
        echo '404 - Página não encontrada';
        break;
}