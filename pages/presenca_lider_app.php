<?php
// App simplificado do líder: 4 abas (Início, Membros, Reuniões, Perfil).
// Só é usado quando o usuário logado é líder/líder em treinamento com uma
// única célula — ver index.php ($app_lider_simplificado) e
// config/permissoes.php (get_permissoes_usuario restringe o líder a 'presenca').
$user_nome = $_SESSION['user_nome'] ?? 'Líder';
$user_email = $_SESSION['user_email'] ?? '';
$celulas_lider = function_exists('obter_celulas_do_lider') ? obter_celulas_do_lider() : [];
$celula_id_lider = $celulas_lider[0] ?? 0;
?>

<div class="lider-app" id="liderApp" data-celula-id="<?php echo intval($celula_id_lider); ?>">

    <header class="lider-header">
        <p class="lider-saudacao" id="liderSaudacao">Olá,</p>
        <h1 class="lider-nome"><?php echo htmlspecialchars($user_nome); ?></h1>

        <div class="reuniao-card-hoje" id="reuniaoCardHoje">
            <span class="dot"></span>
            <div>
                <strong id="reuniaoHojeTitulo">Carregando célula...</strong>
                <span id="reuniaoHojeSub"></span>
            </div>
        </div>
    </header>

    <main class="lider-content">

        <!-- ===================== ABA: INÍCIO ===================== -->
        <section id="tabInicio" class="tab-panel is-active">
            <h2 class="secao-titulo">Resumo da célula</h2>
            <div class="stats-row">
                <div class="stat-mini">
                    <span class="stat-mini-icon stat-icon-roxo">👥</span>
                    <strong id="statMembros">--</strong>
                    <span>membros</span>
                </div>
                <div class="stat-mini">
                    <span class="stat-mini-icon stat-icon-verde">✓</span>
                    <strong id="statPresentes">--</strong>
                    <span>presentes hoje</span>
                </div>
                <div class="stat-mini">
                    <span class="stat-mini-icon stat-icon-amarelo">★</span>
                    <strong id="statVisitantes">--</strong>
                    <span>visitantes</span>
                </div>
            </div>

            <div class="secao-header-flex">
                <h2 class="secao-titulo">Lançar presença de hoje</h2>
                <input type="date" id="filterData" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="lider-card" id="fotoReuniaoSection">
                <div class="foto-preview-reuniao" id="fotoPreviewReuniao" onclick="document.getElementById('foto_reuniao').click()">
                    <div class="foto-placeholder-reuniao">
                        <span>📷</span>
                        <p>Toque para fotografar a célula</p>
                    </div>
                </div>
                <input type="file" id="foto_reuniao" accept="image/jpeg,image/png,image/gif" capture="environment" style="display: none;">
                <textarea id="observacoes_reuniao" placeholder="Observações (opcional)"></textarea>
            </div>

            <div class="lider-card" id="membrosCard" style="padding: 6px 0;">
                <ul class="membros-lista" id="presencaList">
                    <li class="lista-vazia">Carregando membros...</li>
                </ul>

                <div class="visitante-inline" id="visitanteInlineBox" style="display: none;">
                    <button type="button" class="btn-add-visitante" id="btnAbrirAddVisitante">+ Adicionar visitante</button>
                    <div class="add-visitante-form" id="addVisitanteForm" style="display: none;">
                        <input type="text" id="inputNomeVisitante" placeholder="Nome do visitante" maxlength="255">
                        <button type="button" class="btn-mini-primario" id="btnConfirmarVisitante">Adicionar</button>
                        <button type="button" class="btn-mini-secundario" id="btnCancelarVisitante">Cancelar</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===================== ABA: MEMBROS ===================== -->
        <section id="tabMembros" class="tab-panel">
            <h2 class="secao-titulo">Membros da célula</h2>
            <ul class="membros-lista" id="membrosRosterList">
                <li class="lista-vazia">Carregando...</li>
            </ul>
        </section>

        <!-- ===================== ABA: REUNIÕES ===================== -->
        <section id="tabReunioes" class="tab-panel">
            <h2 class="secao-titulo">Reuniões anteriores</h2>
            <ul class="reunioes-lista" id="reunioesList">
                <li class="lista-vazia">Carregando...</li>
            </ul>
        </section>

        <!-- ===================== ABA: PERFIL ===================== -->
        <section id="tabPerfil" class="tab-panel">
            <h2 class="secao-titulo">Perfil</h2>
            <div class="lider-card perfil-card">
                <span class="avatar-membro avatar-grande" id="perfilAvatar">--</span>
                <div>
                    <strong><?php echo htmlspecialchars($user_nome); ?></strong>
                    <span><?php echo htmlspecialchars($user_email); ?></span>
                    <span id="perfilCelulaNome">Célula</span>
                </div>
            </div>
            <a href="index.php?page=logout" class="btn-sair">🚪 Sair do aplicativo</a>
        </section>

    </main>

    <nav class="lider-tabbar">
        <button type="button" class="tab-btn is-active" data-tab="Inicio">
            <span class="tab-icon">🏠</span><span>Início</span>
        </button>
        <button type="button" class="tab-btn" data-tab="Membros">
            <span class="tab-icon">👥</span><span>Membros</span>
        </button>
        <button type="button" class="tab-btn" data-tab="Reunioes">
            <span class="tab-icon">📅</span><span>Reuniões</span>
        </button>
        <button type="button" class="tab-btn" data-tab="Perfil">
            <span class="tab-icon">👤</span><span>Perfil</span>
        </button>
    </nav>

</div>

<script>
(function() {
    const celulaId = document.getElementById('liderApp').dataset.celulaId;
    let visitantesPendentes = [];
    let arquivoFotoReuniao = null;

    function saudacaoPeloHorario() {
        const h = new Date().getHours();
        if (h < 12) return 'Bom dia,';
        if (h < 18) return 'Boa tarde,';
        return 'Boa noite,';
    }
    document.getElementById('liderSaudacao').textContent = saudacaoPeloHorario();

    function iniciaisNome(nome) {
        const partes = (nome || '').trim().split(/\s+/);
        if (partes.length >= 2) return (partes[0][0] + partes[1][0]).toUpperCase();
        return (nome || '').slice(0, 2).toUpperCase();
    }

    function formatarDataLocal(dataString) {
        if (!dataString) return '';
        if (/^\d{4}-\d{2}-\d{2}$/.test(dataString)) {
            const [ano, mes, dia] = dataString.split('-');
            return new Date(ano, mes - 1, dia).toLocaleDateString('pt-BR');
        }
        return new Date(dataString).toLocaleDateString('pt-BR');
    }

    function showNotification(message, type = 'info') {
        const n = document.createElement('div');
        n.textContent = message;
        n.style.cssText = `position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:1000;
            padding:12px 18px;border-radius:12px;color:white;font-weight:600;font-size:14px;
            max-width:90vw;text-align:center;box-shadow:0 6px 16px rgba(0,0,0,0.2);`;
        n.style.background = type === 'success' ? '#16a34a' : type === 'error' ? '#dc2626' : type === 'warning' ? '#ca8a04' : '#4c3d7d';
        document.body.appendChild(n);
        setTimeout(() => n.remove(), 3000);
    }

    // ---------- Abas ----------
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('is-active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('is-active'));
            btn.classList.add('is-active');
            document.getElementById('tab' + btn.dataset.tab).classList.add('is-active');

            if (btn.dataset.tab === 'Membros') carregarRoster();
            if (btn.dataset.tab === 'Reunioes') carregarReunioes();
        });
    });

    // ---------- Card "célula" no topo + perfil ----------
    function carregarCelula() {
        fetch(`api/index.php?acao=obter_celula&id=${celulaId}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'sucesso' && res.dados) {
                    const c = res.dados;
                    document.getElementById('reuniaoHojeTitulo').textContent =
                        `${c.nome}${c.dia_semana ? ' — ' + c.dia_semana : ''}${c.hora ? ' ' + c.hora : ''}`;
                    document.getElementById('reuniaoHojeSub').textContent = c.endereco || 'Sem endereço cadastrado';
                    document.getElementById('perfilCelulaNome').textContent = c.nome;
                } else {
                    document.getElementById('reuniaoHojeTitulo').textContent = 'Sua célula';
                }
            })
            .catch(() => {
                document.getElementById('reuniaoHojeTitulo').textContent = 'Sua célula';
            });
    }

    document.getElementById('perfilAvatar').textContent = iniciaisNome(<?php echo json_encode($user_nome); ?>);

    // ---------- Aba Início: lista de presença ----------
    function renderLinhaMembro(membro, opts = {}) {
        const isVisitante = !!opts.visitante;
        const pendente = !!opts.pendente;
        const presenteInicial = opts.presenteInicial !== false;
        const iniciais = iniciaisNome(membro.nome);

        if (isVisitante) {
            const idAttr = pendente ? `data-pending-id="${membro.id}"` : `data-visitante-id="${membro.id}"`;
            return `<li class="membro-item visitante" ${idAttr}>
                <span class="avatar-membro avatar-laranja">${iniciais}</span>
                <div class="membro-info">
                    <strong>${membro.nome}</strong>
                    <span>${pendente ? 'Visitante (não salvo)' : 'Visitante'}</span>
                </div>
                <span class="pill pill-verde">presente</span>
                <button type="button" class="btn-remover-visitante" ${idAttr} title="Remover">✕</button>
            </li>`;
        }

        return `<li class="membro-item" data-membro="${membro.id}">
            <span class="avatar-membro">${iniciais}</span>
            <div class="membro-info">
                <strong>${membro.nome}</strong>
                <span>Membro ativo</span>
            </div>
            <div class="presenca-status-toggle" data-membro="${membro.id}">
                <button type="button" class="pill pill-verde toggle-btn presente-btn ${presenteInicial ? 'is-active' : ''}" data-presente="1">presente</button>
                <button type="button" class="pill pill-vermelho toggle-btn ausente-btn ${!presenteInicial ? 'is-active' : ''}" data-presente="0">ausente</button>
            </div>
        </li>`;
    }

    function ativarTogglesPresenca() {
        document.querySelectorAll('#presencaList .presenca-status-toggle .toggle-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const toggle = this.closest('.presenca-status-toggle');
                const presente = this.dataset.presente === '1';
                toggle.querySelector('.presente-btn').classList.toggle('is-active', presente);
                toggle.querySelector('.ausente-btn').classList.toggle('is-active', !presente);
                atualizarStatsLocal();
            });
        });
    }

    function ativarRemocaoVisitantePendente() {
        document.querySelectorAll('.btn-remover-visitante[data-pending-id]').forEach(btn => {
            btn.addEventListener('click', function() {
                visitantesPendentes = visitantesPendentes.filter(v => v.id !== this.dataset.pendingId);
                this.closest('li').remove();
                atualizarStatsLocal();
            });
        });
    }

    function ativarRemocaoVisitante() {
        document.querySelectorAll('.btn-remover-visitante[data-visitante-id]').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Remover este visitante do lançamento de hoje?')) return;
                const id = this.dataset.visitanteId;
                const linha = this.closest('li');
                fetch('api/index.php?acao=deletar_visitante', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${id}`
                })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'sucesso') { linha.remove(); atualizarStatsLocal(); }
                    else showNotification('❌ ' + res.mensagem, 'error');
                })
                .catch(() => showNotification('❌ Falha ao remover. Verifique a conexão.', 'error'));
            });
        });
    }

    function atualizarStatsLocal() {
        const toggles = document.querySelectorAll('#presencaList .presenca-status-toggle');
        const presentes = Array.from(toggles).filter(t => t.querySelector('.presente-btn').classList.contains('is-active')).length;
        const visitantes = document.querySelectorAll('#presencaList .membro-item.visitante').length;
        document.getElementById('statMembros').textContent = toggles.length;
        document.getElementById('statPresentes').textContent = presentes;
        document.getElementById('statVisitantes').textContent = visitantes;
    }

    function carregarPresencaHoje() {
        const dataSelecionada = document.getElementById('filterData').value;

        Promise.all([
            fetch(`api/index.php?acao=listar_membros&celula_id=${celulaId}`).then(r => r.json()),
            fetch(`api/index.php?acao=listar_visitantes&celula_id=${celulaId}`).then(r => r.json())
        ]).then(([dm, dv]) => {
            const membros = (dm.status === 'sucesso' && dm.dados) ? dm.dados : [];
            const visitantesDoDia = (dv.status === 'sucesso' && dv.dados)
                ? dv.dados.filter(v => v.data_visita === dataSelecionada)
                : [];

            let html = '';
            if (membros.length === 0 && visitantesDoDia.length === 0) {
                html = '<li class="lista-vazia">Nenhum membro cadastrado nesta célula</li>';
            } else {
                membros.forEach(m => html += renderLinhaMembro(m));
                visitantesDoDia.forEach(v => html += renderLinhaMembro(v, { visitante: true }));
            }
            document.getElementById('presencaList').innerHTML = html;
            ativarTogglesPresenca();
            ativarRemocaoVisitante();
            document.getElementById('visitanteInlineBox').style.display = 'block';
            atualizarStatsLocal();
        }).catch(() => {
            document.getElementById('presencaList').innerHTML =
                '<li class="lista-vazia" style="color:#dc2626;">❌ Não foi possível carregar os membros. Verifique a conexão.</li>';
        });
    }

    // Visitante inline
    const btnAbrir = document.getElementById('btnAbrirAddVisitante');
    const form = document.getElementById('addVisitanteForm');
    const input = document.getElementById('inputNomeVisitante');
    btnAbrir.addEventListener('click', () => {
        btnAbrir.style.display = 'none';
        form.style.display = 'flex';
        input.focus();
    });
    function fecharFormVisitante() {
        form.style.display = 'none';
        btnAbrir.style.display = 'block';
        input.value = '';
    }
    document.getElementById('btnCancelarVisitante').addEventListener('click', fecharFormVisitante);
    function confirmarVisitante() {
        const nome = input.value.trim();
        if (!nome) { showNotification('❌ Informe o nome do visitante', 'error'); return; }

        const vazio = document.querySelector('#presencaList .lista-vazia');
        if (vazio) vazio.remove();

        const pendingId = 'pendente-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7);
        visitantesPendentes.push({ id: pendingId, nome });
        document.getElementById('presencaList').insertAdjacentHTML('beforeend', renderLinhaMembro({ id: pendingId, nome }, { visitante: true, pendente: true }));
        ativarRemocaoVisitantePendente();
        atualizarStatsLocal();
        fecharFormVisitante();
    }
    document.getElementById('btnConfirmarVisitante').addEventListener('click', confirmarVisitante);
    input.addEventListener('keydown', e => { if (e.key === 'Enter') confirmarVisitante(); });

    // Foto
    document.getElementById('foto_reuniao').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        if (!file.type.match('image/jpeg') && !file.type.match('image/png') && !file.type.match('image/gif')) {
            showNotification('❌ Apenas JPG, PNG ou GIF são permitidas!', 'error');
            this.value = ''; return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showNotification('❌ A foto deve ter no máximo 5MB', 'error');
            this.value = ''; return;
        }
        arquivoFotoReuniao = file;
        const reader = new FileReader();
        reader.onload = ev => {
            document.getElementById('fotoPreviewReuniao').innerHTML = `<img src="${ev.target.result}" alt="Foto da célula">`;
        };
        reader.readAsDataURL(file);
    });

    // Salvar tudo (presença + visitantes + foto)
    async function salvarReuniao() {
        const data = document.getElementById('filterData').value;
        const observacoes = document.getElementById('observacoes_reuniao').value;

        let presentes = 0, ausentes = 0, falhas = 0;
        for (const toggle of document.querySelectorAll('#presencaList .presenca-status-toggle')) {
            const membroId = toggle.dataset.membro;
            const presente = toggle.querySelector('.presente-btn').classList.contains('is-active');
            try {
                const r = await fetch('api/index.php?acao=registrar_presenca', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `membro_id=${membroId}&celula_id=${celulaId}&data_presenca=${data}&presente=${presente ? 1 : 0}`
                });
                const res = await r.json();
                if (res.status === 'sucesso') { presente ? presentes++ : ausentes++; } else falhas++;
            } catch (e) { falhas++; }
        }

        let visitantesSalvos = 0;
        for (const pendente of [...visitantesPendentes]) {
            try {
                const r = await fetch('api/index.php?acao=criar_visitante', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `nome=${encodeURIComponent(pendente.nome)}&celula_id=${celulaId}&data_visita=${data}&status=primeira_visita`
                });
                const res = await r.json();
                if (res.status === 'sucesso') {
                    visitantesSalvos++;
                    visitantesPendentes = visitantesPendentes.filter(v => v.id !== pendente.id);
                    const linha = document.querySelector(`[data-pending-id="${pendente.id}"]`)?.closest('li');
                    if (linha && res.dados && res.dados.visitante_id) {
                        linha.outerHTML = renderLinhaMembro({ id: res.dados.visitante_id, nome: pendente.nome }, { visitante: true });
                    }
                } else falhas++;
            } catch (e) { falhas++; }
        }
        ativarRemocaoVisitante();

        try {
            const formData = new FormData();
            if (arquivoFotoReuniao) formData.append('foto', arquivoFotoReuniao);
            formData.append('celula_id', celulaId);
            formData.append('data_reuniao', data);
            formData.append('observacoes', observacoes);
            await fetch('api/index.php?acao=criar_reuniao_com_foto', { method: 'POST', body: formData, credentials: 'include' });
        } catch (e) { /* não bloqueia o resumo final */ }

        const partes = [`${presentes} presente(s)`, `${ausentes} falta(s)`];
        if (visitantesSalvos > 0) partes.push(`${visitantesSalvos} visitante(s)`);
        showNotification(falhas === 0 ? `✅ Reunião salva: ${partes.join(', ')}` : `⚠️ Salvo com ${falhas} erro(s)`, falhas === 0 ? 'success' : 'warning');
    }

    document.getElementById('filterData').addEventListener('change', carregarPresencaHoje);

    // ---------- Aba Membros (roster somente leitura) ----------
    let rosterCarregado = false;
    function carregarRoster() {
        if (rosterCarregado) return;
        fetch(`api/index.php?acao=listar_membros&celula_id=${celulaId}`)
            .then(r => r.json())
            .then(res => {
                const membros = (res.status === 'sucesso' && res.dados) ? res.dados : [];
                if (membros.length === 0) {
                    document.getElementById('membrosRosterList').innerHTML = '<li class="lista-vazia">Nenhum membro cadastrado</li>';
                    return;
                }
                document.getElementById('membrosRosterList').innerHTML = membros.map(m => `
                    <li class="membro-item">
                        <span class="avatar-membro">${iniciaisNome(m.nome)}</span>
                        <div class="membro-info">
                            <strong>${m.nome}</strong>
                            <span>${m.telefone || m.email || 'Sem contato cadastrado'}</span>
                        </div>
                    </li>
                `).join('');
                rosterCarregado = true;
            })
            .catch(() => {
                document.getElementById('membrosRosterList').innerHTML = '<li class="lista-vazia" style="color:#dc2626;">❌ Erro ao carregar membros</li>';
            });
    }

    // ---------- Aba Reuniões (histórico) ----------
    let reunioesCarregadas = false;
    function carregarReunioes() {
        if (reunioesCarregadas) return;
        fetch(`api/index.php?acao=listar_reunioes&celula_id=${celulaId}`)
            .then(r => r.json())
            .then(res => {
                const reunioes = (res.status === 'sucesso' && res.dados) ? res.dados : [];
                if (reunioes.length === 0) {
                    document.getElementById('reunioesList').innerHTML = '<li class="lista-vazia">Nenhuma reunião registrada ainda</li>';
                    return;
                }
                document.getElementById('reunioesList').innerHTML = reunioes.map(r => `
                    <li class="reuniao-item">
                        ${r.foto_url ? `<img src="${r.foto_url}" alt="Foto da reunião" class="reuniao-foto">` : '<span class="reuniao-foto reuniao-foto-vazia">📷</span>'}
                        <div class="reuniao-info">
                            <strong>${formatarDataLocal(r.data_reuniao)}</strong>
                            <span>${r.total_presentes || 0} presente(s) · ${r.total_visitantes || 0} visitante(s)</span>
                        </div>
                    </li>
                `).join('');
                reunioesCarregadas = true;
            })
            .catch(() => {
                document.getElementById('reunioesList').innerHTML = '<li class="lista-vazia" style="color:#dc2626;">❌ Erro ao carregar reuniões</li>';
            });
    }

    // ---------- Botão salvar fixo: fica dentro do tabbar pra não sumir ----------
    const btnSalvarFixo = document.createElement('button');
    btnSalvarFixo.type = 'button';
    btnSalvarFixo.id = 'btnSalvarReuniao';
    btnSalvarFixo.className = 'btn-salvar-flutuante';
    btnSalvarFixo.textContent = '💾 Salvar reunião de hoje';
    btnSalvarFixo.addEventListener('click', async () => {
        btnSalvarFixo.disabled = true;
        btnSalvarFixo.textContent = '📤 Salvando...';
        await salvarReuniao();
        btnSalvarFixo.textContent = '✓ Salvo';
        setTimeout(() => { btnSalvarFixo.disabled = false; btnSalvarFixo.textContent = '💾 Salvar reunião de hoje'; }, 2000);
    });
    document.getElementById('tabInicio').appendChild(btnSalvarFixo);

    carregarCelula();
    carregarPresencaHoje();
})();
</script>
