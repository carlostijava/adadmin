<?php
ob_start();
?>

<div class="login-container">
    <div class="login-card">
        <h1 class="login-title">AD Manager</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/login">
            <div class="form-group">
                <label for="username" class="form-label">Usuário</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-input" 
                    required 
                    autofocus
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">Senha</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    required
                >
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary w-full">
                    Entrar
                </button>
            </div>
        </form>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color); text-align: center;">
            <small style="color: var(--text-secondary);">
                <strong>Login Padrão:</strong><br>
                Usuário: <code>admin</code><br>
                Senha: <code>admin123</code>
            </small>
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
            <small style="color: var(--text-secondary);">
                Sistema de Gestão de Usuários do Active Directory
            </small>
        </div>
    </div>
</div>

<style>
body {
    margin: 0;
    padding: 0;
    background: linear-gradient(135deg, var(--light-blue), var(--primary-blue));
}

code {
    background: var(--light-blue);
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
?>