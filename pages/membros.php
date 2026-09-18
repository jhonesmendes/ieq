<div class="page-header">
    <h1>Membros</h1>
    <p>Gerencie os membros da sua célula</p>
</div>

<?php if (is_lider_exclusivo()): ?>
<div class="info-box">
    <div class="info-box-icon">📌</div>
    <div class="info-box-content">
        <div class="info-box-title">Acesso Restrito - Líder de Célula</div>
        <div class="info-box-text">Você pode gerenciar apenas os membros da sua célula. Não é possível transferir membros para outras células.</div>
    </div>
</div>
<?php endif; ?>

<div style="margin-bottom: 20px;">
    <button class="btn btn-primary" id="btnNovoMembro">+ Novo Membro</button>
</div>

<div class="card">
    <div class="search-box">
        <input type="text" id="searchMembros" placeholder="🔍 Buscar por nome...">
        <select id="filterStatus">
            <option value="">Todos os status</option>
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
        </select>
    </div>

    <div id="membrosList" class="membros-grid">
        <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #999;">
            ⏳ Carregando membros...
        </div>
    </div>
</div>

<!-- Modal para novo/editar membro -->
<div id="membroModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Novo Membro</h2>
        
        <form id="formMembro" class="form-group">
            <div class="form-row">
                <div class="form-item">
                    <label>Nome Completo</label>
                    <input type="text" id="membro_nome" required>
                </div>
                <div class="form-item">
                    <label>Email (opcional)</label>
                    <input type="email" id="membro_email">
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Telefone</label>
                    <input type="tel" id="membro_telefone">
                </div>
                <div class="form-item">
                    <label>Data de Nascimento</label>
                    <input type="date" id="membro_data_nasc">
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Célula</label>
                    <select id="membro_celula_id"></select>
                </div>
                <div class="form-item">
                    <label>Função</label>
                    <select id="membro_funcao">
                        <option value="membro">Membro</option>
                        <option value="lider">Líder de Célula</option>
                        <option value="visitante">Visitante</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Status</label>
                    <select id="membro_status">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Data de Conversão</label>
                    <input type="date" id="membro_data_conversao">
                </div>
                <div class="form-item">
                    <label>Data de Batismo</label>
                    <input type="date" id="membro_data_batismo">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Salvar Membro</button>
        </form>
    </div>
</div>

<style>
    /* Grid de Cards */
    .membros-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }

    /* Card individual do membro */
    .membro-card {
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

    .membro-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        border-color: #667eea;
    }

    /* Nome do membro */
    .membro-nome {
        font-size: 18px;
        font-weight: 700;
        color: #1a202c;
        word-break: break-word;
    }

    /* Seção de informações */
    .membro-info {
        display: flex;
        flex-direction: column;
        gap: 8px;
        font-size: 14px;
        color: #4a5568;
    }

    .membro-info-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .membro-info-label {
        font-weight: 600;
        color: #2d3748;
        min-width: 80px;
    }

    .membro-info-valor {
        color: #4a5568;
        word-break: break-word;
        flex: 1;
    }

    /* Badges */
    .membro-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin: 8px 0;
    }

    .badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-lider {
        background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        color: white;
    }

    .badge-membro {
        background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
        color: white;
    }

    .badge-visitante {
        background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);
        color: white;
    }

    .badge-ativo {
        background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
        color: white;
    }

    .badge-inativo {
        background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        color: white;
    }

    /* Botões do card */
    .membro-actions {
        display: flex;
        gap: 8px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e0e7ff;
    }

    .btn-small {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
        background: white;
        color: #333;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-small:hover {
        background: #f0f0f0;
        border-color: #999;
    }

    .btn-small.btn-danger {
        background: #ffebee;
        color: #d32f2f;
        border-color: #f44336;
    }

    .btn-small.btn-danger:hover {
        background: #ffcdd2;
    }

    /* Search box melhorado */
    .search-box {
        display: flex;
        gap: 10px;
        padding: 20px;
        padding-bottom: 0;
        flex-shrink: 0;
    }

    .search-box input,
    .search-box select {
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        font-family: inherit;
    }

    .search-box input {
        flex: 2;
    }

    .search-box select {
        flex: 1;
        min-width: 150px;
    }

    /* Container do card */
    .card {
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 300px);
        overflow: hidden;
    }

    /* Responsivo para Tablet */
    @media (max-width: 768px) {
        .membros-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            padding: 15px;
        }

        .membro-card {
            padding: 15px;
        }

        .membro-nome {
            font-size: 16px;
        }

        .search-box {
            flex-direction: column;
            gap: 12px;
        }

        .search-box input,
        .search-box select {
            width: 100%;
            min-width: unset;
        }
    }

    /* Responsivo para Mobile */
    @media (max-width: 480px) {
        .membros-grid {
            grid-template-columns: 1fr;
            gap: 12px;
            padding: 12px;
        }

        .membro-card {
            padding: 15px;
        }

        .membro-nome {
            font-size: 15px;
        }

        .membro-info {
            font-size: 13px;
        }

        .badge {
            font-size: 11px;
            padding: 4px 10px;
        }

        .membro-actions {
            flex-wrap: wrap;
        }

        .btn-small {
            font-size: 12px;
            padding: 6px 10px;
        }
    }

    /* Dados tabela antigos (manter para compatibilidade) */
    .data-table {
        display: none;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        overflow-y: auto;
    }

    .modal-content {
        background-color: #ffffff;
        margin: 3% auto;
        padding: 40px;
        border: none;
        width: 90%;
        max-width: 900px;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        position: relative;
        max-height: 95vh;
        overflow-y: auto;
    }

    .modal-content h2 {
        margin: 0 0 30px 0;
        color: #333;
        font-size: 24px;
        font-weight: 600;
    }

    .modal-content .form-group {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .modal-content .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .modal-content .form-row:last-of-type {
        margin-bottom: 0;
    }

    .modal-content .form-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .modal-content .form-item label {
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .modal-content .form-item input,
    .modal-content .form-item select {
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .modal-content .form-item input:focus,
    .modal-content .form-item select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .close {
        position: absolute;
        right: 20px;
        top: 20px;
        color: #999;
        font-size: 32px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
        transition: color 0.3s;
    }

    .close:hover {
        color: #333;
    }
    
    .badge-lider {
        background: #ff9800;
        color: white;
    }
    
    .badge-membro {
        background: #2196f3;
        color: white;
    }

    /* Estilos adicionais do Modal */
    .modal-content .btn {
        width: 100%;
        margin-top: 30px;
        padding: 14px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
    }

    .modal-content .btn-primary {
        background: #667eea;
        color: white;
    }

    .modal-content .btn-primary:hover {
        background: #5568d3;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        transform: translateY(-2px);
    }

    .modal-content .btn-primary:active {
        transform: translateY(0);
    }

    /* Responsividade para Tabela em Tablet e Mobile */
    @media (max-width: 768px) {
        .card {
            max-height: calc(100vh - 250px);
        }

        .search-box {
            flex-direction: column;
        }

        .search-box input,
        .search-box select {
            width: 100%;
        }

        .data-table {
            font-size: 0.85rem;
        }

        .data-table th,
        .data-table td {
            padding: 10px 8px;
        }
    }

    @media (max-width: 480px) {
        .card {
            max-height: calc(100vh - 200px);
        }

        .data-table th,
        .data-table td {
            padding: 8px 5px;
            font-size: 0.75rem;
        }
    }

    /* Responsividade para Modal */
    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            margin: 10% auto;
            padding: 25px;
            max-height: 90vh;
        }

        .modal-content h2 {
            font-size: 20px;
            margin-bottom: 25px;
        }

        .modal-content .form-row {
            grid-template-columns: 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .close {
            font-size: 28px;
            right: 15px;
            top: 15px;
        }
    }

    @media (max-width: 480px) {
        .modal-content {
            width: 100%;
            margin: 0;
            padding: 20px;
            border-radius: 12px 12px 0 0;
            height: 100%;
            overflow-y: auto;
        }

        .modal-content h2 {
            font-size: 18px;
        }

        .foto-preview {
            width: 100px;
            height: 100px;
        }

        .foto-placeholder span {
            font-size: 40px;
        }

        .modal-content .form-item input,
        .modal-content .form-item select {
            font-size: 16px;
        }
    }

    /* Alertas no Modal */
    .modal-content .alert {
        margin: 15px 0;
        padding: 12px 15px;
        border-radius: 6px;
        border-left: 4px solid;
        font-size: 13px;
        line-height: 1.5;
    }

    .modal-content .alert-error {
        background: #ffebee;
        border-left-color: #f44336;
        color: #c62828;
    }

    .modal-content .alert-success {
        background: #e8f5e9;
        border-left-color: #4caf50;
        color: #2e7d32;
    }

    .modal-content .alert-info {
        background: #e3f2fd;
        border-left-color: #2196f3;
        color: #1565c0;
    }

    .modal-content .alert-warning {
        background: #fff3e0;
        border-left-color: #ff9800;
        color: #e65100;
    }
</style>

<script>
    const modal = document.getElementById('membroModal');
    const btnNovoMembro = document.getElementById('btnNovoMembro');
    const closeBtn = document.querySelector('.close');
    let editandoId = null;

    // Função para validar email
    function validarEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    btnNovoMembro.onclick = async function() {
        editandoId = null;
        document.getElementById('formMembro').reset();
        modal.querySelector('h2').textContent = 'Novo Membro';
        
        // Recarregar células quando o modal for aberto
        await carregarCelulas();
        
        modal.style.display = 'block';
    }

    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // Carregar membros
    async function carregarMembros() {
        try {
            console.log('📥 Iniciando carregamento de membros...');
            const resultado = await fazerRequisicao('listar_membros', 'GET');
            
            console.log('📊 Resposta do carregamento:', resultado);
            
            if (resultado.status === 'sucesso') {
                console.log('✅ Membros carregados com sucesso. Total:', resultado.dados.length);
                let html = '';
                resultado.dados.forEach(membro => {
                    const funcaoMap = {
                        'lider': 'Líder',
                        'membro': 'Membro',
                        'visitante': 'Visitante'
                    };
                    const funcaoTexto = funcaoMap[membro.funcao] || membro.funcao || 'Membro';
                    const funcaoClass = membro.funcao === 'lider' ? 'badge-lider' : 'badge-membro';
                    
                    // Botões de ação baseados em permissões
                    let acoesHtml = `
                        <button class="btn-small" onclick="editarMembro(${membro.id})">✏️ Editar</button>
                        <?php if (is_supervisor()): ?>
                        <button class="btn-small btn-danger" onclick="deletarMembro(${membro.id})">🗑️ Deletar</button>
                        <?php endif; ?>
                    `;
                    
                    const statusBadge = membro.status ? membro.status : 'ativo';
                    const statusClass = membro.status === 'inativo' ? 'badge-inativo' : 'badge-ativo';
                    
                    html += `<div class="membro-card">
                        <div class="membro-nome">${membro.nome || 'Sem nome'}</div>
                        
                        <div class="membro-badges">
                            <span class="badge ${funcaoClass}">${funcaoTexto}</span>
                            <span class="badge ${statusClass}">${statusBadge.toUpperCase()}</span>
                        </div>
                        
                        <div class="membro-info">
                            ${membro.email ? `<div class="membro-info-item">
                                <span class="membro-info-label">📧 Email:</span>
                                <span class="membro-info-valor">${membro.email}</span>
                            </div>` : ''}
                            ${membro.telefone ? `<div class="membro-info-item">
                                <span class="membro-info-label">📱 Telefone:</span>
                                <span class="membro-info-valor">${membro.telefone}</span>
                            </div>` : ''}
                            ${membro.celula_nome ? `<div class="membro-info-item">
                                <span class="membro-info-label">🏘️ Célula:</span>
                                <span class="membro-info-valor">${membro.celula_nome}</span>
                            </div>` : ''}
                            ${membro.data_conversao ? `<div class="membro-info-item">
                                <span class="membro-info-label">📅 Conversão:</span>
                                <span class="membro-info-valor">${membro.data_conversao}</span>
                            </div>` : ''}
                        </div>
                        
                        <div class="membro-actions">
                            ${acoesHtml}
                        </div>
                    </div>`;
                });
                document.getElementById('membrosList').innerHTML = html || `<div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #999;">
                    <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                    <p style="font-size: 16px; font-weight: 500;">Nenhum membro encontrado</p>
                    <p style="font-size: 14px; margin-top: 8px;">Clique em "Novo Membro" para adicionar um membro</p>
                </div>`;
                
                // Reaplicar filtros após renderizar
                if (typeof aplicarFiltros === 'function') {
                    setTimeout(aplicarFiltros, 0);
                }
            } else {
                console.error('❌ Erro ao carregar membros:', resultado);
                mostrarNotificacao('Erro ao carregar membros: ' + (resultado.mensagem || 'Erro desconhecido'), 'erro');
            }
        } catch (erro) {
            console.error('❌ Erro crítico ao carregar membros:', erro);
            mostrarNotificacao('Erro ao carregar membros', 'erro');
        }
    }

    // Carregar células no select
    async function carregarCelulas() {
        try {
            console.log('📋 Iniciando carregamento de células...');
            const resultado = await fazerRequisicao('listar_celulas', 'GET');
            console.log('📊 Resposta de células completa:', {
                status: resultado.status,
                mensagem: resultado.mensagem,
                quantidadeCelulas: resultado.dados ? resultado.dados.length : 0,
                dados: resultado.dados
            });
            
            if (resultado.status === 'sucesso' && resultado.dados && resultado.dados.length > 0) {
                const selectElement = document.getElementById('membro_celula_id');
                let html = '<option value="">Selecione uma célula</option>';
                
                resultado.dados.forEach((celula, index) => {
                    console.log(`  Célula ${index + 1}:`, {
                        id: celula.id,
                        idType: typeof celula.id,
                        nome: celula.nome
                    });
                    html += `<option value="${celula.id}">${celula.nome}</option>`;
                });
                
                selectElement.innerHTML = html;
                console.log('✅ Opções de célula carregadas com sucesso. Total: ' + resultado.dados.length);
                
                // Se for líder exclusivo, pré-selecionar a célula
                <?php if (is_lider_exclusivo()): ?>
                console.log('🔐 Usuário é líder exclusivo');
                
                if (resultado.dados.length > 0) {
                    const primeiracelula = resultado.dados[0];
                    const celulaId = String(primeiracelula.id);
                    
                    console.log('Tentando selecionar célula:', {
                        celulaId,
                        type: typeof celulaId,
                        celulaNome: primeiracelula.nome
                    });
                    
                    selectElement.value = celulaId;
                    
                    // Verificar se foi selecionada
                    const selecionada = selectElement.value;
                    console.log('Valor após selecionar:', {
                        selecionada,
                        matches: selecionada === celulaId
                    });
                    
                    if (selecionada === celulaId) {
                        selectElement.disabled = true;
                        console.log('✅ Célula selecionada e desabilitada: ' + primeiracelula.nome);
                    } else {
                        console.error('❌ Falha ao selecionar célula! Esperado: ' + celulaId + ', Obtido: ' + selecionada);
                    }
                }
                <?php else: ?>
                console.log('👤 Usuário NÃO é líder exclusivo (provavelmente admin/pastor/supervisor)');
                <?php endif; ?>
            } else if (resultado.status === 'sucesso') {
                console.warn('⚠️ Nenhuma célula retornada pela API');
                const selectElement = document.getElementById('membro_celula_id');
                selectElement.innerHTML = '<option value="">Nenhuma célula disponível</option>';
                mostrarNotificacao('Nenhuma célula disponível para você', 'aviso');
            } else {
                console.error('❌ Erro ao carregar células:', resultado);
                mostrarNotificacao('Erro ao carregar células: ' + (resultado.mensagem || 'Erro desconhecido'), 'erro');
            }
        } catch (erro) {
            console.error('❌ Erro ao carregar células:', erro);
            console.error('Stack:', erro.stack);
            mostrarNotificacao('Erro ao carregar células: ' + erro.message, 'erro');
        }
    }

    // Editar membro
    async function editarMembro(id) {
        try {
            // Carregar as opções de célula primeiro
            await carregarCelulas();
            
            const resultado = await fazerRequisicao(`obter_membro&id=${id}`, 'GET');
            if (resultado.status === 'sucesso') {
                const membro = resultado.dados;
                editandoId = id;
                
                document.getElementById('membro_nome').value = membro.nome || '';
                document.getElementById('membro_email').value = membro.email || '';
                document.getElementById('membro_telefone').value = membro.telefone || '';
                document.getElementById('membro_data_nasc').value = membro.data_nasc || '';
                
                // Aguardar um pouco para garantir que as opções foram carregadas
                setTimeout(() => {
                    const selectCelula = document.getElementById('membro_celula_id');
                    
                    // Se for líder exclusivo, desabilitar o select
                    <?php if (is_lider_exclusivo()): ?>
                    selectCelula.disabled = true;
                    <?php endif; ?>
                    
                    selectCelula.value = membro.celula_id || '';
                    console.log('✅ Célula selecionada para edição: ' + membro.celula_id);
                }, 100);
                
                document.getElementById('membro_funcao').value = membro.funcao || 'membro';
                document.getElementById('membro_status').value = membro.status || 'ativo';
                document.getElementById('membro_data_conversao').value = membro.data_conversao || '';
                document.getElementById('membro_data_batismo').value = membro.data_batismo || '';
                
                modal.querySelector('h2').textContent = 'Editar Membro';
                modal.style.display = 'block';
            } else {
                console.error('Erro ao obter membro:', resultado);
                mostrarNotificacao('Erro ao carregar membro: ' + (resultado.mensagem || 'Erro desconhecido'), 'erro');
            }
        } catch (erro) {
            console.error('Erro ao editar membro:', erro);
            mostrarNotificacao('Erro ao carregar membro', 'erro');
        }
    }

    // Deletar membro
    async function deletarMembro(id) {
        if (confirm('Tem certeza que deseja deletar este membro?')) {
            try {
                console.log('🗑️ Iniciando exclusão de membro ID:', id);
                const resultado = await fazerRequisicao('deletar_membro', 'POST', { id });
                
                console.log('📊 Resposta da exclusão:', resultado);
                
                if (resultado.status === 'sucesso') {
                    mostrarNotificacao('Membro deletado com sucesso!', 'sucesso');
                    carregarMembros();
                } else {
                    console.error('❌ Erro ao deletar membro:', resultado.mensagem);
                    mostrarNotificacao('Erro ao deletar membro: ' + (resultado.mensagem || 'Erro desconhecido'), 'erro');
                }
            } catch (erro) {
                console.error('❌ Erro ao deletar membro:', erro);
                mostrarNotificacao('Erro ao deletar membro', 'erro');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔄 Inicializando página de membros...');
        
        carregarMembros();
        carregarCelulas().catch(erro => {
            console.error('❌ Erro crítico ao carregar células na inicialização:', erro);
        });

        // ===== FILTRO E BUSCA DE MEMBROS =====
        const searchInput = document.getElementById('searchMembros');
        const filterStatus = document.getElementById('filterStatus');
        
        function aplicarFiltros() {
            const textoBusca = searchInput.value.toLowerCase();
            const statusFiltro = filterStatus.value;
            
            const cards = document.querySelectorAll('.membro-card');
            let cardsVisiveis = 0;
            
            cards.forEach(card => {
                const nome = card.querySelector('.membro-nome').textContent.toLowerCase();
                const badges = card.querySelectorAll('.badge');
                let statusCard = '';
                
                badges.forEach(badge => {
                    const texto = badge.textContent.trim().toLowerCase();
                    if (texto === 'ativo' || texto === 'inativo') {
                        statusCard = texto;
                    }
                });
                
                const contemTexto = nome.includes(textoBusca);
                const contemStatus = !statusFiltro || statusCard === statusFiltro.toLowerCase();
                
                if (contemTexto && contemStatus) {
                    card.style.display = '';
                    cardsVisiveis++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Mostrar mensagem se nenhum card está visível
            if (cardsVisiveis === 0) {
                let membrosList = document.getElementById('membrosList');
                // Verificar se já existe a mensagem vazia
                if (!membrosList.querySelector('.empty-message')) {
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'empty-message';
                    emptyDiv.style.cssText = 'grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #999;';
                    emptyDiv.innerHTML = `
                        <div style="font-size: 48px; margin-bottom: 16px;">🔍</div>
                        <p style="font-size: 16px; font-weight: 500;">Nenhum membro encontrado</p>
                        <p style="font-size: 14px; margin-top: 8px;">Tente ajustar seus filtros</p>
                    `;
                    membrosList.appendChild(emptyDiv);
                }
                document.querySelector('.empty-message').style.display = '';
            } else {
                const emptyMsg = document.querySelector('.empty-message');
                if (emptyMsg) emptyMsg.style.display = 'none';
            }
        }
        
        searchInput.addEventListener('input', aplicarFiltros);
        filterStatus.addEventListener('change', aplicarFiltros);

        // Salvar membro
        document.getElementById('formMembro').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // ===== VALIDAÇÃO DOS CAMPOS =====
            const nome = document.getElementById('membro_nome').value.trim();
            const email = document.getElementById('membro_email').value.trim();
            const selectCelula = document.getElementById('membro_celula_id');
            const celulaId = selectCelula.value ? selectCelula.value.trim() : '';
            const funcao = document.getElementById('membro_funcao').value.trim();
            const status = document.getElementById('membro_status').value.trim();
            
            console.log('📝 Tentando salvar membro com:', {
                nome,
                email,
                celulaId,
                selectDisabled: selectCelula.disabled,
                selectValue: selectCelula.value,
                selectOptions: selectCelula.options.length
            });
            
            // Validação básica
            if (!nome) {
                mostrarNotificacao('❌ Nome completo é obrigatório', 'erro');
                return;
            }
            
            // Email agora é opcional, mas se fornecido deve ser válido
            if (email && !validarEmail(email)) {
                mostrarNotificacao('❌ Email inválido', 'erro');
                return;
            }
            
            if (!celulaId && !editandoId) {
                mostrarNotificacao('❌ Selecione uma célula', 'erro');
                console.error('❌ Célula não foi selecionada. editandoId:', editandoId);
                return;
            }
            
            console.log('✅ Validação passou', {
                nome,
                email,
                celulaId,
                funcao,
                status,
                editandoId
            });
            
            const dados = {
                nome: nome,
                email: email,
                telefone: document.getElementById('membro_telefone').value.trim(),
                data_nasc: document.getElementById('membro_data_nasc').value || undefined,
                celula_id: celulaId,
                funcao: funcao,
                status: status,
                data_conversao: document.getElementById('membro_data_conversao').value || undefined,
                data_batismo: document.getElementById('membro_data_batismo').value || undefined
            };
            
            let resultado;
            if (editandoId) {
                dados.id = editandoId;
                console.log('📝 Atualizando membro:', dados);
                resultado = await fazerRequisicao('atualizar_membro', 'POST', dados);
            } else {
                // Criar novo usuário primeiro
                const usuario = {
                    nome: nome,
                    email: email,
                    senha: '123456', // Senha padrão
                    telefone: document.getElementById('membro_telefone').value.trim(),
                    funcao: funcao
                };
                
                console.log('👤 Criando usuário:', usuario);
                const resUsuario = await fazerRequisicao('registrar', 'POST', usuario);
                
                console.log('📊 Resposta do registro:', resUsuario);
                
                if (resUsuario.status === 'sucesso') {
                    dados.usuario_id = resUsuario.dados.user_id;
                    console.log('📝 Criando membro com usuario_id:', dados.usuario_id);
                    resultado = await fazerRequisicao('criar_membro', 'POST', dados);
                } else {
                    mostrarNotificacao('❌ Erro ao criar usuário: ' + (resUsuario.mensagem || 'Erro desconhecido'), 'erro');
                    console.error('Erro na criação de usuário:', resUsuario);
                    return;
                }
            }
            
            console.log('📊 Resultado final:', resultado);
            
            if (resultado.status === 'sucesso') {
                mostrarNotificacao(editandoId ? '✅ Membro atualizado!' : '✅ Membro criado!', 'sucesso');
                modal.style.display = 'none';
                carregarMembros();
            } else {
                mostrarNotificacao('❌ Erro ao salvar membro: ' + (resultado.mensagem || 'Erro desconhecido'), 'erro');
                console.error('Erro ao salvar:', resultado);
            }
        });
    });
</script>
