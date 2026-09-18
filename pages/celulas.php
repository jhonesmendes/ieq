<div class="page-header">
    <h1>Células</h1>
    <p>Gerencie as células da sua igreja</p>
</div>

<?php if (is_lider_exclusivo()): ?>
<div class="info-box">
    <div class="info-box-icon">📌</div>
    <div class="info-box-content">
        <div class="info-box-title">Acesso Restrito - Líder de Célula</div>
        <div class="info-box-text">Você pode visualizar e editar apenas informações da sua própria célula.</div>
    </div>
</div>
<?php endif; ?>

<div style="margin-bottom: 20px;">
    <button class="btn btn-primary" id="btnNovaCelula">+ Nova Célula</button>
</div>

<div class="celulas-list" id="celulasList">
    <div style="text-align: center; padding: 30px; color: #999;">Carregando células...</div>
</div>

<!-- Modal para nova/editar célula -->
<div id="celulaModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Nova Célula</h2>
        
        <form id="formCelula" class="form-group">
            <div class="form-row">
                <div class="form-item">
                    <label>Nome da Célula</label>
                    <input type="text" id="celula_nome" required>
                </div>
                <div class="form-item">
                    <label>Igreja</label>
                    <select id="celula_igreja_id">
                        <option value="">Selecione uma igreja</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Dia da Semana</label>
                    <select id="celula_dia_semana">
                        <option>Segunda</option>
                        <option>Terça</option>
                        <option>Quarta</option>
                        <option>Quinta</option>
                        <option>Sexta</option>
                        <option>Sábado</option>
                        <option>Domingo</option>
                    </select>
                </div>
                <div class="form-item">
                    <label>Horário</label>
                    <input type="time" id="celula_hora">
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Endereço</label>
                    <input type="text" id="celula_endereco">
                </div>
                <div class="form-item">
                    <label>Bairro</label>
                    <input type="text" id="celula_bairro">
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Cidade</label>
                    <input type="text" id="celula_cidade">
                </div>
                <div class="form-item">
                    <label>Líder</label>
                    <select id="celula_lider_id">
                        <option value="">Selecione um líder</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Líder (Segundo Líder)</label>
                    <select id="celula_lider_id_2">
                        <option value="">Selecione um líder (opcional)</option>
                    </select>
                </div>
                <div class="form-item">
                    <label>Líder em Treinamento</label>
                    <select id="celula_lider_treinamento_id">
                        <option value="">Selecione um líder em treinamento</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Salvar Célula</button>
        </form>
    </div>
</div>

<style>
    .celulas-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }

    .celula-card {
        display: flex;
        flex-direction: column;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s;
    }

    .celula-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }

    .celula-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .celula-header h3 {
        margin: 0;
        color: #333;
        font-size: 18px;
    }

    .celula-Igreja {
        background: #667eea;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .celula-info {
        flex: 1;
        color: #666;
        font-size: 14px;
        margin: 6px 0;
    }

    .celula-info p {
        margin: 8px 0;
    }

    .celula-actions {
        margin-top: 15px;
        display: flex;
        gap: 10px;
    }

    .btn-small {
        flex: 1;
        padding: 8px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s;
        background: #f0f0f0;
        color: #333;
    }

    .btn-small:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }

    .btn-danger {
        background: #fff5f5;
        color: #f44336;
    }

    .btn-danger:hover {
        background: #f44336;
        color: white;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .celulas-list {
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .celula-card {
            padding: 15px;
        }

        .celula-header h3 {
            font-size: 16px;
        }

        .celula-info {
            font-size: 13px;
        }

        .celula-actions {
            flex-direction: column;
            gap: 8px;
        }

        .btn-small {
            min-height: 44px;
            padding: 10px 12px;
        }
    }

    /* Tablet */
    @media (min-width: 769px) and (max-width: 1023px) {
        .celulas-list {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
    }
</style>

<script>
    const celulaModal = document.getElementById('celulaModal');
    const btnNovaCelula = document.getElementById('btnNovaCelula');
    const closeBtn = document.querySelector('.close');
    let editandoId = null;

    <?php if (is_supervisor()): ?>
    btnNovaCelula.onclick = function() {
        editandoId = null;
        document.getElementById('formCelula').reset();
        celulaModal.querySelector('h2').textContent = 'Nova Célula';
        celulaModal.style.display = 'block';
    }
    <?php endif; ?>

    closeBtn.onclick = function() {
        celulaModal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == celulaModal) {
            celulaModal.style.display = 'none';
        }
    }

    // Carregar igrejas no select
    async function carregarIgrejas() {
        const resultado = await fazerRequisicao('listar_igrejas', 'GET');
        if (resultado.status === 'sucesso') {
            let html = '<option value="">Selecione uma igreja</option>';
            resultado.dados.forEach(igreja => {
                html += `<option value="${igreja.id}">${igreja.nome}</option>`;
            });
            document.getElementById('celula_igreja_id').innerHTML = html;
        }
    }

    // Carregar líderes no select
    async function carregarLideres() {
        try {
            const resultado = await fazerRequisicao('listar_lideres', 'GET');
            console.log('Resultado listar_lideres:', resultado);
            
            if (resultado && resultado.status === 'sucesso') {
                let html = '<option value="">Selecione um líder</option>';
                
                if (resultado.dados && Array.isArray(resultado.dados)) {
                    if (resultado.dados.length === 0) {
                        html += '<option disabled>⚠️ Nenhum líder cadastrado</option>';
                        console.warn('Nenhum líder encontrado');
                    } else {
                        resultado.dados.forEach(lider => {
                            html += `<option value="${lider.id}">${lider.nome}</option>`;
                        });
                    }
                } else {
                    html += '<option disabled>⚠️ Erro ao carregar líderes</option>';
                    console.error('Dados de líderes inválidos:', resultado.dados);
                }
                
                document.getElementById('celula_lider_id').innerHTML = html;
                document.getElementById('celula_lider_id_2').innerHTML = '<option value="">Selecione um líder (opcional)</option>' + html.substring(html.indexOf('</option>') + 9);
            } else {
                console.error('Erro ao listar líderes:', resultado);
                document.getElementById('celula_lider_id').innerHTML = '<option disabled>⚠️ Erro ao carregar</option>';
                document.getElementById('celula_lider_id_2').innerHTML = '<option disabled>⚠️ Erro ao carregar</option>';
            }
        } catch (erro) {
            console.error('Exceção ao carregar líderes:', erro);
            document.getElementById('celula_lider_id').innerHTML = '<option disabled>⚠️ Erro na conexão</option>';
            document.getElementById('celula_lider_id_2').innerHTML = '<option disabled>⚠️ Erro na conexão</option>';
        }
    }

    // Carregar líderes em treinamento no select
    async function carregarLideresEmTreinamento() {
        try {
            const resultado = await fazerRequisicao('listar_lideres_treinamento', 'GET');
            console.log('Resultado listar_lideres_treinamento:', resultado);
            
            if (resultado && resultado.status === 'sucesso') {
                let html = '<option value="">Selecione um líder em treinamento</option>';
                
                if (resultado.dados && Array.isArray(resultado.dados)) {
                    if (resultado.dados.length === 0) {
                        html += '<option disabled>⚠️ Nenhum líder em treinamento</option>';
                    } else {
                        resultado.dados.forEach(lider => {
                            html += `<option value="${lider.id}">${lider.nome} (${lider.funcao})</option>`;
                        });
                    }
                } else {
                    html += '<option disabled>⚠️ Erro ao carregar</option>';
                    console.error('Dados inválidos:', resultado.dados);
                }
                
                document.getElementById('celula_lider_treinamento_id').innerHTML = html;
            } else {
                console.error('Erro ao listar líderes em treinamento:', resultado);
                document.getElementById('celula_lider_treinamento_id').innerHTML = '<option disabled>⚠️ Erro ao carregar</option>';
            }
        } catch (erro) {
            console.error('Exceção ao carregar líderes em treinamento:', erro);
            document.getElementById('celula_lider_treinamento_id').innerHTML = '<option disabled>⚠️ Erro na conexão</option>';
        }
    }

    // Carregar células
    async function carregarCelulas() {
        const resultado = await fazerRequisicao('listar_celulas', 'GET');
        if (resultado.status === 'sucesso') {
            let html = '';
            resultado.dados.forEach(celula => {
                // Botões de ação baseados em permissões
                let actionsHtml = `
                    <div class="celula-actions">
                        <button class="btn-small" onclick="editarCelula(${celula.id})">✏️ Editar</button>
                        <?php if (is_supervisor()): ?>
                        <button class="btn-small btn-danger" onclick="deletarCelula(${celula.id})">🗑️ Deletar</button>
                        <?php endif; ?>
                    </div>
                `;
                
                html += `
                    <div class="card celula-card">
                        <div class="celula-header">
                            <h3>${ieq.escapeHtml(celula.nome)}</h3>
                        </div>
                        <div class="celula-info">
                            <p>📍 <strong>${ieq.escapeHtml(celula.endereco || 'Endereço não definido')}</strong></p>
                            <p>📅 ${ieq.escapeHtml(celula.dia_semana || '-')} às ${ieq.escapeHtml(celula.hora || '-')}</p>
                            <p>👥 ${ieq.escapeHtml(celula.total_membros || 0)} membros</p>
                            <p>👤 <strong>Líder:</strong> ${ieq.escapeHtml(celula.lider_nome || 'Não definido')}</p>
                            ${celula.lider_2_nome ? `<p>👥 <strong>2º Líder:</strong> ${ieq.escapeHtml(celula.lider_2_nome)}</p>` : ''}
                            ${celula.lider_treinamento_nome ? `<p>📚 <strong>L. Treinamento:</strong> ${ieq.escapeHtml(celula.lider_treinamento_nome)}</p>` : ''}
                            ${celula.igreja_nome ? `<p>⛪ <strong>Igreja:</strong> ${ieq.escapeHtml(celula.igreja_nome)}</p>` : ''}
                        </div>
                        <div class="celula-actions">
                            <button class="btn-small" onclick="editarCelula(${celula.id})">✏️ Editar</button>
                            <?php if (is_supervisor()): ?>
                            <button class="btn-small btn-danger" onclick="deletarCelula(${celula.id})">🗑️ Deletar</button>
                            <?php endif; ?>
                        </div>
                    </div>
                `;
            });
            document.getElementById('celulasList').innerHTML = html || '<p style="text-align: center; padding: 30px;">Nenhuma célula encontrada</p>';
        }
    }

    // Editar célula
    async function editarCelula(id) {
        const resultado = await fazerRequisicao(`obter_celula&id=${id}`, 'GET');
        if (resultado.status === 'sucesso') {
            const celula = resultado.dados;
            editandoId = id;
            
            document.getElementById('celula_nome').value = celula.nome;
            document.getElementById('celula_igreja_id').value = celula.igreja_id || '';
            document.getElementById('celula_dia_semana').value = celula.dia_semana || '';
            document.getElementById('celula_hora').value = celula.hora || '';
            document.getElementById('celula_endereco').value = celula.endereco || '';
            document.getElementById('celula_bairro').value = celula.bairro || '';
            document.getElementById('celula_cidade').value = celula.cidade || '';
            document.getElementById('celula_lider_id').value = celula.lider_id || '';
            document.getElementById('celula_lider_id_2').value = celula.lider_id_2 || '';
            document.getElementById('celula_lider_treinamento_id').value = celula.lider_treinamento_id || '';
            
            celulaModal.querySelector('h2').textContent = 'Editar Célula';
            celulaModal.style.display = 'block';
        }
    }

    // Deletar célula
    async function deletarCelula(id) {
        if (confirm('Tem certeza que deseja deletar esta célula?')) {
            const resultado = await fazerRequisicao('deletar_celula', 'POST', { id });
            if (resultado.status === 'sucesso') {
                mostrarNotificacao('Célula deletada com sucesso!', 'sucesso');
                carregarCelulas();
            } else {
                mostrarNotificacao('Erro ao deletar célula', 'erro');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        carregarCelulas();
        carregarIgrejas();
        carregarLideres();
        carregarLideresEmTreinamento();

        // Salvar célula
        document.getElementById('formCelula').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const dados = {
                nome: document.getElementById('celula_nome').value,
                igreja_id: document.getElementById('celula_igreja_id').value || null,
                dia_semana: document.getElementById('celula_dia_semana').value,
                hora: document.getElementById('celula_hora').value,
                endereco: document.getElementById('celula_endereco').value,
                bairro: document.getElementById('celula_bairro').value,
                cidade: document.getElementById('celula_cidade').value,
                lider_id: document.getElementById('celula_lider_id').value || null,
                lider_id_2: document.getElementById('celula_lider_id_2').value || null,
                lider_treinamento_id: document.getElementById('celula_lider_treinamento_id').value || null
            };
            
            let resultado;
            if (editandoId) {
                dados.id = editandoId;
                resultado = await fazerRequisicao('atualizar_celula', 'POST', dados);
            } else {
                resultado = await fazerRequisicao('criar_celula', 'POST', dados);
            }
            
            if (resultado.status === 'sucesso') {
                mostrarNotificacao(editandoId ? 'Célula atualizada!' : 'Célula criada!', 'sucesso');
                celulaModal.style.display = 'none';
                carregarCelulas();
            } else {
                mostrarNotificacao('Erro ao salvar célula', 'erro');
            }
        });
    });
</script>
