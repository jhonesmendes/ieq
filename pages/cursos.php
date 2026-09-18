<div class="page-header">
    <h1>Cursos</h1>
    <p>Gerencie cursos e formação de líderes</p>
</div>

<div style="margin-bottom: 20px;">
    <button class="btn btn-primary" id="btnNovoCurso">+ Novo Curso</button>
</div>

<div class="cursos-grid" id="cursosList">
    <div style="text-align: center; padding: 30px; color: #999;">Carregando cursos...</div>
</div>

<!-- Modal para novo/editar curso -->
<div id="cursoModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Novo Curso</h2>
        
        <form id="formCurso" class="form-group">
            <div class="form-row">
                <div class="form-item full-width">
                    <label>Nome do Curso</label>
                    <input type="text" id="curso_nome" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-item full-width">
                    <label>Descrição</label>
                    <textarea id="curso_descricao" rows="3" placeholder="Descreva o conteúdo e objetivos do curso..."></textarea>
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Data de Início</label>
                    <input type="date" id="curso_data_inicio" required>
                </div>
                <div class="form-item">
                    <label>Data de Término</label>
                    <input type="date" id="curso_data_fim">
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Localização</label>
                    <input type="text" id="curso_localizacao" placeholder="Ex: Sala de Ensino, Online...">
                </div>
                <div class="form-item">
                    <label>Vagas</label>
                    <input type="number" id="curso_vagas" min="0" placeholder="0 = ilimitado">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Salvar Curso</button>
        </form>
    </div>
</div>

<style>
    .cursos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }

    .curso-card {
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
    }

    .curso-card h3 {
        margin: 0 0 10px 0;
        color: #333;
    }

    .curso-descricao {
        color: #666;
        margin: 0 0 15px 0;
        flex-grow: 1;
        font-size: 14px;
        line-height: 1.5;
    }

    .curso-info {
        display: flex;
        flex-direction: column;
        gap: 8px;
        font-size: 14px;
        color: #999;
        margin-bottom: 15px;
    }

    .curso-status {
        position: absolute;
        top: 15px;
        right: 15px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .status-ativo {
        background: #4caf50;
        color: white;
    }

    .status-encerrado {
        background: #999;
        color: white;
    }

    .curso-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }

    .full-width {
        grid-column: 1 / -1;
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

    const cursoModal = document.getElementById('cursoModal');
    const btnNovoCurso = document.getElementById('btnNovoCurso');
    const closeBtn = document.querySelector('.close');
    let editandoId = null;

    btnNovoCurso.onclick = function() {
        editandoId = null;
        document.getElementById('formCurso').reset();
        cursoModal.querySelector('h2').textContent = 'Novo Curso';
        cursoModal.style.display = 'block';
    }

    closeBtn.onclick = function() {
        cursoModal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target == cursoModal) {
            cursoModal.style.display = 'none';
        }
    }

    // Carregar cursos
    async function carregarCursos() {
        const resultado = await fazerRequisicao('listar_cursos', 'GET');
        if (resultado.status === 'sucesso') {
            let html = '';
            
            if (resultado.dados.length === 0) {
                html = '<div style="grid-column: 1/-1; text-align: center; padding: 30px; color: #999;">Nenhum curso cadastrado</div>';
            } else {
                resultado.dados.forEach(curso => {
                    const dataInicio = curso.data_inicio ? formatarDataLocal(curso.data_inicio) : 'Não definido';
                    const dataFim = curso.data_fim ? formatarDataLocal(curso.data_fim) : 'Em andamento';
                    
                    // Verificar se o curso está ativo
                    const hoje = new Date();
                    const fimCurso = curso.data_fim ? new Date(curso.data_fim) : null;
                    const ativo = !fimCurso || fimCurso >= hoje;
                    
                    html += `
                        <div class="card curso-card">
                            <span class="curso-status ${ativo ? 'status-ativo' : 'status-encerrado'}">
                                ${ativo ? '✓ Ativo' : '✕ Encerrado'}
                            </span>
                            <h3>${ieq.escapeHtml(curso.nome)}</h3>
                            <p class="curso-descricao">${ieq.escapeHtml(curso.descricao || 'Sem descrição')}</p>
                            <div class="curso-info">
                                <span>📅 Início: ${ieq.escapeHtml(dataInicio)}</span>
                                <span>📅 Término: ${ieq.escapeHtml(dataFim)}</span>
                                ${curso.localizacao ? `<span>📍 ${ieq.escapeHtml(curso.localizacao)}</span>` : ''}
                                ${curso.vagas ? `<span>👥 ${ieq.escapeHtml(curso.vagas)} vagas</span>` : ''}
                                ${curso.professor_nome ? `<span>👨‍🏫 ${ieq.escapeHtml(curso.professor_nome)}</span>` : ''}
                            </div>
                            <div class="curso-actions">
                                <button class="btn btn-small" onclick="editarCurso(${curso.id})">✏️ Editar</button>
                                <button class="btn btn-small btn-danger" onclick="deletarCurso(${curso.id})">🗑️ Deletar</button>
                            </div>
                        </div>
                    `;
                });
            }
            
            document.getElementById('cursosList').innerHTML = html;
        }
    }

    // Editar curso
    async function editarCurso(id) {
        const resultado = await fazerRequisicao(`obter_curso&id=${id}`, 'GET');
        if (resultado.status === 'sucesso') {
            const curso = resultado.dados;
            editandoId = id;
            
            document.getElementById('curso_nome').value = curso.nome;
            document.getElementById('curso_descricao').value = curso.descricao || '';
            document.getElementById('curso_data_inicio').value = curso.data_inicio || '';
            document.getElementById('curso_data_fim').value = curso.data_fim || '';
            document.getElementById('curso_localizacao').value = curso.localizacao || '';
            document.getElementById('curso_vagas').value = curso.vagas || '';
            
            cursoModal.querySelector('h2').textContent = 'Editar Curso';
            cursoModal.style.display = 'block';
        }
    }

    // Deletar curso
    async function deletarCurso(id) {
        if (confirm('Tem certeza que deseja deletar este curso?')) {
            const resultado = await fazerRequisicao('deletar_curso', 'POST', { id });
            if (resultado.status === 'sucesso') {
                mostrarNotificacao('Curso deletado com sucesso!', 'sucesso');
                carregarCursos();
            } else {
                mostrarNotificacao('Erro ao deletar curso', 'erro');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        carregarCursos();

        // Salvar curso
        document.getElementById('formCurso').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const dados = {
                nome: document.getElementById('curso_nome').value,
                descricao: document.getElementById('curso_descricao').value,
                data_inicio: document.getElementById('curso_data_inicio').value,
                data_fim: document.getElementById('curso_data_fim').value,
                localizacao: document.getElementById('curso_localizacao').value,
                vagas: document.getElementById('curso_vagas').value || null
            };
            
            let resultado;
            if (editandoId) {
                dados.id = editandoId;
                resultado = await fazerRequisicao('atualizar_curso', 'POST', dados);
            } else {
                resultado = await fazerRequisicao('criar_curso', 'POST', dados);
            }
            
            if (resultado.status === 'sucesso') {
                mostrarNotificacao(editandoId ? 'Curso atualizado!' : 'Curso criado!', 'sucesso');
                cursoModal.style.display = 'none';
                carregarCursos();
            } else {
                mostrarNotificacao('Erro ao salvar curso', 'erro');
            }
        });
    });
</script>
