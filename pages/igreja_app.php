<?php
// App simplificado do gestor da igreja: 4 abas (Início, Eventos, Pessoas,
// Perfil). Só é usado quando o usuário logado tem funcao='gestor_igreja' —
// ver index.php ($app_gestor_igreja_simplificado) e config/permissoes.php
// (get_permissoes_usuario restringe o acesso a 'igreja_app').
// config/igrejas.php não é carregado pelo bootstrap do index.php (só pela
// API), então precisa ser incluído aqui.
require_once __DIR__ . '/../config/igrejas.php';

$user_nome = $_SESSION['user_nome'] ?? 'Gestor';
$user_email = $_SESSION['user_email'] ?? '';
$igreja = obter_igreja_do_gestor();
$igreja_id = $igreja['id'] ?? 0;
$igreja_nome = $igreja['nome'] ?? 'Sua igreja';
?>

<div class="igreja-app" id="igrejaApp" data-igreja-id="<?php echo intval($igreja_id); ?>">

    <header class="igreja-header">
        <p class="igreja-saudacao" id="igrejaSaudacao">Olá,</p>
        <h1 class="igreja-nome-usuario"><?php echo htmlspecialchars($user_nome); ?></h1>
        <div class="igreja-card-nome">
            <span class="dot"></span>
            <div>
                <strong><?php echo htmlspecialchars($igreja_nome); ?></strong>
                <span id="igrejaEnderecoTopo">Carregando endereço...</span>
            </div>
        </div>
    </header>

    <main class="igreja-content">

        <!-- ===================== ABA: INÍCIO ===================== -->
        <section id="tabInicio" class="tab-panel is-active">
            <h2 class="secao-titulo">Resumo da igreja</h2>
            <div class="stats-grid-igreja">
                <div class="stat-mini">
                    <span class="stat-mini-icon stat-icon-roxo">👋</span>
                    <strong id="statVisitantes">--</strong>
                    <span>visitantes</span>
                </div>
                <div class="stat-mini">
                    <span class="stat-mini-icon stat-icon-verde">✝️</span>
                    <strong id="statConversoes">--</strong>
                    <span>conversões</span>
                </div>
                <div class="stat-mini">
                    <span class="stat-mini-icon stat-icon-azul">💧</span>
                    <strong id="statBatismos">--</strong>
                    <span>batismos</span>
                </div>
                <div class="stat-mini">
                    <span class="stat-mini-icon stat-icon-amarelo">🤝</span>
                    <strong id="statReconciliacao">--</strong>
                    <span>reconciliações</span>
                </div>
            </div>
            <p class="stats-hint">Totais gerais (todos os períodos)</p>

            <h2 class="secao-titulo" style="margin-top: 24px;">Próximos eventos</h2>
            <ul class="lista-igreja" id="proximosEventosList">
                <li class="lista-vazia">Carregando...</li>
            </ul>
        </section>

        <!-- ===================== ABA: EVENTOS ===================== -->
        <section id="tabEventos" class="tab-panel">
            <div class="secao-header-flex">
                <h2 class="secao-titulo">Eventos da igreja</h2>
                <button type="button" class="btn-add-mini" id="btnAbrirEvento">+ Novo</button>
            </div>

            <div class="form-inline-card" id="formEvento" style="display:none;">
                <input type="text" id="eventoNome" placeholder="Nome do evento" maxlength="255">
                <input type="date" id="eventoData">
                <input type="text" id="eventoLocal" placeholder="Local (opcional)">
                <textarea id="eventoDescricao" placeholder="Descrição (opcional)"></textarea>
                <div class="form-inline-acoes">
                    <button type="button" class="btn-mini-primario" id="btnSalvarEvento">Salvar</button>
                    <button type="button" class="btn-mini-secundario" id="btnCancelarEvento">Cancelar</button>
                </div>
            </div>

            <ul class="lista-igreja" id="eventosList">
                <li class="lista-vazia">Carregando...</li>
            </ul>
        </section>

        <!-- ===================== ABA: PESSOAS ===================== -->
        <section id="tabPessoas" class="tab-panel">
            <h2 class="secao-titulo">Pessoas</h2>

            <div class="segmentado" id="pessoasSegmentado">
                <button type="button" class="segmento is-active" data-tipo="visitante">Visitantes</button>
                <button type="button" class="segmento" data-tipo="conversao">Conversões</button>
                <button type="button" class="segmento" data-tipo="batismo">Batismos</button>
                <button type="button" class="segmento" data-tipo="reconciliacao">Reconciliação</button>
            </div>

            <button type="button" class="btn-add-visitante" id="btnAbrirPessoa">+ Adicionar</button>

            <div class="form-inline-card" id="formPessoa" style="display:none;">
                <input type="text" id="pessoaNome" placeholder="Nome" maxlength="255">
                <input type="date" id="pessoaData">
                <input type="tel" id="pessoaTelefone" placeholder="Telefone (opcional)" style="display:none;">
                <input type="email" id="pessoaEmail" placeholder="E-mail (opcional)" style="display:none;">
                <input type="text" id="pessoaMinistro" placeholder="Ministro responsável (opcional)" style="display:none;">
                <input type="text" id="pessoaLocal" placeholder="Local (opcional)" style="display:none;">
                <textarea id="pessoaObs" placeholder="Observações (opcional)"></textarea>
                <div class="form-inline-acoes">
                    <button type="button" class="btn-mini-primario" id="btnSalvarPessoa">Salvar</button>
                    <button type="button" class="btn-mini-secundario" id="btnCancelarPessoa">Cancelar</button>
                </div>
            </div>

            <ul class="lista-igreja" id="pessoasList">
                <li class="lista-vazia">Selecione um tipo acima</li>
            </ul>
        </section>

        <!-- ===================== ABA: PERFIL ===================== -->
        <section id="tabPerfil" class="tab-panel">
            <h2 class="secao-titulo">Perfil</h2>
            <div class="igreja-card perfil-card">
                <span class="avatar-membro avatar-grande" id="perfilAvatar">--</span>
                <div>
                    <strong><?php echo htmlspecialchars($user_nome); ?></strong>
                    <span><?php echo htmlspecialchars($user_email); ?></span>
                    <span><?php echo htmlspecialchars($igreja_nome); ?></span>
                </div>
            </div>
            <a href="index.php?page=logout" class="btn-sair">🚪 Sair do aplicativo</a>
        </section>

    </main>

    <nav class="igreja-tabbar">
        <button type="button" class="tab-btn is-active" data-tab="Inicio">
            <span class="tab-icon">🏠</span><span>Início</span>
        </button>
        <button type="button" class="tab-btn" data-tab="Eventos">
            <span class="tab-icon">📅</span><span>Eventos</span>
        </button>
        <button type="button" class="tab-btn" data-tab="Pessoas">
            <span class="tab-icon">👥</span><span>Pessoas</span>
        </button>
        <button type="button" class="tab-btn" data-tab="Perfil">
            <span class="tab-icon">👤</span><span>Perfil</span>
        </button>
    </nav>

</div>

<script>
(function() {
    const igrejaId = document.getElementById('igrejaApp').dataset.igrejaId;

    function saudacaoPeloHorario() {
        const h = new Date().getHours();
        if (h < 12) return 'Bom dia,';
        if (h < 18) return 'Boa tarde,';
        return 'Boa noite,';
    }
    document.getElementById('igrejaSaudacao').textContent = saudacaoPeloHorario();

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

    document.getElementById('perfilAvatar').textContent = iniciaisNome(<?php echo json_encode($user_nome); ?>);

    // ---------- Abas ----------
    document.querySelectorAll('.igreja-tabbar .tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.igreja-tabbar .tab-btn').forEach(b => b.classList.remove('is-active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('is-active'));
            btn.classList.add('is-active');
            document.getElementById('tab' + btn.dataset.tab).classList.add('is-active');

            if (btn.dataset.tab === 'Eventos') carregarEventos();
            if (btn.dataset.tab === 'Pessoas') carregarPessoas();
        });
    });

    // ---------- Início: resumo + próximos eventos ----------
    function carregarResumo() {
        fetch(`api/index.php?acao=relatorio_igreja&id=${igrejaId}&filtro=todos`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'sucesso' && res.dados) {
                    const d = res.dados;
                    document.getElementById('statVisitantes').textContent = d.total_visitantes ?? 0;
                    document.getElementById('statConversoes').textContent = d.total_conversoes ?? 0;
                    document.getElementById('statBatismos').textContent = d.total_batismos ?? 0;
                    document.getElementById('statReconciliacao').textContent = d.total_reconciliacao ?? 0;
                    if (d.igreja && d.igreja.endereco) {
                        document.getElementById('igrejaEnderecoTopo').textContent = d.igreja.endereco;
                    } else {
                        document.getElementById('igrejaEnderecoTopo').textContent = 'Sem endereço cadastrado';
                    }
                }
            })
            .catch(() => showNotification('❌ Falha ao carregar o resumo', 'error'));

        fetch(`api/index.php?acao=listar_eventos_igreja&id=${igrejaId}`)
            .then(r => r.json())
            .then(res => {
                const eventos = (res.status === 'sucesso' && res.dados) ? res.dados : [];
                const hoje = new Date().toISOString().split('T')[0];
                const futuros = eventos.filter(e => e.data_evento >= hoje).slice(0, 5);
                const lista = document.getElementById('proximosEventosList');
                if (futuros.length === 0) {
                    lista.innerHTML = '<li class="lista-vazia">Nenhum evento futuro cadastrado</li>';
                    return;
                }
                lista.innerHTML = futuros.map(e => `
                    <li class="item-igreja">
                        <span class="avatar-membro avatar-evento">📅</span>
                        <div class="item-info">
                            <strong>${e.nome}</strong>
                            <span>${formatarDataLocal(e.data_evento)}${e.localizacao ? ' · ' + e.localizacao : ''}</span>
                        </div>
                    </li>
                `).join('');
            })
            .catch(() => {
                document.getElementById('proximosEventosList').innerHTML = '<li class="lista-vazia" style="color:#dc2626;">❌ Erro ao carregar eventos</li>';
            });
    }

    // ---------- Eventos ----------
    const btnAbrirEvento = document.getElementById('btnAbrirEvento');
    const formEvento = document.getElementById('formEvento');
    btnAbrirEvento.addEventListener('click', () => {
        formEvento.style.display = formEvento.style.display === 'none' ? 'flex' : 'none';
    });
    document.getElementById('btnCancelarEvento').addEventListener('click', () => {
        formEvento.style.display = 'none';
        document.getElementById('eventoNome').value = '';
        document.getElementById('eventoData').value = '';
        document.getElementById('eventoLocal').value = '';
        document.getElementById('eventoDescricao').value = '';
    });

    document.getElementById('btnSalvarEvento').addEventListener('click', () => {
        const nome = document.getElementById('eventoNome').value.trim();
        const data_evento = document.getElementById('eventoData').value;
        if (!nome || !data_evento) {
            showNotification('❌ Informe nome e data do evento', 'error');
            return;
        }
        const payload = {
            igreja_id: igrejaId,
            nome,
            data_evento,
            localizacao: document.getElementById('eventoLocal').value.trim(),
            descricao: document.getElementById('eventoDescricao').value.trim()
        };
        fetch('api/index.php?acao=criar_evento_igreja', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'sucesso') {
                showNotification('✅ Evento salvo!', 'success');
                document.getElementById('btnCancelarEvento').click();
                carregarEventos();
            } else {
                showNotification('❌ ' + (res.mensagem || 'Erro ao salvar'), 'error');
            }
        })
        .catch(() => showNotification('❌ Falha ao salvar. Verifique a conexão.', 'error'));
    });

    let eventosCarregados = false;
    function carregarEventos() {
        fetch(`api/index.php?acao=listar_eventos_igreja&id=${igrejaId}`)
            .then(r => r.json())
            .then(res => {
                const eventos = (res.status === 'sucesso' && res.dados) ? res.dados : [];
                const lista = document.getElementById('eventosList');
                if (eventos.length === 0) {
                    lista.innerHTML = '<li class="lista-vazia">Nenhum evento cadastrado</li>';
                    return;
                }
                lista.innerHTML = eventos.map(e => `
                    <li class="item-igreja" data-id="${e.id}">
                        <span class="avatar-membro avatar-evento">📅</span>
                        <div class="item-info">
                            <strong>${e.nome}</strong>
                            <span>${formatarDataLocal(e.data_evento)}${e.localizacao ? ' · ' + e.localizacao : ''}</span>
                        </div>
                        <button type="button" class="btn-remover" data-id="${e.id}" data-tipo="evento" title="Excluir">✕</button>
                    </li>
                `).join('');
                ativarRemocao();
                eventosCarregados = true;
            })
            .catch(() => {
                document.getElementById('eventosList').innerHTML = '<li class="lista-vazia" style="color:#dc2626;">❌ Erro ao carregar eventos</li>';
            });
    }

    // ---------- Pessoas (visitante / conversao / batismo / reconciliacao) ----------
    const CONFIG_TIPOS = {
        visitante: {
            criar: 'criar_visitante_igreja', listar: 'listar_visitantes_igreja', deletar: 'deletar_visitante_igreja',
            campoData: 'data_visita', icone: '👋', extras: ['telefone', 'email']
        },
        conversao: {
            criar: 'criar_conversao_igreja', listar: 'listar_conversoes_igreja', deletar: 'deletar_conversao_igreja',
            campoData: 'data_conversao', icone: '✝️', extras: []
        },
        batismo: {
            criar: 'criar_batismo_igreja', listar: 'listar_batismos_igreja', deletar: 'deletar_batismo_igreja',
            campoData: 'data_batismo', icone: '💧', extras: ['ministro', 'localizacao']
        },
        reconciliacao: {
            criar: 'criar_reconciliacao_igreja', listar: 'listar_reconciliacao_igreja', deletar: 'deletar_reconciliacao_igreja',
            campoData: 'data_reconciliacao', icone: '🤝', extras: []
        }
    };
    let tipoAtual = 'visitante';

    document.querySelectorAll('#pessoasSegmentado .segmento').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#pessoasSegmentado .segmento').forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            tipoAtual = btn.dataset.tipo;
            document.getElementById('formPessoa').style.display = 'none';
            atualizarCamposFormPessoa();
            carregarPessoas();
        });
    });

    function atualizarCamposFormPessoa() {
        const extras = CONFIG_TIPOS[tipoAtual].extras;
        document.getElementById('pessoaTelefone').style.display = extras.includes('telefone') ? 'block' : 'none';
        document.getElementById('pessoaEmail').style.display = extras.includes('email') ? 'block' : 'none';
        document.getElementById('pessoaMinistro').style.display = extras.includes('ministro') ? 'block' : 'none';
        document.getElementById('pessoaLocal').style.display = extras.includes('localizacao') ? 'block' : 'none';
    }
    atualizarCamposFormPessoa();

    document.getElementById('btnAbrirPessoa').addEventListener('click', () => {
        const form = document.getElementById('formPessoa');
        form.style.display = form.style.display === 'none' ? 'flex' : 'none';
    });
    document.getElementById('btnCancelarPessoa').addEventListener('click', fecharFormPessoa);
    function fecharFormPessoa() {
        document.getElementById('formPessoa').style.display = 'none';
        document.getElementById('pessoaNome').value = '';
        document.getElementById('pessoaData').value = '';
        document.getElementById('pessoaTelefone').value = '';
        document.getElementById('pessoaEmail').value = '';
        document.getElementById('pessoaMinistro').value = '';
        document.getElementById('pessoaLocal').value = '';
        document.getElementById('pessoaObs').value = '';
    }

    document.getElementById('btnSalvarPessoa').addEventListener('click', () => {
        const nome = document.getElementById('pessoaNome').value.trim();
        const data = document.getElementById('pessoaData').value;
        if (!nome || !data) {
            showNotification('❌ Informe nome e data', 'error');
            return;
        }
        const cfg = CONFIG_TIPOS[tipoAtual];
        const payload = {
            igreja_id: igrejaId,
            nome,
            obs: document.getElementById('pessoaObs').value.trim()
        };
        payload[cfg.campoData] = data;
        if (cfg.extras.includes('telefone')) payload.telefone = document.getElementById('pessoaTelefone').value.trim();
        if (cfg.extras.includes('email')) payload.email = document.getElementById('pessoaEmail').value.trim();
        if (cfg.extras.includes('ministro')) payload.ministro = document.getElementById('pessoaMinistro').value.trim();
        if (cfg.extras.includes('localizacao')) payload.localizacao = document.getElementById('pessoaLocal').value.trim();

        fetch(`api/index.php?acao=${cfg.criar}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'sucesso') {
                showNotification('✅ Salvo!', 'success');
                fecharFormPessoa();
                carregarPessoas();
                carregarResumo();
            } else {
                showNotification('❌ ' + (res.mensagem || 'Erro ao salvar'), 'error');
            }
        })
        .catch(() => showNotification('❌ Falha ao salvar. Verifique a conexão.', 'error'));
    });

    function carregarPessoas() {
        const cfg = CONFIG_TIPOS[tipoAtual];
        const lista = document.getElementById('pessoasList');
        lista.innerHTML = '<li class="lista-vazia">Carregando...</li>';

        fetch(`api/index.php?acao=${cfg.listar}&id=${igrejaId}`)
            .then(r => r.json())
            .then(res => {
                const registros = (res.status === 'sucesso' && res.dados) ? res.dados : [];
                if (registros.length === 0) {
                    lista.innerHTML = '<li class="lista-vazia">Nenhum registro ainda</li>';
                    return;
                }
                lista.innerHTML = registros.map(p => {
                    const dataCampo = p[cfg.campoData];
                    const detalhe = [p.telefone, p.ministro, p.localizacao].filter(Boolean).join(' · ');
                    return `
                        <li class="item-igreja" data-id="${p.id}">
                            <span class="avatar-membro">${iniciaisNome(p.nome)}</span>
                            <div class="item-info">
                                <strong>${p.nome}</strong>
                                <span>${formatarDataLocal(dataCampo)}${detalhe ? ' · ' + detalhe : ''}</span>
                            </div>
                            <button type="button" class="btn-remover" data-id="${p.id}" data-tipo="pessoa" title="Excluir">✕</button>
                        </li>
                    `;
                }).join('');
                ativarRemocao();
            })
            .catch(() => {
                lista.innerHTML = '<li class="lista-vazia" style="color:#dc2626;">❌ Erro ao carregar</li>';
            });
    }

    // ---------- Remoção (eventos e pessoas) ----------
    function ativarRemocao() {
        document.querySelectorAll('.btn-remover').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Excluir este registro?')) return;
                const id = this.dataset.id;
                const acao = this.dataset.tipo === 'evento' ? 'deletar_evento_igreja' : CONFIG_TIPOS[tipoAtual].deletar;
                const linha = this.closest('li');

                fetch(`api/index.php?acao=${acao}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'sucesso') {
                        linha.remove();
                        carregarResumo();
                    } else {
                        showNotification('❌ ' + (res.mensagem || 'Erro ao excluir'), 'error');
                    }
                })
                .catch(() => showNotification('❌ Falha ao excluir. Verifique a conexão.', 'error'));
            });
        });
    }

    carregarResumo();
})();
</script>
