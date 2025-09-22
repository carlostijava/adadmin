<?php

require_once APP_PATH . '/controllers/AuthController.php';
require_once APP_PATH . '/models/User.php';

/**
 * Controller para gerenciamento de usuários
 */
class UserController
{
    /**
     * Lista usuários
     */
    public function index()
    {
        AuthController::checkAuth();
        
        $title = 'Usuários - AD Manager';
        
        if (!defined('LDAP_HOST')) {
            $error = 'Configuração LDAP necessária. Acesse Configurações para configurar.';
            include APP_PATH . '/views/users.php';
            return;
        }
        
        $userModel = new User();
        $search = $_GET['search'] ?? '';
        
        $result = $userModel->getAllUsers($search);
        
        if ($result['success']) {
            $users = $result['users'];
            $success = 'Usuários carregados com sucesso';
        } else {
            $users = [];
            $error = $result['message'];
        }
        
        include APP_PATH . '/views/users.php';
    }
    
    /**
     * Bloquear usuário
     */
    public function block()
    {
        AuthController::checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /users');
            exit;
        }
        
        $username = $_POST['username'] ?? '';
        
        if (empty($username)) {
            $_SESSION['error'] = 'Nome de usuário é obrigatório';
            header('Location: /users');
            exit;
        }
        
        $userModel = new User();
        $result = $userModel->blockUser($username);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        
        header('Location: /users');
        exit;
    }
    
    /**
     * Desbloquear usuário
     */
    public function unblock()
    {
        AuthController::checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /users');
            exit;
        }
        
        $username = $_POST['username'] ?? '';
        
        if (empty($username)) {
            $_SESSION['error'] = 'Nome de usuário é obrigatório';
            header('Location: /users');
            exit;
        }
        
        $userModel = new User();
        $result = $userModel->unblockUser($username);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        
        header('Location: /users');
        exit;
    }
    
    /**
     * Reset de senha
     */
    public function resetPassword()
    {
        AuthController::checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /users');
            exit;
        }
        
        $username = $_POST['username'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        
        if (empty($username) || empty($newPassword)) {
            $_SESSION['error'] = 'Nome de usuário e nova senha são obrigatórios';
            header('Location: /users');
            exit;
        }
        
        if (strlen($newPassword) < 6) {
            $_SESSION['error'] = 'A senha deve ter pelo menos 6 caracteres';
            header('Location: /users');
            exit;
        }
        
        $userModel = new User();
        $result = $userModel->resetPassword($username, $newPassword);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
        
        header('Location: /users');
        exit;
    }
    
    /**
     * Ação em massa (bloquear/desbloquear múltiplos usuários)
     */
    public function bulkAction()
    {
        AuthController::checkAuth();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /users');
            exit;
        }
        
        $action = $_POST['bulk_action'] ?? '';
        $usernames = $_POST['selected_users'] ?? [];
        
        if (empty($action) || empty($usernames) || !is_array($usernames)) {
            $_SESSION['error'] = 'Selecione usuários e uma ação válida';
            header('Location: /users');
            exit;
        }
        
        $userModel = new User();
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($usernames as $username) {
            if ($action === 'block') {
                $result = $userModel->blockUser($username);
            } elseif ($action === 'unblock') {
                $result = $userModel->unblockUser($username);
            } else {
                continue;
            }
            
            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }
        
        $actionText = $action === 'block' ? 'bloqueados' : 'desbloqueados';
        
        if ($successCount > 0) {
            $_SESSION['success'] = "$successCount usuários $actionText com sucesso";
        }
        
        if ($errorCount > 0) {
            $_SESSION['error'] = "$errorCount usuários não puderam ser processados";
        }
        
        header('Location: /users');
        exit;
    }
}