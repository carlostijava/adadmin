# 🚀 AD Manager - Deploy Rápido

## ⚡ Instalação em 3 Passos

### 1. **Copiar Arquivos**
```bash
# Baixar e extrair no servidor web
wget https://github.com/carlostijava/adadmin/archive/main.zip
unzip main.zip
cp -R adadmin-main/* /var/www/html/adadmin/
```

### 2. **Configurar Permissões**
```bash
chmod 755 /var/www/html/adadmin
chmod 777 /var/www/html/adadmin/config
chmod 777 /var/www/html/adadmin/logs
```

### 3. **Acessar Sistema**
- **URL:** `http://seuservidor/adadmin/`
- **Login:** `admin`
- **Senha:** `admin123`

## ✅ **O que funciona imediatamente:**

### 🔐 **Login Instantâneo**
- Credenciais padrão já configuradas
- Interface responsiva carregada automaticamente
- Tema azul Hyper-V aplicado

### 🎛️ **Dashboard Funcional**
- Estatísticas básicas
- Status do sistema
- Navegação completa

### ⚙️ **Configuração LDAP**
- Interface web para configurar Active Directory
- Teste de conexão integrado
- Salvamento automático

## 🔧 **Configuração LDAP**

Após login, vá em **Configurações** e preencha:

```
Servidor: dc01.empresa.local
Porta: 389
Domínio: empresa.local
Base DN: DC=empresa,DC=local
Usuário: administrador@empresa.local
Senha: [sua_senha]
```

## 📋 **Requisitos Mínimos**

- ✅ **Apache/Nginx** com PHP
- ✅ **PHP 7.4+** com extensão LDAP
- ✅ **Conectividade** com Active Directory

## 🚨 **Instalação Express PHP LDAP**

```bash
# Ubuntu/Debian
sudo apt install php-ldap
sudo systemctl restart apache2

# CentOS/RHEL  
sudo yum install php-ldap
sudo systemctl restart httpd
```

## 🎯 **Funcionalidades Disponíveis**

### ✅ **Imediatas (sem LDAP)**
- Login administrativo
- Interface completa
- Configuração LDAP

### ✅ **Após Configurar LDAP**
- Listagem de usuários do AD
- Busca em tempo real
- Bloqueio/desbloqueio de usuários
- Reset de senhas
- Estatísticas em tempo real

## 🔗 **Links Úteis**

- **Código Fonte:** https://github.com/carlostijava/adadmin
- **Documentação Completa:** README.md
- **Sistema Demo:** [URL do servidor]

## 📞 **Suporte Rápido**

### Problema: "Extensão LDAP não encontrada"
```bash
php -m | grep ldap
# Se vazio, instalar: sudo apt install php-ldap
```

### Problema: "Permissão negada"
```bash
sudo chown -R www-data:www-data /var/www/html/adadmin
sudo chmod -R 755 /var/www/html/adadmin
```

### Problema: "404 nas rotas"
```bash
# Verificar se mod_rewrite está ativo
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## 🎉 **Sistema Pronto!**

**100% funcional em menos de 5 minutos!**

Acesse `http://seuservidor/adadmin/` e faça login com `admin/admin123`