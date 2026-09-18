<?php
require_once 'config/permissoes.php';

// Apenas admin pode acessar
if (!is_admin()) {
    header('Location: index.php?page=dashboard&erro=sem_permissao');
    exit;
}
?>

<!-- Cabeçalho da Página -->
<div class="page-header">
    <div>
        <h1>👥 Gerenciamento de Usuários</h1>
        <p>Controle de acesso e permissões do sistema</p>
    </div>
    <button class="btn btn-primary" onclick="abrirModalUsuario()">+ Novo Usuário</button>
</div>

<!-- Estatísticas de Usuários -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">👑</div>
        <div class="stat-info">
            <div class="stat-value" id="totalAdmins">0</div>
            <div class="stat-label">Administradores</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">✝️</div>
        <div class="stat-info">
            <div class="stat-value" id="totalPastores">0</div>
            <div class="stat-label">Pastores</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">👔</div>
        <div class="stat-info">
            <div class="stat-value" id="totalSupervisores">0</div>
            <div class="stat-label">Supervisores</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">⭐</div>
        <div class="stat-info">
            <div class="stat-value" id="totalLideres">0</div>
            <div class="stat-label">Líderes</div>
        </div>
    </div>
</div>

<!-- Lista de Usuários -->
<div class="card">
    <div class="card-header">
        <h3>📋 Usuários do Sistema</h3>
        <div class="filtros">
            <input type="text" id="searchUsuario" placeholder="Buscar usuário..." class="search-input">
            <select id="filterFuncao" class="filter-select">
                <option value="">Todas as funções</option>
                <option value="admin">👑 Admin</option>
                <option value="pastor">✝️ Pastor</option>
                <option value="supervisor">👔 Supervisor</option>
                <option value="lider">⭐ Líder</option>
                <option value="lider_treinamento">📚 Líder em Treinamento</option>
                <option value="gestor_igreja">⛪ Gestor da Igreja</option>
                <option value="membro">👤 Membro</option>
            </select>
        </div>
    </div>

    <div id="usuariosList" class="usuarios-grid">
        <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
            ⏳ Carregando usuários...
        </div>
    </div>
</div>

<!-- Modal de Usuário -->
<div id="usuarioModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="fecharModalUsuario()">&times;</span>
        <h2 id="modalTitle">Novo Usuário</h2>
        
        <form id="usuarioForm" onsubmit="salvarUsuario(event)">
            <input type="hidden" id="usuarioId">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome Completo *</label>
                    <input type="text" id="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" placeholder="(00) 00000-0000">
                </div>
                
                <div class="form-group">
                    <label for="funcao">Função *</label>
                    <select id="funcao" required>
                        <option value="membro">👤 Membro</option>
                        <option value="lider">⭐ Líder</option>
                        <option value="lider_treinamento">📚 Líder em Treinamento</option>
                        <option value="gestor_igreja">⛪ Gestor da Igreja</option>
                        <option value="supervisor">👔 Supervisor</option>
                        <option value="pastor">✝️ Pastor</option>
                        <option value="admin">👑 Administrador</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group" id="senhaGroup">
                <label for="senha">Senha *</label>
                <input type="password" id="senha" placeholder="Mínimo 6 caracteres">
                <small>Deixe em branco para manter a senha atual (ao editar)</small>
            </div>
            
            <div class="alert alert-info">
                <strong>ℹ️ Permissões por Função:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <li><strong>Admin:</strong> Acesso total ao sistema</li>
                    <li><strong>Pastor:</strong> Gerencia todas as células e membros</li>
                    <li><strong>Supervisor:</strong> Gerencia células específicas</li>
                    <li><strong>Líder:</strong> Gerencia apenas sua célula</li>
                    <li><strong>Líder em Treinamento:</strong> Auxilia o líder e gerencia sua célula</li>
                    <li><strong>Membro:</strong> Acesso apenas leitura</li>
                </ul>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="fecharModalUsuario()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Usuário</button>
            </div>
        </form>
    </div>
</div>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
    }

    .page-header h1 {
        margin: 0;
        font-size: 28px;
    }

    .page-header p {
        margin: 5px 0 0 0;
        opacity: 0.9;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }

    .stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #333;
    }

    .stat-label {
        font-size: 14px;
        color: #666;
    }

    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 300px);
    }

    .card-header {
        padding: 20px;
        background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
        border-bottom: 2px solid #e0e7ff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        flex-shrink: 0;
    }

    .filtros {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .search-input, .filter-select {
        padding: 8px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }

    .search-input {
        min-width: 250px;
    }

    /* Container para tabela com scroll */
    .card > table,
    .card > div[class*="table"] {
        overflow-y: auto;
        flex: 1;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        display: block;
        overflow-y: auto;
    }

    .data-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .data-table th {
        padding: 15px;
        text-align: left;
        font-weight: 500;
    }

    .data-table tbody {
        display: table;
        width: 100%;
    }

    .data-table td {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .data-table tbody tr:hover {
        background: #f8f9ff;
    }

    .badge {
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
    }

    .badge-admin { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .badge-pastor { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
    .badge-supervisor { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
    .badge-lider { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; }
    .badge-lider_treinamento { background: linear-gradient(135deg, #e67e22 0%, #d35400 100%); color: white; }
    .badge-membro { background: #e3e3e3; color: #666; }
    .badge-ativo { background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%); color: white; }

    .status-ativo { background: #e8f5e9; color: #388e3c; }
    .status-inativo { background: #ffebee; color: #d32f2f; }

    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-edit {
        background: #2196f3;
        color: white;
        padding: 6px 12px;
        font-size: 12px;
    }

    .btn-delete {
        background: #f44336;
        color: white;
        padding: 6px 12px;
        font-size: 12px;
        margin-left: 5px;
    }

    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 12px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
    }

    .close {
        position: absolute;
        right: 20px;
        top: 20px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        color: #999;
    }

    .close:hover {
        color: #333;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #333;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }

    .form-group small {
        color: #666;
        font-size: 12px;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 30px;
    }

    .alert {
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .alert-info {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        /* Ajustar altura máxima em mobile */
        .card {
            max-height: calc(100vh - 250px);
        }

        .data-table {
            width: 100%;
        }

        .data-table th,
        .data-table td {
            padding: 12px 8px;
            font-size: 0.8rem;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
            min-height: 45px;
            vertical-align: middle;
        }

        .data-table th {
            min-height: 50px;
            font-size: 0.75rem;
        }

        .data-table .btn-small,
        .data-table button {
            min-height: 40px;
            padding: 8px 10px;
            font-size: 0.75rem;
            margin: 4px 0;
            white-space: normal;
        }

        .badge {
            display: block;
            margin: 4px 0;
            font-size: 0.7rem;
            padding: 4px 8px;
        }
    }

    /* Grid de Cards para Usuários */
    .usuarios-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }

    /* Card individual do usuário */
    .usuario-card {
        background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);
        border: 1px solid #e0e7ff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .usuario-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        border-color: #667eea;
    }

    .usuario-nome {
        font-size: 18px;
        font-weight: 700;
        color: #1a202c;
        word-break: break-word;
    }

    .usuario-info {
        display: flex;
        flex-direction: column;
        gap: 8px;
        font-size: 14px;
        color: #4a5568;
    }

    .usuario-info-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .usuario-info-label {
        font-weight: 600;
        color: #2d3748;
        min-width: 80px;
    }

    .usuario-info-valor {
        color: #4a5568;
        word-break: break-word;
        flex: 1;
    }

    .usuario-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin: 8px 0;
    }

    .usuario-actions {
        display: flex;
        gap: 8px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e0e7ff;
    }

    .usuario-actions .btn {
        flex: 1;
        padding: 8px 12px !important;
        font-size: 13px !important;
        height: auto !important;
        margin: 0 !important;
    }

    @media (max-width: 768px) {
        .usuarios-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            padding: 15px;
        }

        .usuario-card {
            padding: 15px;
        }

        .usuario-nome {
            font-size: 16px;
        }

        .usuario-info {
            font-size: 13px;
        }
    }

    @media (max-width: 480px) {
        .usuarios-grid {
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 12px;
        }

        .usuario-card {
            padding: 15px;
        }

        .usuario-nome {
            font-size: 15px;
        }

        .usuario-info {
            font-size: 13px;
        }

        .usuario-actions {
            flex-wrap: wrap;
        }
    }
</style>

<script>
    let usuarios = [];

    document.addEventListener('DOMContentLoaded', function() {
        carregarUsuarios();
        
        document.getElementById('searchUsuario').addEventListener('input', filtrarUsuarios);
        document.getElementById('filterFuncao').addEventListener('change', filtrarUsuarios);
    });

    async function carregarUsuarios() {
        try {
            const resultado = await fazerRequisicao('listar_usuarios', 'GET');
            if (resultado.status === 'sucesso') {
                usuarios = resultado.dados;
                renderizarUsuarios(usuarios);
                atualizarEstatisticas(usuarios);
            }
        } catch (erro) {
            console.error('Erro ao carregar usuários:', erro);
        }
    }

    function atualizarEstatisticas(lista) {
        document.getElementById('totalAdmins').textContent = lista.filter(u => u.funcao === 'admin').length;
        document.getElementById('totalPastores').textContent = lista.filter(u => u.funcao === 'pastor').length;
        document.getElementById('totalSupervisores').textContent = lista.filter(u => u.funcao === 'supervisor').length;
        document.getElementById('totalLideres').textContent = lista.filter(u => u.funcao === 'lider').length;
    }

    function renderizarUsuarios(lista) {
        const container = document.getElementById('usuariosList');
        
        if (!lista || lista.length === 0) {
            container.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #999;">
                <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                <p style="font-size: 16px; font-weight: 500;">Nenhum usuário encontrado</p>
            </div>`;
            return;
        }

        const funcaoIcons = {
            'admin': '👑',
            'pastor': '✝️',
            'supervisor': '👔',
            'lider': '⭐',
            'lider_treinamento': '📚',
            'membro': '👤'
        };

        let html = '';
        lista.forEach(usuario => {
            const funcaoClass = `badge-${usuario.funcao}`;
            const funcaoLabel = funcaoIcons[usuario.funcao] + ' ' + usuario.funcao.charAt(0).toUpperCase() + usuario.funcao.slice(1);
            const isCurrentUser = usuario.id == <?php echo $_SESSION['user_id']; ?>;

            html += `
                <div class="usuario-card">
                    <div class="usuario-nome">${ieq.escapeHtml(usuario.nome)}</div>
                    
                    <div class="usuario-badges">
                        <span class="badge ${funcaoClass}">${ieq.escapeHtml(funcaoLabel)}</span>
                        <span class="badge badge-ativo">Ativo</span>
                    </div>
                    
                    <div class="usuario-info">
                        ${usuario.email ? `<div class="usuario-info-item">
                            <span class="usuario-info-label">📧 Email:</span>
                            <span class="usuario-info-valor">${ieq.escapeHtml(usuario.email)}</span>
                        </div>` : ''}
                        ${usuario.telefone ? `<div class="usuario-info-item">
                            <span class="usuario-info-label">📱 Telefone:</span>
                            <span class="usuario-info-valor">${ieq.escapeHtml(usuario.telefone)}</span>
                        </div>` : ''}
                    </div>
                    
                    <div class="usuario-actions">
                        <button class="btn btn-edit" onclick="editarUsuario(${usuario.id})">✏️ Editar</button>
                        ${!isCurrentUser ? `<button class="btn btn-delete" onclick="deletarUsuario(${usuario.id})">🗑️ Excluir</button>` : ''}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        
        // Reaplicar filtros após renderizar
        if (typeof aplicarFiltrosUsuarios === 'function') {
            setTimeout(aplicarFiltrosUsuarios, 0);
        }
    }

    function filtrarUsuarios() {
        const busca = document.getElementById('searchUsuario').value.toLowerCase();
        const funcaoFiltro = document.getElementById('filterFuncao').value;
        
        const filtrados = usuarios.filter(u => {
            const matchBusca = !busca || 
                u.nome.toLowerCase().includes(busca) || 
                u.email.toLowerCase().includes(busca);
            const matchFuncao = !funcaoFiltro || u.funcao === funcaoFiltro;
            return matchBusca && matchFuncao;
        });
        
        renderizarUsuarios(filtrados);
    }

    function abrirModalUsuario() {
        document.getElementById('modalTitle').textContent = 'Novo Usuário';
        document.getElementById('usuarioForm').reset();
        document.getElementById('usuarioId').value = '';
        document.getElementById('senha').required = true;
        document.getElementById('usuarioModal').style.display = 'flex';
    }

    function fecharModalUsuario() {
        document.getElementById('usuarioModal').style.display = 'none';
    }

    async function editarUsuario(id) {
        const usuario = usuarios.find(u => u.id === id);
        if (!usuario) return;

        document.getElementById('modalTitle').textContent = 'Editar Usuário';
        document.getElementById('usuarioId').value = id;
        document.getElementById('nome').value = usuario.nome;
        document.getElementById('email').value = usuario.email;
        document.getElementById('telefone').value = usuario.telefone || '';
        document.getElementById('funcao').value = usuario.funcao;
        document.getElementById('senha').value = '';
        document.getElementById('senha').required = false;
        
        document.getElementById('usuarioModal').style.display = 'flex';
    }

    async function salvarUsuario(event) {
        event.preventDefault();

        const id = document.getElementById('usuarioId').value;
        const dados = {
            nome: document.getElementById('nome').value,
            email: document.getElementById('email').value,
            telefone: document.getElementById('telefone').value,
            funcao: document.getElementById('funcao').value
        };

        const senha = document.getElementById('senha').value;
        if (senha) {
            if (senha.length < 6) {
                mostrarNotificacao('A senha deve ter no mínimo 6 caracteres', 'erro');
                return;
            }
            dados.senha = senha;
        }

        if (id) {
            dados.id = id;
        }

        const acao = id ? 'atualizar_usuario' : 'criar_usuario';
        
        try {
            const resultado = await fazerRequisicao(acao, 'POST', dados);
            
            if (resultado.status === 'sucesso') {
                mostrarNotificacao(resultado.mensagem, 'sucesso');
                fecharModalUsuario();
                await carregarUsuarios();
            } else {
                mostrarNotificacao(resultado.mensagem, 'erro');
            }
        } catch (erro) {
            mostrarNotificacao('Erro ao salvar usuário', 'erro');
        }
    }

    async function deletarUsuario(id) {
        if (!confirm('Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.')) return;

        try {
            const resultado = await fazerRequisicao('deletar_usuario', 'POST', { id });
            
            if (resultado.status === 'sucesso') {
                mostrarNotificacao('Usuário excluído com sucesso', 'sucesso');
                await carregarUsuarios();
            } else {
                mostrarNotificacao(resultado.mensagem, 'erro');
            }
        } catch (erro) {
            mostrarNotificacao('Erro ao excluir usuário', 'erro');
        }
    }
</script>
