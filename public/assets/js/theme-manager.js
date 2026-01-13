/**
 * Theme Manager - Sistema Administrativo MVC
 * Gerenciamento completo de temas (claro/escuro/automático)
 */

class ThemeManager {
    constructor() {
        this.themes = {
            light: 'light',
            dark: 'dark',
            auto: 'auto'
        };
        
        this.storageKey = 'user_theme_preference';
        this.currentTheme = this.getStoredTheme();
        
        this.init();
    }
    
    /**
     * Inicializa o gerenciador de temas
     */
    init() {
        // Aplica tema inicial
        this.applyTheme(this.currentTheme);
        
        // Configura listeners
        this.setupEventListeners();
        
        // Monitora mudanças de preferência do sistema
        this.setupSystemThemeListener();
        
        // Atualiza UI
        this.updateThemeToggleButton();
        
        console.log('Theme Manager initialized with theme:', this.currentTheme);
    }
    
    /**
     * Retorna tema armazenado
     */
    getStoredTheme() {
        // Prioridade: localStorage > cookie > sessão > auto
        return localStorage.getItem(this.storageKey) || 
               this.getCookie('theme') || 
               (window.sessionTheme || this.themes.auto);
    }
    
    /**
     * Retorna tema efetivo (resolve 'auto')
     */
    getEffectiveTheme(theme = this.currentTheme) {
        if (theme === this.themes.auto) {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 
                   this.themes.dark : this.themes.light;
        }
        return theme;
    }
    
    /**
     * Aplica tema ao documento
     */
    applyTheme(theme) {
        const effectiveTheme = this.getEffectiveTheme(theme);
        const body = document.body;
        
        // Remove classes de tema existentes
        body.classList.remove('theme-light', 'theme-dark', 'theme-auto');
        
        // Adiciona nova classe de tema
        body.classList.add(`theme-${theme}`);
        
        // Define atributo data-bs-theme para Bootstrap
        document.documentElement.setAttribute('data-bs-theme', effectiveTheme);
        
        // Atualiza meta theme-color para mobile
        this.updateThemeColor(effectiveTheme);
        
        // Dispara evento customizado
        this.dispatchThemeChangeEvent(theme, effectiveTheme);
        
        console.log(`Theme applied: ${theme} (effective: ${effectiveTheme})`);
    }
    
    /**
     * Alterna para próximo tema
     */
    toggleTheme() {
        const themes = Object.values(this.themes);
        const currentIndex = themes.indexOf(this.currentTheme);
        const nextIndex = (currentIndex + 1) % themes.length;
        const nextTheme = themes[nextIndex];
        
        this.setTheme(nextTheme);
    }
    
    /**
     * Define tema específico
     */
    setTheme(theme) {
        if (!Object.values(this.themes).includes(theme)) {
            console.error('Invalid theme:', theme);
            return;
        }
        
        this.currentTheme = theme;
        
        // Armazena preferência
        this.storeTheme(theme);
        
        // Aplica tema
        this.applyTheme(theme);
        
        // Atualiza UI
        this.updateThemeToggleButton();
        
        // Sincroniza com servidor se usuário logado
        this.syncWithServer(theme);
    }
    
    /**
     * Armazena tema localmente
     */
    storeTheme(theme) {
        localStorage.setItem(this.storageKey, theme);
        this.setCookie('theme', theme, 30); // 30 dias
        window.sessionTheme = theme;
    }
    
    /**
     * Sincroniza tema com servidor
     */
    async syncWithServer(theme) {
        try {
            const response = await fetch('/theme/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ theme: theme })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                console.warn('Failed to sync theme with server:', data.error);
            }
        } catch (error) {
            console.warn('Error syncing theme with server:', error);
        }
    }
    
    /**
     * Configura event listeners
     */
    setupEventListeners() {
        // Theme toggle buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.theme-toggle, .theme-toggle *')) {
                e.preventDefault();
                this.toggleTheme();
            }
        });
        
        // Theme selector dropdowns
        document.addEventListener('change', (e) => {
            if (e.target.matches('.theme-selector')) {
                this.setTheme(e.target.value);
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + Shift + T para alternar tema
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'T') {
                e.preventDefault();
                this.toggleTheme();
            }
        });
    }
    
    /**
     * Monitora mudanças na preferência do sistema
     */
    setupSystemThemeListener() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        mediaQuery.addEventListener('change', (e) => {
            if (this.currentTheme === this.themes.auto) {
                // Re-aplica tema automático
                this.applyTheme(this.themes.auto);
                console.log('System theme changed, re-applying auto theme');
            }
        });
    }
    
    /**
     * Atualiza botão de alternância de tema
     */
    updateThemeToggleButton() {
        const toggleButtons = document.querySelectorAll('.theme-toggle');
        const selectors = document.querySelectorAll('.theme-selector');
        
        toggleButtons.forEach(button => {
            const icon = button.querySelector('i');
            if (icon) {
                // Remove classes de ícone existentes
                icon.className = icon.className.replace(/fa-\w+/g, '');
                
                // Adiciona ícone apropriado
                switch (this.currentTheme) {
                    case this.themes.light:
                        icon.classList.add('fa-sun');
                        button.title = 'Tema Claro (clique para alternar)';
                        break;
                    case this.themes.dark:
                        icon.classList.add('fa-moon');
                        button.title = 'Tema Escuro (clique para alternar)';
                        break;
                    case this.themes.auto:
                        icon.classList.add('fa-adjust');
                        button.title = 'Tema Automático (clique para alternar)';
                        break;
                }
            }
        });
        
        // Atualiza selectors
        selectors.forEach(select => {
            select.value = this.currentTheme;
        });
    }
    
    /**
     * Atualiza cor do tema para mobile
     */
    updateThemeColor(effectiveTheme) {
        let themeColor = '#ffffff'; // light
        
        if (effectiveTheme === this.themes.dark) {
            themeColor = '#0d1117'; // dark
        }
        
        // Atualiza ou cria meta tag
        let metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (!metaThemeColor) {
            metaThemeColor = document.createElement('meta');
            metaThemeColor.name = 'theme-color';
            document.head.appendChild(metaThemeColor);
        }
        metaThemeColor.content = themeColor;
    }
    
    /**
     * Dispara evento de mudança de tema
     */
    dispatchThemeChangeEvent(theme, effectiveTheme) {
        const event = new CustomEvent('themeChanged', {
            detail: {
                theme: theme,
                effectiveTheme: effectiveTheme,
                timestamp: new Date().toISOString()
            }
        });
        
        document.dispatchEvent(event);
    }
    
    /**
     * Utilitários para cookies
     */
    setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/;SameSite=Strict`;
    }
    
    getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    /**
     * Retorna configuração atual
     */
    getConfig() {
        return {
            current: this.currentTheme,
            effective: this.getEffectiveTheme(),
            available: this.themes,
            systemPreference: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
        };
    }
    
    /**
     * Força atualização do tema
     */
    refresh() {
        this.applyTheme(this.currentTheme);
        this.updateThemeToggleButton();
    }
}

// Inicialização automática quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Cria instância global
    window.themeManager = new ThemeManager();
    
    // Adiciona listener para mudanças de tema
    document.addEventListener('themeChanged', (e) => {
        console.log('Theme changed:', e.detail);
        
        // Atualiza gráficos se Chart.js estiver presente
        if (typeof Chart !== 'undefined') {
            Chart.helpers.each(Chart.instances, (instance) => {
                instance.update();
            });
        }
        
        // Atualiza outros componentes que dependem do tema
        const event = new CustomEvent('themeUpdated', { detail: e.detail });
        document.dispatchEvent(event);
    });
});

// Aplica tema inicial antes do DOM carregar (evita flash)
(function() {
    const getStoredTheme = () => {
        return localStorage.getItem('user_theme_preference') || 
               document.cookie.replace(/(?:(?:^|.*;\s*)theme\s*\=\s*([^;]*).*$)|^.*$/, "$1") || 
               'auto';
    };
    
    const getEffectiveTheme = (theme) => {
        if (theme === 'auto') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        return theme;
    };
    
    const theme = getStoredTheme();
    const effectiveTheme = getEffectiveTheme(theme);
    
    // Aplica tema imediatamente
    document.documentElement.setAttribute('data-bs-theme', effectiveTheme);
    document.documentElement.classList.add(`theme-${theme}`);
})();