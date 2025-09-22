<?php
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Gerenciamento de Usuários</h1>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!defined('LDAP_HOST')): ?>
        <div class="alert alert-warning">
            <strong>Configuração LDAP necessária!</strong><br>
            Para gerenciar usuários do Active Directory, configure a conexão LDAP primeiro.
            <br><br>
            <a href="/config" class="btn btn-primary">Configurar LDAP</a>
        </div>
    <?php else: ?>
        
        <!-- Barra de busca -->
        <div class="search-container">
            <input 
                type="text" 
                id="searchInput" 
                class="search-input" 
                placeholder="Buscar usuários por nome, username ou email..."
                value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
            >
            <button type="button" id="searchBtn" class="btn btn-primary">
                Buscar
            </button>
            <button type="button" id="clearSearchBtn" class="btn btn-secondary">
                Limpar
            </button>
        </div>
        
        <?php if (!empty($users)): ?>
            
            <!-- Ações em massa -->
            <form method="POST" action="/users/bulk-action" id="bulkActionForm">
                <div class="bulk-actions">
                    <label class="form-label">
                        <input type="checkbox" id="selectAll" class="form-checkbox">
                        Selecionar Todos
                    </label>
                    
                    <select name="bulk_action" class="form-select" style="width: auto;">
                        <option value="">Selecione uma ação</option>
                        <option value="block">Bloquear Selecionados</option>
                        <option value="unblock">Desbloquear Selecionados</option>
                    </select>
                    
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Tem certeza que deseja aplicar esta ação aos usuários selecionados?')">
                        Aplicar Ação
                    </button>
                    
                    <span id="selectedCount" class="text-secondary">0 usuários selecionados</span>
                </div>
                
                <!-- Tabela de usuários -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAllHeader" class="form-checkbox">
                                </th>
                                <th>Nome</th>
                                <th>Username</th>
                                <th>E-mail</th>
                                <th>Departamento</th>
                                <th>Status</th>
                                <th>Último Login</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <input 
                                            type="checkbox" 
                                            name="selected_users[]" 
                                            value="<?php echo htmlspecialchars($user['username']); ?>" 
                                            class="form-checkbox user-checkbox"
                                        >
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['department']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $user['is_enabled'] ? 'status-enabled' : 'status-disabled'; ?>">
                                            <?php echo $user['is_enabled'] ? 'Ativo' : 'Bloqueado'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['last_logon']); ?></td>
                                    <td class="text-center">
                                        <?php if ($user['is_enabled']): ?>
                                            <form method="POST" action="/users/block" style="display: inline;">
                                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                                <button 
                                                    type="submit" 
                                                    class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Tem certeza que deseja bloquear o usuário <?php echo htmlspecialchars($user['username']); ?>?')"
                                                >
                                                    Bloquear
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="/users/unblock" style="display: inline;">
                                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                                <button 
                                                    type="submit" 
                                                    class="btn btn-success btn-sm"
                                                    onclick="return confirm('Tem certeza que deseja desbloquear o usuário <?php echo htmlspecialchars($user['username']); ?>?')"
                                                >
                                                    Desbloquear
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <button 
                                            type="button" 
                                            class="btn btn-warning btn-sm" 
                                            onclick="openResetPasswordModal('<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['name']); ?>')"
                                        >
                                            Reset Senha
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            
        <?php elseif (isset($users)): ?>
            <div class="alert alert-info">
                Nenhum usuário encontrado.
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
</div>

<!-- Modal Reset de Senha -->
<div id="resetPasswordModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="modal-header">
            <h2 class="modal-title">Reset de Senha</h2>
        </div>
        
        <form method="POST" action="/users/reset-password" id="resetPasswordForm">
            <input type="hidden" name="username" id="resetUsername">
            
            <div class="form-group">
                <label class="form-label">Usuário:</label>
                <p id="resetUserDisplay" style="font-weight: bold; color: var(--primary-blue);"></p>
            </div>
            
            <div class="form-group">
                <label for="new_password" class="form-label">Nova Senha:</label>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    class="form-input" 
                    required 
                    minlength="6"
                    placeholder="Mínimo 6 caracteres"
                >
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirmar Senha:</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    class="form-input" 
                    required 
                    minlength="6"
                    placeholder="Digite a senha novamente"
                >
                <small id="passwordError" style="color: var(--error-red); display: none;">As senhas não coincidem</small>
            </div>
            
            <div class="form-group" style="text-align: right;">
                <button type="button" class="btn btn-secondary" onclick="closeResetPasswordModal()">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary" id="resetPasswordSubmit">
                    Redefinir Senha
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Busca em tempo real
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        performSearch();
    }, 500);
});

document.getElementById('searchBtn').addEventListener('click', performSearch);
document.getElementById('clearSearchBtn').addEventListener('click', function() {
    document.getElementById('searchInput').value = '';
    performSearch();
});

function performSearch() {
    const searchTerm = document.getElementById('searchInput').value;
    window.location.href = '/users' + (searchTerm ? '?search=' + encodeURIComponent(searchTerm) : '');
}

// Busca por Enter
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        performSearch();
    }
});

// Seleção em massa
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
});

document.getElementById('selectAllHeader').addEventListener('change', function() {
    document.getElementById('selectAll').checked = this.checked;
    document.getElementById('selectAll').dispatchEvent(new Event('change'));
});

document.querySelectorAll('.user-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const selected = document.querySelectorAll('.user-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = selected + ' usuários selecionados';
}

// Modal Reset de Senha
function openResetPasswordModal(username, displayName) {
    document.getElementById('resetUsername').value = username;
    document.getElementById('resetUserDisplay').textContent = displayName + ' (' + username + ')';
    document.getElementById('resetPasswordModal').style.display = 'block';
    document.getElementById('new_password').value = '';
    document.getElementById('confirm_password').value = '';
    document.getElementById('passwordError').style.display = 'none';
}

function closeResetPasswordModal() {
    document.getElementById('resetPasswordModal').style.display = 'none';
}

// Fechar modal clicando no X ou fora dele
document.querySelector('.close').addEventListener('click', closeResetPasswordModal);
window.addEventListener('click', function(e) {
    if (e.target === document.getElementById('resetPasswordModal')) {
        closeResetPasswordModal();
    }
});

// Validação de senha
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirm = this.value;
    const error = document.getElementById('passwordError');
    const submit = document.getElementById('resetPasswordSubmit');
    
    if (password !== confirm) {
        error.style.display = 'block';
        submit.disabled = true;
    } else {
        error.style.display = 'none';
        submit.disabled = false;
    }
});

document.getElementById('new_password').addEventListener('input', function() {
    document.getElementById('confirm_password').dispatchEvent(new Event('input'));
});

// Validação do formulário de reset
document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    const password = document.getElementById('new_password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('As senhas não coincidem!');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('A senha deve ter pelo menos 6 caracteres!');
        return false;
    }
    
    return confirm('Tem certeza que deseja redefinir a senha deste usuário?');
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>