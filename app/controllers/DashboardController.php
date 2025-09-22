<?php

require_once APP_PATH . '/controllers/AuthController.php';

/**
 * Controller do Dashboard
 */
class DashboardController
{
    /**
     * Página principal do dashboard
     */
    public function index()
    {
        AuthController::checkAuth();
        
        $title = 'Dashboard - AD Manager';
        $ldapConfigured = defined('LDAP_HOST');
        
        // Estatísticas básicas se LDAP estiver configurado
        $stats = [
            'total_users' => 0,
            'enabled_users' => 0,
            'disabled_users' => 0,
            'ldap_status' => 'Não configurado'
        ];
        
        if ($ldapConfigured) {
            require_once APP_PATH . '/models/User.php';
            $userModel = new User();
            $result = $userModel->getAllUsers();
            
            if ($result['success']) {
                $users = $result['users'];
                $stats['total_users'] = count($users);
                $stats['enabled_users'] = count(array_filter($users, function($u) { return $u['is_enabled']; }));
                $stats['disabled_users'] = $stats['total_users'] - $stats['enabled_users'];
                $stats['ldap_status'] = 'Conectado';
            } else {
                $stats['ldap_status'] = 'Erro de conexão';
            }
        }
        
        include APP_PATH . '/views/dashboard.php';
    }
}