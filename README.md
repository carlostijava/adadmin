# AD Manager - Sistema de Gestão de Usuários do Active Directory

Sistema completo para gerenciamento de usuários do Active Directory desenvolvido em PHP com arquitetura MVC, interface responsiva e tema azul estilo Hyper-V.

## 🚀 Características

- ✅ **Login administrativo padrão** (admin/admin123)
- ✅ **Configuração LDAP integrada** via interface web
- ✅ **Listagem de usuários do AD** com busca em tempo real
- ✅ **Bloqueio/desbloqueio individual** de usuários
- ✅ **Bloqueio/desbloqueio em massa** via checkboxes
- ✅ **Reset de senha** com validação
- ✅ **Interface responsiva** com tema azul Hyper-V
- ✅ **Busca em tempo real** nos usuários
- ✅ **Sistema de autenticação via AD**
- ✅ **Arquitetura MVC organizada**
- ✅ **Segurança integrada** com proteções XSS e CSRF

## 📋 Requisitos

### Servidor
- Apache 2.4+
- PHP 7.4+ ou 8.x
- Extensão PHP LDAP habilitada
- mod_rewrite habilitado

### Active Directory
- Servidor Windows Server com AD configurado
- Usuário administrativo com permissões para:
  - Leitura de propriedades de usuários
  - Modificação de userAccountControl
  - Reset de senhas (unicodePwd)
- Conectividade na porta 389 (LDAP) ou 636 (LDAPS)

## 🛠 Instalação

### 1. Configuração do Ambiente

```bash
# Instalar dependências PHP (Ubuntu/Debian)
sudo apt update
sudo apt install apache2 php php-ldap php-mbstring

# Habilitar mod_rewrite
sudo a2enmod rewrite

# Reiniciar Apache
sudo systemctl restart apache2
```

### 2. Configuração do Sistema

1. **Clone ou copie os arquivos** para o diretório do servidor web
2. **Defina permissões** adequadas:

```bash
# Permissões para o diretório config
chmod 755 config/
chown www-data:www-data config/

# Permissões para logs
chmod 755 logs/
chown www-data:www-data logs/
```

3. **Configure o Apache** para apontar para o diretório public/ ou configure um VirtualHost

### 3. Configuração do VirtualHost (Recomendado)

```apache
<VirtualHost *:80>
    ServerName admanager.local
    DocumentRoot /var/www/html/ad-manager/public
    
    <Directory /var/www/html/ad-manager/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/admanager_error.log
    CustomLog ${APACHE_LOG_DIR}/admanager_access.log combined
</VirtualHost>
```

## 🔧 Configuração Inicial

### 1. Primeiro Acesso

1. Acesse o sistema via navegador
2. Use as credenciais padrão:
   - **Usuário:** `admin`
   - **Senha:** `admin123`

### 2. Configuração LDAP

1. Após o login, acesse **Configurações**
2. Preencha os dados do Active Directory:

```
Servidor LDAP: dc01.empresa.local
Porta: 389 (ou 636 para SSL)
Domínio: empresa.local
Base DN: DC=empresa,DC=local
Usuário Admin: administrador@empresa.local
Senha: [senha do administrador]
```

3. **Teste a conexão** antes de salvar
4. **Salve a configuração**

### 3. Exemplos de Configuração

#### Configuração Básica
```
Host: 192.168.1.10
Porta: 389
Domínio: empresa.local
Base DN: DC=empresa,DC=local
Admin: admin@empresa.local
```

#### Configuração com SSL
```
Host: ldap.empresa.com
Porta: 636
TLS/SSL: ✓ Habilitado
Domínio: empresa.com
Base DN: DC=empresa,DC=com
Admin: ldapadmin@empresa.com
```

## 📖 Uso do Sistema

### Dashboard
- **Estatísticas** dos usuários (total, ativos, bloqueados)
- **Status da conexão** LDAP
- **Ações rápidas** para navegação

### Gerenciamento de Usuários
- **Listagem completa** de usuários do AD
- **Busca em tempo real** por nome, username ou email
- **Ações individuais:**
  - Bloquear/desbloquear usuário
  - Reset de senha
- **Ações em massa:**
  - Seleção múltipla via checkbox
  - Bloqueio/desbloqueio em massa

### Configurações (Admin)
- **Configuração LDAP** com teste de conexão
- **Validação** de parâmetros em tempo real
- **Status** da configuração atual

## 🔒 Segurança

### Autenticação
- Login administrativo padrão
- Autenticação via Active Directory
- Sistema de sessões seguras
- Logout automático por inatividade

### Proteções Implementadas
- **XSS Protection:** Headers de segurança
- **CSRF Protection:** Validação de tokens
- **Input Validation:** Sanitização de dados
- **SQL Injection:** Uso de prepared statements (não aplicável - usa LDAP)
- **Directory Traversal:** Validação de caminhos
- **File Access:** Bloqueio de arquivos sensíveis

### Headers de Segurança
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'self'
```

## 📁 Estrutura do Projeto

```
ad-manager/
├── app/
│   ├── controllers/          # Controllers MVC
│   │   ├── api/             # Controllers da API
│   │   ├── AuthController.php
│   │   ├── ConfigController.php
│   │   ├── DashboardController.php
│   │   └── UserController.php
│   ├── models/              # Models MVC
│   │   └── User.php
│   └── views/               # Views MVC
│       ├── config.php
│       ├── dashboard.php
│       ├── layout.php
│       ├── login.php
│       └── users.php
├── config/                  # Configurações (geradas automaticamente)
│   └── ldap.php            # Configuração LDAP
├── logs/                   # Logs do sistema
├── public/                 # Diretório público (document root)
│   ├── css/
│   │   └── style.css       # Estilos tema Hyper-V
│   ├── js/
│   │   └── app.js          # JavaScript principal
│   ├── images/             # Imagens e ícones
│   ├── .htaccess          # Configuração Apache
│   └── index.php          # Ponto de entrada
├── .htaccess              # Configuração raiz Apache
└── README.md              # Documentação
```

## 🎨 Interface

### Tema Visual
- **Core:** Fundo branco limpo
- **Cores principais:** Tons de azul estilo Hyper-V
- **Tipografia:** Segoe UI (fonte do Windows)
- **Layout:** Design responsivo e moderno
- **Componentes:** Cards, modais, tabelas estilizadas

### Responsividade
- **Desktop:** Layout completo com sidebar
- **Tablet:** Layout adaptado com navegação otimizada
- **Mobile:** Interface compacta com menu collapse

## 🔄 API Endpoints

### Autenticação
- `POST /login` - Login no sistema
- `GET /logout` - Logout do sistema

### Usuários
- `GET /users` - Listagem de usuários
- `GET /api/users/search?q=termo` - Busca AJAX de usuários
- `POST /users/block` - Bloquear usuário
- `POST /users/unblock` - Desbloquear usuário
- `POST /users/reset-password` - Reset de senha
- `POST /users/bulk-action` - Ações em massa

### Configurações
- `GET /config` - Página de configurações
- `POST /config/ldap` - Salvar configuração LDAP

## 📝 Logs

### Localização
- **Logs do sistema:** `/logs/`
- **Logs do PHP:** `/logs/php_errors.log`
- **Logs do Apache:** Conforme configuração do server

### Tipos de Log
- Erros de conexão LDAP
- Tentativas de login
- Alterações de usuários
- Erros de sistema

## 🛠 Troubleshooting

### Problemas Comuns

#### 1. Erro: "Extensão LDAP não encontrada"
```bash
# Ubuntu/Debian
sudo apt install php-ldap
sudo systemctl restart apache2

# CentOS/RHEL
sudo yum install php-ldap
sudo systemctl restart httpd
```

#### 2. Erro: "Permissão negada ao salvar configuração"
```bash
chmod 755 config/
chown www-data:www-data config/
```

#### 3. Erro: "Conexão LDAP falhou"
- Verifique conectividade: `telnet [servidor] 389`
- Confirme credenciais do usuário administrador
- Verifique firewall e políticas de rede
- Teste com ldapsearch: `ldapsearch -x -H ldap://servidor -D "user" -W`

#### 4. Erro 404 nas rotas
- Verifique se mod_rewrite está habilitado
- Confirme se .htaccess tem as permissões corretas
- Verifique configuração do VirtualHost

#### 5. Interface não carrega CSS/JS
- Verifique permissões do diretório public/
- Confirme se os arquivos CSS/JS existem
- Verifique configuração do .htaccess

### Logs de Debug

Para habilitar logs detalhados, adicione ao arquivo de configuração PHP:

```php
// Em config/debug.php
define('DEBUG_MODE', true);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
```

## 🚀 Performance

### Otimizações Implementadas
- **Cache de arquivos estáticos:** 1 mês
- **Compressão gzip:** Habilitada para CSS/JS
- **Lazy loading:** Carregamento sob demanda
- **Debounce:** Busca otimizada com atraso

### Monitoramento
- Logs de acesso e erro
- Métricas de conexão LDAP
- Tempo de resposta das consultas

## 📄 Licença

Este projeto está licenciado sob a MIT License - veja o arquivo LICENSE para detalhes.

## 🤝 Contribuição

1. Fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📞 Suporte

Para suporte e dúvidas:
- Abra uma issue no repositório
- Consulte a documentação completa
- Verifique os logs de erro do sistema

---

**AD Manager v1.0** - Sistema de Gestão de Usuários do Active Directory
Desenvolvido com ❤️ para facilitar a administração do seu Active Directory.