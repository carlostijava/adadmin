<?php
ob_start();
?>

<div class="card">
    <div class="card-header">
        <h1 class="card-title">Dashboard</h1>
    </div>
    
    <?php if (!$ldapConfigured): ?>
        <div class="alert alert-warning">
            <strong>Configuração necessária!</strong><br>
            Para gerenciar usuários do Active Directory, você precisa configurar a conexão LDAP.
            <br><br>
            <a href="/config" class="btn btn-primary">Configurar LDAP</a>
        </div>
    <?php else: ?>
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Total de Usuários</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number" style="color: var(--success-green);"><?php echo $stats['enabled_users']; ?></div>
                <div class="stat-label">Usuários Ativos</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number" style="color: var(--error-red);"><?php echo $stats['disabled_users']; ?></div>
                <div class="stat-label">Usuários Bloqueados</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number" style="color: <?php echo ($stats['ldap_status'] === 'Conectado') ? 'var(--success-green)' : 'var(--error-red)'; ?>;">
                    <?php echo ($stats['ldap_status'] === 'Conectado') ? '✓' : '✗'; ?>
                </div>
                <div class="stat-label">Status LDAP: <?php echo htmlspecialchars($stats['ldap_status']); ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Ações Rápidas -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Ações Rápidas</h2>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div style="padding: 20px; border: 2px solid var(--primary-blue); border-radius: 8px; text-align: center;">
            <h3 style="color: var(--primary-blue); margin-bottom: 15px;">Gerenciar Usuários</h3>
            <p style="color: var(--text-secondary); margin-bottom: 20px;">
                Visualizar, bloquear, desbloquear e redefinir senhas de usuários do Active Directory.
            </p>
            <a href="/users" class="btn btn-primary">Acessar Usuários</a>
        </div>
        
        <?php if ($_SESSION['user_type'] === 'admin'): ?>
        <div style="padding: 20px; border: 2px solid var(--secondary-blue); border-radius: 8px; text-align: center;">
            <h3 style="color: var(--secondary-blue); margin-bottom: 15px;">Configurações</h3>
            <p style="color: var(--text-secondary); margin-bottom: 20px;">
                Configurar conexão LDAP, definir parâmetros de servidor e domínio.
            </p>
            <a href="/config" class="btn btn-secondary">Acessar Configurações</a>
        </div>
        <?php endif; ?>
        
        <div style="padding: 20px; border: 2px solid var(--success-green); border-radius: 8px; text-align: center;">
            <h3 style="color: var(--success-green); margin-bottom: 15px;">Busca Avançada</h3>
            <p style="color: var(--text-secondary); margin-bottom: 20px;">
                Buscar usuários por nome, departamento, e-mail ou outras propriedades.
            </p>
            <a href="/users" class="btn btn-success">Buscar Usuários</a>
        </div>
    </div>
</div>

<!-- Informações do Sistema -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Informações do Sistema</h2>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <div>
            <h4 style="color: var(--primary-blue); margin-bottom: 10px;">Usuário Logado</h4>
            <p><strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
            <p><small>Tipo: <?php echo ($_SESSION['user_type'] === 'admin') ? 'Administrador' : 'Usuário LDAP'; ?></small></p>
        </div>
        
        <div>
            <h4 style="color: var(--primary-blue); margin-bottom: 10px;">Status da Conexão</h4>
            <p><strong><?php echo htmlspecialchars($stats['ldap_status']); ?></strong></p>
            <?php if ($ldapConfigured): ?>
                <p><small>Host: <?php echo htmlspecialchars(LDAP_HOST . ':' . LDAP_PORT); ?></small></p>
            <?php endif; ?>
        </div>
        
        <div>
            <h4 style="color: var(--primary-blue); margin-bottom: 10px;">Última Atualização</h4>
            <p><strong><?php echo date('d/m/Y H:i:s'); ?></strong></p>
            <p><small>Dados em tempo real</small></p>
        </div>
        
        <div>
            <h4 style="color: var(--primary-blue); margin-bottom: 10px;">Versão</h4>
            <p><strong>AD Manager 1.0</strong></p>
            <p><small>Sistema de Gestão AD</small></p>
        </div>
    </div>
</div>

<?php if ($ldapConfigured): ?>
<!-- Auto-refresh para estatísticas -->
<script>
// Atualizar estatísticas a cada 30 segundos
setTimeout(function() {
    window.location.reload();
}, 30000);
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include 'layout.php';
?>