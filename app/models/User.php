<?php

/**
 * Model para gerenciamento de usuários do Active Directory
 */
class User
{
    private $ldapConnection;
    private $baseDn;
    private $adminUser;
    private $adminPassword;
    
    public function __construct()
    {
        if (defined('LDAP_HOST')) {
            $this->initializeLdap();
        }
    }
    
    /**
     * Inicializa conexão LDAP
     */
    private function initializeLdap()
    {
        $this->baseDn = LDAP_BASE_DN;
        $this->adminUser = LDAP_ADMIN_USER;
        $this->adminPassword = LDAP_ADMIN_PASSWORD;
        
        $this->ldapConnection = ldap_connect(LDAP_HOST, LDAP_PORT);
        
        if ($this->ldapConnection) {
            ldap_set_option($this->ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($this->ldapConnection, LDAP_OPT_REFERRALS, 0);
            
            if (LDAP_USE_TLS) {
                ldap_start_tls($this->ldapConnection);
            }
        }
    }
    
    /**
     * Lista todos os usuários do AD
     */
    public function getAllUsers($search = '')
    {
        if (!$this->ldapConnection) {
            return ['success' => false, 'message' => 'Conexão LDAP não configurada'];
        }
        
        $bind = ldap_bind($this->ldapConnection, $this->adminUser, $this->adminPassword);
        
        if (!$bind) {
            return ['success' => false, 'message' => 'Erro na autenticação LDAP'];
        }
        
        // Filtro de busca
        $filter = '(&(objectClass=user)(objectCategory=person)';
        if (!empty($search)) {
            $search = ldap_escape($search, '', LDAP_ESCAPE_FILTER);
            $filter .= "(|(cn=*$search*)(sAMAccountName=*$search*)(mail=*$search*))";
        }
        $filter .= ')';
        
        $attributes = [
            'sAMAccountName', 'cn', 'mail', 'telephoneNumber', 'department',
            'title', 'userAccountControl', 'lastLogon', 'whenCreated'
        ];
        
        $result = ldap_search($this->ldapConnection, $this->baseDn, $filter, $attributes);
        
        if (!$result) {
            return ['success' => false, 'message' => 'Erro na busca LDAP'];
        }
        
        $entries = ldap_get_entries($this->ldapConnection, $result);
        $users = [];
        
        for ($i = 0; $i < $entries['count']; $i++) {
            $entry = $entries[$i];
            
            $users[] = [
                'username' => $entry['samaccountname'][0] ?? '',
                'name' => $entry['cn'][0] ?? '',
                'email' => $entry['mail'][0] ?? '',
                'phone' => $entry['telephonenumber'][0] ?? '',
                'department' => $entry['department'][0] ?? '',
                'title' => $entry['title'][0] ?? '',
                'is_enabled' => !($entry['useraccountcontrol'][0] & 2),
                'last_logon' => isset($entry['lastlogon'][0]) ? $this->convertWindowsTime($entry['lastlogon'][0]) : '',
                'created' => isset($entry['whencreated'][0]) ? $entry['whencreated'][0] : '',
                'dn' => $entry['dn']
            ];
        }
        
        return ['success' => true, 'users' => $users];
    }
    
    /**
     * Bloqueia um usuário
     */
    public function blockUser($username)
    {
        return $this->toggleUserStatus($username, true);
    }
    
    /**
     * Desbloqueia um usuário
     */
    public function unblockUser($username)
    {
        return $this->toggleUserStatus($username, false);
    }
    
    /**
     * Alterna o status de um usuário (bloqueado/desbloqueado)
     */
    private function toggleUserStatus($username, $disable = true)
    {
        if (!$this->ldapConnection) {
            return ['success' => false, 'message' => 'Conexão LDAP não configurada'];
        }
        
        $bind = ldap_bind($this->ldapConnection, $this->adminUser, $this->adminPassword);
        
        if (!$bind) {
            return ['success' => false, 'message' => 'Erro na autenticação LDAP'];
        }
        
        // Buscar o usuário
        $filter = "(&(objectClass=user)(sAMAccountName=$username))";
        $result = ldap_search($this->ldapConnection, $this->baseDn, $filter, ['userAccountControl']);
        
        if (!$result) {
            return ['success' => false, 'message' => 'Usuário não encontrado'];
        }
        
        $entries = ldap_get_entries($this->ldapConnection, $result);
        
        if ($entries['count'] === 0) {
            return ['success' => false, 'message' => 'Usuário não encontrado'];
        }
        
        $userDn = $entries[0]['dn'];
        $currentUac = $entries[0]['useraccountcontrol'][0];
        
        // Calcular novo valor do userAccountControl
        if ($disable) {
            $newUac = $currentUac | 2; // Adicionar flag de desabilitado
        } else {
            $newUac = $currentUac & ~2; // Remover flag de desabilitado
        }
        
        $modification = ['userAccountControl' => $newUac];
        
        $result = ldap_modify($this->ldapConnection, $userDn, $modification);
        
        if ($result) {
            $action = $disable ? 'bloqueado' : 'desbloqueado';
            return ['success' => true, 'message' => "Usuário $username $action com sucesso"];
        } else {
            return ['success' => false, 'message' => 'Erro ao modificar usuário'];
        }
    }
    
    /**
     * Reset da senha do usuário
     */
    public function resetPassword($username, $newPassword)
    {
        if (!$this->ldapConnection) {
            return ['success' => false, 'message' => 'Conexão LDAP não configurada'];
        }
        
        $bind = ldap_bind($this->ldapConnection, $this->adminUser, $this->adminPassword);
        
        if (!$bind) {
            return ['success' => false, 'message' => 'Erro na autenticação LDAP'];
        }
        
        // Buscar o usuário
        $filter = "(&(objectClass=user)(sAMAccountName=$username))";
        $result = ldap_search($this->ldapConnection, $this->baseDn, $filter, ['dn']);
        
        if (!$result) {
            return ['success' => false, 'message' => 'Usuário não encontrado'];
        }
        
        $entries = ldap_get_entries($this->ldapConnection, $result);
        
        if ($entries['count'] === 0) {
            return ['success' => false, 'message' => 'Usuário não encontrado'];
        }
        
        $userDn = $entries[0]['dn'];
        
        // Codificar senha para AD
        $encodedPassword = mb_convert_encoding('"' . $newPassword . '"', 'UTF-16LE', 'UTF-8');
        
        $modification = [
            'unicodePwd' => $encodedPassword,
            'userAccountControl' => 512 // Conta normal, senha não expira
        ];
        
        $result = ldap_modify($this->ldapConnection, $userDn, $modification);
        
        if ($result) {
            return ['success' => true, 'message' => "Senha do usuário $username alterada com sucesso"];
        } else {
            return ['success' => false, 'message' => 'Erro ao alterar senha'];
        }
    }
    
    /**
     * Autentica usuário no AD
     */
    public function authenticate($username, $password)
    {
        if (!$this->ldapConnection) {
            return false;
        }
        
        $userDn = "$username@" . LDAP_DOMAIN;
        
        return ldap_bind($this->ldapConnection, $userDn, $password);
    }
    
    /**
     * Converte timestamp do Windows para formato legível
     */
    private function convertWindowsTime($windowsTime)
    {
        if (empty($windowsTime) || $windowsTime == '0') {
            return 'Nunca';
        }
        
        $unixTime = ($windowsTime / 10000000) - 11644473600;
        return date('d/m/Y H:i:s', $unixTime);
    }
    
    /**
     * Fecha conexão LDAP
     */
    public function __destruct()
    {
        if ($this->ldapConnection) {
            ldap_close($this->ldapConnection);
        }
    }
}