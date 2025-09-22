# 🔧 Solução Definitiva para Erro "Cannot redeclare showPage()" no XAMPP

## 🚨 PROBLEMA RESOLVIDO

O erro `Fatal error: Cannot redeclare showPage()` estava ocorrendo devido a conflitos de função em versões anteriores. 

## ✅ SOLUÇÃO FINAL

Use o arquivo **`admanager-final.php`** - Esta versão foi completamente reescrita com:

- ✅ **Funções com nomes únicos** (sem conflitos)
- ✅ **Código 100% limpo** (testado sintaxe OK)
- ✅ **Compatibilidade XAMPP garantida**
- ✅ **Sistema completo em arquivo único**

## 📋 PASSOS PARA INSTALAÇÃO NO XAMPP

### 1. Preparar XAMPP
```bash
# Inicie Apache e MySQL no painel XAMPP
# Acesse: http://localhost/phpmyadmin (para verificar funcionamento)
```

### 2. Instalar o AD Manager
```bash
# Copie APENAS o arquivo admanager-final.php para:
C:\xampp\htdocs\admanager-final.php

# OU crie uma pasta específica:
C:\xampp\htdocs\admanager\
    ├── admanager-final.php
    └── config\  (será criada automaticamente)
```

### 3. Configurar Permissões
```bash
# No Windows, clique com botão direito na pasta xampp\htdocs
# Propriedades > Segurança > Editar
# Dar "Controle Total" para "Usuários"

# OU via comando (como administrador):
icacls "C:\xampp\htdocs" /grant Users:F /T
```

### 4. Verificar PHP LDAP
```bash
# Edite: C:\xampp\php\php.ini
# Descomente (remova ; do início):
extension=ldap

# Reinicie Apache no painel XAMPP
```

## 🌐 ACESSAR O SISTEMA

### URL de Acesso
```
http://localhost/admanager-final.php
```

### Login Padrão
- **Usuário:** `admin`
- **Senha:** `admin123`

## 🔍 VERIFICAÇÕES DE FUNCIONAMENTO

### 1. Teste Básico
```bash
# Acesse: http://localhost/admanager-final.php
# Deve aparecer a tela de login sem erros
```

### 2. Teste de Sintaxe PHP
```bash
# Via linha de comando (opcional):
C:\xampp\php\php.exe -l C:\xampp\htdocs\admanager-final.php
# Deve retornar: "No syntax errors detected"
```

### 3. Verificar Logs de Erro
```bash
# Se houver erros, consulte:
C:\xampp\apache\logs\error.log
```

## 🛠 SOLUÇÃO DE PROBLEMAS

### Se ainda der erro de função redeclarada:

1. **Limpe o cache do navegador** (Ctrl+F5)
2. **Reinicie o Apache** no painel XAMPP
3. **Verifique se não há outros arquivos** PHP na mesma pasta que possam ter funções similares
4. **Use modo incógnito** do navegador para testar

### Se a extensão LDAP não funcionar:

1. Abra `C:\xampp\php\php.ini`
2. Procure por `extension=ldap`
3. Remova o `;` do início da linha
4. Reinicie Apache
5. Verifique em `http://localhost/dashboard/phpinfo.php` se LDAP aparece

### Estrutura de Diretórios Criada Automaticamente:
```
C:\xampp\htdocs\
├── admanager-final.php
└── config\
    ├── ad_config.json (criado automaticamente)
    └── users.json (criado automaticamente)
```

## 📝 FUNCIONALIDADES INCLUÍDAS

✅ **Login/Logout** com sessões PHP  
✅ **Dashboard** com estatísticas  
✅ **Gerenciamento de usuários** LDAP/AD  
✅ **Configuração LDAP** via interface  
✅ **Design Hyper-V** (azul/branco)  
✅ **Responsivo** para mobile/desktop  
✅ **Sistema de arquivos** para configurações  

## 🎯 PRÓXIMOS PASSOS

1. **Acesse o sistema** e faça login
2. **Configure o LDAP** em "Configuração"
3. **Teste a conexão** com seu Active Directory
4. **Gerencie usuários** via interface

## ⚠️ IMPORTANTE

- Este arquivo substitui TODOS os anteriores
- Use APENAS `admanager-final.php`
- Delete versões antigas se existirem
- Todas as funções têm nomes únicos para evitar conflitos

## 🚀 RESULTADO ESPERADO

Após seguir estes passos, você terá:
- ✅ Sistema funcionando sem erros
- ✅ Interface completa e responsiva
- ✅ Todas as funcionalidades do AD Manager
- ✅ Compatibilidade total com XAMPP