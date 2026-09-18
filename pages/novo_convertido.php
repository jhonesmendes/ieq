<div class="page-header">
    <h1>Novo Convertido</h1>
    <p>Registrar visitantes que irão tornar-se membros e atribuí-los à célula</p>
</div>

<div style="display:flex;gap:16px;margin-bottom:20px;align-items:center;">
    <button class="btn btn-primary" id="btnNovoVisitante">+ Novo Visitante</button>
    <input type="text" id="searchVisitante" placeholder="Buscar visitante por nome..." style="flex:1;padding:8px;border:1px solid #ddd;border-radius:4px;">
</div>

<div class="card" style="margin-bottom:20px;">
    <h3>Visitantes</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Telefone</th>
                <th>Presença</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="visitantesList">
            <tr><td colspan="4" style="text-align:center;padding:30px">Carregando...</td></tr>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Membros Existentes</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Célula</th>
                <th>Telefone</th>
            </tr>
        </thead>
        <tbody id="membrosExistentes">
            <tr><td colspan="3" style="text-align:center;padding:30px">Carregando...</td></tr>
        </tbody>
    </table>
</div>

<!-- Modal: novo visitante / converter -->
<div id="visitanteModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitle">Novo Visitante</h2>

        <form id="formVisitante" class="form-group">
            <div class="form-row">
                <div class="form-item">
                    <label>Nome</label>
                    <input type="text" id="visitante_nome" required>
                </div>
                <div class="form-item">
                    <label>Telefone</label>
                    <input type="tel" id="visitante_telefone">
                </div>
            </div>

            <div class="form-row">
                <div class="form-item">
                    <label>Observações</label>
                    <input type="text" id="visitante_obs">
                </div>
                <div class="form-item">
                    <label>Atribuir à Célula</label>
                    <select id="visitante_celula_id"></select>
                </div>
            </div>

            <div style="display:flex;gap:8px;margin-top:12px;">
                <button type="submit" class="btn btn-primary" id="btnSalvarVisitante">Salvar Visitante</button>
                <button type="button" class="btn" id="btnConverter">Converter em Membro</button>
            </div>
        </form>
    </div>
</div>

<style>
    .data-table { width:100%; border-collapse:collapse; }
    .data-table thead { background:#f5f5f5; }
    .data-table th,.data-table td { padding:10px; border-bottom:1px solid #eee; text-align:left; }
    .card { padding:16px; border-radius:8px; background:#fff; box-shadow:0 2px 8px rgba(0,0,0,0.04); margin-bottom:16px; }
</style>

<script>
const modal = document.getElementById('visitanteModal');
const btnNovoVisitante = document.getElementById('btnNovoVisitante');
const closeBtn = modal.querySelector('.close');
const form = document.getElementById('formVisitante');
let editingVisitante = null;
let visitantesCache = [];

btnNovoVisitante.onclick = () => { openModal(); };
closeBtn.onclick = () => { closeModal(); };
window.onclick = (e) => { if (e.target == modal) closeModal(); };

function openModal(visitante = null) {
    editingVisitante = visitante;
    document.getElementById('modalTitle').textContent = visitante ? 'Editar Visitante' : 'Novo Visitante';
    document.getElementById('visitante_nome').value = visitante ? visitante.nome : '';
    document.getElementById('visitante_telefone').value = visitante ? (visitante.telefone||'') : '';
    document.getElementById('visitante_obs').value = visitante ? (visitante.obs||'') : '';
    carregarCelulasInSelect();
    modal.style.display = 'block';
}

function closeModal() { modal.style.display = 'none'; editingVisitante = null; }

// Carregar visitantes
function carregarVisitantes() {
    fetch('api/index.php?acao=listar_visitantes')
        .then(r => r.json())
        .then(json => {
            if (json.status === 'sucesso') {
                renderVisitantes(json.dados);
            } else {
                document.getElementById('visitantesList').innerHTML = '<tr><td colspan="4" style="text-align:center;padding:18px">Nenhum visitante encontrado</td></tr>';
            }
        }).catch(err => {
            document.getElementById('visitantesList').innerHTML = '<tr><td colspan="4" style="text-align:center;padding:18px">API indisponível</td></tr>';
        });
}

function renderVisitantes(list) {
    const tbody = document.getElementById('visitantesList');
    visitantesCache = list || [];
    if (!list || list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:18px">Nenhum visitante registrado</td></tr>';
        return;
    }
    let html = '';
    list.forEach((v, idx) => {
        html += `<tr>
            <td>${ieq.escapeHtml(v.nome)}</td>
            <td>${ieq.escapeHtml(v.telefone||'-')}</td>
            <td>${ieq.escapeHtml(v.presenca_count||'-')}</td>
            <td>
                <button class="btn-small" data-idx="${idx}" data-action="editar">Editar</button>
                <button class="btn-small btn-primary" data-idx="${idx}" data-action="converter">Converter</button>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
    // Delegação de eventos para botões renderizados
    tbody.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', function(){
            const i = parseInt(this.getAttribute('data-idx'));
            const action = this.getAttribute('data-action');
            if (action === 'editar') openModal(visitantesCache[i]);
            if (action === 'converter') converterVisitante(visitantesCache[i]);
        });
    });
}

// Carregar membros existentes
function carregarMembrosExistentes() {
    fetch('api/index.php?acao=listar_membros')
        .then(r => r.json())
        .then(json => {
            if (json.status === 'sucesso') renderMembros(json.dados);
            else document.getElementById('membrosExistentes').innerHTML = '<tr><td colspan="3" style="text-align:center;padding:18px">Nenhum membro</td></tr>';
        }).catch(err => {
            document.getElementById('membrosExistentes').innerHTML = '<tr><td colspan="3" style="text-align:center;padding:18px">API indisponível</td></tr>';
        });
}

function renderMembros(list) {
    const tbody = document.getElementById('membrosExistentes');
    if (!list || list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:18px">Nenhum membro encontrado</td></tr>';
        return;
    }
    let html = '';
    list.forEach(m => {
        html += `<tr>
            <td>${ieq.escapeHtml(m.nome)}</td>
            <td>${ieq.escapeHtml(m.celula_nome || 'Sem célula')}</td>
            <td>${ieq.escapeHtml(m.telefone || '-')}</td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

// Carregar células para select
function carregarCelulasInSelect() {
    fetch('api/index.php?acao=listar_celulas')
        .then(r => r.json())
        .then(json => {
            const sel = document.getElementById('visitante_celula_id');
            if (json.status === 'sucesso') {
                let html = '<option value="">(Sem atribuição)</option>';
                json.dados.forEach(c => html += `<option value="${c.id}">${c.nome}</option>`);
                sel.innerHTML = html;
            } else {
                sel.innerHTML = '<option value="">(Nenhuma célula)</option>';
            }
        }).catch(() => { document.getElementById('visitante_celula_id').innerHTML = '<option value="">(API indisponível)</option>'; });
}

// Salvar visitante (POST)
form.addEventListener('submit', function(e){
    e.preventDefault();
    // Validação simples
    const nomeEl = document.getElementById('visitante_nome');
    const nome = nomeEl.value.trim();
    let valid = true;
    // remover mensagem antiga
    let err = document.getElementById('errorVisitanteNome');
    if (!err) { err = document.createElement('div'); err.id = 'errorVisitanteNome'; err.style.color = '#b00020'; err.style.fontSize = '0.9em'; nomeEl.parentNode.appendChild(err); }
    err.textContent = '';
    if (!nome) { err.textContent = 'Nome é obrigatório'; valid = false; nomeEl.focus(); }
    if (!valid) return;

    const data = {
        nome: nome,
        telefone: document.getElementById('visitante_telefone').value,
        obs: document.getElementById('visitante_obs').value,
        celula_id: document.getElementById('visitante_celula_id').value
    };

    fetch('api/index.php?acao=salvar_visitante', {
        method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data)
    }).then(r=>r.json()).then(resp=>{
        if (resp.status === 'sucesso') {
            closeModal(); carregarVisitantes(); notify('Visitante salvo','sucesso');
        } else notify('Erro: ' + (resp.mensagem||'não foi possível salvar'),'erro');
    }).catch(()=> notify('Erro ao conectar com a API','erro'));
});

// btnConverter dentro do modal: converte o visitante atualmente editado (ou o formulário atual)
document.getElementById('btnConverter').addEventListener('click', function(){
    const v = editingVisitante || { nome: document.getElementById('visitante_nome').value, telefone: document.getElementById('visitante_telefone').value, celula_id: document.getElementById('visitante_celula_id').value };
    if (!v || !v.nome) { notify('Nome é obrigatório para converter','erro'); return; }
    if (!confirm('Converter ' + v.nome + ' em membro?')) return;
    const payload = { nome: v.nome, telefone: v.telefone || '', celula_id: v.celula_id || document.getElementById('visitante_celula_id').value };
    fetch('api/index.php?acao=adicionar_membro', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) })
        .then(r=>r.json()).then(resp=>{
            if (resp.status === 'sucesso') { notify('Visitante convertido','sucesso'); carregarMembrosExistentes(); carregarVisitantes(); closeModal(); }
            else notify('Erro: ' + (resp.mensagem||'não foi possível converter'),'erro');
        }).catch(()=> notify('Erro ao conectar com a API','erro'));
});

// Converter visitante em membro
function converterVisitante(v) {
    if (!confirm('Converter '+v.nome+' em membro?')) return;
    const payload = { nome: v.nome, telefone: v.telefone || '', celula_id: v.celula_id || '' };
    fetch('api/index.php?acao=adicionar_membro', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) })
        .then(r=>r.json()).then(resp=>{
            if (resp.status === 'sucesso') { mostrarNotificacao && mostrarNotificacao('Visitante convertido','sucesso'); carregarMembrosExistentes(); carregarVisitantes(); }
            else alert('Erro: ' + (resp.mensagem||'não foi possível converter'));
        }).catch(()=> alert('Erro ao conectar com a API'));
}

// Inicialização
document.addEventListener('DOMContentLoaded', function(){
    carregarVisitantes();
    carregarMembrosExistentes();
    carregarCelulasInSelect();

    document.getElementById('searchVisitante').addEventListener('input', function(e){
        const q = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#visitantesList tr');
        rows.forEach(r => { r.style.display = r.innerText.toLowerCase().includes(q) ? '' : 'none'; });
    });
});

// helper de notificação que usa mostrarNotificacao quando disponível
function notify(mensagem, tipo) {
    if (typeof mostrarNotificacao === 'function') mostrarNotificacao(mensagem, tipo === 'sucesso' ? 'success' : tipo);
    else alert(mensagem);
}
</script>
