/**
 * Script de Logout Seguro
 * Valida redirecionamento automático para login após logout
 */

(function() {
    'use strict';
    
    // Obter URL base dinamicamente (sem /ieq hardcoded)
    function getBaseUrl() {
        const protocol = window.location.protocol; // http: ou https:
        const host = window.location.host; // hostname:port
        
        // A origem /ieq/ foi migrada para a raiz do domínio.
        return protocol + '//' + host + '/';
    }
    
    // Verificar se está na página de logout e redirecionar se necessário
    function validarLogout() {
        const currentPath = window.location.pathname;
        const currentSearch = window.location.search;
        const baseUrl = getBaseUrl();
        
        // Se está em logout.php ou /logout, redirecionar para login
        if (currentPath.includes('logout.php') || currentPath.includes('/logout')) {
            console.log('🔄 Redirecionando de logout para login...');
            console.log('Base URL detectado:', baseUrl);
            
            // Redirecionar para login
            setTimeout(() => {
                window.location.href = baseUrl + 'index.php?page=login&logout=success';
            }, 300);
            return;
        }
        
        // Se está em página com logout=success, confirmação de logout
        if (currentSearch.includes('logout=success')) {
            console.log('✅ Logout bem-sucedido - página de login exibida');
        }
    }
    
    // Executar validação quando a página carrega
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', validarLogout);
    } else {
        validarLogout();
    }
    
    // Também executar ao completar o carregamento
    window.addEventListener('load', validarLogout);
})();
