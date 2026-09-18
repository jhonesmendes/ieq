<div class="page-header">
    <h1>Eventos</h1>
    <p>Crie e gerencie eventos da sua igreja</p>
</div>

<div style="margin-bottom: 20px;">
    <button class="btn btn-primary" id="btnNovoEvento">+ Novo Evento</button>
</div>

<div class="eventos-list" id="eventosList">
    <div style="text-align: center; padding: 30px; color: #999;">Carregando eventos...</div>
</div>

<!-- Modal para novo/editar evento -->
<div id="eventoModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Novo Evento</h2>
        
        <form id="formEvento" class="form-group">
            <div class="form-row">
                <div class="form-item">
                    <label>Nome do Evento</label>
                    <input type="text" id="evento_nome" required>
                </div>
                <div class="form-item">
                    <label>Tipo</label>
                    <select id="evento_tipo">
                        <option value="evento">Evento</option>
                        <option value="culto">Culto</option>
                        <option value="retiro">Retiro</option>
                        <option value="conferencia">Conferência</option>
                        <option value="congresso">Congresso</option>
                        <option value="reuniao">Reunião</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Data e Hora</label>
                    <input type="datetime-local" id="evento_data" required>
                </div>
                <div class="form-item">
                    <label>Vagas</label>
                    <input type="number" id="evento_vagas" min="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-item full-width">
                    <label>Localização</label>
                    <input type="text" id="evento_localizacao" placeholder="Ex: Templo Central, Parque da Lagoa...">
                </div>
            </div>

            <div class="form-row">
                <div class="form-item full-width">
                    <label>Descrição</label>
                    <textarea id="evento_descricao" rows="4"></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Salvar Evento</button>
        </form>
    </div>
</div>

<style>
    .eventos-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }

    .evento-card {
        display: flex;
        flex-direction: column;
    }

    .evento-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .evento-header h3 {
        margin: 0;
    }

    .evento-tipo {
        background: #667eea;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .evento-info {
        flex: 1;
        color: #666;
        font-size: 14px;
    }

    .evento-info p {
        margin: 8px 0;
    }

    .evento-actions {
        margin-top: 15px;
        display: flex;
        gap: 10px;
    }

    .full-width {
        grid-column: 1 / -1;
    }

    textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: inherit;
        resize: vertical;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .eventos-list {
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .evento-card {
            padding: 15px;
        }

        .evento-header h3 {
            font-size: 16px;
        }

        .evento-info {
            font-size: 13px;
        }

        .evento-actions {
            flex-direction: column;
            gap: 8px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        textarea {
            min-height: 120px;
            font-size: 1rem;
        }
    }

    /* Tablet */
    @media (min-width: 769px) and (max-width: 1023px) {
        .eventos-list {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
    }
</style>

<script>
    const eventoModal = document.getElementById('eventoModal');
    const btnNovoEvento = document.getElementById('btnNovoEvento');
    const closeBtn = document.querySelector('.close');
    let editandoId = null;

    btnNovoEvento.onclick = function() {
        editandoId = null;
        document.getElementById('formEvento').reset();
        eventoModal.querySelector('h2').textContent = 'Novo Evento';
        eventoModal.style.display = 'block';
    }

    closeBtn.onclick = function() {
        eventoModal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == eventoModal) {
            eventoModal.style.display = 'none';
        }
    }

    // Formatar data para exibição
    function formatarDataEvento(dataString) {
        const data = new Date(dataString);
        const opcoes = { 
            day: 'numeric', 
            month: 'long', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return data.toLocaleDateString('pt-BR', opcoes);
    }

    // Carregar eventos
    async function carregarEventos() {
        const resultado = await fazerRequisicao('listar_eventos', 'GET');
        if (resultado.status === 'sucesso') {
            let html = '';
            
            if (resultado.dados.length === 0) {
                html = '<div style="text-align: center; padding: 30px; color: #999;">Nenhum evento cadastrado</div>';
            } else {
                resultado.dados.forEach(evento => {
                    const dataFormatada = formatarDataEvento(evento.data_evento);
                    const tipoClass = evento.tipo || 'evento';
                    
                    html += `
                        <div class="card evento-card">
                            <div class="evento-header">
                                <h3>${ieq.escapeHtml(evento.nome)}</h3>
                                <span class="evento-tipo">${ieq.escapeHtml(evento.tipo || 'Evento')}</span>
                            </div>
                            <div class="evento-info">
                                <p>📅 <strong>${ieq.escapeHtml(dataFormatada)}</strong></p>
                                <p>📍 ${ieq.escapeHtml(evento.localizacao || 'Local não definido')}</p>
                                ${evento.vagas ? `<p>👥 ${ieq.escapeHtml(evento.vagas)} vagas</p>` : ''}
                                ${evento.descricao ? `<p style="margin-top: 10px; font-style: italic;">${ieq.escapeHtml(evento.descricao)}</p>` : ''}
                            </div>
                            <div class="evento-actions">
                                <button class="btn btn-small" onclick="editarEvento(${evento.id})">✏️ Editar</button>
                                <button class="btn btn-small btn-danger" onclick="deletarEvento(${evento.id})">🗑️ Deletar</button>
                            </div>
                        </div>
                    `;
                });
            }
            
            document.getElementById('eventosList').innerHTML = html;
        }
    }

    // Editar evento
    async function editarEvento(id) {
        const resultado = await fazerRequisicao(`obter_evento&id=${id}`, 'GET');
        if (resultado.status === 'sucesso') {
            const evento = resultado.dados;
            editandoId = id;
            
            document.getElementById('evento_nome').value = evento.nome;
            document.getElementById('evento_tipo').value = evento.tipo || 'evento';
            document.getElementById('evento_localizacao').value = evento.localizacao || '';
            document.getElementById('evento_descricao').value = evento.descricao || '';
            document.getElementById('evento_vagas').value = evento.vagas || '';
            
            // Formatar data para datetime-local
            const data = new Date(evento.data_evento);
            const dataFormatada = data.toISOString().slice(0, 16);
            document.getElementById('evento_data').value = dataFormatada;
            
            eventoModal.querySelector('h2').textContent = 'Editar Evento';
            eventoModal.style.display = 'block';
        }
    }

    // Deletar evento
    async function deletarEvento(id) {
        if (confirm('Tem certeza que deseja deletar este evento?')) {
            const resultado = await fazerRequisicao('deletar_evento', 'POST', { id });
            if (resultado.status === 'sucesso') {
                mostrarNotificacao('Evento deletado com sucesso!', 'sucesso');
                carregarEventos();
            } else {
                mostrarNotificacao('Erro ao deletar evento', 'erro');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        carregarEventos();

        // Salvar evento
        document.getElementById('formEvento').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const dados = {
                nome: document.getElementById('evento_nome').value,
                tipo: document.getElementById('evento_tipo').value,
                data_evento: document.getElementById('evento_data').value.replace('T', ' ') + ':00',
                localizacao: document.getElementById('evento_localizacao').value,
                descricao: document.getElementById('evento_descricao').value,
                vagas: document.getElementById('evento_vagas').value || null
            };
            
            let resultado;
            if (editandoId) {
                dados.id = editandoId;
                resultado = await fazerRequisicao('atualizar_evento', 'POST', dados);
            } else {
                resultado = await fazerRequisicao('criar_evento', 'POST', dados);
            }
            
            if (resultado.status === 'sucesso') {
                mostrarNotificacao(editandoId ? 'Evento atualizado!' : 'Evento criado!', 'sucesso');
                eventoModal.style.display = 'none';
                carregarEventos();
            } else {
                mostrarNotificacao('Erro ao salvar evento', 'erro');
            }
        });
    });
</script>
<style>

    .evento-info {
        color: #666;
        margin-bottom: 15px;
        flex-grow: 1;
    }

    .evento-info p {
        margin: 8px 0;
        font-size: 14px;
    }

    .evento-info strong {
        color: #333;
    }
</style>
