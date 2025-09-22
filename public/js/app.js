/**
 * JavaScript principal do AD Manager
 */

// Inicialização quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes
    initializeAlerts();
    initializeModals();
    initializeTables();
    initializeSearch();
    initializeTooltips();
    
    // Auto-refresh para páginas específicas
    if (window.location.pathname === '/dashboard') {
        startAutoRefresh();
    }
});

/**
 * Gerenciamento de alertas
 */
function initializeAlerts() {
    // Auto-dismiss de alertas após 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            fadeOutElement(alert);
        }, 5000);
    });
}

/**
 * Inicializar modais
 */
function initializeModals() {
    // Fechar modais com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    modal.style.display = 'none';
                }
            });
        }
    });
    
    // Fechar modal clicando fora
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
}

/**
 * Inicializar funcionalidades de tabelas
 */
function initializeTables() {
    // Zebra striping para tabelas
    const tables = document.querySelectorAll('.table');
    tables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            if (index % 2 === 1) {
                row.style.backgroundColor = '#f8f9fa';
            }
        });
    });
}

/**
 * Inicializar funcionalidades de busca
 */
function initializeSearch() {
    // Busca em tempo real (já implementada nas páginas específicas)
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        // Adicionar indicador de loading
        const container = input.parentElement;
        if (!container.querySelector('.search-loading')) {
            const loading = document.createElement('span');
            loading.className = 'search-loading loading';
            loading.style.display = 'none';
            loading.style.position = 'absolute';
            loading.style.right = '10px';
            loading.style.top = '50%';
            loading.style.transform = 'translateY(-50%)';
            container.style.position = 'relative';
            container.appendChild(loading);
        }
    });
}

/**
 * Inicializar tooltips
 */
function initializeTooltips() {
    // Adicionar tooltips para botões de ação
    const buttons = document.querySelectorAll('button, .btn');
    buttons.forEach(button => {
        if (button.textContent.includes('Bloquear')) {
            button.title = 'Desabilitar conta do usuário no Active Directory';
        } else if (button.textContent.includes('Desbloquear')) {
            button.title = 'Habilitar conta do usuário no Active Directory';
        } else if (button.textContent.includes('Reset')) {
            button.title = 'Redefinir senha do usuário';
        }
    });
}

/**
 * Auto-refresh para dashboard
 */
function startAutoRefresh() {
    // Refresh a cada 2 minutos se não houver atividade
    let lastActivity = Date.now();
    let refreshInterval;
    
    // Detectar atividade do usuário
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, () => {
            lastActivity = Date.now();
        });
    });
    
    // Verificar se deve fazer refresh
    refreshInterval = setInterval(() => {
        const timeSinceActivity = Date.now() - lastActivity;
        
        // Se não há atividade por 2 minutos, fazer refresh
        if (timeSinceActivity > 120000) {
            // Mostrar notificação antes de recarregar
            showNotification('Atualizando dados...', 'info');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }, 30000); // Verificar a cada 30 segundos
}

/**
 * Fade out de elementos
 */
function fadeOutElement(element) {
    if (!element) return;
    
    element.style.transition = 'opacity 0.5s';
    element.style.opacity = '0';
    
    setTimeout(() => {
        element.remove();
    }, 500);
}

/**
 * Mostrar notificações
 */
function showNotification(message, type = 'info', duration = 3000) {
    // Criar elemento de notificação
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} notification`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideIn 0.3s ease-out;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    `;
    
    // Adicionar ao body
    document.body.appendChild(notification);
    
    // Remover após duração especificada
    setTimeout(() => {
        fadeOutElement(notification);
    }, duration);
}

/**
 * Confirmar ações destrutivas
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Validar formulários
 */
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = 'var(--error-red)';
            isValid = false;
            
            // Adicionar mensagem de erro se não existir
            if (!field.parentElement.querySelector('.field-error')) {
                const error = document.createElement('small');
                error.className = 'field-error';
                error.style.color = 'var(--error-red)';
                error.textContent = 'Este campo é obrigatório';
                field.parentElement.appendChild(error);
            }
        } else {
            field.style.borderColor = 'var(--border-color)';
            
            // Remover mensagem de erro se existir
            const error = field.parentElement.querySelector('.field-error');
            if (error) {
                error.remove();
            }
        }
    });
    
    return isValid;
}

/**
 * Formatar números
 */
function formatNumber(num) {
    return new Intl.NumberFormat('pt-BR').format(num);
}

/**
 * Debounce para otimizar performance
 */
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func(...args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func(...args);
    };
}

/**
 * Utilitários de loading
 */
function showLoading(element) {
    if (!element) return;
    
    element.disabled = true;
    const originalText = element.textContent;
    element.dataset.originalText = originalText;
    element.innerHTML = '<span class="loading"></span> Carregando...';
}

function hideLoading(element) {
    if (!element) return;
    
    element.disabled = false;
    const originalText = element.dataset.originalText;
    if (originalText) {
        element.textContent = originalText;
    }
}

/**
 * Copiar para clipboard
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showNotification('Texto copiado para a área de transferência', 'success', 2000);
    } catch (err) {
        // Fallback para navegadores mais antigos
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showNotification('Texto copiado para a área de transferência', 'success', 2000);
    }
}

/**
 * Validação de senha
 */
function validatePassword(password) {
    const minLength = 6;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumbers = /\d/.test(password);
    const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);
    
    const errors = [];
    
    if (password.length < minLength) {
        errors.push(`Mínimo ${minLength} caracteres`);
    }
    
    if (!hasUpperCase) {
        errors.push('Pelo menos uma letra maiúscula');
    }
    
    if (!hasLowerCase) {
        errors.push('Pelo menos uma letra minúscula');
    }
    
    if (!hasNumbers) {
        errors.push('Pelo menos um número');
    }
    
    if (!hasSpecialChar) {
        errors.push('Pelo menos um caractere especial');
    }
    
    return {
        isValid: errors.length === 0,
        errors: errors
    };
}

/**
 * Formato de data brasileiro
 */
function formatDateBR(dateString) {
    if (!dateString) return 'N/A';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString;
    }
}

/**
 * Escape HTML para prevenir XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    
    return text.replace(/[&<>"']/g, function(m) {
        return map[m];
    });
}

// Exportar funções globais
window.ADManager = {
    showNotification,
    confirmAction,
    validateForm,
    formatNumber,
    debounce,
    showLoading,
    hideLoading,
    copyToClipboard,
    validatePassword,
    formatDateBR,
    escapeHtml
};