<?php
require_once 'config/permissoes.php';

// Verificar se pode gerenciar aprovações
if (!pode_gerenciar_aprovacoes()) {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
    exit;
}
?>

<style>
    @media (max-width: 768px) {
        .table th,
        .table td {
            padding: 12px 8px !important;
            font-size: 0.8rem;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
            min-height: 45px;
            vertical-align: middle;
        }

        .table th {
            min-height: 50px;
            font-size: 0.75rem;
        }

        .table button {
            min-height: 40px;
            padding: 8px 10px;
            font-size: 0.75rem;
            margin: 4px 2px;
            white-space: nowrap;
        }

        .search-input {
            min-height: 44px;
            font-size: 1rem;
        }

        .badge {
            font-size: 0.7rem;
            padding: 4px 8px;
        }
    }
</style>

<div class="page-header">
    <h1>🔓 Aprovação de Cadastros</h1>
    <p>Gerencie solicitações de cadastro via Google</p>
</div>

<div class="card">
    <div class="card-header">
        <h2>Cadastros Pendentes</h2>
        <span class="badge" id="totalPendentes" style="background: #ff9800; color: white; padding: 5px 10px; border-radius: 20px;">0</span>
    </div>

    <div style="margin-bottom: 20px;">
        <input type="text" id="searchCadastro" placeholder="🔍 Buscar por nome ou email..." class="search-input" style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px;">
    </div>

    <div style="overflow-x: auto;">
        <table class="table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                    <th style="padding: 12px; text-align: left;">Nome</th>
                    <th style="padding: 12px; text-align: left;">Email</th>
                    <th style="padding: 12px; text-align: left;">Data Cadastro</th>
                    <th style="padding: 12px; text-align: left;">Status</th>
                    <th style="padding: 12px; text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody id="cadastrosList">
                <tr><td colspan="5" style="text-align: center; padding: 40px; color: #999;">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Aprovação -->
<div id="modalAprovacao" class="modal-overlay" style="display: none;" onclick="fecharModalAprovacao(event)">
    <div class="modal-content modal-large" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3>Aprovar Cadastro</h3>
            <button class="modal-close" onclick="fecharModalAprovacao()">&times;</button>
        </div>
        <form id="formAprovacao" onsubmit="processarAprovacao(event)">
            <div class="form-group">
                <h4 id="nomeUsuario"></h4>
                <p><strong>Email:</strong> <span id="emailUsuario"></span></p>
                <img id="fotoUsuario" src="" alt="Foto" style="max-width: 150px; max-height: 150px; border-radius: 8px; margin-top: 10px;">
            </div>

            <div class="form-group">
                <label>Funcao *</label>
                <select id="funcaoAprovacao" required>
                    <option value="membro">👤 Membro</option>
                    <option value="lider">⭐ Líder de Célula</option>
                    <option value="supervisor">👔 Supervisor</option>
                    <option value="pastor">✝️ Pastor</option>
                </select>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="fecharModalAprovacao()">Cancelar</button>
                <button type="submit" class="btn btn-success" style="background: #28a745;">Aprovar Cadastro</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Rejeição -->
<div id="modalRejeicao" class="modal-overlay" style="display: none;" onclick="fecharModalRejeicao(event)">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h3>Rejeitar Cadastro</h3>
            <button class="modal-close" onclick="fecharModalRejeicao()">&times;</button>
        </div>
        <form id="formRejeicao" onsubmit="processarRejeicao(event)">
            <div class="form-group">
                <p><strong id="nomeRejeitar"></strong></p>
            </div>

            <div class="form-group">
                <label>Motivo da Rejeição (opcional)</label>
                <textarea id="motivoRejeicao" placeholder="Descreva o motivo da rejeição..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif; resize: vertical; height: 100px;"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="fecharModalRejeicao()">Cancelar</button>
                <button type="submit" class="btn btn-danger" style="background: #dc3545;">Rejeitar</button>
            </div>
        </form>
    </div>
</div>

<style>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }

    .modal-content {
        background: white;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-large {
        max-width: 600px !important;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #eee;
    }

    .modal-close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #999;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        padding: 20px;
        border-top: 1px solid #eee;
    }

    .btn-success {
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-danger {
        background: #dc3545;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .btn-success:hover {
        background: #218838;
    }

    .btn-danger:hover {
        background: #c82333;
    }

    .table {
        width: 100%;
    }

    .table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }

    .badge-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }

    .badge-pendente {
        background: #fff3cd;
        color: #856404;
    }

    .btn-action {
        padding: 6px 12px;
        margin: 2px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
    }

    .btn-approve {
        background: #28a745;
        color: white;
    }

    .btn-approve:hover {
        background: #218838;
    }

    .btn-reject {
        background: #dc3545;
        color: white;
    }

    .btn-reject:hover {
        background: #c82333;
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

    let editandoCadastroId = null;

    function carregarCadastrosPendentes() {
        fetch('/api/aprovacoes.php?acao=listar_cadastros_pendentes', {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => response.json())
        .then(resposta => {
            if (resposta.status === 'sucesso') {
                const cadastros = resposta.dados || [];
                document.getElementById('totalPendentes').textContent = cadastros.length;
                renderizarCadastros(cadastros);
            } else {
                mostrarNotificacao('Erro ao carregar cadastros: ' + resposta.mensagem, 'erro');
            }
        })
        .catch(erro => {
            console.error('Erro ao carregar cadastros:', erro);
            mostrarNotificacao('Erro ao carregar: ' + erro.message, 'erro');
        });
    }

    function renderizarCadastros(cadastros) {
        const tbody = document.getElementById('cadastrosList');
        
        if (cadastros.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px; color: #999;">✅ Nenhum cadastro pendente!</td></tr>';
            return;
        }

        tbody.innerHTML = cadastros.map(cadastro => `
            <tr>
                <td style="padding: 12px;">
                    <strong>${cadastro.nome}</strong>
                </td>
                <td style="padding: 12px;">
                    ${cadastro.email}
                </td>
                <td style="padding: 12px;">
                    ${formatarDataLocal(cadastro.criado_em)}
                </td>
                <td style="padding: 12px;">
                    <span class="badge-status badge-pendente">⏳ Pendente</span>
                </td>
                <td style="padding: 12px; text-align: center;">
                    <button class="btn-action btn-approve" onclick="abrirAprovacao(${cadastro.id}, '${cadastro.nome}', '${cadastro.email}', '${cadastro.foto_url || ''}')">
                        ✅ Aprovar
                    </button>
                    <button class="btn-action btn-reject" onclick="abrirRejeicao(${cadastro.id}, '${cadastro.nome}')">
                        ❌ Rejeitar
                    </button>
                </td>
            </tr>
        `).join('');
    }

    function abrirAprovacao(id, nome, email, foto) {
        editandoCadastroId = id;
        document.getElementById('nomeUsuario').textContent = nome;
        document.getElementById('emailUsuario').textContent = email;
        if (foto) {
            document.getElementById('fotoUsuario').src = foto;
            document.getElementById('fotoUsuario').style.display = 'block';
        } else {
            document.getElementById('fotoUsuario').style.display = 'none';
        }
        document.getElementById('funcaoAprovacao').value = 'membro';
        document.getElementById('modalAprovacao').style.display = 'flex';
    }

    function abrirRejeicao(id, nome) {
        editandoCadastroId = id;
        document.getElementById('nomeRejeitar').textContent = nome;
        document.getElementById('motivoRejeicao').value = '';
        document.getElementById('modalRejeicao').style.display = 'flex';
    }

    function fecharModalAprovacao(event) {
        if (!event || event.target.classList.contains('modal-overlay') || event.type === 'click') {
            document.getElementById('modalAprovacao').style.display = 'none';
            editandoCadastroId = null;
        }
    }

    function fecharModalRejeicao(event) {
        if (!event || event.target.classList.contains('modal-overlay') || event.type === 'click') {
            document.getElementById('modalRejeicao').style.display = 'none';
            editandoCadastroId = null;
        }
    }

    async function processarAprovacao(event) {
        event.preventDefault();
        
        const funcao = document.getElementById('funcaoAprovacao').value;
        
        try {
            const formData = new FormData();
            formData.append('acao', 'aprovar_cadastro');
            formData.append('cadastro_id', editandoCadastroId);
            formData.append('funcao', funcao);
            
            const resposta = await fetch('/api/aprovacoes.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            
            const dados = await resposta.json();

            if (dados.status === 'sucesso') {
                mostrarNotificacao(dados.mensagem, 'sucesso');
                fecharModalAprovacao();
                carregarCadastrosPendentes();
            } else {
                mostrarNotificacao(dados.mensagem, 'erro');
            }
        } catch (erro) {
            console.error('Erro:', erro);
            mostrarNotificacao('Erro: ' + erro.message, 'erro');
        }
    }

    async function processarRejeicao(event) {
        event.preventDefault();
        
        const motivo = document.getElementById('motivoRejeicao').value;
        
        if (!confirm('Tem certeza que deseja rejeitar este cadastro?')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('acao', 'rejeitar_cadastro');
            formData.append('cadastro_id', editandoCadastroId);
            formData.append('motivo', motivo);
            
            const resposta = await fetch('/api/aprovacoes.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            
            const dados = await resposta.json();

            if (dados.status === 'sucesso') {
                mostrarNotificacao(dados.mensagem, 'sucesso');
                fecharModalRejeicao();
                carregarCadastrosPendentes();
            } else {
                mostrarNotificacao(dados.mensagem, 'erro');
            }
        } catch (erro) {
            console.error('Erro:', erro);
            mostrarNotificacao('Erro: ' + erro.message, 'erro');
        }
    }

    // Busca
    document.getElementById('searchCadastro')?.addEventListener('input', function() {
        const busca = this.value.toLowerCase();
        const linhas = document.querySelectorAll('#cadastrosList tr');
        
        linhas.forEach(linha => {
            const texto = linha.textContent.toLowerCase();
            linha.style.display = texto.includes(busca) ? '' : 'none';
        });
    });

    // Carregar cadastros ao iniciar
    carregarCadastrosPendentes();
</script>
