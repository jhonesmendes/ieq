<div class="page-header">
    <h1>⛪ Igrejas - Gestão Completa</h1>
    <p>Gerencie igrejas, eventos, visitantes, conversões, reconciliações e batismos</p>
</div>

<div style="margin-bottom: 20px;">
    <button class="btn btn-primary" id="btnNovaIgreja">+ Nova Igreja</button>
</div>

<div class="igrejas-grid" id="igrejasList">
    <div style="text-align: center; padding: 30px; color: #999;">Carregando igrejas...</div>
</div>

<!-- Modal para nova/editar igreja -->
<div id="igrejaModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Nova Igreja</h2>
        
        <form id="formIgreja" class="form-group">
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Nome da Igreja</label>
                    <input type="text" id="igreja_nome" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Pastor Presidente</label>
                    <select id="igreja_pastor_presidente_id">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="form-item">
                    <label>Pastor Auxiliar</label>
                    <select id="igreja_pastor_auxiliar_id">
                        <option value="">Selecione...</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-item full-width">
                    <label>Endereço</label>
                    <input type="text" id="igreja_endereco" placeholder="Rua, número...">
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Bairro</label>
                    <input type="text" id="igreja_bairro">
                </div>
                <div class="form-item">
                    <label>Cidade</label>
                    <input type="text" id="igreja_cidade">
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Telefone</label>
                    <input type="text" id="igreja_telefone" placeholder="(00) 0000-0000">
                </div>
                <div class="form-item">
                    <label>Email</label>
                    <input type="email" id="igreja_email">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Salvar Igreja</button>
        </form>
    </div>
</div>

<!-- Modal para dados específicos da Igreja -->
<div id="dadosIgrejaModal" class="modal" style="display: none;">
    <div class="modal-content modal-large">
        <span class="close" onclick="fecharModalDados()">&times;</span>
        <h2 id="tituloIgreja">Dados da Igreja</h2>
        
        <div class="tabs">
            <button class="tab-button active" onclick="mudarAba('aba-resumo')">📊 Resumo</button>
            <button class="tab-button" onclick="mudarAba('aba-eventos')">🎯 Eventos</button>
            <button class="tab-button" onclick="mudarAba('aba-visitantes')">👥 Visitantes</button>
            <button class="tab-button" onclick="mudarAba('aba-conversoes')">✝️ Conversões</button>
            <button class="tab-button" onclick="mudarAba('aba-reconciliacao')">🤝 Reconciliação</button>
            <button class="tab-button" onclick="mudarAba('aba-batismos')">💧 Batismos</button>
            <button class="tab-button" onclick="mudarAba('aba-relatorio')">📄 Relatório</button>
        </div>

        <!-- ABA RESUMO -->
        <div id="aba-resumo" class="tab-content active">
            <div style="display: flex; gap: 15px; margin-bottom: 30px; align-items: center; flex-wrap: wrap; justify-content: center;">
                <label for="filtroDataResumo" style="font-weight: 600;">Período:</label>
                <select id="filtroDataResumo" style="padding: 8px 12px; border: 2px solid #667eea; border-radius: 6px; cursor: pointer; font-size: 14px;">
                    <option value="todos">Todos os registros</option>
                    <option value="mes_atual" selected>Este mês</option>
                    <option value="ultimos_30_dias">Últimos 30 dias</option>
                    <option value="ultimos_90_dias">Últimos 90 dias</option>
                </select>
                <button class="btn btn-primary" onclick="atualizarResumoIgreja()" style="padding: 8px 20px;">🔄 Atualizar</button>
            </div>
            <div class="resumo-stats">
                <div class="stat-card">
                    <div class="stat-number" id="stat-visitantes">0</div>
                    <div class="stat-label">Visitantes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="stat-conversoes">0</div>
                    <div class="stat-label">Conversões</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="stat-reconciliacao">0</div>
                    <div class="stat-label">Reconciliações</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="stat-batismos">0</div>
                    <div class="stat-label">Batismos</div>
                </div>
            </div>
        </div>

        <!-- ABA EVENTOS -->
        <div id="aba-eventos" class="tab-content">
            <div class="tab-header">
                <h3>🎯 Eventos da Igreja</h3>
                <button class="btn btn-small btn-primary" onclick="abrirFormEvento()">+ Novo Evento</button>
            </div>
            <div id="listaEventos" class="lista-items"></div>
        </div>

        <!-- ABA VISITANTES -->
        <div id="aba-visitantes" class="tab-content">
            <div class="tab-header">
                <h3>👥 Visitantes</h3>
                <button class="btn btn-small btn-primary" onclick="abrirFormVisitante()">+ Novo Visitante</button>
            </div>
            <div id="listaVisitantes" class="lista-items"></div>
        </div>

        <!-- ABA CONVERSÕES -->
        <div id="aba-conversoes" class="tab-content">
            <div class="tab-header">
                <h3>✝️ Aceitações de Jesus</h3>
                <button class="btn btn-small btn-primary" onclick="abrirFormConversao()">+ Novo Registro</button>
            </div>
            <div id="listaConversoes" class="lista-items"></div>
        </div>

        <!-- ABA RECONCILIAÇÃO -->
        <div id="aba-reconciliacao" class="tab-content">
            <div class="tab-header">
                <h3>🤝 Reconciliações</h3>
                <button class="btn btn-small btn-primary" onclick="abrirFormReconciliacao()">+ Novo Registro</button>
            </div>
            <div id="listaReconciliacao" class="lista-items"></div>
        </div>

        <!-- ABA BATISMOS -->
        <div id="aba-batismos" class="tab-content">
            <div class="tab-header">
                <h3>💧 Batismos</h3>
                <button class="btn btn-small btn-primary" onclick="abrirFormBatismo()">+ Novo Batismo</button>
            </div>
            <div id="listaBatismos" class="lista-items"></div>
        </div>

        <!-- ABA RELATÓRIO -->
        <div id="aba-relatorio" class="tab-content">
            <div class="tab-header">
                <h3>📄 Relatório da Igreja</h3>
                <button class="btn btn-small" onclick="exportarRelatorio()">📥 Exportar</button>
            </div>
            <div id="relatorioIgreja" class="relatorio-content"></div>
        </div>
    </div>
</div>

<!-- Modal para formulários dinâmicos -->
<div id="modalFormulario" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="fecharModalFormulario()">&times;</span>
        <h2 id="tituloFormulario">Formulário</h2>
        <form id="formDados" class="form-group"></form>
    </div>
</div>

<style>
    .igrejas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }

    .igreja-card {
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .igreja-card h3 {
        margin: 0 0 15px 0;
        color: #333;
        font-size: 18px;
    }

    .igreja-info {
        flex: 1;
        color: #666;
        font-size: 14px;
        margin-bottom: 15px;
    }

    .igreja-info p {
        margin: 8px 0;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .igreja-info strong {
        color: #333;
        min-width: 120px;
        display: inline-block;
    }

    .igreja-pastores {
        background: #f8f9fa;
        padding: 12px;
        border-radius: 8px;
        margin: 10px 0;
    }

    .igreja-pastores p {
        margin: 5px 0;
        font-size: 13px;
    }

    .igreja-stats {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #667eea;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        position: absolute;
        top: 15px;
        right: 15px;
    }

    .igreja-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    /* MODAL STYLES */
    .modal-large {
        max-width: 900px !important;
        max-height: 90vh !important;
        overflow-y: auto;
    }

    .tabs {
        display: flex;
        gap: 5px;
        margin: 20px 0;
        flex-wrap: wrap;
        border-bottom: 2px solid #eee;
    }

    .tab-button {
        padding: 10px 15px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-size: 14px;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }

    .tab-button.active {
        color: #667eea;
        border-bottom-color: #667eea;
    }

    .tab-button:hover {
        color: #667eea;
    }

    .tab-content {
        display: none;
        padding: 20px 0;
        animation: fadeIn 0.3s;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .tab-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .tab-header h3 {
        margin: 0;
    }

    .resumo-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 24px;
        margin: 40px auto;
        max-width: 800px;
        padding: 30px 20px;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 16px;
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 32px 24px;
        border-radius: 16px;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .stat-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 16px 32px rgba(102, 126, 234, 0.5);
    }

    .stat-card:hover::before {
        left: 100%;
    }

    .stat-number {
        font-size: 48px;
        font-weight: bold;
        margin-bottom: 12px;
        display: block;
        animation: slideUp 0.6s ease;
    }

    .stat-label {
        font-size: 16px;
        opacity: 0.95;
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .lista-items {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .item-card {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .item-info {
        flex: 1;
    }

    .item-info h4 {
        margin: 0 0 5px 0;
        color: #333;
    }

    .item-info p {
        margin: 3px 0;
        font-size: 12px;
        color: #999;
    }

    .item-actions {
        display: flex;
        gap: 8px;
    }

    .item-actions button {
        padding: 6px 12px;
        font-size: 12px;
    }

    /* Estatísticas Mini Cards */
    .igreja-stats-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 8px;
        margin: 15px 0;
        padding: 12px;
        background: #f0f4ff;
        border-radius: 8px;
    }

    .stat-mini {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        border-radius: 6px;
        background: white;
    }

    .stat-mini:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .stat-mini:hover .stat-value,
    .stat-mini:hover .stat-label {
        color: white;
    }

    .stat-icon {
        font-size: 20px;
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 18px;
        font-weight: bold;
        color: #667eea;
        line-height: 1;
        transition: color 0.3s;
    }

    .stat-label {
        font-size: 10px;
        color: #666;
        margin-top: 2px;
        display: block;
        word-break: break-word;
        transition: color 0.3s;
    }

    .relatorio-content {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        line-height: 1.8;
    }

    .relatorio-section {
        margin-bottom: 20px;
    }

    .relatorio-section h4 {
        color: #667eea;
        margin-bottom: 10px;
        font-size: 16px;
    }

    .full-width {
        grid-column: 1 / -1;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .igrejas-grid {
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .modal-large {
            max-width: 100% !important;
        }

        .tabs {
            gap: 2px;
            overflow-x: auto;
        }

        .tab-button {
            font-size: 12px;
            padding: 8px 10px;
            white-space: nowrap;
        }

        .resumo-stats {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px auto;
            max-width: 100%;
            padding: 20px 10px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 12px;
        }

        .stat-card {
            padding: 20px 15px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.25);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.35);
        }

        .stat-number {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 12px;
        }

        .item-card {
            flex-direction: column;
            align-items: flex-start;
        }

        .igreja-stats-grid {
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
            padding: 10px;
        }

        .stat-mini {
            padding: 6px;
        }

        .stat-icon {
            font-size: 16px;
        }

        .stat-value {
            font-size: 14px;
        }

        .stat-label {
            font-size: 9px;
        }


        .item-actions {
            margin-top: 10px;
            width: 100%;
        }

        .item-actions button {
            flex: 1;
        }
    }
</style>

<script>
    // 🔧 Função para parsear data de string (YYYY-MM-DD) para formato local correto
    // Evita problema de timezone onde data aparece um dia atrasada
    function formatarDataLocal(dataString) {
        if (!dataString) return '';
        // Se for string no formato YYYY-MM-DD, parsear como local
        if (/^\d{4}-\d{2}-\d{2}$/.test(dataString)) {
            const [ano, mes, dia] = dataString.split('-');
            return new Date(ano, mes - 1, dia).toLocaleDateString('pt-BR');
        }
        // Fallback: tentar como string padrão
        return new Date(dataString).toLocaleDateString('pt-BR');
    }

    let igrejaAtual = null;
    const igrejaModal = document.getElementById('igrejaModal');
    const dadosIgrejaModal = document.getElementById('dadosIgrejaModal');
    const modalFormulario = document.getElementById('modalFormulario');
    const btnNovaIgreja = document.getElementById('btnNovaIgreja');
    const closeBtn = document.querySelector('.close');
    let editandoId = null;
    let tipoFormularioAtual = null;

    btnNovaIgreja.onclick = function() {
        editandoId = null;
        document.getElementById('formIgreja').reset();
        igrejaModal.querySelector('h2').textContent = 'Nova Igreja';
        igrejaModal.style.display = 'block';
    }

    closeBtn.onclick = function() {
        igrejaModal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == igrejaModal) {
            igrejaModal.style.display = 'none';
        }
        if (event.target == dadosIgrejaModal) {
            fecharModalDados();
        }
        if (event.target == modalFormulario) {
            fecharModalFormulario();
        }
    }

    function fecharModalDados() {
        dadosIgrejaModal.style.display = 'none';
    }

    function fecharModalFormulario() {
        modalFormulario.style.display = 'none';
    }

    function mudarAba(abaId) {
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        document.getElementById(abaId).classList.add('active');
        event.target.classList.add('active');

        if (abaId === 'aba-eventos') carregarEventos();
        if (abaId === 'aba-visitantes') carregarVisitantes();
        if (abaId === 'aba-conversoes') carregarConversoes();
        if (abaId === 'aba-reconciliacao') carregarReconciliacao();
        if (abaId === 'aba-batismos') carregarBatismos();
        if (abaId === 'aba-relatorio') carregarRelatorio();
    }

    async function carregarPastores() {
        const resultado = await fazerRequisicao('listar_pastores', 'GET');
        if (resultado.status === 'sucesso') {
            const selectPresidente = document.getElementById('igreja_pastor_presidente_id');
            const selectAuxiliar = document.getElementById('igreja_pastor_auxiliar_id');
            
            let options = '<option value="">Selecione...</option>';
            resultado.dados.forEach(pastor => {
                options += `<option value="${pastor.id}">${pastor.nome}</option>`;
            });
            
            selectPresidente.innerHTML = options;
            selectAuxiliar.innerHTML = options;
        }
    }

    async function carregarIgrejas() {
        const resultado = await fazerRequisicao('listar_igrejas', 'GET');
        
        if (resultado.status === 'sucesso') {
            let html = '';
            
            if (!resultado.dados || resultado.dados.length === 0) {
                html = '<div style="grid-column: 1/-1; text-align: center; padding: 30px; color: #999;">Nenhuma igreja cadastrada</div>';
            } else {
                resultado.dados.forEach(igreja => {
                    const totalCelulas = igreja.total_celulas || 0;
                    const totalEventos = igreja.total_eventos || 0;
                    const totalVisitantes = igreja.total_visitantes || 0;
                    const totalConversoes = igreja.total_conversoes || 0;
                    const totalReconciliacao = igreja.total_reconciliacao || 0;
                    const totalBatismos = igreja.total_batismos || 0;
                    
                    html += `
                        <div class="card igreja-card">
                            ${totalCelulas > 0 ? `<span class="igreja-stats">📍 ${totalCelulas} célula${totalCelulas > 1 ? 's' : ''}</span>` : ''}
                            
                            <h3>⛪ ${ieq.escapeHtml(igreja.nome)}</h3>
                            
                            <div class="igreja-info">
                                ${igreja.endereco ? `<p>📍 ${ieq.escapeHtml(igreja.endereco)}</p>` : ''}
                                ${igreja.bairro || igreja.cidade ? `<p>🏘️ ${[ieq.escapeHtml(igreja.bairro), ieq.escapeHtml(igreja.cidade)].filter(Boolean).join(', ')}</p>` : ''}
                                ${igreja.telefone ? `<p>📞 ${ieq.escapeHtml(igreja.telefone)}</p>` : ''}
                                ${igreja.email ? `<p>📧 ${ieq.escapeHtml(igreja.email)}</p>` : ''}
                            </div>
                            
                            <div class="igreja-pastores">
                                ${igreja.pastor_presidente_nome ? `<p>👔 <strong>Pastor Presidente:</strong> ${ieq.escapeHtml(igreja.pastor_presidente_nome)}</p>` : '<p style="color: #999;">👔 Pastor Presidente não definido</p>'}
                                ${igreja.pastor_auxiliar_nome ? `<p>🤝 <strong>Pastor Auxiliar:</strong> ${ieq.escapeHtml(igreja.pastor_auxiliar_nome)}</p>` : '<p style="color: #999;">🤝 Pastor Auxiliar não definido</p>'}
                            </div>

                            <!-- Estatísticas -->
                            <div class="igreja-stats-grid">
                                <div class="stat-mini" onclick="abrirFormEvenjoRapido(${igreja.id})">
                                    <span class="stat-icon">🎯</span>
                                    <span class="stat-value">${totalEventos}</span>
                                    <span class="stat-label">Eventos</span>
                                </div>
                                <div class="stat-mini" onclick="abrirFormVisitanteRapido(${igreja.id})">
                                    <span class="stat-icon">👥</span>
                                    <span class="stat-value">${totalVisitantes}</span>
                                    <span class="stat-label">Visitantes</span>
                                </div>
                                <div class="stat-mini" onclick="abrirFormConversaoRapido(${igreja.id})">
                                    <span class="stat-icon">✝️</span>
                                    <span class="stat-value">${totalConversoes}</span>
                                    <span class="stat-label">Conversões</span>
                                </div>
                                <div class="stat-mini" onclick="abrirFormReconciliacaoRapido(${igreja.id})">
                                    <span class="stat-icon">🤝</span>
                                    <span class="stat-value">${totalReconciliacao}</span>
                                    <span class="stat-label">Reconciliações</span>
                                </div>
                                <div class="stat-mini" onclick="abrirFormBatismoRapido(${igreja.id})">
                                    <span class="stat-icon">💧</span>
                                    <span class="stat-value">${totalBatismos}</span>
                                    <span class="stat-label">Batismos</span>
                                </div>
                            </div>
                            
                            <div class="igreja-actions">
                                <button class="btn btn-small" onclick="abrirDadosIgreja(${igreja.id})">👁️ Visualizar</button>
                                <button class="btn btn-small" onclick="editarIgreja(${igreja.id})">✏️ Editar</button>
                                <button class="btn btn-small btn-danger" onclick="deletarIgreja(${igreja.id})">🗑️ Deletar</button>
                            </div>
                        </div>
                    `;
                });
            }
            
            document.getElementById('igrejasList').innerHTML = html;
        } else {
            console.error('Erro ao carregar igrejas:', resultado);
            document.getElementById('igrejasList').innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 30px; color: #f44336;">Erro ao carregar igrejas</div>';
        }
    }

    async function abrirDadosIgreja(id) {
        igrejaAtual = id;
        const resultado = await fazerRequisicao(`obter_igreja&id=${id}`, 'GET');
        if (resultado.status === 'sucesso') {
            const igreja = resultado.dados;
            document.getElementById('tituloIgreja').textContent = `⛪ ${ieq.escapeHtml(igreja.nome)}`;
            
            // Resetar filtro para padrão
            document.getElementById('filtroDataResumo').value = 'mes_atual';
            
            // Carregar estatísticas
            await atualizarResumoIgreja();
            
            dadosIgrejaModal.style.display = 'block';
        }
    }

    async function atualizarResumoIgreja() {
        if (!igrejaAtual) return;
        
        const filtro = document.getElementById('filtroDataResumo').value;
        const relatorio = await fazerRequisicao(`relatorio_igreja&id=${igrejaAtual}&filtro=${filtro}`, 'GET');
        
        if (relatorio.status === 'sucesso') {
            const dados = relatorio.dados;
            
            // Animação de atualização
            const cards = document.querySelectorAll('.stat-card .stat-number');
            cards.forEach(card => {
                card.style.opacity = '0.5';
                card.style.transform = 'scale(0.95)';
            });
            
            setTimeout(() => {
                document.getElementById('stat-visitantes').textContent = dados.total_visitantes;
                document.getElementById('stat-conversoes').textContent = dados.total_conversoes;
                document.getElementById('stat-reconciliacao').textContent = dados.total_reconciliacao;
                document.getElementById('stat-batismos').textContent = dados.total_batismos;
                
                cards.forEach(card => {
                    card.style.opacity = '1';
                    card.style.transform = 'scale(1)';
                });
            }, 200);
        }
    }

    async function editarIgreja(id) {
        const resultado = await fazerRequisicao(`obter_igreja&id=${id}`, 'GET');
        if (resultado.status === 'sucesso') {
            const igreja = resultado.dados;
            editandoId = id;
            
            document.getElementById('igreja_nome').value = igreja.nome;
            document.getElementById('igreja_endereco').value = igreja.endereco || '';
            document.getElementById('igreja_bairro').value = igreja.bairro || '';
            document.getElementById('igreja_cidade').value = igreja.cidade || '';
            document.getElementById('igreja_telefone').value = igreja.telefone || '';
            document.getElementById('igreja_email').value = igreja.email || '';
            document.getElementById('igreja_pastor_presidente_id').value = igreja.pastor_presidente_id || '';
            document.getElementById('igreja_pastor_auxiliar_id').value = igreja.pastor_auxiliar_id || '';
            
            igrejaModal.querySelector('h2').textContent = 'Editar Igreja';
            igrejaModal.style.display = 'block';
        }
    }

    async function deletarIgreja(id) {
        if (confirm('Tem certeza que deseja deletar esta igreja? Esta ação não pode ser desfeita.')) {
            const resultado = await fazerRequisicao('deletar_igreja', 'POST', { id });
            if (resultado.status === 'sucesso') {
                mostrarNotificacao('Igreja deletada com sucesso!', 'sucesso');
                carregarIgrejas();
            } else {
                mostrarNotificacao(resultado.mensagem || 'Erro ao deletar igreja', 'erro');
            }
        }
    }

    // EVENTOS
    function abrirFormEvenjoRapido(igreja_id) {
        igrejaAtual = igreja_id;
        abrirFormEvento();
    }

    function abrirFormEvento() {
        tipoFormularioAtual = 'evento';
        document.getElementById('tituloFormulario').textContent = '🎯 Novo Evento';
        const form = document.getElementById('formDados');
        form.innerHTML = `
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Nome do Evento</label>
                    <input type="text" id="evento_nome" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Descrição</label>
                    <textarea id="evento_descricao" rows="3"></textarea>
                </div>
            </div>
            <div class="form-row">
                <div class="form-item">
                    <label>Data do Evento</label>
                    <input type="datetime-local" id="evento_data" required>
                </div>
                <div class="form-item">
                    <label>Localização</label>
                    <input type="text" id="evento_localizacao">
                </div>
            </div>
            <div class="form-row">
                <div class="form-item">
                    <label>Tipo</label>
                    <input type="text" id="evento_tipo" placeholder="culto, congresso, retiro...">
                </div>
                <div class="form-item">
                    <label>Vagas</label>
                    <input type="number" id="evento_vagas">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Evento</button>
        `;
        form.onsubmit = salvarEvento;
        modalFormulario.style.display = 'block';
    }

    async function salvarEvento(e) {
        e.preventDefault();
        const dados = {
            igreja_id: igrejaAtual,
            nome: document.getElementById('evento_nome').value,
            descricao: document.getElementById('evento_descricao').value,
            data_evento: document.getElementById('evento_data').value,
            localizacao: document.getElementById('evento_localizacao').value,
            tipo: document.getElementById('evento_tipo').value,
            vagas: document.getElementById('evento_vagas').value
        };
        
        const resultado = await fazerRequisicao('criar_evento_igreja', 'POST', dados);
        if (resultado.status === 'sucesso') {
            mostrarNotificacao('Evento criado com sucesso!', 'sucesso');
            fecharModalFormulario();
            carregarEventos();
        } else {
            mostrarNotificacao(resultado.mensagem || 'Erro ao salvar evento', 'erro');
        }
    }

    async function carregarEventos() {
        const resultado = await fazerRequisicao(`listar_eventos_igreja&id=${igrejaAtual}`, 'GET');
        let html = '';
        
        if (resultado.status === 'sucesso' && resultado.dados.length > 0) {
            resultado.dados.forEach(evento => {
                // Parsear data local corretamente
                let dataFormatada = '';
                if (evento.data_evento) {
                    // Se tem data_evento, formatar
                    if (/^\d{4}-\d{2}-\d{2}$/.test(evento.data_evento)) {
                        // Só data YYYY-MM-DD
                        const [ano, mes, dia] = evento.data_evento.split('-');
                        dataFormatada = new Date(ano, mes - 1, dia).toLocaleDateString('pt-BR', { 
                            day: '2-digit', month: '2-digit', year: 'numeric'
                        });
                    } else {
                        // Com hora ou outro formato
                        dataFormatada = new Date(evento.data_evento).toLocaleDateString('pt-BR', { 
                            day: '2-digit', month: '2-digit', year: 'numeric', 
                            hour: '2-digit', minute: '2-digit' 
                        });
                    }
                }
                
                html += `
                    <div class="item-card">
                        <div class="item-info">
                            <h4>${ieq.escapeHtml(evento.nome)}</h4>
                            <p>${dataFormatada || 'Sem data'}</p>
                            <p>${ieq.escapeHtml(evento.tipo || 'Sem tipo')}</p>
                        </div>
                        <div class="item-actions">
                            <button class="btn btn-small btn-danger" onclick="deletarEvento(${evento.id})">🗑️</button>
                        </div>
                    </div>
                `;
            });
        } else {
            html = '<p style="text-align: center; color: #999;">Nenhum evento cadastrado</p>';
        }
        
        document.getElementById('listaEventos').innerHTML = html;
    }

    async function deletarEvento(id) {
        if (confirm('Deletar este evento?')) {
            const resultado = await fazerRequisicao('deletar_evento_igreja', 'POST', { id });
            if (resultado.status === 'sucesso') {
                mostrarNotificacao('Evento deletado!', 'sucesso');
                carregarEventos();
            }
        }
    }

    // VISITANTES
    function abrirFormVisitanteRapido(igreja_id) {
        igrejaAtual = igreja_id;
        abrirFormVisitante();
    }

    function abrirFormVisitante() {
        tipoFormularioAtual = 'visitante';
        document.getElementById('tituloFormulario').textContent = '👥 Novo Visitante';
        const form = document.getElementById('formDados');
        form.innerHTML = `
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Nome</label>
                    <input type="text" id="visitante_nome" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-item">
                    <label>Telefone</label>
                    <input type="tel" id="visitante_telefone">
                </div>
                <div class="form-item">
                    <label>Email</label>
                    <input type="email" id="visitante_email">
                </div>
            </div>
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Data da Visita</label>
                    <input type="date" id="visitante_data" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Observações</label>
                    <textarea id="visitante_obs" rows="2"></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Visitante</button>
        `;
        form.onsubmit = salvarVisitante;
        modalFormulario.style.display = 'block';
    }

    async function salvarVisitante(e) {
        e.preventDefault();
        const dados = {
            igreja_id: igrejaAtual,
            nome: document.getElementById('visitante_nome').value,
            telefone: document.getElementById('visitante_telefone').value,
            email: document.getElementById('visitante_email').value,
            data_visita: document.getElementById('visitante_data').value,
            obs: document.getElementById('visitante_obs').value
        };
        
        const resultado = await fazerRequisicao('criar_visitante_igreja', 'POST', dados);
        if (resultado.status === 'sucesso') {
            mostrarNotificacao('Visitante cadastrado!', 'sucesso');
            fecharModalFormulario();
            carregarVisitantes();
        } else {
            mostrarNotificacao(resultado.mensagem || 'Erro', 'erro');
        }
    }

    async function carregarVisitantes() {
        const resultado = await fazerRequisicao(`listar_visitantes_igreja&id=${igrejaAtual}`, 'GET');
        let html = '';
        
        if (resultado.status === 'sucesso' && resultado.dados.length > 0) {
            resultado.dados.forEach(visitante => {
                const data = formatarDataLocal(visitante.data_visita);
                html += `
                    <div class="item-card">
                        <div class="item-info">
                            <h4>${ieq.escapeHtml(visitante.nome)}</h4>
                            <p>${data}</p>
                            <p>${ieq.escapeHtml(visitante.telefone || visitante.email || 'Sem contato')}</p>
                        </div>
                        <div class="item-actions">
                            <button class="btn btn-small btn-danger" onclick="deletarVisitante(${visitante.id})">🗑️</button>
                        </div>
                    </div>
                `;
            });
        } else {
            html = '<p style="text-align: center; color: #999;">Nenhum visitante cadastrado</p>';
        }
        
        document.getElementById('listaVisitantes').innerHTML = html;
    }

    async function deletarVisitante(id) {
        if (confirm('Deletar este visitante?')) {
            const resultado = await fazerRequisicao('deletar_visitante_igreja', 'POST', { id });
            if (resultado.status === 'sucesso') {
                mostrarNotificacao('Visitante deletado!', 'sucesso');
                carregarVisitantes();
            }
        }
    }

    // CONVERSÕES
    function abrirFormConversaoRapido(igreja_id) {
        igrejaAtual = igreja_id;
        abrirFormConversao();
    }

    function abrirFormConversao() {
        tipoFormularioAtual = 'conversao';
        document.getElementById('tituloFormulario').textContent = '✝️ Novo Registro de Conversão';
        const form = document.getElementById('formDados');
        form.innerHTML = `
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Nome da Pessoa</label>
                    <input type="text" id="conversao_nome" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Data da Conversão</label>
                    <input type="date" id="conversao_data" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Observações</label>
                    <textarea id="conversao_obs" rows="2"></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Registro</button>
        `;
        form.onsubmit = salvarConversao;
        modalFormulario.style.display = 'block';
    }

    async function salvarConversao(e) {
        e.preventDefault();
        const dados = {
            igreja_id: igrejaAtual,
            nome: document.getElementById('conversao_nome').value,
            data_conversao: document.getElementById('conversao_data').value,
            obs: document.getElementById('conversao_obs').value
        };
        
        const resultado = await fazerRequisicao('criar_conversao_igreja', 'POST', dados);
        if (resultado.status === 'sucesso') {
            mostrarNotificacao('Conversão registrada!', 'sucesso');
            fecharModalFormulario();
            carregarConversoes();
        } else {
            mostrarNotificacao(resultado.mensagem || 'Erro', 'erro');
        }
    }

    async function carregarConversoes() {
        const resultado = await fazerRequisicao(`listar_conversoes_igreja&id=${igrejaAtual}`, 'GET');
        let html = '';
        
        if (resultado.status === 'sucesso' && resultado.dados.length > 0) {
            resultado.dados.forEach(conversao => {
                const data = formatarDataLocal(conversao.data_conversao);
                html += `
                    <div class="item-card">
                        <div class="item-info">
                            <h4>✝️ ${ieq.escapeHtml(conversao.nome)}</h4>
                            <p>${data}</p>
                        </div>
                        <div class="item-actions">
                            <button class="btn btn-small btn-danger" onclick="deletarConversao(${conversao.id})">🗑️</button>
                        </div>
                    </div>
                `;
            });
        } else {
            html = '<p style="text-align: center; color: #999;">Nenhuma conversão registrada</p>';
        }
        
        document.getElementById('listaConversoes').innerHTML = html;
    }

    async function deletarConversao(id) {
        if (confirm('Deletar este registro?')) {
            const resultado = await fazerRequisicao('deletar_conversao_igreja', 'POST', { id });
            if (resultado.status === 'sucesso') {
                mostrarNotificacao('Registro deletado!', 'sucesso');
                carregarConversoes();
            }
        }
    }

    // RECONCILIAÇÃO
    function abrirFormReconciliacaoRapido(igreja_id) {
        igrejaAtual = igreja_id;
        abrirFormReconciliacao();
    }

    function abrirFormReconciliacao() {
        tipoFormularioAtual = 'reconciliacao';
        document.getElementById('tituloFormulario').textContent = '🤝 Nova Reconciliação';
        const form = document.getElementById('formDados');
        form.innerHTML = `
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Nome da Pessoa</label>
                    <input type="text" id="reconciliacao_nome" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Data da Reconciliação</label>
                    <input type="date" id="reconciliacao_data" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Observações</label>
                    <textarea id="reconciliacao_obs" rows="2"></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Reconciliação</button>
        `;
        form.onsubmit = salvarReconciliacao;
        modalFormulario.style.display = 'block';
    }

    async function salvarReconciliacao(e) {
        e.preventDefault();
        const dados = {
            igreja_id: igrejaAtual,
            nome: document.getElementById('reconciliacao_nome').value,
            data_reconciliacao: document.getElementById('reconciliacao_data').value,
            obs: document.getElementById('reconciliacao_obs').value
        };
        
        const resultado = await fazerRequisicao('criar_reconciliacao_igreja', 'POST', dados);
        if (resultado.status === 'sucesso') {
            mostrarNotificacao('Reconciliação registrada!', 'sucesso');
            fecharModalFormulario();
            carregarReconciliacao();
        } else {
            mostrarNotificacao(resultado.mensagem || 'Erro', 'erro');
        }
    }

    async function carregarReconciliacao() {
        const resultado = await fazerRequisicao(`listar_reconciliacao_igreja&id=${igrejaAtual}`, 'GET');
        let html = '';
        
        if (resultado.status === 'sucesso' && resultado.dados.length > 0) {
            resultado.dados.forEach(reconciliacao => {
                const data = formatarDataLocal(reconciliacao.data_reconciliacao);
                html += `
                    <div class="item-card">
                        <div class="item-info">
                            <h4>🤝 ${ieq.escapeHtml(reconciliacao.nome)}</h4>
                            <p>${data}</p>
                        </div>
                        <div class="item-actions">
                            <button class="btn btn-small btn-danger" onclick="deletarReconciliacao(${reconciliacao.id})">🗑️</button>
                        </div>
                    </div>
                `;
            });
        } else {
            html = '<p style="text-align: center; color: #999;">Nenhuma reconciliação registrada</p>';
        }
        
        document.getElementById('listaReconciliacao').innerHTML = html;
    }

    async function deletarReconciliacao(id) {
        if (confirm('Deletar este registro?')) {
            const resultado = await fazerRequisicao('deletar_reconciliacao_igreja', 'POST', { id });
            if (resultado.status === 'sucesso') {
                mostrarNotificacao('Registro deletado!', 'sucesso');
                carregarReconciliacao();
            }
        }
    }

    // BATISMOS
    function abrirFormBatismoRapido(igreja_id) {
        igrejaAtual = igreja_id;
        abrirFormBatismo();
    }

    function abrirFormBatismo() {
        tipoFormularioAtual = 'batismo';
        document.getElementById('tituloFormulario').textContent = '💧 Novo Batismo';
        const form = document.getElementById('formDados');
        form.innerHTML = `
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Nome da Pessoa</label>
                    <input type="text" id="batismo_nome" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-item">
                    <label>Data do Batismo</label>
                    <input type="date" id="batismo_data" required>
                </div>
                <div class="form-item">
                    <label>Ministro/Pastor</label>
                    <input type="text" id="batismo_ministro">
                </div>
            </div>
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Localização</label>
                    <input type="text" id="batismo_localizacao" placeholder="Rio, Piscina, Igreja...">
                </div>
            </div>
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Observações</label>
                    <textarea id="batismo_obs" rows="2"></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Batismo</button>
        `;
        form.onsubmit = salvarBatismo;
        modalFormulario.style.display = 'block';
    }

    async function salvarBatismo(e) {
        e.preventDefault();
        const dados = {
            igreja_id: igrejaAtual,
            nome: document.getElementById('batismo_nome').value,
            data_batismo: document.getElementById('batismo_data').value,
            ministro: document.getElementById('batismo_ministro').value,
            localizacao: document.getElementById('batismo_localizacao').value,
            obs: document.getElementById('batismo_obs').value
        };
        
        const resultado = await fazerRequisicao('criar_batismo_igreja', 'POST', dados);
        if (resultado.status === 'sucesso') {
            mostrarNotificacao('Batismo registrado!', 'sucesso');
            fecharModalFormulario();
            carregarBatismos();
        } else {
            mostrarNotificacao(resultado.mensagem || 'Erro', 'erro');
        }
    }

    async function carregarBatismos() {
        const resultado = await fazerRequisicao(`listar_batismos_igreja&id=${igrejaAtual}`, 'GET');
        let html = '';
        
        if (resultado.status === 'sucesso' && resultado.dados.length > 0) {
            resultado.dados.forEach(batismo => {
                const data = formatarDataLocal(batismo.data_batismo);
                html += `
                    <div class="item-card">
                        <div class="item-info">
                            <h4>💧 ${ieq.escapeHtml(batismo.nome)}</h4>
                            <p>${data}</p>
                            <p>${ieq.escapeHtml(batismo.localizacao || 'Local não informado')}</p>
                        </div>
                        <div class="item-actions">
                            <button class="btn btn-small btn-danger" onclick="deletarBatismo(${batismo.id})">🗑️</button>
                        </div>
                    </div>
                `;
            });
        } else {
            html = '<p style="text-align: center; color: #999;">Nenhum batismo registrado</p>';
        }
        
        document.getElementById('listaBatismos').innerHTML = html;
    }

    async function deletarBatismo(id) {
        if (confirm('Deletar este batismo?')) {
            const resultado = await fazerRequisicao('deletar_batismo_igreja', 'POST', { id });
            if (resultado.status === 'sucesso') {
                mostrarNotificacao('Batismo deletado!', 'sucesso');
                carregarBatismos();
            }
        }
    }

    // RELATÓRIO
    async function carregarRelatorio() {
        const resultado = await fazerRequisicao(`relatorio_igreja&id=${igrejaAtual}`, 'GET');
        
        if (resultado.status === 'sucesso') {
            const dados = resultado.dados;
            const igreja = dados.igreja;
            
            let html = `
                <div class="relatorio-section">
                    <h4>📋 Informações da Igreja</h4>
                    <p><strong>Nome:</strong> ${ieq.escapeHtml(igreja.nome)}</p>
                    <p><strong>Endereço:</strong> ${ieq.escapeHtml(igreja.endereco || 'Não informado')}</p>
                    <p><strong>Cidade:</strong> ${ieq.escapeHtml(igreja.cidade || 'Não informado')}</p>
                    <p><strong>Telefone:</strong> ${ieq.escapeHtml(igreja.telefone || 'Não informado')}</p>
                </div>
                
                <div class="relatorio-section">
                    <h4>📊 Estatísticas</h4>
                    <p><strong>Visitantes:</strong> ${dados.total_visitantes}</p>
                    <p><strong>Conversões:</strong> ${dados.total_conversoes}</p>
                    <p><strong>Reconciliações:</strong> ${dados.total_reconciliacao}</p>
                    <p><strong>Batismos:</strong> ${dados.total_batismos}</p>
                </div>
                
                <div class="relatorio-section">
                    <h4>📅 Relatório Gerado em:</h4>
                    <p>${new Date().toLocaleDateString('pt-BR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
                </div>
            `;
            
            document.getElementById('relatorioIgreja').innerHTML = html;
        }
    }

    function exportarRelatorio() {
        const relatorioHTML = document.getElementById('relatorioIgreja').innerHTML;
        const janela = window.open('', '', 'width=800,height=600');
        janela.document.write(`
            <html>
            <head>
                <title>Relatório - ${document.getElementById('tituloIgreja').textContent}</title>
                <style>
                    body { font-family: Arial; margin: 20px; }
                    h2 { color: #667eea; }
                    .relatorio-section { margin-bottom: 30px; }
                    .relatorio-section h4 { color: #333; }
                </style>
            </head>
            <body>
                <h2>${document.getElementById('tituloIgreja').textContent}</h2>
                ${relatorioHTML}
                <hr>
                <p style="text-align: center; color: #999; font-size: 12px;">
                    Relatório impresso em ${new Date().toLocaleDateString('pt-BR')} às ${new Date().toLocaleTimeString('pt-BR')}
                </p>
            </body>
            </html>
        `);
        janela.document.close();
        janela.print();
    }

    document.addEventListener('DOMContentLoaded', function() {
        carregarPastores();
        carregarIgrejas();

        document.getElementById('formIgreja').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const dados = {
                nome: document.getElementById('igreja_nome').value,
                endereco: document.getElementById('igreja_endereco').value,
                bairro: document.getElementById('igreja_bairro').value,
                cidade: document.getElementById('igreja_cidade').value,
                telefone: document.getElementById('igreja_telefone').value,
                email: document.getElementById('igreja_email').value,
                pastor_presidente_id: document.getElementById('igreja_pastor_presidente_id').value || '',
                pastor_auxiliar_id: document.getElementById('igreja_pastor_auxiliar_id').value || ''
            };
            
            let resultado;
            if (editandoId) {
                dados.id = editandoId;
                resultado = await fazerRequisicao('atualizar_igreja', 'POST', dados);
            } else {
                resultado = await fazerRequisicao('criar_igreja', 'POST', dados);
            }
            
            if (resultado.status === 'sucesso') {
                mostrarNotificacao(editandoId ? 'Igreja atualizada!' : 'Igreja criada!', 'sucesso');
                igrejaModal.style.display = 'none';
                document.getElementById('formIgreja').reset();
                editandoId = null;
                await carregarIgrejas();
            } else {
                mostrarNotificacao(resultado.mensagem || 'Erro ao salvar igreja', 'erro');
            }
        });
    });
</script>
