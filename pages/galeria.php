<?php
// Obter informações do usuário da sessão
$user_funcao = $_SESSION['user_funcao'] ?? 'membro';
$user_id = $_SESSION['user_id'] ?? 0;

// Definir se pode ver filtro de célula
$pode_filtrar_celula = !in_array($user_funcao, ['lider', 'membro']);
?>

<div class="page-header">
    <h1>📸 Galeria de Reuniões</h1>
    <p>Visualize e gerencie as fotos das reuniões das células</p>
</div>

<div class="galeria-container">
    <!-- Seção de Filtros -->
    <div class="galeria-filters card">
        <h3>🔍 Filtros</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <?php if ($pode_filtrar_celula): ?>
            <div class="form-item">
                <label>Célula:</label>
                <select id="filtroGaleriaCelula" onchange="carregarGaleriaCompleta()">
                    <option value="">Todas as células</option>
                </select>
            </div>
            <?php else: ?>
            <div class="form-item" style="opacity: 0.6;">
                <label>Célula:</label>
                <input type="text" readonly placeholder="Apenas sua célula" style="background-color: #f0f0f0; cursor: not-allowed;">
                <small style="color: #999; display: block; margin-top: 4px;">⚠️ Você vê apenas sua célula</small>
            </div>
            <?php endif; ?>
            
            <div class="form-item">
                <label>Mês/Ano:</label>
                <input type="month" id="filtroGaleriaMes" onchange="carregarGaleriaCompleta()">
            </div>
            
            <div class="form-item">
                <button onclick="limparFiltrosGaleria()" class="btn btn-secondary" style="margin-top: 28px;">🔄 Limpar Filtros</button>
            </div>
            
            <div class="form-item">
                <label>Mês/Ano:</label>
                <input type="month" id="filtroGaleriaMes" onchange="carregarGaleriaCompleta()">
            </div>
            
            <div class="form-item">
                <button onclick="limparFiltrosGaleria()" class="btn btn-secondary" style="margin-top: 28px;">🔄 Limpar Filtros</button>
            </div>
        </div>
    </div>

    <!-- Seção de Upload de Foto -->
    <div class="foto-reuniao-section card">
        <h3>📤 Enviar Foto de Reunião</h3>
        <p>Selecione uma célula e data, depois envie a foto</p>
        
        <div class="foto-upload-horizontal">
            <div class="foto-preview-reuniao" id="fotoPreviewReuniao">
                <div class="foto-placeholder-reuniao">
                    <span>📷</span>
                    <p>Clique para adicionar foto</p>
                </div>
            </div>
            <div class="foto-upload-actions">
                <div class="form-item">
                    <label>Célula:</label>
                    <select id="uploadCelula">
                        <option value="">Selecione uma célula</option>
                    </select>
                </div>
                
                <div class="form-item">
                    <label>Data da Reunião:</label>
                    <input type="date" id="uploadData" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <input type="file" id="foto_reuniao" accept="image/jpeg,image/png,image/gif" multiple style="display: none;">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('foto_reuniao').click()" style="background-color: #007bff !important; color: white !important; border: none !important;">
                    📤 Escolher Foto(s)
                </button>
                <small style="display: block; color: #666; margin: 8px 0;">JPG, PNG ou GIF. Máximo 5MB</small>
                
                <label style="display: block; margin-top: 15px; font-weight: bold; color: #333;">Observações:</label>
                <textarea id="observacoes_reuniao" placeholder="Ex: Ótima participação, novos visitantes, temas abordados..." style="width: 100%; margin-top: 5px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit;" rows="3"></textarea>
                
                <button type="button" class="btn btn-success" onclick="uploadFotoReuniao()" style="margin-top: 15px; width: 100%; padding: 12px; font-size: 16px; background-color: #28a745 !important; color: white !important; border: none !important; font-weight: 600;">
                    💾 Salvar Foto e Observações
                </button>
            </div>
        </div>
    </div>

    <!-- Grid de Galeria -->
    <div class="card">
        <h3>📷 Galeria Completa</h3>
        <div id="galeriaCompletaGrid" class="galeria-reunioes">
            <p style="text-align: center; color: #999; padding: 40px;">⏳ Carregando fotos...</p>
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

    .galeria-container {
        max-width: 1400px;
    }

    .galeria-filters {
        background: #f8f9fc;
        padding: 20px;
        border-radius: 12px;
        border: 2px dashed #4e73df;
        margin-bottom: 30px;
    }

    .galeria-filters h3 {
        margin-top: 0;
        color: #4e73df;
    }

    .form-item {
        margin: 0;
    }

    .form-item label {
        display: block;
        font-weight: 500;
        margin-bottom: 8px;
        color: #333;
    }

    .form-item input,
    .form-item select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: inherit;
    }

    /* Estilos para foto da reunião */
    .foto-reuniao-section {
        background: #f8f9fc;
        padding: 20px;
        border-radius: 12px;
        border: 2px dashed #4e73df;
        margin-bottom: 30px;
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
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .reuniao-item {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
        position: relative;
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
        padding: 12px;
    }

    .reuniao-info .data {
        font-weight: bold;
        color: #4e73df;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .reuniao-info .stats {
        font-size: 12px;
        color: #666;
        line-height: 1.4;
    }

    @media (max-width: 768px) {
        .foto-upload-horizontal {
            flex-direction: column;
        }

        .foto-preview-reuniao {
            width: 100%;
            height: auto;
            min-height: 200px;
        }

        .galeria-reunioes {
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
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

    // Variável global para controlar qual foto está sendo deletada
    let fotoEmDelecao = null;

    // Preview das fotos antes de subir
    document.getElementById('foto_reuniao')?.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        if (files.length === 0) return;

        const previewDiv = document.getElementById('fotoPreviewReuniao');
        previewDiv.innerHTML = ''; // Limpar previews anteriores
        
        let fotosValidas = 0;

        files.forEach((file, index) => {
            // Validar tipo
            if (!file.type.match('image/jpeg') && !file.type.match('image/png') && !file.type.match('image/gif')) {
                console.warn(`❌ ${file.name}: Apenas JPG, PNG ou GIF são permitidas!`);
                return;
            }

            // Validar tamanho (5MB)
            if (file.size > 5 * 1024 * 1024) {
                console.warn(`❌ ${file.name}: Máximo 5MB`);
                return;
            }

            fotosValidas++;

            // Mostrar preview
            const reader = new FileReader();
            reader.onload = function(event) {
                const previewContainer = document.createElement('div');
                previewContainer.style.cssText = `
                    display: inline-block;
                    margin: 5px;
                    position: relative;
                    width: 80px;
                    height: 80px;
                    border-radius: 4px;
                    overflow: hidden;
                `;
                previewContainer.innerHTML = `<img src="${event.target.result}" alt="Preview ${index + 1}" style="width: 100%; height: 100%; object-fit: cover;">`;
                previewDiv.appendChild(previewContainer);
            };
            reader.readAsDataURL(file);
        });

        if (fotosValidas > 0) {
            const countDiv = document.createElement('div');
            countDiv.style.cssText = 'margin-top: 10px; font-size: 12px; color: #666;';
            countDiv.textContent = `📷 ${files.length} foto(s) selecionada(s)`;
            previewDiv.appendChild(countDiv);
        }

        if (fotosValidas === 0) {
            showNotification('❌ Nenhuma foto válida selecionada!', 'error');
            this.value = '';
        }
    });

    // Upload da foto da reunião
    async function uploadFotoReuniao() {
        const celulaId = document.getElementById('uploadCelula').value;
        const dataReuniao = document.getElementById('uploadData').value;
        const fotoInput = document.getElementById('foto_reuniao');
        const observacoes = document.getElementById('observacoes_reuniao').value;

        if (!celulaId) {
            showNotification('❌ Selecione uma célula primeiro!', 'error');
            return;
        }

        if (!dataReuniao) {
            showNotification('❌ Selecione uma data primeiro!', 'error');
            return;
        }

        if (!fotoInput.files || fotoInput.files.length === 0) {
            showNotification('❌ Selecione ao menos uma foto para enviar!', 'error');
            return;
        }

        // Obter token CSRF
        let csrfToken = localStorage.getItem('csrf_token') || '';
        if (!csrfToken) {
            const inputToken = document.querySelector('input[name="csrf_token"]');
            if (inputToken) {
                csrfToken = inputToken.value;
            }
        }

        const totalFotos = fotoInput.files.length;
        let sucessos = 0;
        let erros = 0;

        showNotification(`📤 Enviando ${totalFotos} foto(s)...`, 'info');

        // Loop para fazer upload de cada foto
        for (let i = 0; i < totalFotos; i++) {
            const arquivo = fotoInput.files[i];
            
            // Validar tamanho
            if (arquivo.size > 5 * 1024 * 1024) {
                console.warn(`Arquivo ${arquivo.name} muito grande (${(arquivo.size / 1024 / 1024).toFixed(2)}MB)`);
                erros++;
                continue;
            }

            // Criar FormData para cada arquivo
            const formData = new FormData();
            formData.append('foto', arquivo);
            formData.append('celula_id', celulaId);
            formData.append('data_reuniao', dataReuniao);
            formData.append('observacoes', observacoes);
            if (csrfToken) {
                formData.append('csrf_token', csrfToken);
            }

            try {
                const response = await fetch('api/index.php?acao=criar_reuniao_com_foto', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include' // Enviar cookies da sessão
                });

                const result = await response.json();

                if (result.status === 'sucesso') {
                    console.log(`✅ Foto ${i + 1}/${totalFotos} enviada: ${arquivo.name}`);
                    sucessos++;
                } else {
                    console.error(`❌ Foto ${i + 1} falhou: ${result.mensagem}`);
                    erros++;
                }
            } catch (error) {
                console.error(`❌ Erro ao enviar foto ${i + 1}:`, error);
                erros++;
            }

            // Pequeno delay entre uploads para não sobrecarregar
            await new Promise(resolve => setTimeout(resolve, 500));
        }

        // Mostrar resultado final
        if (sucessos > 0) {
            showNotification(`✅ ${sucessos} foto(s) enviada(s) com sucesso!`, 'success');
        }
        if (erros > 0) {
            showNotification(`⚠️ ${erros} foto(s) não puderam ser enviadas`, 'warning');
        }

        // Limpar formulário
        fotoInput.value = '';
        document.getElementById('observacoes_reuniao').value = '';
        document.getElementById('fotoPreviewReuniao').innerHTML = `
            <div class="foto-placeholder-reuniao">
                <span>📷</span>
                <p>Preview da foto aparecerá aqui</p>
            </div>
        `;

        // Recarregar galeria se houver sucessos
        if (sucessos > 0) {
            setTimeout(() => {
                carregarGaleriaCompleta();
            }, 1000);
        }
    }

    // Carregar células no select de upload
    function carregarCelulasUpload() {
        console.log('🔄 Iniciando carregamento de células...');
        
        fetch('api/index.php?acao=listar_celulas', {
            method: 'GET',
            credentials: 'include' // Enviar cookies da sessão
        })
            .then(response => {
                console.log('📊 Resposta recebida:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('📦 Dados completos de células:', JSON.stringify(data, null, 2));
                
                // Verificar se há erro
                if (data.status !== 'sucesso') {
                    console.error('❌ Erro ao listar células:', data.mensagem);
                    showNotification(`❌ Erro ao carregar células: ${data.mensagem}`, 'error');
                    return;
                }
                
                // Verificar se há dados
                const celulas = data.dados || [];
                
                if (Array.isArray(celulas) && celulas.length > 0) {
                    let html = '<option value="">Selecione uma célula</option>';
                    
                    celulas.forEach((celula, index) => {
                        console.log(`  Célula ${index + 1}: ${celula.nome} (ID: ${celula.id})`);
                        html += `<option value="${celula.id}">${celula.nome} - ${celula.lider_nome || 'Sem líder'}</option>`;
                    });
                    
                    const uploadElement = document.getElementById('uploadCelula');
                    if (uploadElement) {
                        uploadElement.innerHTML = html;
                        console.log(`✅ Upload: ${celulas.length} célula(s) carregada(s)`);
                    } else {
                        console.error('❌ Elemento uploadCelula não encontrado!');
                    }
                    
                    // Também carregar no filtro (se existir)
                    const filtroElement = document.getElementById('filtroGaleriaCelula');
                    if (filtroElement && filtroElement.style.display !== 'none') {
                        filtroElement.innerHTML = '<option value="">Todas as células</option>' + html.slice(html.indexOf('<option'));
                        console.log(`✅ Filtro: ${celulas.length} célula(s) carregada(s)`);
                    } else if (!filtroElement) {
                        console.warn('⚠️ Elemento filtroGaleriaCelula não encontrado');
                    } else {
                        console.log('ℹ️ Elemento filtroGaleriaCelula está oculto (display: none)');
                    }
                } else {
                    console.warn('⚠️ Nenhuma célula retornada ou erro na resposta:', data);
                    console.warn('   Status:', data.status);
                    console.warn('   Dados:', data.dados);
                    console.warn('   É array?', Array.isArray(data.dados));
                    console.warn('   Tem itens?', data.dados ? data.dados.length : 'N/A');
                    
                    const uploadElement = document.getElementById('uploadCelula');
                    if (uploadElement) {
                        uploadElement.innerHTML = '<option value="">Nenhuma célula disponível</option>';
                    }
                }
            })
            .catch(error => {
                console.error('❌ Erro ao carregar células:', error);
                console.error('   Stack:', error.stack);
            });
    }
    

    // Carregar galeria completa
    async function carregarGaleriaCompleta() {
        const grid = document.getElementById('galeriaCompletaGrid');
        
        // Tentar obter célula selecionada (pode não existir para líder/membro)
        const filtroElement = document.getElementById('filtroGaleriaCelula');
        let celulaId = filtroElement ? (filtroElement.value || '') : '';
        
        const mesElement = document.getElementById('filtroGaleriaMes');
        const mes = mesElement ? (mesElement.value || '') : '';
        
        grid.innerHTML = '<p style="text-align:center;color:#4e73df;padding:40px;">⏳ Carregando fotos...</p>';
        
        try {
            let url = 'api/index.php?acao=galeria_completa';
            if (celulaId) url += '&celula_id=' + celulaId;
            if (mes) url += '&mes=' + mes;
            
            console.log('🔍 Carregando galeria:', url);
            const response = await fetch(url, {
                credentials: 'include' // Enviar cookies da sessão
            });
            const result = await response.json();
            
            console.log('📸 Resposta da galeria:', result);
            console.log('   Status:', result.status);
            console.log('   Dados:', result.dados);
            console.log('   É array?', Array.isArray(result.dados));
            console.log('   Tamanho:', result.dados ? result.dados.length : 'N/A');
            
            if (result.status === 'sucesso' && result.dados) {
                if (result.dados.length === 0) {
                    grid.innerHTML = '<p style="text-align:center;color:#999;padding:40px;">📷 Nenhuma reunião encontrada com os filtros selecionados.</p>';
                    return;
                }
                
                let html = '';
                let fotosValidas = 0;
                let fotosInvalidas = 0;
                
                result.dados.forEach((reuniao, index) => {
                    // Apenas mostrar reuniões que têm foto
                    if (!reuniao.foto_url || reuniao.foto_url.trim() === '') {
                        console.warn(`Reunião ${reuniao.id} sem foto_url`);
                        fotosInvalidas++;
                        return;
                    }
                    
                    // Validar que a URL não é vazia ou contém apenas espaços
                    const fotoUrl = reuniao.foto_url.trim();
                    if (fotoUrl.length === 0) {
                        console.warn(`Reunião ${reuniao.id} tem foto_url vazia`);
                        fotosInvalidas++;
                        return;
                    }
                    
                    const data = formatarDataLocal(reuniao.data_reuniao);
                    console.log(`Foto ${index + 1}: ${fotoUrl}`);
                    
                    fotosValidas++;
                    html += `
                        <div class="reuniao-item" style="cursor: pointer; position: relative;">
                            <button type="button" onclick="event.stopPropagation(); deletarFotoReuniao(${reuniao.id})" 
                                    title="Deletar foto" style="position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; z-index: 10; opacity: 0; transition: opacity 0.2s; font-weight: bold;">×</button>
                            <img src="${ieq.escapeHtml(fotoUrl)}" alt="Reunião ${ieq.escapeHtml(data)}" onerror="this.src='assets/img/no-image.png'; console.error('Erro ao carregar: ${ieq.escapeHtml(fotoUrl)}')" loading="lazy" onclick="verDetalhesReuniao(${reuniao.id})" style="width: 100%; height: 150px; object-fit: cover; background-color: #f0f0f0;">
                            <div class="reuniao-info" onclick="verDetalhesReuniao(${reuniao.id})">
                                <div style="font-weight: bold; color: #333; margin-bottom: 5px; font-size: 13px;">${ieq.escapeHtml(reuniao.celula_nome)}</div>
                                <div class="data">${ieq.escapeHtml(data)}</div>
                                <div class="stats">
                                    👥 ${ieq.escapeHtml(reuniao.total_presentes || 0)} presentes | 
                                    👋 ${ieq.escapeHtml(reuniao.total_visitantes || 0)} visitantes
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                console.log(`Fotos válidas: ${fotosValidas}, Inválidas: ${fotosInvalidas}`);
                
                grid.innerHTML = html || '<p style="text-align:center;color:#999;padding:40px;">📷 Nenhuma foto com URL válida encontrada.</p>';
                
                // Mostrar botão de delete ao passar o mouse
                document.querySelectorAll('.reuniao-item').forEach(item => {
                    const btn = item.querySelector('button');
                    if (btn) {
                        item.addEventListener('mouseenter', () => btn.style.opacity = '1');
                        item.addEventListener('mouseleave', () => btn.style.opacity = '0');
                    }
                });
            } else {
                console.error('Erro na resposta da API:', result);
                grid.innerHTML = '<p style="text-align:center;color:#dc3545;padding:40px;">❌ Erro ao carregar galeria</p>';
            }
        } catch (error) {
            console.error('Erro ao carregar galeria completa:', error);
            grid.innerHTML = '<p style="text-align:center;color:#dc3545;padding:40px;">❌ Erro ao carregar galeria. Verifique o console.</p>';
        }
    }

    // Ver detalhes de uma reunião específica
    async function verDetalhesReuniao(reuniaoId) {
        try {
            const response = await fetch(`api/index.php?acao=obter_reuniao&reuniao_id=${reuniaoId}`);
            const result = await response.json();

            if (result.status === 'sucesso' && result.dados) {
                const reuniao = result.dados;
                const data = formatarDataLocal(reuniao.data_reuniao);
                
                // 🔍 Buscar TODAS as fotos da mesma data e célula
                const fotosResponse = await fetch(`api/index.php?acao=listar_fotos_reuniao&celula_id=${reuniao.celula_id}&data=${reuniao.data_reuniao}`);
                const fotosResult = await fotosResponse.json();
                const fotos = (fotosResult.status === 'sucesso' && fotosResult.dados) ? fotosResult.dados : [reuniao];
                
                // Criar modal para mostrar detalhes
                const modal = document.createElement('div');
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.7);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                `;

                // Gerar HTML para as fotos
                let fotosHtml = '';
                if (fotos.length === 1) {
                    fotosHtml = `<img src="${ieq.escapeHtml(fotos[0].foto_url)}" alt="Foto da reunião" style="width: 100%; border-radius: 8px; margin: 15px 0;">`;
                } else {
                    fotosHtml = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0;">
                            ${fotos.map((foto, idx) => `
                                <img src="${ieq.escapeHtml(foto.foto_url)}" alt="Foto da reunião ${idx + 1}" style="width: 100%; border-radius: 8px; object-fit: cover; max-height: 250px;">
                            `).join('')}
                        </div>
                    `;
                }

                modal.innerHTML = `
                    <div style="background: white; padding: 30px; border-radius: 12px; max-width: 700px; max-height: 90vh; overflow-y: auto;">
                        <h3 style="margin-top: 0;">Reunião - ${ieq.escapeHtml(data)} (${fotos.length} foto${fotos.length > 1 ? 's' : ''})</h3>
                        ${fotosHtml}
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;">
                            <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 32px; color: #4e73df;">👥</div>
                                <div style="font-size: 24px; font-weight: bold; color: #4e73df;">${ieq.escapeHtml(reuniao.total_presentes || 0)}</div>
                                <div style="color: #666; font-size: 14px;">Presentes</div>
                            </div>
                            <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 32px; color: #4e73df;">👋</div>
                                <div style="font-size: 24px; font-weight: bold; color: #4e73df;">${ieq.escapeHtml(reuniao.total_visitantes || 0)}</div>
                                <div style="color: #666; font-size: 14px;">Visitantes</div>
                            </div>
                        </div>

                        ${reuniao.observacoes ? `
                            <div style="margin: 15px 0;">
                                <strong>Observações:</strong>
                                <p style="background: #f8f9fc; padding: 10px; border-radius: 8px; margin-top: 5px;">${ieq.escapeHtml(reuniao.observacoes)}</p>
                            </div>
                        ` : ''}

                        <button onclick="this.closest('div[style*=fixed]').remove()" 
                                style="width: 100%; padding: 12px; background: #4e73df; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; margin-top: 15px;">
                            Fechar
                        </button>
                    </div>
                `;

                document.body.appendChild(modal);
                
                // Fechar ao clicar fora
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        modal.remove();
                    }
                });
            }
        } catch (error) {
            console.error('Erro ao carregar detalhes:', error);
            showNotification('❌ Erro ao carregar detalhes da reunião!', 'error');
        }
    }

    // Função para deletar foto de reunião
    function deletarFotoReuniao(reuniaoId) {
        fotoEmDelecao = reuniaoId;
        document.getElementById('modalConfirmarDelecao').style.display = 'flex';
    }
    
    function cancelarDelecao() {
        fotoEmDelecao = null;
        document.getElementById('modalConfirmarDelecao').style.display = 'none';
    }
    
    async function confirmarDelecaoFoto() {
        if (!fotoEmDelecao) return;
        
        const btnConfirmar = document.getElementById('btnConfirmarDelecao');
        btnConfirmar.disabled = true;
        btnConfirmar.textContent = '⏳ Deletando...';

        try {
            const response = await fetch('api/index.php?acao=deletar_presenca', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: fotoEmDelecao
                })
            });

            const result = await response.json();

            if (result.status === 'sucesso') {
                showNotification('✅ Foto deletada com sucesso!', 'success');
                cancelarDelecao();
                carregarGaleriaCompleta();
            } else {
                showNotification('❌ ' + (result.mensagem || 'Erro ao deletar foto'), 'error');
            }
        } catch (error) {
            console.error('Erro ao deletar foto:', error);
            showNotification('❌ Erro ao deletar foto', 'error');
        } finally {
            btnConfirmar.disabled = false;
            btnConfirmar.textContent = 'Deletar';
        }
    }

    // Limpar filtros
    function limparFiltrosGaleria() {
        const filtroElement = document.getElementById('filtroGaleriaCelula');
        if (filtroElement) {
            filtroElement.value = '';
        }
        
        const mesElement = document.getElementById('filtroGaleriaMes');
        if (mesElement) {
            mesElement.value = '';
        }
        
        carregarGaleriaCompleta();
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

    // Inicializar quando a página carregar
    document.addEventListener('DOMContentLoaded', function() {
        carregarCelulasUpload();
        carregarGaleriaCompleta();
    });

    // Fechar modal ao clicar fora
    document.getElementById('modalConfirmarDelecao')?.addEventListener('click', function(e) {
        if (e.target === this) {
            cancelarDelecao();
        }
    });
</script>
