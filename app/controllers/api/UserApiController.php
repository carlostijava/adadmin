<?php

require_once APP_PATH . '/controllers/AuthController.php';
require_once APP_PATH . '/models/User.php';

/**
 * API Controller para usuários
 */
class UserApiController
{
    /**
     * Busca usuários via AJAX
     */
    public function search()
    {
        header('Content-Type: application/json');
        
        // Verificar autenticação
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        if (!defined('LDAP_HOST')) {
            echo json_encode(['success' => false, 'message' => 'LDAP não configurado']);
            exit;
        }
        
        $search = $_GET['q'] ?? '';
        
        $userModel = new User();
        $result = $userModel->getAllUsers($search);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'users' => $result['users'],
                'total' => count($result['users'])
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => $result['message']
            ]);
        }
        
        exit;
    }
}