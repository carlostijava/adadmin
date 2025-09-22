<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'AD Manager'); ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
</head>
<body>
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <a href="/dashboard" class="logo">
                    <strong>AD Manager</strong>
                </a>
                <div class="user-info">
                    <span>Bem-vindo, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                    <a href="/logout" class="logout-btn">Sair</a>
                </div>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="nav">
            <div class="nav-content">
                <ul class="nav-links">
                    <li><a href="/dashboard" class="nav-link <?php echo ($_SERVER['REQUEST_URI'] == '/dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="/users" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/users') === 0) ? 'active' : ''; ?>">Usuários</a></li>
                    <?php if ($_SESSION['user_type'] === 'admin'): ?>
                        <li><a href="/config" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/config') === 0) ? 'active' : ''; ?>">Configurações</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="container fade-in">
        <?php
        // Exibir mensagens de sessão
        if (isset($_SESSION['success'])):
        ?>
            <div class="alert alert-success slide-in">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php
        if (isset($_SESSION['error'])):
        ?>
            <div class="alert alert-error slide-in">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Page Content -->
        <?php echo $content ?? ''; ?>
    </main>

    <!-- Footer -->
    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
        <footer style="background: var(--light-blue); padding: 20px 0; text-align: center; margin-top: auto; border-top: 1px solid var(--border-color);">
            <div style="max-width: 1200px; margin: 0 auto; color: var(--text-secondary);">
                <p>&copy; <?php echo date('Y'); ?> AD Manager - Sistema de Gestão de Usuários do Active Directory</p>
            </div>
        </footer>
    <?php endif; ?>

    <!-- JavaScript -->
    <script src="/js/app.js"></script>
</body>
</html>