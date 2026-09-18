<!-- Cabeçalho da Página -->
<div class="page-header">
    <h1>👥 Cadastro de Visitantes</h1>
    <p>Registre os visitantes que compareceram às células</p>
    <button class="btn btn-primary" onclick="abrirModal()">+ Novo Visitante</button>
</div>

<!-- Seção de Relatório -->
<div class="relatorio-section">
    <div class="relatorio-header">
        <h2>📊 Relatório de Visitantes</h2>
        <div class="relatorio-filtros">
            <select id="relatorioMes" class="filter-select">
                <option value="">Todos os meses</option>
                <option value="01">Janeiro</option>
                <option value="02">Fevereiro</option>
                <option value="03">Março</option>
                <option value="04">Abril</option>
                <option value="05">Maio</option>
                <option value="06">Junho</option>
                <option value="07">Julho</option>
                <option value="08">Agosto</option>
                <option value="09">Setembro</option>
                <option value="10">Outubro</option>
                <option value="11">Novembro</option>
                <option value="12">Dezembro</option>
            </select>
            <select id="relatorioAno" class="filter-select">
                <option value="">Todos os anos</option>
            </select>
            <select id="relatorioCelula" class="filter-select">
                <option value="">Todas as células</option>
            </select>
            <button class="btn btn-primary" onclick="atualizarRelatorio()">🔄 Atualizar</button>
        </div>
    </div>

    <div class="relatorio-grid">
        <!-- Cards de Estatísticas -->
        <div class="relatorio-card stats-card">
            <div class="stats-icon">👁️</div>
            <div class="stats-content">
                <div class="stats-label">Total de Visitantes</div>
                <div class="stats-valor" id="totalVisitantes">0</div>
                <div class="stats-subtitle">Novo(s) neste período</div>
            </div>
        </div>

        <div class="relatorio-card stats-card">
            <div class="stats-icon">🔄</div>
            <div class="stats-content">
                <div class="stats-label">Retornos</div>
                <div class="stats-valor" id="totalRetornos">0</div>
                <div class="stats-subtitle">Visitantes que retornaram</div>
            </div>
        </div>

        <div class="relatorio-card stats-card">
            <div class="stats-icon">✝️</div>
            <div class="stats-content">
                <div class="stats-label">Convertidos</div>
                <div class="stats-valor" id="totalConvertidos">0</div>
                <div class="stats-subtitle">Aceitos em Cristo</div>
            </div>
        </div>

        <div class="relatorio-card stats-card">
            <div class="stats-icon">👨‍👩‍👧‍👦</div>
            <div class="stats-content">
                <div class="stats-label">Novos Membros</div>
                <div class="stats-valor" id="totalMembros">0</div>
                <div class="stats-subtitle">Incorporados à comunidade</div>
            </div>
        </div>
    </div>

    <!-- Tabela de Relatório Detalhado -->
    <div class="relatorio-tabela">
        <h3>📋 Detalhamento por Células</h3>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Célula</th>
                    <th>Visitantes</th>
                    <th>Retornos</th>
                    <th>Convertidos</th>
                    <th>Membros</th>
                    <th>Taxa Conversão</th>
                </tr>
            </thead>
            <tbody id="relatorioTabela">
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">Carregando relatório...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Gráfico de Evolução -->
    <div class="relatorio-graficos">
        <div class="grafico-container">
            <h3>📈 Evolução de Visitantes (Últimos 30 dias)</h3>
            <canvas id="graficoEvolucao" style="max-height: 300px;"></canvas>
        </div>
        
        <div class="grafico-container">
            <h3>🎯 Distribuição por Status</h3>
            <canvas id="graficoStatus" style="max-height: 300px;"></canvas>
        </div>
    </div>
</div>

<!-- Lista de Visitantes -->
<div class="card">
    <div class="card-header">
        <h3>📋 Visitantes Cadastrados</h3>
        <div class="filtros">
            <input type="text" id="searchVisitante" placeholder="Buscar visitante..." class="search-input">
            <select id="filterCelula" class="filter-select">
                <option value="">Todas as células</option>
            </select>
        </div>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Telefone</th>
                <th>Célula Visitada</th>
                <th>Data da Visita</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="visitantesList">
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px;">Carregando visitantes...</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Modal de Cadastro/Edição -->
<div id="visitanteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="fecharModal()">&times;</span>
        <h2 id="modalTitle">Novo Visitante</h2>
        
        <form id="visitanteForm" onsubmit="salvarVisitante(event)">
            <input type="hidden" id="visitanteId">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nome">Nome Completo *</label>
                    <input type="text" id="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone *</label>
                    <input type="tel" id="telefone" placeholder="(00) 00000-0000" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email">
                </div>
                
                <div class="form-group">
                    <label for="celula">Célula Visitada *</label>
                    <select id="celula" required>
                        <option value="">Selecione uma célula</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="dataVisita">Data da Visita *</label>
                    <input type="date" id="dataVisita" required>
                </div>
                
                <div class="form-group">
                    <label for="statusVisitante">Status</label>
                    <select id="statusVisitante">
                        <option value="primeira_visita">Primeira Visita</option>
                        <option value="retornou">Retornou</option>
                        <option value="convertido">Convertido</option>
                        <option value="membro">Tornou-se Membro</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="observacoes">Observações</label>
                <textarea id="observacoes" rows="3" placeholder="Informações adicionais sobre o visitante..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Visitante</button>
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
        padding: 20px;
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

    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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

    .card-header h3 {
        margin: 0;
        color: #333;
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

    .data-table {
        width: 100%;
        border-collapse: collapse;
        flex: 1;
        overflow-y: auto;
        display: block;
    }

    .data-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        position: sticky;
        top: 0;
        z-index: 10;
        display: table;
        width: 100%;
    }

    .data-table tbody {
        display: table;
        width: 100%;
    }

    .data-table th {
        padding: 15px;
        text-align: left;
        font-weight: 500;
    }

    .data-table td {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .data-table tbody tr:hover {
        background: #f8f9ff;
    }

    .status-badge {
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: 500;
        text-align: center;
        display: inline-block;
    }

    .status-primeira_visita { background: #e3f2fd; color: #1976d2; }
    .status-retornou { background: #fff3e0; color: #f57c00; }
    .status-convertido { background: #e8f5e9; color: #388e3c; }
    .status-membro { background: #f3e5f5; color: #7b1fa2; }

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
        max-width: 700px;
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
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }

    .form-group textarea {
        resize: vertical;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 30px;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .filtros {
            width: 100%;
        }

        .search-input {
            width: 100%;
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
            font-size: 0.85rem;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
            min-height: 45px;
            vertical-align: middle;
        }

        .data-table th {
            font-size: 0.75rem;
            min-height: 50px;
        }

        .status-badge {
            display: block;
            margin: 4px 0;
            padding: 6px 10px;
            font-size: 11px;
        }
    }

    /* Estilos para Relatório */
    .relatorio-section {
        margin-bottom: 30px;
    }

    .relatorio-header {
        background: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .relatorio-header h2 {
        margin: 0 0 15px 0;
        color: #333;
        font-size: 22px;
    }

    .relatorio-filtros {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .relatorio-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }

    .stats-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        display: flex;
        gap: 15px;
        align-items: center;
        border-left: 4px solid #667eea;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .stats-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .stats-card:nth-child(2) {
        border-left-color: #f57c00;
    }

    .stats-card:nth-child(3) {
        border-left-color: #388e3c;
    }

    .stats-card:nth-child(4) {
        border-left-color: #7b1fa2;
    }

    .stats-icon {
        font-size: 32px;
        min-width: 45px;
    }

    .stats-content {
        flex: 1;
    }

    .stats-label {
        font-size: 12px;
        color: #666;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stats-valor {
        font-size: 28px;
        font-weight: 700;
        color: #333;
        margin: 5px 0;
    }

    .stats-subtitle {
        font-size: 11px;
        color: #999;
    }

    .relatorio-tabela {
        background: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .relatorio-tabela h3 {
        margin: 0 0 15px 0;
        color: #333;
    }

    .report-table {
        width: 100%;
        border-collapse: collapse;
    }

    .report-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .report-table th {
        padding: 12px 15px;
        text-align: left;
        font-weight: 500;
        font-size: 13px;
    }

    .report-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }

    .report-table tbody tr:hover {
        background: #f8f9ff;
    }

    .taxa-conversao {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }

    .relatorio-graficos {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
    }

    .grafico-container {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .grafico-container h3 {
        margin: 0 0 15px 0;
        color: #333;
        font-size: 16px;
    }

    @media (max-width: 768px) {
        .relatorio-grid {
            grid-template-columns: 1fr;
        }

        .relatorio-filtros {
            flex-direction: column;
        }

        .relatorio-filtros select,
        .relatorio-filtros button {
            width: 100%;
        }

        .relatorio-graficos {
            grid-template-columns: 1fr;
        }
    }

</style>

<script>
    let visitantes = [];
    let editandoId = null;

    // Carregar dados ao iniciar
    document.addEventListener('DOMContentLoaded', function() {
        carregarCelulas();
        carregarVisitantes();
        preencherAnosRelatorio();
        atualizarRelatorio();
        
        // Definir data de hoje como padrão
        document.getElementById('dataVisita').valueAsDate = new Date();
        
        // Filtro de busca
        document.getElementById('searchVisitante').addEventListener('input', filtrarVisitantes);
        document.getElementById('filterCelula').addEventListener('change', filtrarVisitantes);
        
        // Filtros do relatório
        document.getElementById('relatorioMes').addEventListener('change', atualizarRelatorio);
        document.getElementById('relatorioAno').addEventListener('change', atualizarRelatorio);
        document.getElementById('relatorioCelula').addEventListener('change', atualizarRelatorio);
    });

    // Carregar células para os selects
    async function carregarCelulas() {
        try {
            const resultado = await fazerRequisicao('listar_celulas', 'GET');
            if (resultado.status === 'sucesso') {
                const celulas = resultado.dados;
                let html = '<option value="">Selecione uma célula</option>';
                celulas.forEach(celula => {
                    html += `<option value="${celula.id}">${celula.nome}</option>`;
                });
                document.getElementById('celula').innerHTML = html;
                
                // Filtro
                let htmlFiltro = '<option value="">Todas as células</option>';
                celulas.forEach(celula => {
                    htmlFiltro += `<option value="${celula.id}">${celula.nome}</option>`;
                });
                document.getElementById('filterCelula').innerHTML = htmlFiltro;
                
                // Relatório
                let htmlRelatorio = '<option value="">Todas as células</option>';
                celulas.forEach(celula => {
                    htmlRelatorio += `<option value="${celula.id}">${celula.nome}</option>`;
                });
                document.getElementById('relatorioCelula').innerHTML = htmlRelatorio;
            }
        } catch (erro) {
            console.error('Erro ao carregar células:', erro);
        }
    }

    // Carregar visitantes
    async function carregarVisitantes() {
        try {
            const resultado = await fazerRequisicao('listar_visitantes', 'GET');
            if (resultado.status === 'sucesso') {
                visitantes = resultado.dados;
                console.log('📥 Visitantes carregados do servidor:', visitantes);
                console.log('Tipo de ID do primeiro visitante:', typeof visitantes[0]?.id);
                renderizarVisitantes(visitantes);
            } else {
                document.getElementById('visitantesList').innerHTML = 
                    '<tr><td colspan="6" style="text-align: center; padding: 40px;">Nenhum visitante cadastrado</td></tr>';
            }
        } catch (erro) {
            console.error('Erro ao carregar visitantes:', erro);
            document.getElementById('visitantesList').innerHTML = 
                '<tr><td colspan="6" style="text-align: center; padding: 40px; color: red;">Erro ao carregar visitantes</td></tr>';
        }
    }

    // Renderizar lista de visitantes
    function renderizarVisitantes(lista) {
        const tbody = document.getElementById('visitantesList');
        
        if (!lista || lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px;">Nenhum visitante encontrado</td></tr>';
            return;
        }

        let html = '';
        lista.forEach((visitante, idx) => {
            console.log(`📋 Renderizando visitante ${idx}:`, visitante.id, 'Tipo:', typeof visitante.id, 'Nome:', visitante.nome);
            
            const statusClass = `status-${visitante.status || 'primeira_visita'}`;
            const statusTexto = {
                'primeira_visita': 'Primeira Visita',
                'retornou': 'Retornou',
                'convertido': 'Convertido',
                'membro': 'Membro'
            }[visitante.status] || 'Primeira Visita';

            html += `
                <tr>
                    <td><strong>${ieq.escapeHtml(visitante.nome)}</strong></td>
                    <td>${ieq.escapeHtml(visitante.telefone)}</td>
                    <td>${ieq.escapeHtml(visitante.celula_nome || 'N/A')}</td>
                    <td>${formatarData(visitante.data_visita)}</td>
                    <td><span class="status-badge ${statusClass}">${ieq.escapeHtml(statusTexto)}</span></td>
                    <td>
                        <button class="btn btn-edit" onclick="editarVisitante(${visitante.id})">Editar</button>
                        <button class="btn btn-delete" onclick="deletarVisitante(${visitante.id})">Excluir</button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        console.log('✅ Tabela renderizada com', lista.length, 'visitantes');
    }

    // Filtrar visitantes
    function filtrarVisitantes() {
        const busca = document.getElementById('searchVisitante').value.toLowerCase();
        const celulaFiltro = document.getElementById('filterCelula').value;
        
        const filtrados = visitantes.filter(v => {
            const matchBusca = !busca || 
                v.nome.toLowerCase().includes(busca) || 
                v.telefone.includes(busca);
            const matchCelula = !celulaFiltro || v.celula_id == celulaFiltro;
            return matchBusca && matchCelula;
        });
        
        renderizarVisitantes(filtrados);
    }

    // Abrir modal
    function abrirModal() {
        editandoId = null;
        document.getElementById('modalTitle').textContent = 'Novo Visitante';
        document.getElementById('visitanteForm').reset();
        document.getElementById('visitanteId').value = '';
        document.getElementById('dataVisita').valueAsDate = new Date();
        document.getElementById('visitanteModal').style.display = 'flex';
    }

    // Fechar modal
    function fecharModal() {
        document.getElementById('visitanteModal').style.display = 'none';
    }

    // Editar visitante
    async function editarVisitante(id) {
        console.log('🔍 Tentando editar visitante ID:', id, 'Tipo:', typeof id);
        
        // Verifica se o modal existe
        const modal = document.getElementById('visitanteModal');
        if (!modal) {
            console.error('❌ ERRO CRÍTICO: Modal element não encontrado na DOM');
            return;
        }
        console.log('✅ Modal element encontrado na DOM');
        
        // Converter para número se necessário
        const idNumerico = parseInt(id, 10);
        console.log('🔢 ID convertido para número:', idNumerico);
        
        console.log('🔎 Procurando visitante na array. Array tem', visitantes.length, 'elementos');
        const visitante = visitantes.find(v => {
            const vId = parseInt(v.id, 10);
            const match = vId === idNumerico;
            console.log(`   Comparando: v.id=${v.id} (${typeof v.id}) → parseInt=${vId} === ${idNumerico} ? ${match}`);
            return match;
        });
        
        if (!visitante) {
            console.error('❌ Visitante não encontrado. ID buscado:', idNumerico);
            console.error('IDs disponíveis na array:', visitantes.map(v => ({ id: v.id, nome: v.nome })));
            alert('Erro: Visitante não encontrado no sistema.');
            return;
        }
        
        console.log('✅ Visitante encontrado:', visitante);

        editandoId = id;
        document.getElementById('modalTitle').textContent = 'Editar Visitante';
        document.getElementById('visitanteId').value = id;
        document.getElementById('nome').value = visitante.nome;
        document.getElementById('telefone').value = visitante.telefone;
        document.getElementById('email').value = visitante.email || '';
        document.getElementById('celula').value = visitante.celula_id;
        document.getElementById('dataVisita').value = visitante.data_visita;
        document.getElementById('statusVisitante').value = visitante.status || 'primeira_visita';
        document.getElementById('observacoes').value = visitante.observacoes || '';
        
        console.log('📝 Modal preenchido com dados');
        console.log('Tentando abrir modal. display atual:', modal.style.display);
        modal.style.display = 'flex';
        console.log('✅ Modal display definido como flex. Nova display:', modal.style.display);
        
        // Adiciona pequeno delay para garantir que o DOM foi atualizado
        await new Promise(resolve => setTimeout(resolve, 100));
        console.log('✅ Modal deveria estar visível agora');
    }

    // Salvar visitante
    async function salvarVisitante(event) {
        event.preventDefault();

        const dados = {
            id: document.getElementById('visitanteId').value,
            nome: document.getElementById('nome').value,
            telefone: document.getElementById('telefone').value,
            email: document.getElementById('email').value,
            celula_id: document.getElementById('celula').value,
            data_visita: document.getElementById('dataVisita').value,
            status: document.getElementById('statusVisitante').value,
            observacoes: document.getElementById('observacoes').value
        };

        const acao = dados.id ? 'atualizar_visitante' : 'criar_visitante';
        
        try {
            const resultado = await fazerRequisicao(acao, 'POST', dados);
            
            if (resultado.status === 'sucesso') {
                mostrarNotificacao(resultado.mensagem, 'sucesso');
                fecharModal();
                await carregarVisitantes();
            } else {
                mostrarNotificacao(resultado.mensagem, 'erro');
            }
        } catch (erro) {
            mostrarNotificacao('Erro ao salvar visitante', 'erro');
        }
    }

    // Deletar visitante
    async function deletarVisitante(id) {
        if (!confirm('Tem certeza que deseja excluir este visitante?')) return;

        try {
            const resultado = await fazerRequisicao('deletar_visitante', 'POST', { id });
            
            if (resultado.status === 'sucesso') {
                mostrarNotificacao('Visitante excluído com sucesso', 'sucesso');
                await carregarVisitantes();
            } else {
                mostrarNotificacao(resultado.mensagem, 'erro');
            }
        } catch (erro) {
            mostrarNotificacao('Erro ao excluir visitante', 'erro');
        }
    }

    // Formatar data
    function formatarData(data) {
        if (!data) return 'N/A';
        const d = new Date(data + 'T00:00:00');
        return d.toLocaleDateString('pt-BR');
    }

    // ============ FUNÇÕES DO RELATÓRIO ============

    // Preencher anos no select do relatório
    function preencherAnosRelatorio() {
        const anoAtual = new Date().getFullYear();
        let html = '<option value="">Todos os anos</option>';
        
        for (let i = anoAtual; i >= anoAtual - 5; i--) {
            html += `<option value="${i}">${i}</option>`;
        }
        
        document.getElementById('relatorioAno').innerHTML = html;
    }

    // Atualizar relatório completo
    async function atualizarRelatorio() {
        const mes = document.getElementById('relatorioMes').value;
        const ano = document.getElementById('relatorioAno').value;
        const celulaId = document.getElementById('relatorioCelula').value;

        // Filtrar visitantes baseado nos critérios
        let visitantesFiltrados = visitantes;

        if (mes) {
            visitantesFiltrados = visitantesFiltrados.filter(v => {
                const dataParts = v.data_visita.split('-');
                return dataParts[1] === mes;
            });
        }

        if (ano) {
            visitantesFiltrados = visitantesFiltrados.filter(v => {
                const dataParts = v.data_visita.split('-');
                return dataParts[0] === ano;
            });
        }

        if (celulaId) {
            visitantesFiltrados = visitantesFiltrados.filter(v => v.celula_id == celulaId);
        }

        // Calcular estatísticas
        const totalVisitantes = visitantesFiltrados.length;
        const totalRetornos = visitantesFiltrados.filter(v => v.status === 'retornou').length;
        const totalConvertidos = visitantesFiltrados.filter(v => v.status === 'convertido').length;
        const totalMembros = visitantesFiltrados.filter(v => v.status === 'membro').length;

        // Atualizar cards
        document.getElementById('totalVisitantes').textContent = totalVisitantes;
        document.getElementById('totalRetornos').textContent = totalRetornos;
        document.getElementById('totalConvertidos').textContent = totalConvertidos;
        document.getElementById('totalMembros').textContent = totalMembros;

        // Atualizar tabela detalhada
        atualizarTabelaRelatorio(visitantesFiltrados);

        // Atualizar gráficos
        atualizarGraficos(visitantesFiltrados);
    }

    // Atualizar tabela do relatório
    function atualizarTabelaRelatorio(visitantesFiltrados) {
        // Agrupar por célula
        const agrupoPorCelula = {};
        
        visitantesFiltrados.forEach(v => {
            const celulaId = v.celula_id;
            const celulaNome = v.celula_nome || 'Sem Célula';
            
            if (!agrupoPorCelula[celulaId]) {
                agrupoPorCelula[celulaId] = {
                    nome: celulaNome,
                    visitantes: [],
                    retornos: 0,
                    convertidos: 0,
                    membros: 0
                };
            }
            
            agrupoPorCelula[celulaId].visitantes.push(v);
            if (v.status === 'retornou') agrupoPorCelula[celulaId].retornos++;
            if (v.status === 'convertido') agrupoPorCelula[celulaId].convertidos++;
            if (v.status === 'membro') agrupoPorCelula[celulaId].membros++;
        });

        let html = '';
        
        if (Object.keys(agrupoPorCelula).length === 0) {
            html = '<tr><td colspan="6" style="text-align: center; padding: 20px;">Nenhum dado para este período</td></tr>';
        } else {
            Object.values(agrupoPorCelula).forEach(grupo => {
                const totalGrupo = grupo.visitantes.length;
                const taxaConversao = totalGrupo > 0 
                    ? Math.round((grupo.convertidos / totalGrupo) * 100) 
                    : 0;

                html += `
                    <tr>
                        <td><strong>${ieq.escapeHtml(grupo.nome)}</strong></td>
                        <td><strong>${totalGrupo}</strong></td>
                        <td>${grupo.retornos}</td>
                        <td>${grupo.convertidos}</td>
                        <td>${grupo.membros}</td>
                        <td><span class="taxa-conversao">${taxaConversao}%</span></td>
                    </tr>
                `;
            });
        }

        document.getElementById('relatorioTabela').innerHTML = html;
    }

    // Atualizar gráficos
    function atualizarGraficos(visitantesFiltrados) {
        const Chart = window.Chart;
        
        if (Chart) {
            // Gráfico de Evolução (últimos 30 dias)
            atualizarGraficoEvolucao(visitantesFiltrados);
            
            // Gráfico de Status
            atualizarGraficoStatus(visitantesFiltrados);
        }
    }

    // Gráfico de Evolução
    function atualizarGraficoEvolucao(visitantesFiltrados) {
        const Chart = window.Chart;
        if (!Chart) return;

        // Agrupar visitantes por dia dos últimos 30 dias
        const hoje = new Date();
        const dados = {};
        
        for (let i = 29; i >= 0; i--) {
            const data = new Date(hoje);
            data.setDate(data.getDate() - i);
            const chave = data.toISOString().split('T')[0];
            dados[chave] = 0;
        }

        visitantesFiltrados.forEach(v => {
            if (dados.hasOwnProperty(v.data_visita)) {
                dados[v.data_visita]++;
            }
        });

        const labels = Object.keys(dados);
        const values = Object.values(dados);

        // Converter labels para formato legível
        const labelsFormatados = labels.map(d => {
            const date = new Date(d + 'T00:00:00');
            return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
        });

        const ctx = document.getElementById('graficoEvolucao');
        
        if (window.graficoEvolucaoInstance) {
            window.graficoEvolucaoInstance.destroy();
        }

        window.graficoEvolucaoInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labelsFormatados,
                datasets: [{
                    label: 'Visitantes por dia',
                    data: values,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // Gráfico de Status
    function atualizarGraficoStatus(visitantesFiltrados) {
        const Chart = window.Chart;
        if (!Chart) return;

        const primeiraVisita = visitantesFiltrados.filter(v => v.status === 'primeira_visita').length;
        const retornou = visitantesFiltrados.filter(v => v.status === 'retornou').length;
        const convertido = visitantesFiltrados.filter(v => v.status === 'convertido').length;
        const membro = visitantesFiltrados.filter(v => v.status === 'membro').length;

        const ctx = document.getElementById('graficoStatus');
        
        if (window.graficoStatusInstance) {
            window.graficoStatusInstance.destroy();
        }

        window.graficoStatusInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Primeira Visita', 'Retornou', 'Convertido', 'Membro'],
                datasets: [{
                    data: [primeiraVisita, retornou, convertido, membro],
                    backgroundColor: [
                        '#2196f3',
                        '#ff9800',
                        '#4caf50',
                        '#9c27b0'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

</script>
