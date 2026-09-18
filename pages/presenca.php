<?php
// Obter informações do usuário da sessão
$user_funcao = $_SESSION['user_funcao'] ?? 'membro';
$user_id = $_SESSION['user_id'] ?? 0;

// Verificar se é líder e obter suas células
$eh_lider = false;
$celulas_lider = [];

if (function_exists('is_lider_exclusivo') && is_lider_exclusivo()) {
    $eh_lider = true;
    $celulas_lider = obter_celulas_do_lider();
}
?>

<div class="page-header">
    <h1>Presença</h1>
    <p>Registre e acompanhe a presença dos membros</p>
</div>

<div class="presenca-container">
    <!-- Cabeçalho fixo: líder com uma única célula não precisa escolher, a tela já abre nela -->
    <div class="celula-fixa-header" id="celulaFixaHeader" style="display: none;">
        <span class="icon">🏠</span>
        <div>
            <div class="nome" id="celulaFixaNome">Célula</div>
            <div class="sub">Sua célula</div>
        </div>
    </div>

    <div class="presenca-filters">
        <div class="form-item" id="celulaFilterItem">
            <label>Célula:</label>
            <select id="filterCelula">
                <option value="">Selecione uma célula</option>
            </select>
        </div>
        <div class="form-item">
            <label>Data:</label>
            <input type="date" id="filterData" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <button class="btn btn-secondary" id="btnVerRelatorio">📊 Ver Relatório</button>
        <button class="btn btn-info" id="btnExportarExcel">📥 Exportar Excel</button>
    </div>

    <!-- Seção de Relatórios -->
    <div id="relatorioSection" class="relatorio-section" style="display: none;">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>📊 Relatório de Presença</h2>
                <button class="btn btn-small" id="btnFecharRelatorio">✕ Fechar</button>
            </div>
            
            <div class="relatorio-filters" style="margin-bottom: 20px;">
                <div class="form-item">
                    <label>Período:</label>
                    <select id="relatorioPeriodo">
                        <option value="7">Últimos 7 dias</option>
                        <option value="15">Últimos 15 dias</option>
                        <option value="30" selected>Últimos 30 dias</option>
                        <option value="90">Últimos 90 dias</option>
                    </select>
                </div>
                <div class="form-item">
                    <label>Célula:</label>
                    <select id="relatorioCelula">
                        <option value="">Todas as células</option>
                    </select>
                </div>
                <button class="btn btn-primary" id="btnAtualizarRelatorio">Atualizar</button>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <div class="stat-label">Taxa de Presença</div>
                        <div class="stat-value" id="statTaxaPresenca">--%</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <div class="stat-label">Total Presenças</div>
                        <div class="stat-value" id="statTotalPresencas">--</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">❌</div>
                    <div class="stat-info">
                        <div class="stat-label">Total Ausências</div>
                        <div class="stat-value" id="statTotalAusencias">--</div>
                    </div>
                </div>
            </div>

            <div class="relatorio-tabela" style="margin-top: 30px;">
                <h3>📋 Desempenho por Célula</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Membro</th>
                            <th>Data</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="relatorioTableBody">
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 30px;">Carregando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="relatorio-membros" style="margin-top: 30px;">
                <h3>👥 Desempenho por Membro</h3>
                <div id="relatorioMembrosGrid" class="membros-grid">
                    <!-- Será preenchido via JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- Foto da reunião: mesma célula e data já selecionadas acima, sem repetir formulário -->
    <div class="foto-reuniao-section card" id="fotoReuniaoSection">
        <h3>📸 Foto da célula de hoje</h3>
        <p style="margin: -8px 0 15px; color: #666; font-size: 13px;">A foto entra junto com a presença — sem precisar ir até a Galeria.</p>

        <div class="foto-upload-horizontal">
            <div class="foto-preview-reuniao" id="fotoPreviewReuniao" onclick="document.getElementById('foto_reuniao').click()" style="cursor: pointer;">
                <div class="foto-placeholder-reuniao">
                    <span>📷</span>
                    <p>Toque para fotografar a célula</p>
                </div>
            </div>
            <div class="foto-upload-actions">
                <input type="file" id="foto_reuniao" accept="image/jpeg,image/png,image/gif" capture="environment" style="display: none;">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('foto_reuniao').click()">
                    📷 Escolher/Tirar Foto
                </button>
                <small style="display: block; color: #666; margin: 8px 0;">JPG, PNG ou GIF. Máximo 5MB</small>

                <label style="display: block; margin-top: 12px; font-weight: bold; color: #333;">Observações (opcional):</label>
                <textarea id="observacoes_reuniao" placeholder="Ex: ótima participação, tema abordado..." style="width: 100%; margin-top: 5px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit;" rows="2"></textarea>
            </div>
        </div>
    </div>

    <div class="card" id="membrosCard">
        <div class="card-header-presenca">
            <div>
                <h2>👥 Membros da Célula</h2>
                <p class="card-subtitle">Toque em Presente/Faltou — tudo é salvo de uma vez no botão abaixo</p>
            </div>
            <div class="legenda-presenca">
                <span class="legenda-item"><span class="dot dot-green"></span> Presente</span>
                <span class="legenda-item"><span class="dot dot-red"></span> Faltou</span>
            </div>
        </div>
        <table class="data-table presenca-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Nome</th>
                    <th style="width: 55%; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody id="presencaList">
                <tr>
                    <td colspan="2" style="text-align: center; padding: 40px; color: #999;">Selecione uma célula</td>
                </tr>
            </tbody>
        </table>

        <div class="visitante-inline" id="visitanteInlineBox" style="display: none;">
            <button type="button" class="btn-add-visitante" id="btnAbrirAddVisitante">+ Adicionar visitante</button>
            <div class="add-visitante-form" id="addVisitanteForm" style="display: none;">
                <input type="text" id="inputNomeVisitante" placeholder="Nome do visitante" maxlength="255">
                <button type="button" class="btn btn-primary" id="btnConfirmarVisitante">Adicionar</button>
                <button type="button" class="btn btn-secondary" id="btnCancelarVisitante">Cancelar</button>
            </div>
        </div>

        <div class="salvar-reuniao-box" id="salvarReuniaoBox" style="display: none;">
            <button type="button" class="btn-salvar-reuniao" id="btnSalvarReuniao">💾 Salvar reunião de hoje</button>
            <p class="salvar-reuniao-hint">Presença, visitantes e foto são enviados juntos, de uma vez</p>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div id="modalConfirmarDelecao" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 10001; align-items: center; justify-content: center;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 40px; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); text-align: center; animation: slideUp 0.3s ease;">
        <div style="font-size: 48px; margin-bottom: 15px;">🗑️</div>
        <h2 style="margin: 0 0 10px 0; color: white; font-size: 22px; font-weight: 600;">Deletar Foto?</h2>
        <p style="margin: 0 0 30px 0; color: rgba(255,255,255,0.9); font-size: 15px;">Tem certeza que deseja deletar esta foto permanentemente?</p>
        
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button onclick="cancelarDelecao()" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid white; padding: 12px 32px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; font-size: 14px;">
                Cancelar
            </button>
            <button id="btnConfirmarDelecao" onclick="confirmarDelecaoFoto()" style="background: #ff6b6b; color: white; border: none; padding: 12px 32px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s; font-size: 14px;">
                Deletar
            </button>
        </div>
    </div>
</div>

<style>
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
    
    #modalConfirmarDelecao[style*="flex"] {
        display: flex !important;
    }
</style>

<style>
    .celula-fixa-header {
        display: none;
        align-items: center;
        gap: 14px;
        padding: 16px 20px;
        margin-bottom: 15px;
        border-radius: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .celula-fixa-header .icon {
        font-size: 26px;
        line-height: 1;
    }

    .celula-fixa-header .nome {
        font-size: 17px;
        font-weight: 700;
    }

    .celula-fixa-header .sub {
        font-size: 12.5px;
        opacity: 0.85;
        margin-top: 2px;
    }

    .presenca-filters {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
        padding: 20px;
        background: #f5f5f5;
        border-radius: 8px;
    }

    .presenca-filters .form-item {
        margin: 0;
    }

    .presenca-filters label {
        display: block;
        font-weight: 500;
        margin-bottom: 5px;
        color: #333;
    }

    .presenca-filters input,
    .presenca-filters select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    /* Estilos para foto da reunião */
    .foto-reuniao-section {
        background: #f8f9fc;
        padding: 20px;
        border-radius: 12px;
        border: 2px dashed #4e73df;
    }

    .foto-reuniao-section h3 {
        margin-top: 0;
        color: #4e73df;
    }

    .foto-upload-horizontal {
        display: flex;
        gap: 20px;
        align-items: flex-start;
    }

    .foto-preview-reuniao {
        width: 300px;
        height: 200px;
        border-radius: 12px;
        overflow: hidden;
        border: 3px solid #4e73df;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .foto-preview-reuniao img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .foto-placeholder-reuniao {
        text-align: center;
        color: #999;
    }

    .foto-placeholder-reuniao span {
        font-size: 64px;
        display: block;
        margin-bottom: 10px;
    }

    .foto-placeholder-reuniao p {
        font-size: 14px;
        margin: 0;
    }

    .foto-upload-actions {
        flex: 1;
    }

    .galeria-reunioes {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }

    .reuniao-item {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.2s;
        cursor: pointer;
    }

    .reuniao-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .reuniao-item img {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }

    .reuniao-info {
        padding: 10px;
    }

    .reuniao-info .data {
        font-weight: bold;
        color: #4e73df;
        margin-bottom: 5px;
    }

    .reuniao-info .stats {
        font-size: 12px;
        color: #666;
    }

    .presenca-filters input,
    .presenca-filters select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .checkbox-group {
        margin-bottom: 20px;
        padding: 15px;
        background: #f5f5f5;
        border-radius: 4px;
    }

    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }

    .checkbox-item input[type="checkbox"] {
        width: 20px;
        height: 20px;
    }

    .btn-small {
        padding: 6px 12px;
        font-size: 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-registrar {
        background: #28a745;
        color: white;
    }

    .btn-registrar:hover:not(:disabled) {
        background: #218838;
    }

    .btn-registrar:disabled {
        background: #6c757d;
        cursor: not-allowed;
    }

    .btn-registrar.registrado {
        background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
        color: white;
        border: 2px solid #2e7d32;
        box-shadow: 0 0 10px rgba(76, 175, 80, 0.4);
        font-weight: 600;
        cursor: default !important;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .data-table input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #667eea;
        transform: scale(1.1);
    }

    .data-table td {
        text-align: center;
        vertical-align: middle;
        padding: 15px 10px;
    }

    .data-table td:first-child {
        text-align: left;
        font-weight: 500;
        color: #333;
    }

    .data-table td:nth-child(2) {
        color: #666;
        font-size: 14px;
    }

    .data-table tbody tr {
        transition: all 0.2s ease;
        border-bottom: 1px solid #f0f0f0;
    }

    .data-table tbody tr:hover {
        background: #f8f9ff;
        transform: scale(1.01);
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
    }

    .data-table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
        padding: 15px 10px;
        border: none;
    }

    .btn-registrar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        font-size: 13px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
    }

    .btn-registrar:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.4);
    }

    .btn-registrar:active {
        transform: translateY(0);
    }

    .btn-registrar:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-registrar.registrado {
        background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%) !important;
        border: 2px solid #2e7d32 !important;
        box-shadow: 0 0 12px rgba(76, 175, 80, 0.5) !important;
        font-weight: 700;
        opacity: 1 !important;
        transform: none !important;
        cursor: default !important;
    }

    .card {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        overflow: hidden;
    }

    .card h2 {
        color: #333;
        font-size: 20px;
        font-weight: 600;
    }

    /* Melhorar a aparência dos checkboxes */
    .data-table input[type="checkbox"]:checked {
        background: #28a745;
    }

    .data-table input[type="checkbox"]:not(:checked) {
        opacity: 0.5;
    }

    .data-table input[type="checkbox"]:hover {
        transform: scale(1.2);
    }

    /* Estilizar checkbox de ausente com cor vermelha */
    .check-ausente {
        accent-color: #dc3545 !important;
    }

    .check-ausente:checked {
        background: #dc3545;
    }

    .data-table thead th:nth-child(4) {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    }

    .relatorio-section {
        margin-bottom: 30px;
    }

    .relatorio-filters {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .relatorio-filters .form-item {
        margin: 0;
    }

    .relatorio-filters label {
        display: block;
        font-weight: 500;
        margin-bottom: 5px;
        color: #333;
        font-size: 14px;
    }

    .relatorio-filters select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .stat-card:nth-child(2) {
        background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    }

    .stat-card:nth-child(3) {
        background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
    }

    .stat-card:nth-child(4) {
        background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
    }

    .stat-icon {
        font-size: 36px;
        opacity: 0.9;
    }

    .stat-info {
        flex: 1;
    }

    .stat-label {
        font-size: 13px;
        opacity: 0.9;
        margin-bottom: 5px;
    }

    .stat-value {
        font-size: 28px;
        font-weight: bold;
    }

    .relatorio-tabela h3 {
        margin-bottom: 15px;
        color: #333;
    }

    .relatorio-membros h3 {
        margin-bottom: 20px;
        color: #333;
    }

    .membros-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }

    .membro-card-relatorio {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        transition: all 0.3s ease;
    }

    .membro-card-relatorio:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }

    .membro-card-relatorio .membro-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .membro-card-relatorio .membro-header h4 {
        margin: 0;
        font-size: 16px;
        color: #333;
    }

    .membro-card-relatorio .celula-badge {
        font-size: 11px;
        padding: 3px 8px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        font-weight: 500;
    }

    .membro-card-relatorio .membro-stats {
        display: flex;
        justify-content: space-around;
        margin-bottom: 10px;
    }

    .membro-card-relatorio .nome {
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
        font-size: 15px;
    }

    .membro-card-relatorio .stats {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-top: 10px;
    }

    .membro-card-relatorio .stat-item {
        text-align: center;
        flex: 1;
    }

    .membro-card-relatorio .stat-value {
        font-size: 20px;
        font-weight: bold;
        display: block;
    }

    .membro-card-relatorio .stat-label {
        font-size: 11px;
        color: #666;
        text-transform: uppercase;
        margin-top: 2px;
    }

    .membro-card-relatorio .taxa-bar {
        height: 6px;
        background: #e0e0e0;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 10px;
    }

    .membro-card-relatorio .taxa-fill {
        height: 100%;
        transition: width 0.5s ease;
    }

    .taxa-excelente { background: linear-gradient(90deg, #28a745, #20c997); }
    .taxa-bom { background: linear-gradient(90deg, #17a2b8, #0056b3); }
    .taxa-regular { background: linear-gradient(90deg, #ffc107, #ff9800); }
    .taxa-baixo { background: linear-gradient(90deg, #dc3545, #c82333); }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    .btn-success {
        background: #28a745;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
    }

    .btn-success:hover {
        background: #218838;
    }

    .btn-info {
        background: #17a2b8;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
    }

    .btn-info:hover {
        background: #138496;
    }

    .card-header-presenca {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
        border-bottom: 2px solid #e0e7ff;
        margin: -1px -1px 0 -1px;
    }

    .card-header-presenca h2 {
        margin: 0;
        color: #333;
        font-size: 22px;
        font-weight: 600;
    }

    .card-subtitle {
        margin: 5px 0 0 0;
        color: #666;
        font-size: 13px;
        font-weight: normal;
    }

    .legenda-presenca {
        display: flex;
        gap: 20px;
        align-items: center;
    }

    .legenda-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #666;
        font-weight: 500;
    }

    .dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
    }

    .dot-green {
        background: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
    }

    .dot-red {
        background: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.2);
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .presenca-table thead {
        background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
        color: white;
    }

    .presenca-table th {
        padding: 14px;
        text-align: left;
        font-weight: 600;
        font-size: 13px;
        letter-spacing: 0.5px;
    }

    .presenca-table td {
        padding: 14px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }

    .presenca-table tbody tr {
        transition: background 0.2s ease;
    }

    .presenca-table tbody tr:hover {
        background: #f8f9ff;
    }

    .presenca-status {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .status-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 12px;
        border: 2px solid #e0e0e0;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.2s ease;
        min-width: 95px;
    }

    .status-button.presente {
        border-color: #28a745;
        background: #f0f7f4;
        color: #28a745;
    }

    .status-button.presente:hover,
    .status-button.presente.active {
        background: #28a745;
        color: white;
    }

    .status-button.ausente {
        border-color: #dc3545;
        background: #fff5f5;
        color: #dc3545;
    }

    .status-button.ausente:hover,
    .status-button.ausente.active {
        background: #dc3545;
        color: white;
    }

    .btn-registrar {
        background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .btn-registrar:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
    }

    .btn-registrar:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .btn-registrar.registrado {
        background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%) !important;
        border: 2px solid #2e7d32 !important;
        box-shadow: 0 0 12px rgba(76, 175, 80, 0.5) !important;
        font-weight: 700;
        opacity: 1 !important;
        transform: none !important;
        cursor: default !important;
    }

    .visitante-inline {
        padding: 16px 20px;
        border-top: 1px solid #f0f0f0;
    }

    .salvar-reuniao-box {
        padding: 18px 20px 22px;
        border-top: 1px solid #f0f0f0;
        text-align: center;
    }

    .btn-salvar-reuniao {
        width: 100%;
        padding: 15px;
        font-size: 16px;
        font-weight: 700;
        color: white;
        background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
        border: none;
        border-radius: 10px;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(40, 167, 69, 0.35);
        transition: all 0.2s ease;
    }

    .btn-salvar-reuniao:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(40, 167, 69, 0.45);
    }

    .btn-salvar-reuniao:disabled {
        opacity: 0.7;
        cursor: wait;
        transform: none;
    }

    .salvar-reuniao-hint {
        margin: 10px 0 0;
        font-size: 12.5px;
        color: #888;
    }

    .btn-add-visitante {
        width: 100%;
        padding: 12px;
        border: 2px dashed #4e73df;
        background: #f8f9fc;
        color: #4e73df;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-add-visitante:hover {
        background: #eef1fd;
    }

    .add-visitante-form {
        display: flex;
        gap: 10px;
        margin-top: 10px;
        flex-wrap: wrap;
    }

    .add-visitante-form input {
        flex: 1;
        min-width: 160px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }

    .presenca-status-toggle {
        display: inline-flex;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #e0e0e0;
    }

    .toggle-btn {
        border: none;
        background: white;
        padding: 9px 16px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        color: #999;
    }

    .toggle-btn.presente-btn.is-active {
        background: #28a745;
        color: white;
    }

    .toggle-btn.ausente-btn.is-active {
        background: #dc3545;
        color: white;
    }

    .toggle-btn:disabled {
        cursor: wait;
        opacity: 0.7;
    }

    .row-visitante td {
        background: #fff9ec;
    }

    .badge-visitante {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        color: #b4611b;
        background: #faeadb;
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 6px;
        vertical-align: middle;
    }

    .pill-presente-fixo {
        display: inline-block;
        font-size: 12px;
        font-weight: 600;
        color: #28a745;
        background: #eaf7ee;
        border: 1px solid #cdeed7;
        padding: 6px 12px;
        border-radius: 20px;
        margin-right: 8px;
    }

    .btn-remover-visitante {
        border: none;
        background: transparent;
        color: #b3323f;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        padding: 6px 8px;
    }

    .btn-remover-visitante:hover {
        text-decoration: underline;
    }

    .btn-remover-visitante:disabled {
        opacity: 0.5;
        cursor: wait;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .presenca-filters {
            grid-template-columns: 1fr;
            gap: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .presenca-filters .form-item {
            margin: 0;
        }

        .presenca-filters input,
        .presenca-filters select {
            padding: 12px;
            font-size: 1rem;
            min-height: 44px;
        }

        .presenca-filters button {
            width: 100%;
            min-height: 44px;
            padding: 12px 10px;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .data-table {
            font-size: 0.85rem;
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

        .data-table input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin: 8px 0;
        }

        /* Lista de membros vira uma lista de cartões, não uma tabela espremida —
           é o que faz a tela parecer um app de celular, não uma planilha. */
        .presenca-table thead {
            display: none;
        }

        .presenca-table,
        .presenca-table tbody {
            display: block;
            width: 100%;
        }

        .presenca-table tr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: white;
            border-radius: 14px;
            margin-bottom: 10px;
            padding: 14px 16px;
            box-shadow: 0 1px 3px rgba(20, 20, 43, 0.08);
        }

        .presenca-table tr:last-child {
            margin-bottom: 0;
        }

        .presenca-table td {
            padding: 0;
            border: none;
            min-height: 0;
            font-size: 0.95rem;
        }

        .presenca-table td:first-child {
            flex: 1;
            min-width: 0;
            text-align: left;
        }

        .presenca-table td:first-child strong {
            display: block;
            overflow-wrap: break-word;
        }

        /* Linha de "selecione uma célula" / vazio / erro não deve virar cartão */
        .presenca-table tr:has(td[colspan]) {
            display: block;
            box-shadow: none;
            background: transparent;
            padding: 0;
        }

        .presenca-status-toggle {
            width: 100%;
            max-width: 220px;
        }

        .toggle-btn {
            flex: 1;
            padding: 11px 8px;
            font-size: 12.5px;
        }

        .relatorio-filters {
            grid-template-columns: 1fr;
            gap: 10px;
            padding: 12px;
        }

        .relatorio-filters select {
            min-height: 44px;
            padding: 10px;
            font-size: 1rem;
        }

        .stats-grid {
            grid-template-columns: 1fr;
            gap: 15px;
            margin: 15px 0;
        }

        .stat-card {
            padding: 15px;
        }

        .stat-icon {
            font-size: 28px;
        }

        .foto-upload-horizontal {
            flex-direction: column;
            gap: 15px;
        }

        /* Botão de salvar fixo no rodapé, como a barra de ação de um app nativo.
           Sem transform no hover aqui: um ancestral com transform vira o
           "containing block" de um filho position:fixed, o que quebraria a
           barra fixa no rodapé da tela. */
        #membrosCard {
            padding-bottom: 92px;
        }

        #membrosCard:hover {
            transform: none;
        }

        .salvar-reuniao-box {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 60;
            background: white;
            box-shadow: 0 -4px 16px rgba(20, 20, 43, 0.12);
            padding: 12px 16px calc(12px + env(safe-area-inset-bottom));
            border-top: 1px solid #eee;
        }

        .salvar-reuniao-hint {
            display: none;
        }

        .foto-preview-reuniao {
            width: 100%;
            height: 200px;
            max-width: 300px;
        }

        .galeria-reunioes {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .checkbox-item {
            gap: 8px;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .btn-small {
            padding: 8px 10px;
            font-size: 0.75rem;
            min-height: 40px;
        }

        .legenda-presenca {
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
        }

        .legenda-item {
            font-size: 12px;
        }

        .membro-card-relatorio {
            padding: 12px;
        }

        .membro-card-relatorio .membro-stats {
            gap: 5px;
        }

        .membro-card-relatorio .stat-item {
            font-size: 11px;
        }

        .status-button {
            padding: 8px 12px;
            font-size: 0.8rem;
            min-height: 40px;
        }

        .card-header-presenca {
            padding: 15px;
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .card-header-presenca h2 {
            font-size: 18px;
        }

        .btn-success,
        .btn-secondary,
        .btn-info {
            width: auto;
            padding: 10px 15px;
            min-height: 40px;
            font-size: 0.9rem;
        }
    }

    /* Tablet */
    @media (min-width: 769px) and (max-width: 1023px) {
        .presenca-filters {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .presenca-filters button {
            min-height: 44px;
        }

        .relatorio-filters {
            grid-template-columns: 1fr 1fr auto;
            gap: 12px;
        }

        .data-table th,
        .data-table td {
            padding: 12px 8px;
            font-size: 0.9rem;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .galeria-reunioes {
            grid-template-columns: repeat(3, 1fr);
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

    // Contexto do usuário
    const userRole = '<?php echo htmlspecialchars($user_funcao); ?>';
    const userId = <?php echo intval($user_id); ?>;
    const isLider = <?php echo $eh_lider ? 'true' : 'false'; ?>;
    const celulaLider = <?php echo !empty($celulas_lider) ? intval($celulas_lider[0] ?? 0) : 0; ?>;

    // Carregar células
    function carregarCelulas() {
        let url = 'api/index.php?acao=listar_celulas';
        
        // Se é líder, filtrar apenas suas células
        if (isLider) {
            url += '&only_my_cells=1&user_id=' + userId;
        }
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'sucesso') {
                    let html = '<option value="">Selecione uma célula</option>';
                    data.dados.forEach(celula => {
                        html += `<option value="${celula.id}">${celula.nome} - ${celula.lider_nome || 'Sem líder'}</option>`;
                    });

                    const filterCelula = document.getElementById('filterCelula');
                    filterCelula.innerHTML = html;

                    // Líder com uma única célula: pula a seleção, a tela já abre na célula dele
                    if (isLider && celulaLider > 0 && data.dados.length === 1) {
                        const celula = data.dados[0];
                        filterCelula.value = celulaLider;

                        document.getElementById('celulaFilterItem').style.display = 'none';
                        const header = document.getElementById('celulaFixaHeader');
                        document.getElementById('celulaFixaNome').textContent = celula.nome;
                        header.querySelector('.sub').textContent = celula.lider_nome ? `Líder: ${celula.lider_nome}` : 'Sua célula';
                        header.style.display = 'flex';

                        carregarMembrosCelula(celulaLider);
                    }
                } else {
                    console.error('Erro ao carregar células:', data.mensagem);
                    document.getElementById('filterCelula').innerHTML = '<option value="">Não foi possível carregar as células</option>';
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                document.getElementById('filterCelula').innerHTML = '<option value="">Erro de conexão — tente recarregar a página</option>';
            });
    }

    // Renderiza uma linha de membro com toggle único Presente/Faltou.
    // Visitantes não usam o toggle: já entram como presentes no registro de visita
    // (a tabela "visitantes" não tem vínculo de membro para lançar em "presencas"),
    // então recebem apenas a opção de remover um lançamento feito por engano.
    //
    // Nada aqui grava no servidor na hora do toque: os toques só mudam o estado
    // visual (is-active) e ficam pendentes até o botão único "Salvar reunião de
    // hoje", que envia tudo de uma vez (ver salvarReuniao()).
    // Iniciais do avatar: mesma regra usada no menu (primeira letra dos dois
    // primeiros nomes, ou as duas primeiras letras se for um nome só).
    function iniciaisNome(nome) {
        const partes = (nome || '').trim().split(/\s+/);
        if (partes.length >= 2) {
            return (partes[0][0] + partes[1][0]).toUpperCase();
        }
        return (nome || '').slice(0, 2).toUpperCase();
    }

    function renderLinhaMembro(membro, celulaId, opts = {}) {
        const isVisitante = !!opts.visitante;
        const pendente = !!opts.pendente; // visitante ainda não salvo no servidor
        const presenteInicial = opts.presenteInicial !== false; // padrão: presente
        const iniciais = iniciaisNome(membro.nome);

        if (isVisitante) {
            const idAttr = pendente ? `data-pending-id="${membro.id}"` : `data-visitante-id="${membro.id}"`;
            return `<tr class="row-visitante" ${idAttr}>
                <td><span class="avatar-membro">${iniciais}</span><strong>${membro.nome}</strong><span class="badge-visitante">${pendente ? 'Visitante (não salvo)' : 'Visitante'}</span></td>
                <td style="text-align:center;">
                    <span class="pill-presente-fixo">✓ Presente</span>
                    <button type="button" class="btn-remover-visitante" ${idAttr} title="Remover lançamento">✕ Remover</button>
                </td>
            </tr>`;
        }

        return `<tr data-membro="${membro.id}">
            <td><span class="avatar-membro">${iniciais}</span><strong>${membro.nome}</strong></td>
            <td style="text-align:center;">
                <div class="presenca-status-toggle" data-membro="${membro.id}" data-celula="${celulaId}">
                    <button type="button" class="toggle-btn presente-btn ${presenteInicial ? 'is-active' : ''}" data-presente="1">✓ Presente</button>
                    <button type="button" class="toggle-btn ausente-btn ${!presenteInicial ? 'is-active' : ''}" data-presente="0">✗ Faltou</button>
                </div>
            </td>
        </tr>`;
    }

    // Visitante já salvo no servidor: remover chama a API na hora (não há "pendência" a perder).
    function ativarRemocaoVisitante() {
        document.querySelectorAll('.btn-remover-visitante[data-visitante-id]').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Remover este visitante do lançamento de hoje?')) return;
                const visitanteId = this.dataset.visitanteId;
                const linha = this.closest('tr');
                this.disabled = true;

                fetch('api/index.php?acao=deletar_visitante', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${visitanteId}`
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'sucesso') {
                        linha.remove();
                    } else {
                        showNotification('❌ ' + result.mensagem, 'error');
                        this.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Erro ao remover visitante:', error);
                    showNotification('❌ Falha ao remover. Verifique a conexão.', 'error');
                    this.disabled = false;
                });
            });
        });
    }

    // Visitante ainda pendente (adicionado nesta sessão, não enviado ao servidor):
    // remover é só local, sem chamada de API.
    function ativarRemocaoVisitantePendente() {
        document.querySelectorAll('.btn-remover-visitante[data-pending-id]').forEach(btn => {
            btn.addEventListener('click', function() {
                const pendingId = this.dataset.pendingId;
                visitantesPendentes = visitantesPendentes.filter(v => v.id !== pendingId);
                this.closest('tr').remove();
            });
        });
    }

    // Toque no toggle só atualiza o visual — o envio real acontece todo junto em salvarReuniao()
    function registrarToggle(toggleEl, presente) {
        toggleEl.querySelector('.presente-btn').classList.toggle('is-active', presente);
        toggleEl.querySelector('.ausente-btn').classList.toggle('is-active', !presente);
    }

    function ativarTogglesPresenca() {
        document.querySelectorAll('.presenca-status-toggle .toggle-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const toggleEl = this.closest('.presenca-status-toggle');
                const presente = this.dataset.presente === '1';
                registrarToggle(toggleEl, presente);
            });
        });
    }

    // Visitantes adicionados nesta sessão que ainda não foram enviados ao servidor.
    // São enviados junto com o resto no botão único "Salvar reunião de hoje".
    let visitantesPendentes = [];

    // Carregar membros da célula
    function carregarMembrosCelula(celulaId) {
        const fotoSection = document.getElementById('fotoReuniaoSection');
        const visitanteBox = document.getElementById('visitanteInlineBox');
        const salvarBox = document.getElementById('salvarReuniaoBox');
        visitantesPendentes = [];
        resetFotoReuniao();

        if (!celulaId) {
            document.getElementById('presencaList').innerHTML = '<tr><td colspan="2" style="text-align: center; padding: 30px;">Selecione uma célula</td></tr>';
            fotoSection.style.display = 'none';
            visitanteBox.style.display = 'none';
            salvarBox.style.display = 'none';
            return;
        }

        // Se é líder, validar se tem permissão para acessar essa célula
        if (isLider && celulaLider > 0 && celulaId != celulaLider) {
            alert('Você não tem permissão para acessar esta célula.');
            return;
        }

        const dataSelecionada = document.getElementById('filterData').value;

        Promise.all([
            fetch(`api/index.php?acao=listar_membros&celula_id=${celulaId}`).then(r => r.json()),
            fetch(`api/index.php?acao=listar_visitantes&celula_id=${celulaId}`).then(r => r.json())
        ])
            .then(([dataMembros, dataVisitantes]) => {
                const membros = (dataMembros.status === 'sucesso' && dataMembros.dados) ? dataMembros.dados : [];
                const visitantesDoDia = (dataVisitantes.status === 'sucesso' && dataVisitantes.dados)
                    ? dataVisitantes.dados.filter(v => v.data_visita === dataSelecionada)
                    : [];

                let html = '';
                if (membros.length === 0 && visitantesDoDia.length === 0) {
                    html = '<tr><td colspan="2" style="text-align: center; padding: 40px; color: #999;">Nenhum membro cadastrado nesta célula</td></tr>';
                } else {
                    membros.forEach(membro => {
                        html += renderLinhaMembro(membro, celulaId);
                    });
                    visitantesDoDia.forEach(visitante => {
                        html += renderLinhaMembro(visitante, celulaId, { visitante: true });
                    });
                }

                document.getElementById('presencaList').innerHTML = html;
                ativarTogglesPresenca();
                ativarRemocaoVisitante();

                fotoSection.style.display = 'block';
                visitanteBox.style.display = 'block';
                salvarBox.style.display = 'block';
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                document.getElementById('presencaList').innerHTML = '<tr><td colspan="2" style="text-align: center; padding: 30px; color: #dc3545;">❌ Não foi possível carregar os membros. Verifique a conexão e tente novamente.</td></tr>';
                fotoSection.style.display = 'none';
                visitanteBox.style.display = 'none';
                salvarBox.style.display = 'none';
            });
    }

    // ---------- Visitante inline ----------
    function initVisitanteInline() {
        const btnAbrir = document.getElementById('btnAbrirAddVisitante');
        const form = document.getElementById('addVisitanteForm');
        const input = document.getElementById('inputNomeVisitante');
        const btnConfirmar = document.getElementById('btnConfirmarVisitante');
        const btnCancelar = document.getElementById('btnCancelarVisitante');

        btnAbrir.addEventListener('click', () => {
            btnAbrir.style.display = 'none';
            form.style.display = 'flex';
            input.focus();
        });

        function fecharForm() {
            form.style.display = 'none';
            btnAbrir.style.display = 'block';
            input.value = '';
        }

        btnCancelar.addEventListener('click', fecharForm);

        // Só guarda localmente — o envio real (criar_visitante) acontece em salvarReuniao()
        function confirmarVisitante() {
            const nome = input.value.trim();
            const celulaId = document.getElementById('filterCelula').value;

            if (!nome) {
                showNotification('❌ Informe o nome do visitante', 'error');
                return;
            }
            if (!celulaId) {
                showNotification('❌ Selecione uma célula primeiro', 'error');
                return;
            }

            const linhaVazia = document.querySelector('#presencaList td[colspan]');
            if (linhaVazia) linhaVazia.closest('tr').remove();

            const pendingId = 'pendente-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7);
            visitantesPendentes.push({ id: pendingId, nome });

            const linhaHtml = renderLinhaMembro({ id: pendingId, nome }, celulaId, { visitante: true, pendente: true });
            document.getElementById('presencaList').insertAdjacentHTML('beforeend', linhaHtml);
            ativarRemocaoVisitantePendente();

            fecharForm();
        }

        btnConfirmar.addEventListener('click', confirmarVisitante);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') confirmarVisitante();
        });
    }

    // ---------- Foto da reunião ----------
    let arquivoFotoReuniao = null;

    // Limpa a foto escolhida ao trocar de célula/data, para não anexar a foto errada na reunião
    function resetFotoReuniao() {
        arquivoFotoReuniao = null;
        const fotoInput = document.getElementById('foto_reuniao');
        if (fotoInput) fotoInput.value = '';
        const preview = document.getElementById('fotoPreviewReuniao');
        if (preview) {
            preview.innerHTML = `
                <div class="foto-placeholder-reuniao">
                    <span>📷</span>
                    <p>Toque para fotografar a célula</p>
                </div>
            `;
        }
        const observacoes = document.getElementById('observacoes_reuniao');
        if (observacoes) observacoes.value = '';
    }

    function initFotoReuniao() {
        document.getElementById('foto_reuniao').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (!file.type.match('image/jpeg') && !file.type.match('image/png') && !file.type.match('image/gif')) {
                showNotification('❌ Apenas JPG, PNG ou GIF são permitidas!', 'error');
                this.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showNotification('❌ A foto deve ter no máximo 5MB', 'error');
                this.value = '';
                return;
            }

            arquivoFotoReuniao = file;
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('fotoPreviewReuniao').innerHTML = `<img src="${event.target.result}" alt="Foto da célula">`;
            };
            reader.readAsDataURL(file);
        });
    }

    function obterCsrfToken() {
        let csrfToken = localStorage.getItem('csrf_token') || '';
        if (!csrfToken) {
            const inputToken = document.querySelector('input[name="csrf_token"]');
            if (inputToken) csrfToken = inputToken.value;
        }
        return csrfToken;
    }

    // ---------- Salvar reunião de hoje (presença + visitantes + foto, tudo de uma vez) ----------
    async function salvarReuniao() {
        const celulaId = document.getElementById('filterCelula').value;
        const data = document.getElementById('filterData').value;
        const btn = document.getElementById('btnSalvarReuniao');
        const csrfToken = obterCsrfToken();

        if (!celulaId) {
            showNotification('❌ Selecione uma célula primeiro!', 'error');
            return;
        }

        btn.disabled = true;
        btn.textContent = '📤 Salvando...';

        let presentes = 0;
        let ausentes = 0;
        let falhasPresenca = 0;

        // 1) Presença de cada membro (toggles pendentes)
        const toggles = document.querySelectorAll('.presenca-status-toggle');
        for (const toggleEl of toggles) {
            const membroId = toggleEl.dataset.membro;
            const presente = toggleEl.querySelector('.presente-btn').classList.contains('is-active');

            try {
                const response = await fetch('api/index.php?acao=registrar_presenca', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `membro_id=${membroId}&celula_id=${celulaId}&data_presenca=${data}&presente=${presente ? 1 : 0}`
                });
                const result = await response.json();
                if (result.status === 'sucesso') {
                    presente ? presentes++ : ausentes++;
                } else {
                    falhasPresenca++;
                }
            } catch (error) {
                console.error('Erro ao registrar presença:', error);
                falhasPresenca++;
            }
        }

        // 2) Visitantes adicionados nesta sessão
        let visitantesSalvos = 0;
        let falhasVisitante = 0;
        const pendentesDesteEnvio = [...visitantesPendentes];

        for (const pendente of pendentesDesteEnvio) {
            try {
                const response = await fetch('api/index.php?acao=criar_visitante', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `nome=${encodeURIComponent(pendente.nome)}&celula_id=${celulaId}&data_visita=${data}&status=primeira_visita`
                });
                const result = await response.json();
                if (result.status === 'sucesso') {
                    visitantesSalvos++;
                    visitantesPendentes = visitantesPendentes.filter(v => v.id !== pendente.id);

                    // Troca a linha "pendente" por uma linha real, removível pela API a partir de agora
                    const linhaAntiga = document.querySelector(`[data-pending-id="${pendente.id}"]`)?.closest('tr');
                    const visitanteId = result.dados && result.dados.visitante_id;
                    if (linhaAntiga && visitanteId) {
                        linhaAntiga.outerHTML = renderLinhaMembro({ id: visitanteId, nome: pendente.nome }, celulaId, { visitante: true });
                    }
                } else {
                    falhasVisitante++;
                }
            } catch (error) {
                console.error('Erro ao salvar visitante:', error);
                falhasVisitante++;
            }
        }
        ativarRemocaoVisitante();

        // 3) Foto da reunião + observações (também atualiza os totais da reunião no servidor)
        let fotoSalva = false;
        let falhaFoto = false;
        const observacoes = document.getElementById('observacoes_reuniao').value;

        try {
            const formData = new FormData();
            if (arquivoFotoReuniao) formData.append('foto', arquivoFotoReuniao);
            formData.append('celula_id', celulaId);
            formData.append('data_reuniao', data);
            formData.append('observacoes', observacoes);
            if (csrfToken) formData.append('csrf_token', csrfToken);

            const response = await fetch('api/index.php?acao=criar_reuniao_com_foto', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            const result = await response.json();
            if (result.status === 'sucesso') {
                fotoSalva = !!arquivoFotoReuniao;
            } else {
                falhaFoto = true;
                console.error('Erro ao salvar reunião/foto:', result.mensagem);
            }
        } catch (error) {
            console.error('Erro ao salvar reunião/foto:', error);
            falhaFoto = true;
        }

        // 4) Um único resumo final, em vez de uma notificação por item
        const partes = [`${presentes} presente(s)`, `${ausentes} falta(s)`];
        if (visitantesSalvos > 0) partes.push(`${visitantesSalvos} visitante(s)`);
        if (fotoSalva) partes.push('foto enviada');

        if (falhasPresenca === 0 && falhasVisitante === 0 && !falhaFoto) {
            showNotification(`✅ Reunião salva: ${partes.join(', ')}`, 'success');
            btn.textContent = '✓ Reunião salva';
            setTimeout(() => {
                btn.disabled = false;
                btn.textContent = '💾 Salvar reunião de hoje';
            }, 2000);
        } else {
            const problemas = [];
            if (falhasPresenca > 0) problemas.push(`${falhasPresenca} presença(s) não salva(s)`);
            if (falhasVisitante > 0) problemas.push(`${falhasVisitante} visitante(s) não salvo(s)`);
            if (falhaFoto) problemas.push('foto/observações não salvas');
            showNotification(`⚠️ Salvo parcialmente (${partes.join(', ')}) — ${problemas.join(', ')}. Tente novamente.`, 'warning');
            btn.disabled = false;
            btn.textContent = '💾 Salvar reunião de hoje';
        }
    }

    // Função para mostrar notificações
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        `;
        
        if (type === 'success') notification.style.background = '#28a745';
        else if (type === 'error') notification.style.background = '#dc3545';
        else if (type === 'warning') notification.style.background = '#ff9800';
        else notification.style.background = '#17a2b8';
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        carregarCelulas();
        initFotoReuniao();
        initVisitanteInline();

        document.getElementById('filterCelula').addEventListener('change', function() {
            carregarMembrosCelula(this.value);
        });

        document.getElementById('filterData').addEventListener('change', function() {
            const celulaId = document.getElementById('filterCelula').value;
            if (celulaId) carregarMembrosCelula(celulaId);
        });

        document.getElementById('btnSalvarReuniao').addEventListener('click', salvarReuniao);

        // Botões do relatório
        document.getElementById('btnVerRelatorio').addEventListener('click', function() {
            document.getElementById('relatorioSection').style.display = 'block';
            document.getElementById('membrosCard').style.display = 'none';
            carregarRelatorio();
        });

        document.getElementById('btnFecharRelatorio').addEventListener('click', function() {
            document.getElementById('relatorioSection').style.display = 'none';
            document.getElementById('membrosCard').style.display = 'block';
        });

        document.getElementById('btnAtualizarRelatorio').addEventListener('click', function() {
            carregarRelatorio();
        });

        // Exportar presença para Excel
        document.getElementById('btnExportarExcel').addEventListener('click', async function() {
            const celulaId = document.getElementById('filterCelula').value;
            const data = document.getElementById('filterData').value;

            if (!celulaId) {
                mostrarNotificacao('Selecione uma célula para exportar', 'erro');
                return;
            }

            // Chamar API para exportar
            try {
                const resposta = await fetch('/api/index.php?acao=exportar_presenca_excel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        celula_id: celulaId,
                        data: data
                    })
                });

                if (resposta.ok) {
                    const blob = await resposta.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `presenca_${celulaId}_${data}.xlsx`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    mostrarNotificacao('Arquivo exportado com sucesso!', 'sucesso');
                } else {
                    mostrarNotificacao('Erro ao exportar arquivo', 'erro');
                }
            } catch (erro) {
                console.error('Erro:', erro);
                mostrarNotificacao('Erro ao exportar arquivo', 'erro');
            }
        });

        document.getElementById('btnFecharRelatorio').addEventListener('click', function() {
            document.getElementById('relatorioSection').style.display = 'none';
            document.getElementById('membrosCard').style.display = 'block';
        });
        setTimeout(() => {
            const filterCelula = document.getElementById('filterCelula');
            const relatorioCelula = document.getElementById('relatorioCelula');
            
            let opcoes = [];
            
            // Se é líder, mostrar apenas sua célula
            if (isLider && celulaLider > 0) {
                // Buscar apenas a célula do líder
                const optionsArray = Array.from(filterCelula.options).filter(opt => opt.value == celulaLider);
                opcoes = optionsArray.map(opt => 
                    `<option value="${opt.value}" selected>${opt.text}</option>`
                ).join('');
                
                // Desabilitar o select para líderes
                relatorioCelula.disabled = true;
            } else {
                // Para admin/pastor/supervisor, mostrar todas as opções
                opcoes = '<option value="">Todas as células</option>' + 
                    Array.from(filterCelula.options).filter(opt => opt.value).map(opt => 
                        `<option value="${opt.value}">${opt.text}</option>`
                    ).join('');
            }
            
            relatorioCelula.innerHTML = opcoes;
        }, 1000);
    });

    // Carregar relatório de presença
    async function carregarRelatorio() {
        const periodo = document.getElementById('relatorioPeriodo').value;
        let celulaId = document.getElementById('relatorioCelula').value;
        
        // Se é líder, forçar a usar apenas sua célula
        if (isLider && celulaLider > 0) {
            celulaId = celulaLider;
            document.getElementById('relatorioCelula').value = celulaLider;
        }
        
        // Calcular data de início baseada no período
        const dataFim = new Date();
        const dataInicio = new Date();
        dataInicio.setDate(dataInicio.getDate() - parseInt(periodo));
        
        const dataInicioStr = dataInicio.toISOString().split('T')[0];
        const dataFimStr = dataFim.toISOString().split('T')[0];
        
        try {
            // Buscar estatísticas da API
            let url = `api/index.php?acao=relatorio_presenca&data_inicio=${dataInicioStr}&data_fim=${dataFimStr}`;
            if (celulaId) {
                url += `&celula_id=${celulaId}`;
            }
            
            // Se é líder, passar o identificador
            if (isLider) {
                url += `&only_my_cells=1&user_id=${userId}`;
            }
            
            const response = await fetch(url);
            const resultado = await response.json();

            if (resultado.status === 'sucesso' && resultado.dados) {
                atualizarRelatorio(resultado.dados);
            } else {
                mostrarErroRelatorio(resultado.mensagem || 'Não foi possível carregar o relatório.');
            }
        } catch (error) {
            console.error('Erro ao carregar relatório:', error);
            mostrarErroRelatorio('Falha de conexão ao carregar o relatório. Tente novamente.');
        }
    }

    // Mostra um estado de erro claro no relatório, em vez de números inventados
    function mostrarErroRelatorio(mensagem) {
        document.getElementById('statTaxaPresenca').textContent = '--%';
        document.getElementById('statTotalPresencas').textContent = '--';
        document.getElementById('statTotalAusencias').textContent = '--';
        document.getElementById('relatorioTableBody').innerHTML =
            `<tr><td colspan="3" style="text-align: center; padding: 30px; color: #dc3545;">❌ ${mensagem}</td></tr>`;
        document.getElementById('relatorioMembrosGrid').innerHTML = '';
        document.querySelector('.relatorio-membros').style.display = 'none';
        showNotification('❌ ' + mensagem, 'error');
    }

    function atualizarRelatorio(dados) {
        // Atualizar cards de estatísticas
        document.getElementById('statTaxaPresenca').textContent = dados.totais.taxa_presenca + '%';
        document.getElementById('statTotalPresencas').textContent = dados.totais.total_presencas;
        document.getElementById('statTotalAusencias').textContent = dados.totais.total_ausencias;
        
        // Atualizar tabela com lançamentos de presença individuais da última semana
        let htmlTabela = '';
        let registrosPorData = [];
        
        // Coletar todos os registros e ordenar por data
        if (dados.celulas && dados.celulas.length > 0) {
            dados.celulas.forEach(celula => {
                if (celula.registros_presenca && celula.registros_presenca.length > 0) {
                    celula.registros_presenca.forEach(registro => {
                        registrosPorData.push({
                            membro_nome: registro.membro_nome,
                            data_presenca: registro.data_presenca,
                            presente: registro.presente
                        });
                    });
                }
            });
        }
        
        // Ordenar por data (mais recentes primeiro)
        registrosPorData.sort((a, b) => {
            return new Date(b.data_presenca) - new Date(a.data_presenca);
        });
        
        // Gerar tabela com registros ordenados
        if (registrosPorData.length > 0) {
            registrosPorData.forEach(registro => {
                const statusText = registro.presente == 1 ? 'Presente' : 'Ausente';
                const statusColor = registro.presente == 1 ? '#28a745' : '#dc3545';
                const statusIcon = registro.presente == 1 ? '✓' : '✗';
                const dataFormatada = formatarDataLocal(registro.data_presenca);
                
                htmlTabela += `
                    <tr>
                        <td><strong>${registro.membro_nome}</strong></td>
                        <td>${dataFormatada}</td>
                        <td style="text-align: center;"><span style="color: ${statusColor}; font-weight: bold; font-size: 14px;">${statusIcon} ${statusText}</span></td>
                    </tr>
                `;
            });
        } else {
            htmlTabela = '<tr><td colspan="3" style="text-align: center; padding: 30px; color: #999;">Nenhum lançamento de presença no período</td></tr>';
        }
        
        document.getElementById('relatorioTableBody').innerHTML = htmlTabela;
        
        // Manter a seção de membros (Desempenho por Membro)
        let htmlMembros = '';
        if (dados.celulas && dados.celulas.length > 0) {
            dados.celulas.forEach(celula => {
                // Gerar cards de membros individuais com resumo
                if (celula.membros && celula.membros.length > 0) {
                    celula.membros.forEach(membro => {
                        const totalMembro = membro.presencas + membro.ausencias;
                        const taxaMembro = totalMembro > 0 ? ((membro.presencas / totalMembro) * 100).toFixed(1) : 0;
                        
                        let classeGradiente = 'taxa-baixo';
                        if (taxaMembro >= 90) classeGradiente = 'taxa-excelente';
                        else if (taxaMembro >= 70) classeGradiente = 'taxa-bom';
                        else if (taxaMembro >= 50) classeGradiente = 'taxa-regular';
                        
                        htmlMembros += `
                            <div class="membro-card-relatorio">
                                <div class="membro-header">
                                    <h4>${membro.nome}</h4>
                                    <span class="celula-badge">${celula.nome}</span>
                                </div>
                                <div class="membro-stats">
                                    <div class="stat-item">
                                        <div class="stat-value">${membro.presencas}</div>
                                        <div class="stat-label">Presenças</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value">${membro.ausencias}</div>
                                        <div class="stat-label">Faltas</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value">${taxaMembro}%</div>
                                        <div class="stat-label">Taxa</div>
                                    </div>
                                </div>
                                <div class="taxa-bar">
                                    <div class="taxa-fill ${classeGradiente}" style="width: ${taxaMembro}%"></div>
                                </div>
                            </div>
                        `;
                    });
                }
            });
        }
        
        // Atualizar seção de membros
        if (htmlMembros) {
            document.getElementById('relatorioMembrosGrid').innerHTML = htmlMembros;
            document.querySelector('.relatorio-membros').style.display = 'block';
        } else {
            document.querySelector('.relatorio-membros').style.display = 'none';
        }
    }
</script>
