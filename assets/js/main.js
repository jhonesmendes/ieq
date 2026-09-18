// Funções utilitárias JavaScript

// Função de Sanitização Global (XSS Prevention)
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Função para fazer requisições à API
async function fazerRequisicao(acao, metodo = 'GET', dados = null) {
    const url = `api/index.php?acao=${acao}`;
    const opcoes = {
        method: metodo,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    };

    if (metodo === 'POST') {
        const params = new URLSearchParams();
        
        // Adicionar CSRF token automaticamente
        const csrfToken = localStorage.getItem('csrf_token');
        if (csrfToken) {
            params.append('csrf_token', csrfToken);
        }
        
        // Processar cada campo dos dados passados
        if (dados) {
            for (const [chave, valor] of Object.entries(dados)) {
                // Não enviar campos com valor undefined (não fornecidos)
                // Mas enviar null e '' (string vazia) para permitir remover valores
                if (valor === undefined) {
                    continue;
                }
                
                if (Array.isArray(valor)) {
                    // Se é array, adicionar múltiplas vezes com []
                    valor.forEach(item => {
                        params.append(`${chave}[]`, item);
                    });
                } else {
                    // Enviar mesmo se null ou '' para permitir remover valores no backend
                    params.append(chave, valor ?? '');
                }
            }
        }
        
        opcoes.body = params.toString();
    }

    try {
        const resposta = await fetch(url, opcoes);
        const textoResposta = await resposta.text();
        
        // Tentar fazer parse do JSON
        try {
            return JSON.parse(textoResposta);
        } catch (erroJson) {
            console.error('Resposta não é JSON válido:', textoResposta);
            return { 
                status: 'erro', 
                mensagem: 'Erro no servidor - resposta inválida',
                debug: textoResposta.substring(0, 200) 
            };
        }
    } catch (erro) {
        console.error('Erro na requisição:', erro);
        return { status: 'erro', mensagem: 'Erro ao fazer a requisição: ' + erro.message };
    }
}

// Função para exibir notificações
function mostrarNotificacao(mensagem, tipo = 'info') {
    const notif = document.createElement('div');
    notif.className = `notificacao notificacao-${tipo}`;
    notif.textContent = mensagem;
    
    // Style injected dynamically, but also handled by CSS
    notif.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        background: ${tipo === 'sucesso' ? 'var(--success, #4caf50)' : tipo === 'erro' ? 'var(--danger, #d32f2f)' : 'var(--info, #2196f3)'};
        color: white;
        border-radius: var(--radius-md, 4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        animation: slideIn 0.3s ease;
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
    `;
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// Animação de slide
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);

// Função para formatar data
function formatarData(data) {
    if (!data) return '-';
    const d = new Date(data);
    return d.toLocaleDateString('pt-BR');
}

function formatarDataHora(data) {
    if (!data) return '-';
    const d = new Date(data);
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});
}

// Função para validar email
function validarEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Exportar funções
window.ieq = {
    escapeHtml,
    fazerRequisicao,
    mostrarNotificacao,
    formatarData,
    formatarDataHora,
    validarEmail
};
