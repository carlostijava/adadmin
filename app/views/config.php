<?php
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Configurações do Sistema</h1>
    </div>
    
    <div class="alert alert-info">
        <strong>Informação:</strong> Configure a conexão LDAP para gerenciar usuários do Active Directory.
        Certifique-se de que o servidor PHP tem a extensão LDAP instalada e habilitada.
    </div>
</div>

<!-- Configuração LDAP -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Configuração LDAP/Active Directory</h2>
    </div>
    
    <form method="POST" action="/config/ldap" id="ldapConfigForm">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            
            <!-- Conexão -->
            <div>
                <h3 style="color: var(--primary-blue); margin-bottom: 15px;">Conexão</h3>
                
                <div class="form-group">
                    <label for="ldap_host" class="form-label">Servidor LDAP *</label>
                    <input 
                        type="text" 
                        id="ldap_host" 
                        name="ldap_host" 
                        class="form-input" 
                        required 
                        placeholder="ldap.empresa.com.br ou 192.168.1.10"
                        value="<?php echo htmlspecialchars($ldapConfig['host']); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="ldap_port" class="form-label">Porta</label>
                    <input 
                        type="number" 
                        id="ldap_port" 
                        name="ldap_port" 
                        class="form-input" 
                        min="1" 
                        max="65535"
                        placeholder="389 (padrão) ou 636 (SSL)"
                        value="<?php echo htmlspecialchars($ldapConfig['port']); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <input 
                            type="checkbox" 
                            name="ldap_use_tls" 
                            value="1" 
                            class="form-checkbox"
                            <?php echo $ldapConfig['use_tls'] ? 'checked' : ''; ?>
                        >
                        Usar TLS/SSL
                    </label>
                    <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                        Recomendado para conexões seguras
                    </small>
                </div>
            </div>
            
            <!-- Domínio e Estrutura -->
            <div>
                <h3 style="color: var(--primary-blue); margin-bottom: 15px;">Domínio e Estrutura</h3>
                
                <div class="form-group">
                    <label for="ldap_domain" class="form-label">Domínio *</label>
                    <input 
                        type="text" 
                        id="ldap_domain" 
                        name="ldap_domain" 
                        class="form-input" 
                        required 
                        placeholder="empresa.com.br"
                        value="<?php echo htmlspecialchars($ldapConfig['domain']); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="ldap_base_dn" class="form-label">Base DN *</label>
                    <input 
                        type="text" 
                        id="ldap_base_dn" 
                        name="ldap_base_dn" 
                        class="form-input" 
                        required 
                        placeholder="DC=empresa,DC=com,DC=br"
                        value="<?php echo htmlspecialchars($ldapConfig['base_dn']); ?>"
                    >
                    <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                        Distinguished Name base para busca de usuários
                    </small>
                </div>
            </div>
            
            <!-- Autenticação -->
            <div>
                <h3 style="color: var(--primary-blue); margin-bottom: 15px;">Autenticação</h3>
                
                <div class="form-group">
                    <label for="ldap_admin_user" class="form-label">Usuário Administrador *</label>
                    <input 
                        type="text" 
                        id="ldap_admin_user" 
                        name="ldap_admin_user" 
                        class="form-input" 
                        required 
                        placeholder="administrator@empresa.com.br"
                        value="<?php echo htmlspecialchars($ldapConfig['admin_user']); ?>"
                    >
                    <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                        Usuário com permissões para gerenciar contas
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="ldap_admin_password" class="form-label">Senha do Administrador *</label>
                    <input 
                        type="password" 
                        id="ldap_admin_password" 
                        name="ldap_admin_password" 
                        class="form-input" 
                        required 
                        placeholder="••••••••"
                    >
                    <small style="color: var(--text-secondary); display: block; margin-top: 5px;">
                        A senha será criptografada e armazenada com segurança
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Botões de ação -->
        <div style="margin-top: 30px; text-align: right; border-top: 1px solid var(--border-color); padding-top: 20px;">
            <button type="button" id="testConnectionBtn" class="btn btn-secondary">
                <span class="loading" id="testLoading" style="display: none;"></span>
                Testar Conexão
            </button>
            
            <button type="submit" class="btn btn-primary">
                Salvar Configuração
            </button>
        </div>
    </form>
</div>

<!-- Status da Configuração Atual -->
<?php if (defined('LDAP_HOST')): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Status da Configuração Atual</h2>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <div>
            <h4 style="color: var(--success-green);">✓ LDAP Configurado</h4>
            <p><strong>Servidor:</strong> <?php echo htmlspecialchars(LDAP_HOST . ':' . LDAP_PORT); ?></p>
            <p><strong>Domínio:</strong> <?php echo htmlspecialchars(LDAP_DOMAIN); ?></p>
        </div>
        
        <div>
            <h4 style="color: var(--primary-blue);">Configuração</h4>
            <p><strong>Base DN:</strong> <?php echo htmlspecialchars(LDAP_BASE_DN); ?></p>
            <p><strong>TLS:</strong> <?php echo LDAP_USE_TLS ? 'Habilitado' : 'Desabilitado'; ?></p>
        </div>
        
        <div>
            <h4 style="color: var(--primary-blue);">Usuário Admin</h4>
            <p><strong>Login:</strong> <?php echo htmlspecialchars(LDAP_ADMIN_USER); ?></p>
            <p><strong>Senha:</strong> ••••••••</p>
        </div>
        
        <div>
            <h4 style="color: var(--primary-blue);">Arquivo de Config</h4>
            <p><strong>Localização:</strong> /config/ldap.php</p>
            <p><strong>Status:</strong> <span style="color: var(--success-green);">Ativo</span></p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Informações de Ajuda -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Ajuda e Informações</h2>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div>
            <h4 style="color: var(--primary-blue);">Exemplos de Configuração</h4>
            <p><strong>Servidor:</strong> dc01.empresa.local</p>
            <p><strong>Porta:</strong> 389 (LDAP) ou 636 (LDAPS)</p>
            <p><strong>Base DN:</strong> DC=empresa,DC=local</p>
            <p><strong>Usuário:</strong> administrador@empresa.local</p>
        </div>
        
        <div>
            <h4 style="color: var(--primary-blue);">Requisitos</h4>
            <ul style="margin-left: 20px;">
                <li>Extensão PHP LDAP instalada</li>
                <li>Conectividade com o servidor AD</li>
                <li>Usuário com permissões administrativas</li>
                <li>Porta LDAP liberada no firewall</li>
            </ul>
        </div>
        
        <div>
            <h4 style="color: var(--primary-blue);">Permissões Necessárias</h4>
            <ul style="margin-left: 20px;">
                <li>Leitura de propriedades de usuários</li>
                <li>Modificação de userAccountControl</li>
                <li>Reset de senhas (unicodePwd)</li>
                <li>Acesso ao diretório especificado</li>
            </ul>
        </div>
    </div>
</div>

<script>
// Teste de conexão LDAP
document.getElementById('testConnectionBtn').addEventListener('click', async function() {
    const btn = this;
    const loading = document.getElementById('testLoading');
    const form = document.getElementById('ldapConfigForm');
    
    // Validar campos obrigatórios
    const requiredFields = form.querySelectorAll('input[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--error-red)';
            isValid = false;
        } else {
            field.style.borderColor = 'var(--border-color)';
        }
    });
    
    if (!isValid) {
        alert('Por favor, preencha todos os campos obrigatórios.');
        return;
    }
    
    // Mostrar loading
    btn.disabled = true;
    loading.style.display = 'inline-block';
    btn.innerHTML = '<span class="loading"></span> Testando...';
    
    // Preparar dados
    const formData = new FormData(form);
    
    try {
        const response = await fetch('/config/ldap', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.text();
        
        if (response.ok && result.includes('sucesso')) {
            alert('✓ Conexão LDAP testada com sucesso!');
        } else {
            alert('✗ Erro ao testar conexão LDAP. Verifique os dados e tente novamente.');
        }
        
    } catch (error) {
        alert('✗ Erro de rede ao testar a conexão.');
    } finally {
        // Restaurar botão
        btn.disabled = false;
        loading.style.display = 'none';
        btn.innerHTML = 'Testar Conexão';
    }
});

// Validação em tempo real
document.querySelectorAll('input[required]').forEach(input => {
    input.addEventListener('blur', function() {
        if (!this.value.trim()) {
            this.style.borderColor = 'var(--error-red)';
        } else {
            this.style.borderColor = 'var(--border-color)';
        }
    });
});

// Auto-preencher Base DN baseado no domínio
document.getElementById('ldap_domain').addEventListener('input', function() {
    const domain = this.value.trim();
    const baseDnField = document.getElementById('ldap_base_dn');
    
    if (domain && !baseDnField.value) {
        const parts = domain.split('.');
        const dn = parts.map(part => 'DC=' + part).join(',');
        baseDnField.value = dn;
    }
});

// Validação do formulário
document.getElementById('ldapConfigForm').addEventListener('submit', function(e) {
    const requiredFields = this.querySelectorAll('input[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--error-red)';
            isValid = false;
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Por favor, preencha todos os campos obrigatórios.');
        return false;
    }
    
    return confirm('Deseja salvar a configuração LDAP? A conexão será testada antes de salvar.');
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>