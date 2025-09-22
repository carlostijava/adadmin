#!/bin/bash

# Script para executar o servidor AD Manager
# Sistema de Gestão de Usuários do Active Directory

echo "=== AD Manager - Sistema de Gestão de Usuários do Active Directory ==="
echo "Configurando servidor Apache com PHP..."

# Verificar se Apache está rodando
if ! pgrep -x "apache2" > /dev/null; then
    echo "Iniciando Apache..."
    sudo systemctl start apache2
else
    echo "Apache já está rodando"
fi

# Verificar status do Apache
sudo systemctl status apache2 --no-pager

# Configurar VirtualHost se não existir
VHOST_FILE="/etc/apache2/sites-available/ad-manager.conf"

if [ ! -f "$VHOST_FILE" ]; then
    echo "Criando VirtualHost para AD Manager..."
    sudo tee "$VHOST_FILE" > /dev/null << 'EOF'
<VirtualHost *:80>
    ServerName admanager.local
    DocumentRoot /var/www/html/ad-manager/public
    
    <Directory /var/www/html/ad-manager/public>
        AllowOverride All
        Require all granted
        Options -Indexes
        
        # Rewrite rules
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>
    
    # Bloquear acesso a diretórios sensíveis
    <Directory /var/www/html/ad-manager/config>
        Require all denied
    </Directory>
    
    <Directory /var/www/html/ad-manager/logs>
        Require all denied
    </Directory>
    
    <Directory /var/www/html/ad-manager/app>
        Require all denied
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/admanager_error.log
    CustomLog ${APACHE_LOG_DIR}/admanager_access.log combined
</VirtualHost>
EOF

    # Habilitar site
    sudo a2ensite ad-manager.conf
    sudo systemctl reload apache2
    echo "VirtualHost criado e habilitado!"
fi

# Verificar se PHP LDAP está instalado
if ! php -m | grep -q ldap; then
    echo "AVISO: Extensão PHP LDAP não encontrada!"
    echo "Execute: sudo apt install php-ldap"
else
    echo "✓ Extensão PHP LDAP instalada"
fi

# Informações de acesso
echo ""
echo "=== INFORMAÇÕES DE ACESSO ==="
echo "URL do Sistema: http://localhost/ad-manager/public/"
echo "VirtualHost URL: http://admanager.local/ (se configurado no /etc/hosts)"
echo ""
echo "CREDENCIAIS PADRÃO:"
echo "Usuário: admin"
echo "Senha: admin123"
echo ""
echo "PRÓXIMOS PASSOS:"
echo "1. Acesse o sistema via navegador"
echo "2. Faça login com as credenciais padrão"
echo "3. Configure a conexão LDAP em 'Configurações'"
echo ""
echo "DIRETÓRIO DO PROJETO: /var/www/html/ad-manager/"
echo "LOGS: /var/www/html/ad-manager/logs/"
echo "CONFIGURAÇÃO: /var/www/html/ad-manager/config/"
echo ""

# Verificar logs em tempo real (opcional)
echo "Para visualizar logs em tempo real:"
echo "sudo tail -f /var/log/apache2/admanager_error.log"
echo "sudo tail -f /var/log/apache2/admanager_access.log"
echo ""
echo "Sistema configurado e pronto para uso!"