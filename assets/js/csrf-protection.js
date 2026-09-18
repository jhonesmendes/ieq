/**
 * Sistema de Proteção CSRF Global
 * Injeta automaticamente tokens CSRF em todos os formulários
 * Compatível com fetch() e FormData
 */

(function() {
    'use strict';

    // Token CSRF atual da sessão
    let csrfToken = null;

    /**
     * Obtém o token CSRF do localStorage ou da página
     */
    function obterCsrfToken() {
        if (csrfToken) return csrfToken;
        
        // Tenta obter do localStorage PRIMEIRO (pois pode ter sido atualizado recentemente)
        const storedToken = localStorage.getItem('csrf_token');
        if (storedToken) {
            csrfToken = storedToken;
            console.log('Token CSRF obtido do localStorage');
            return csrfToken;
        }
        
        // Tenta obter do input hidden
        const inputToken = document.querySelector('input[name="csrf_token"]');
        if (inputToken) {
            csrfToken = inputToken.value;
            console.log('Token CSRF obtido do input hidden');
            return csrfToken;
        }
        
        // Tenta obter do meta tag
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            csrfToken = metaToken.getAttribute('content');
            console.log('Token CSRF obtido do meta tag');
            return csrfToken;
        }

        console.warn('⚠️ Token CSRF não encontrado em nenhuma fonte');
        return null;
    }

    /**
     * Armazena o token CSRF (chamado após login bem-sucedido)
     */
    function armazenarCsrfToken(token) {
        csrfToken = token;
        if (token) {
            localStorage.setItem('csrf_token', token);
            // Atualizar todos os inputs hidden
            document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                input.value = token;
            });
        }
    }

    /**
     * Injeta token CSRF em um formulário
     */
    function injetarTokenNaForm(form) {
        const token = obterCsrfToken();
        if (!token) {
            console.warn('⚠️ Token CSRF não encontrado. Formulário pode não ser protegido.');
            return;
        }

        // Verificar se já existe input com CSRF token
        if (form.querySelector('input[name="csrf_token"]')) {
            return; // Já existe
        }

        // Criar e injetar input hidden com CSRF token
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = token;
        form.insertBefore(input, form.firstChild);
    }

    /**
     * Intercepta submissão de formulários
     */
    function interceptarFormularios() {
        document.addEventListener('submit', function(e) {
            const form = e.target;
            
            // Não interceptar formulários que já têm token
            if (form.querySelector('input[name="csrf_token"]')) {
                return;
            }

            // Injetar token CSRF
            injetarTokenNaForm(form);
        });
    }

    /**
     * Intercepta chamadas fetch para adicionar CSRF token
     */
    function interceptarFetch() {
        const originalFetch = window.fetch;
        
        window.fetch = function(...args) {
            const config = args[1] || {};

            // Apenas para POST, PUT, DELETE
            if (config.method && ['POST', 'PUT', 'DELETE'].includes(config.method.toUpperCase())) {
                const token = obterCsrfToken();
                
                if (token) {
                    // Se já há headers, adicionar
                    if (!config.headers) {
                        config.headers = {};
                    }
                    config.headers['X-CSRF-Token'] = token;

                    // Se há body em JSON, adicionar token ao JSON
                    if (config.body && typeof config.body === 'string') {
                        try {
                            const data = JSON.parse(config.body);
                            if (!data.csrf_token) {
                                data.csrf_token = token;
                                config.body = JSON.stringify(data);
                            }
                        } catch (e) {
                            // Se não for JSON, ignorar
                        }
                    }
                    
                    // Se há FormData, adicionar token
                    if (config.body instanceof FormData) {
                        if (!config.body.has('csrf_token')) {
                            config.body.append('csrf_token', token);
                        }
                    }
                }

                args[1] = config;
            }

            return originalFetch.apply(this, args);
        };
    }

    /**
     * Função pública para atualizar token (chamada após login)
     */
    window.atualizarCsrfToken = function(novoToken) {
        armazenarCsrfToken(novoToken);
        console.log('✅ Token CSRF atualizado');
    };

    /**
     * Função pública para obter token (para uso em JavaScript)
     */
    window.obterTokenCsrf = function() {
        return obterCsrfToken();
    };

    /**
     * Função pública para injetar token em um formulário específico
     */
    window.injetarCsrfEmForm = function(selectorForm) {
        const form = document.querySelector(selectorForm);
        if (form) {
            injetarTokenNaForm(form);
            return true;
        }
        return false;
    };

    /**
     * Inicializar quando DOM estiver pronto
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            interceptarFormularios();
            interceptarFetch();
            
            // Injetar token em todos os formulários existentes
            document.querySelectorAll('form').forEach(form => {
                injetarTokenNaForm(form);
            });
        });
    } else {
        interceptarFormularios();
        interceptarFetch();
        
        // Injetar token em todos os formulários existentes
        document.querySelectorAll('form').forEach(form => {
            injetarTokenNaForm(form);
        });
    }

    // Log para debug
    console.log('🔒 Sistema de proteção CSRF carregado');
})();
