/**
 * JavaScript principal do Sistema Administrativo MVC
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicialização
    initializeApp();
    
    // Event listeners
    setupEventListeners();
    
    // Auto-hide flash messages
    autoHideFlashMessages();
    
    // Initialize tooltips
    initializeTooltips();
});

/**
 * Inicializa a aplicação
 */
function initializeApp() {
    console.log('Sistema Administrativo MVC carregado');
    
    // Adiciona classe fade-in ao body
    document.body.classList.add('fade-in');
    
    // Configura CSRF token para requisições AJAX
    setupCsrfToken();
}

/**
 * Configura event listeners
 */
function setupEventListeners() {
    // Confirmação de exclusão
    setupDeleteConfirmation();
    
    // Formulários com loading
    setupFormLoading();
    
    // Sidebar toggle para mobile
    setupSidebarToggle();
    
    // Auto-save em formulários
    setupAutoSave();
}

/**
 * Configura CSRF token para AJAX
 */
function setupCsrfToken() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (csrfToken) {
        // Configura axios se disponível
        if (typeof axios !== 'undefined') {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
        }
        
        // Configura fetch
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            if (options.method && options.method.toUpperCase() === 'POST') {
                options.headers = options.headers || {};
                options.headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
            }
            return originalFetch(url, options);
        };
    }
}

/**
 * Auto-hide flash messages
 */
function autoHideFlashMessages() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

/**
 * Inicializa tooltips do Bootstrap
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Configuração de confirmação de exclusão
 */
function setupDeleteConfirmation() {
    document.addEventListener('click', function(e) {
        if (e.target.matches('.btn-delete, .delete-btn') || e.target.closest('.btn-delete, .delete-btn')) {
            e.preventDefault();
            
            const button = e.target.matches('.btn-delete, .delete-btn') ? e.target : e.target.closest('.btn-delete, .delete-btn');
            const message = button.getAttribute('data-message') || 'Tem certeza que deseja excluir este item?';
            
            if (confirm(message)) {
                const form = button.closest('form');
                if (form) {
                    form.submit();
                } else {
                    window.location.href = button.getAttribute('href');
                }
            }
        }
    });
}

/**
 * Loading em formulários
 */
function setupFormLoading() {
    document.addEventListener('submit', function(e) {
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (submitBtn && !submitBtn.disabled) {
            // Adiciona spinner
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processando...';
            submitBtn.disabled = true;
            
            // Restaura o botão se houver erro de validação
            setTimeout(() => {
                if (form.querySelector('.is-invalid')) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 100);
        }
    });
}

/**
 * Toggle da sidebar para mobile
 */
function setupSidebarToggle() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
        
        // Fecha sidebar ao clicar fora (mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth < 768 && 
                !sidebar.contains(e.target) && 
                !sidebarToggle.contains(e.target) &&
                sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        });
    }
}

/**
 * Auto-save em formulários (draft)
 */
function setupAutoSave() {
    const autoSaveForms = document.querySelectorAll('.auto-save');
    
    autoSaveForms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('input', debounce(function() {
                saveFormDraft(form);
            }, 2000));
        });
        
        // Carrega draft salvo
        loadFormDraft(form);
    });
}

/**
 * Salva rascunho do formulário
 */
function saveFormDraft(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    const formId = form.getAttribute('id') || 'default';
    localStorage.setItem(`form_draft_${formId}`, JSON.stringify(data));
    
    showNotification('Rascunho salvo automaticamente', 'info', 2000);
}

/**
 * Carrega rascunho do formulário
 */
function loadFormDraft(form) {
    const formId = form.getAttribute('id') || 'default';
    const draft = localStorage.getItem(`form_draft_${formId}`);
    
    if (draft) {
        const data = JSON.parse(draft);
        
        Object.keys(data).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input && input.value === '') {
                input.value = data[key];
            }
        });
    }
}

/**
 * Mostra notificação toast
 */
function showNotification(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    // Container de toasts
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast, { delay: duration });
    bsToast.show();
    
    // Remove o elemento após esconder
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Utilitários para AJAX
 */
const Ajax = {
    /**
     * GET request
     */
    get: function(url, callback) {
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => callback(null, data))
        .catch(error => callback(error, null));
    },
    
    /**
     * POST request
     */
    post: function(url, data, callback) {
        fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => callback(null, data))
        .catch(error => callback(error, null));
    }
};

/**
 * Utilitários para formulários
 */
const FormUtils = {
    /**
     * Serializa formulário para objeto
     */
    serialize: function(form) {
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    },
    
    /**
     * Valida formulário
     */
    validate: function(form) {
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    },
    
    /**
     * Limpa validação do formulário
     */
    clearValidation: function(form) {
        const inputs = form.querySelectorAll('.is-invalid');
        inputs.forEach(input => {
            input.classList.remove('is-invalid');
        });
    }
};

// Exporta para uso global
window.Ajax = Ajax;
window.FormUtils = FormUtils;
window.showNotification = showNotification;