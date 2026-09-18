<?php
require_once 'config/permissoes.php';
?>

<div class="page-header">
    <h1>Configurações</h1>
    <p>Gerencie suas preferências e configurações da igreja</p>
</div>

<div class="config-tabs">
    <div class="tab-buttons">
        <button class="tab-btn active" data-tab="perfil">👤 Perfil</button>
        <button class="tab-btn" data-tab="seguranca">🔒 Segurança</button>
        <?php if (is_admin()): ?>
        <button class="tab-btn" data-tab="igreja">⛪ Igreja</button>
        <button class="tab-btn" data-tab="usuarios">👥 Usuários & Permissões</button>
        <button class="tab-btn" data-tab="notificacoes">🔔 Notificações</button>
        <button class="tab-btn" data-tab="teste">🔧 Teste & Diagnóstico</button>
        <?php endif; ?>
        <button class="tab-btn" data-tab="suporte">💬 Suporte</button>
    </div>

    <!-- ABA PERFIL -->
    <div id="perfil" class="tab-content active">
        <div class="card">
            <h2>Meu Perfil</h2>
            <p class="text-muted">Gerencie suas informações pessoais</p>

            <form id="formPerfil" class="form-group">
                <div class="form-row">
                    <div class="form-item">
                        <label>Nome Completo</label>
                        <input type="text" id="nome" placeholder="Seu nome" value="<?php echo $_SESSION['user_nome'] ?? ''; ?>">
                    </div>
                    <div class="form-item">
                        <label>Email</label>
                        <input type="email" id="email" placeholder="seu@email.com" value="<?php echo $_SESSION['user_email'] ?? ''; ?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-item">
                        <label>Telefone</label>
                        <input type="tel" id="telefone" placeholder="(XX) XXXXX-XXXX">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Salvar Perfil</button>
            </form>
        </div>
    </div>

    <!-- ABA SEGURANÇA -->
    <div id="seguranca" class="tab-content">
        <div class="card">
            <h2>Alterar Senha</h2>
            <p class="text-muted">Mantenha sua conta segura com uma senha forte</p>

            <form id="formAlterarSenha" class="form-group">
                <div class="form-item">
                    <label>Senha Atual *</label>
                    <input type="password" id="senha_atual" placeholder="Digite sua senha atual" required>
                    <small style="color: #666;">Confirme sua senha atual para segurança</small>
                </div>

                <div class="form-row">
                    <div class="form-item">
                        <label>Nova Senha *</label>
                        <input type="password" id="senha_nova" placeholder="Digite a nova senha" required minlength="6">
                        <small style="color: #999;">Mínimo 6 caracteres</small>
                    </div>
                    <div class="form-item">
                        <label>Confirmar Nova Senha *</label>
                        <input type="password" id="confirmar_senha" placeholder="Confirme a nova senha" required minlength="6">
                    </div>
                </div>

                <div class="alert alert-info" style="margin-bottom: 15px; padding: 12px; font-size: 13px; background: #e3f2fd; border-left: 4px solid #2196f3; border-radius: 4px;">
                    ℹ️ Use uma senha forte com números, letras maiúsculas e minúsculas para melhor segurança.
                </div>

                <button type="submit" class="btn btn-primary">Alterar Senha</button>
            </form>
        </div>
    </div>

    <!-- ABA IGREJA -->
    <?php if (is_admin()): ?>
    <div id="igreja" class="tab-content">
        <div class="card">
            <h2>Configurações da Igreja</h2>
            <p class="text-muted">Personalize o aplicativo para sua igreja</p>

            <form id="formIgreja" class="form-group">
                <div class="form-item">
                    <label>Nome da Igreja</label>
                    <input type="text" id="nome_igreja" placeholder="Igreja do Evangelho Quadrangular" value="Igreja do Evangelho Quadrangular">
                </div>

                <div class="form-row">
                    <div class="form-item">
                        <label>Nome da Célula (singular)</label>
                        <input type="text" id="nome_celula_singular" placeholder="Célula" value="Célula">
                    </div>
                    <div class="form-item">
                        <label>Nome da Célula (plural)</label>
                        <input type="text" id="nome_celula_plural" placeholder="Células" value="Células">
                    </div>
                </div>

                <div class="form-item">
                    <label>URL do Logo</label>
                    <input type="url" id="logo_url" placeholder="https://...">>
                </div>

                <div class="form-row">
                    <div class="form-item">
                        <label>Fuso Horário</label>
                        <select id="fuso_horario">
                            <option>Brasília (GMT-3)</option>
                            <option>Manaus (GMT-4)</option>
                            <option>Rio Branco (GMT-5)</option>
                        </select>
                    </div>
                    <div class="form-item">
                        <label>Idioma</label>
                        <select id="idioma">
                            <option selected>Português (Brasil)</option>
                            <option>English</option>
                            <option>Español</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Salvar Configurações</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (is_admin()): ?>
    <!-- ABA USUÁRIOS & PERMISSÕES -->
    <div id="usuarios" class="tab-content">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h2>👥 Gerenciamento de Usuários</h2>
                    <p class="text-muted">Controle de acesso e permissões do sistema</p>
                </div>
                <button class="btn btn-primary" onclick="abrirModalUsuario()">+ Novo Usuário</button>
            </div>

            <!-- Estatísticas -->
            <div class="stats-mini-grid">
                <div class="stat-mini-card">
                    <div class="stat-mini-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">👑</div>
                    <div class="stat-mini-info">
                        <div class="stat-mini-value" id="totalAdmins">0</div>
                        <div class="stat-mini-label">Admins</div>
                    </div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">✝️</div>
                    <div class="stat-mini-info">
                        <div class="stat-mini-value" id="totalPastores">0</div>
                        <div class="stat-mini-label">Pastores</div>
                    </div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">👔</div>
                    <div class="stat-mini-info">
                        <div class="stat-mini-value" id="totalSupervisores">0</div>
                        <div class="stat-mini-label">Supervisores</div>
                    </div>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">⭐</div>
                    <div class="stat-mini-info">
                        <div class="stat-mini-value" id="totalLideres">0</div>
                        <div class="stat-mini-label">Líderes</div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filtros-usuarios" style="margin: 20px 0;">
                <input type="text" id="searchUsuario" placeholder="Buscar usuário..." class="search-input">
                <select id="filterFuncao" class="filter-select">
                    <option value="">Todas as funções</option>
                    <option value="admin">👑 Admin</option>
                    <option value="pastor">✝️ Pastor</option>
                    <option value="supervisor">👔 Supervisor</option>
                    <option value="lider">⭐ Líder</option>
                    <option value="membro">👤 Membro</option>
                </select>
            </div>

            <!-- Lista de Usuários -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Função</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="usuariosList">
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">Carregando usuários...</td>
                    </tr>
                </tbody>
            </table>

            <!-- Informações de Permissões -->
            <div class="alert alert-info" style="margin-top: 20px;">
                <strong>ℹ️ Hierarquia de Permissões:</strong>
                <ul style="margin: 10px 0 0 20px; line-height: 1.8;">
                    <li><strong>👑 Admin:</strong> Acesso total ao sistema, incluindo gerenciamento de usuários</li>
                    <li><strong>✝️ Pastor:</strong> Gerencia todas as células, membros e eventos</li>
                    <li><strong>👔 Supervisor:</strong> Gerencia células específicas sob sua supervisão</li>
                    <li><strong>⭐ Líder:</strong> Gerencia apenas sua própria célula</li>
                    <li><strong>👤 Membro:</strong> Acesso apenas leitura às informações</li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ABA NOTIFICAÇÕES -->
    <?php if (is_admin()): ?>
    <div id="notificacoes" class="tab-content">
        <!-- Seção de Notificações Email -->
        <div class="card">
            <h2>📧 Notificações por Email</h2>
            <p class="text-muted">Configure suas preferências de notificação por email</p>

            <form id="formNotificacoes" class="form-group">
                <div class="checkbox-group">
                    <label class="checkbox-item">
                        <input type="checkbox" id="notif_email" checked>
                        <span>Ativar Notificações por Email</span>
                        <small>Receber atualizações importantes por email</small>
                    </label>
                </div>

                <div class="checkbox-group">
                    <label class="checkbox-item">
                        <input type="checkbox" id="notif_novos_membros" checked>
                        <span>Alertas de Novos Membros</span>
                        <small>Ser notificado quando novos membros se cadastram</small>
                    </label>
                </div>

                <div class="checkbox-group">
                    <label class="checkbox-item">
                        <input type="checkbox" id="notif_relatorios" checked>
                        <span>Lembretes de Relatórios</span>
                        <small>Receber lembretes para enviar relatórios de célula</small>
                    </label>
                </div>

                <div class="checkbox-group">
                    <label class="checkbox-item">
                        <input type="checkbox" id="notif_eventos" checked>
                        <span>Lembretes de Eventos</span>
                        <small>Receber notificações de eventos próximos</small>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Salvar Preferências</button>
            </form>
        </div>

        <!-- Seção de Integração Telegram -->
        <div class="card" style="margin-top: 30px;">
            <h2>🤖 Integração com Telegram Bot</h2>
            <p class="text-muted">Receba notificações críticas via Telegram Bot</p>

            <div style="background: #f0f7ff; border-left: 4px solid #2196f3; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <p><strong>Como configurar:</strong></p>
                <ol style="margin: 10px 0; padding-left: 20px;">
                    <li>Crie um bot no Telegram com <strong>@BotFather</strong></li>
                    <li>Copie o <strong>Token</strong> fornecido</li>
                    <li>Envie uma mensagem para seu bot e obtenha o <strong>Chat ID</strong> usando <code style="background: #d4e8f7; padding: 2px 5px;">https://api.telegram.org/bot[TOKEN]/getUpdates</code></li>
                    <li>Cole ambos os dados abaixo</li>
                </ol>
            </div>

            <form id="formTelegram" class="form-group">
                <div class="form-row">
                    <div class="form-item">
                        <label>Bot Token *</label>
                        <input type="password" id="telegram_bot_token" placeholder="123456:ABC..." required>
                        <small style="color: #999;">Obtido do @BotFather</small>
                    </div>
                    <div class="form-item">
                        <label>Chat ID *</label>
                        <input type="text" id="telegram_chat_id" placeholder="1234567890" required>
                        <small style="color: #999;">ID do chat onde receberá notificações</small>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Salvar Configuração</button>
                    <button type="button" class="btn btn-secondary" id="btnEnviarRelatorio">📊 Enviar Relatório Agora</button>
                </div>

                <div id="telegrarStatus" style="margin-top: 15px; padding: 12px; border-radius: 4px; display: none;"></div>
            </form>

            <!-- Log de Notificações -->
            <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 8px;">
                <h3 style="margin-top: 0;">📋 O que será notificado no Telegram?</h3>
                <ul style="margin: 15px 0; padding-left: 20px;">
                    <li><strong>⚠️ Erros Gerais:</strong> Qualquer erro no sistema será notificado imediatamente</li>
                    <li><strong>🔐 Cadastros Pendentes:</strong> Novos usuários aguardando aprovação</li>
                    <li><strong>📊 Relatório Diário:</strong> Resumo de cadastros e presenças por célula</li>
                    <li><strong>👥 Novas Células/Membros:</strong> Registros criados durante o dia</li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ABA TESTE & DIAGNÓSTICO -->
    <?php if (is_admin()): ?>
    <div id="teste" class="tab-content">
        <div class="card">
            <h2>🔧 Teste & Diagnóstico</h2>
            <p class="text-muted">Ferramentas para validar, testar e diagnosticar problemas do sistema</p>
        </div>

        <!-- Seção: Diagnóstico -->
        <div class="card" style="margin-top: 20px;">
            <h3>📊 Ferramentas de Diagnóstico</h3>
            <p class="text-muted">Verifique o estado geral do sistema e identifique problemas</p>
            
            <div class="test-grid">
                <div class="test-item">
                    <h4>🏥 Diagnóstico Completo</h4>
                    <p>Verifica status geral do sistema, configurações e conectividade</p>
                    <a href="diagnostico.php" class="btn btn-sm btn-primary" target="_blank">Executar</a>
                </div>
                
                <div class="test-item">
                    <h4>🖼️ Diagnóstico Galeria & Membros</h4>
                    <p>Valida dados de galeria, celulas, membros e permissões de líderes</p>
                    <a href="diagnostico_galeria.php" class="btn btn-sm btn-primary" target="_blank">Executar</a>
                </div>
            </div>
        </div>

        <!-- Seção: Testes de Conexão -->
        <div class="card" style="margin-top: 20px;">
            <h3>🔌 Testes de Conexão e Banco de Dados</h3>
            <p class="text-muted">Valide conectividade com serviços e banco de dados</p>
            
            <div class="test-grid">
                <div class="test-item">
                    <h4>🗄️ Conexão com Banco de Dados</h4>
                    <p>Verifica conexão MySQL/SQLite e tabelas do sistema</p>
                    <a href="test_db_connection.php" class="btn btn-sm btn-primary" target="_blank">Teste</a>
                </div>
                
                <div class="test-item">
                    <h4>📧 Teste de Email/SMTP</h4>
                    <p>Valida configuração de SMTP e envio de emails</p>
                    <a href="teste_email_direto.php" class="btn btn-sm btn-primary" target="_blank">Teste</a>
                </div>

                <div class="test-item">
                    <h4>🔐 Teste de Credenciais</h4>
                    <p>Valida credenciais de banco de dados e email</p>
                    <a href="teste_credenciais.php" class="btn btn-sm btn-primary" target="_blank">Teste</a>
                </div>
            </div>
        </div>

        <!-- Seção: Testes de Funcionalidades -->
        <div class="card" style="margin-top: 20px;">
            <h3>✅ Testes de Funcionalidades</h3>
            <p class="text-muted">Valide funcionalidades específicas do sistema</p>
            
            <div class="test-grid">
                <div class="test-item">
                    <h4>🔑 Teste de Recuperação de Senha</h4>
                    <p>Valida fluxo de recuperação e redefinição de senha</p>
                    <a href="test_redefinir.php" class="btn btn-sm btn-primary" target="_blank">Teste</a>
                </div>

                <div class="test-item">
                    <h4>🔑 Debug Recuperação Senha</h4>
                    <p>Detalhes técnicos do processo de redefinição</p>
                    <a href="debug_redefinir.php" class="btn btn-sm btn-primary" target="_blank">Debug</a>
                </div>
            </div>
        </div>

        <!-- Seção: Informações de Estrutura -->
        <div class="card" style="margin-top: 20px;">
            <h3>📋 Informações de Estrutura de Banco de Dados</h3>
            <p class="text-muted">Verifique estruturas de tabelas e colunas</p>
            
            <div class="test-grid">
                <div class="test-item">
                    <h4>👥 Estrutura da Tabela Membros</h4>
                    <p>Lista todas as colunas e tipos de dados</p>
                    <a href="check_membros_columns.php" class="btn btn-sm btn-primary" target="_blank">Verificar</a>
                </div>

                <div class="test-item">
                    <h4>📅 Estrutura da Tabela Reuniões</h4>
                    <p>Lista todas as colunas da tabela reunioes_celula</p>
                    <a href="check_reunioes_columns.php" class="btn btn-sm btn-primary" target="_blank">Verificar</a>
                </div>
            </div>
        </div>

        <!-- Seção: Logs e Relatórios -->
        <div class="card" style="margin-top: 20px;">
            <h3>📖 Logs e Relatórios</h3>
            <p class="text-muted">Acesse logs do sistema e relatórios de erro</p>
            
            <div class="test-grid">
                <div class="test-item">
                    <h4>🚨 Logs de Erro Apache</h4>
                    <p><code style="background: #f0f0f0; padding: 2px 5px; border-radius: 3px;">C:\\xampp02\\apache\\logs\\error.log</code></p>
                    <a href="javascript:alert('Acesse via terminal: Get-Content C:\\xampp02\\apache\\logs\\error.log -Tail 100')" class="btn btn-sm btn-secondary">Ver Caminho</a>
                </div>

                <div class="test-item">
                    <h4>📊 Logs de API</h4>
                    <p><code style="background: #f0f0f0; padding: 2px 5px; border-radius: 3px;">api/error_log</code></p>
                    <a href="javascript:alert('Arquivo relativo: /api/error_log')" class="btn btn-sm btn-secondary">Ver Caminho</a>
                </div>
            </div>
        </div>

        <!-- Seção: Comandos Úteis -->
        <div class="card" style="margin-top: 20px;">
            <h3>⌨️ Comandos Úteis para Terminal</h3>
            <p class="text-muted">Copie e execute estes comandos via PowerShell</p>
            
            <div class="command-section">
                <h4>Verificar últimos erros (<strong>últimas 50 linhas</strong>):</h4>
                <div class="code-block">
                    <code>Get-Content C:\xampp02\apache\logs\error.log -Tail 50 | Select-String -Pattern "500|error" -Context 3</code>
                    <button class="btn-copy" onclick="copiarParaClipboard(this)">📋 Copiar</button>
                </div>
            </div>

            <div class="command-section">
                <h4>Buscar erros de recuperação de senha:</h4>
                <div class="code-block">
                    <code>Get-Content C:\xampp02\apache\logs\error.log -Tail 100 | Select-String -Pattern "redefinir|token|recuperacao"</code>
                    <button class="btn-copy" onclick="copiarParaClipboard(this)">📋 Copiar</button>
                </div>
            </div>

            <div class="command-section">
                <h4>Listar todos os arquivos de teste:</h4>
                <div class="code-block">
                    <code>Get-ChildItem C:\xampp02\htdocs\ieq -Filter *{test,debug,diagnostico,check}*.php -Recurse</code>
                    <button class="btn-copy" onclick="copiarParaClipboard(this)">📋 Copiar</button>
                </div>
            </div>
        </div>

        <!-- Seção: Status do Sistema -->
        <div class="card" style="margin-top: 20px;">
            <h3>⚙️ Informações do Sistema</h3>
            <p class="text-muted">Dados de configuração e status</p>
            
            <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea;">
                <table class="info-table" style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Versão PHP:</strong></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><code><?php echo phpversion(); ?></code></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Driver BD:</strong></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><code><?php echo defined('DB_DRIVER') ? DB_DRIVER : 'Não definido'; ?></code></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Host BD:</strong></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><code><?php echo defined('DB_HOST') ? DB_HOST : 'localhost'; ?></code></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Nome BD:</strong></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><code><?php echo defined('DB_NAME') ? DB_NAME : 'ieq'; ?></code></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Servidor:</strong></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><code><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Apache'; ?></code></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px;"><strong>URL Base:</strong></td>
                        <td style="padding: 8px;"><code><?php echo rtrim($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'], '/'); ?></code></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ABA SUPORTE -->
    <div id="suporte" class="tab-content">
        <div class="card">
            <h2>💬 Suporte e Contato</h2>
            <p class="text-muted">Entre em contato conosco para dúvidas e suporte</p>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px;">
                <!-- Coluna Esquerda - Informações de Contato -->
                <div>
                    <h3 style="margin-bottom: 20px; color: #333;">📋 Informações de Contato</h3>
                    
                    <div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #667eea;">
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: #666; display: block; margin-bottom: 5px;">📧 Email de Suporte</label>
                            <p style="margin: 0; font-size: 16px; color: #333;">
                                <a href="mailto:jhones.mendes.ti@gmail.com" style="color: #667eea; text-decoration: none;">jhones.mendes.ti@gmail.com</a>
                            </p>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: #666; display: block; margin-bottom: 5px;">📱 Telefone</label>
                            <p style="margin: 0; font-size: 16px; color: #333;">
                                <a href="tel:(66)99967-9169" style="color: #667eea; text-decoration: none;">(66) 99967-9169</a>
                            </p>
                        </div>

                        <div>
                            <label style="font-weight: 600; color: #666; display: block; margin-bottom: 5px;">🌐 Site</label>
                            <p style="margin: 0; font-size: 16px; color: #333;">
                                <a href="https://ieq.com" target="_blank" style="color: #667eea; text-decoration: none;">www.jhonescosta.com</a>
                            </p>
                        </div>
                    </div>

                    <div style="background: #f5f5f5; padding: 20px; border-radius: 8px; border-left: 4px solid #ff6b6b;">
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight: 600; color: #666; display: block; margin-bottom: 5px;">⏰ Horário de Atendimento</label>
                            <p style="margin: 5px 0; font-size: 14px; color: #333;">
                                <strong>Segunda à Sexta-feira:</strong> 09:00 - 18:00
                            </p>
                            <p style="margin: 5px 0; font-size: 14px; color: #333;">
                                <strong>Sábados:</strong> 09:00 - 13:00
                            </p>
                            <p style="margin: 5px 0; font-size: 14px; color: #999;">
                                Horário de Brasília (GMT-3)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Coluna Direita - Perguntas Frequentes -->
                <div>
                    <h3 style="margin-bottom: 20px; color: #333;">❓ Perguntas Frequentes</h3>
                    
                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <details style="cursor: pointer;">
                            <summary style="font-weight: 600; color: #333; padding: 10px 0; user-select: none;">Como faço para recuperar minha senha?</summary>
                            <p style="margin: 15px 0 0 0; color: #666; font-size: 14px; line-height: 1.6;">
                                Clique em "Esqueci minha senha" na tela de login e siga as instruções enviadas ao seu email cadastrado.
                            </p>
                        </details>
                    </div>

                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <details style="cursor: pointer;">
                            <summary style="font-weight: 600; color: #333; padding: 10px 0; user-select: none;">Como gerenciar comentários de usuários?</summary>
                            <p style="margin: 15px 0 0 0; color: #666; font-size: 14px; line-height: 1.6;">
                                Na seção de Usuários & Permissões (apenas para Admin), você pode editar as permissões de cada usuário para controlar quais módulos poderá acessar.
                            </p>
                        </details>
                    </div>

                    <div style="background: #f5f5f5; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                        <details style="cursor: pointer;">
                            <summary style="font-weight: 600; color: #333; padding: 10px 0; user-select: none;">Encontrei um erro, como reportar?</summary>
                            <p style="margin: 15px 0 0 0; color: #666; font-size: 14px; line-height: 1.6;">
                                Entre em contato conosco via email (jhones.mendes.ti@gmail.com) ou telefone ((66) 99967-9169) descrevendo o erro em detalhes. Responderemos em até 24 horas.
                            </p>
                        </details>
                    </div>

                    <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;">
                        <p style="margin: 0; font-size: 13px; color: #1565c0; line-height: 1.6;">
                            <strong>💡 Dica:</strong> Na aba de Segurança você pode alterar sua senha a qualquer momento.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .config-tabs {
        margin-top: 30px;
    }

    .tab-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #eee;
    }

    .tab-btn {
        padding: 12px 20px;
        background: none;
        border: none;
        font-size: 14px;
        cursor: pointer;
        color: #666;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }

    .tab-btn.active {
        color: #667eea;
        border-bottom-color: #667eea;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .text-muted {
        color: #999;
        font-size: 14px;
        margin-top: 5px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .checkbox-group {
        margin-bottom: 20px;
        padding: 15px;
        background: #f5f5f5;
        border-radius: 4px;
    }

    .checkbox-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        cursor: pointer;
    }

    .checkbox-item input[type="checkbox"] {
        margin-top: 5px;
    }

    .checkbox-item span {
        font-weight: 500;
    }

    .checkbox-item small {
        display: block;
        color: #999;
        font-weight: normal;
        margin-top: 3px;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .tab-buttons {
            flex-wrap: wrap;
        }

        #suporte > .card > div {
            grid-template-columns: 1fr !important;
        }
    }

    /* Estilos para Suporte */
    details {
        transition: all 0.3s ease;
    }

    details > summary {
        outline: none;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    details > summary:hover {
        color: #667eea !important;
    }

    details[open] > summary {
        color: #667eea;
        margin-bottom: 10px;
    }

    /* Estilos para Teste & Diagnóstico - Design Mobile Moderno */
    .test-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-top: 30px;
        padding: 0;
    }

    .test-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        position: relative;
        transition: all 0.3s ease;
        background: none;
        border: none;
        padding: 0;
        border-radius: 50%;
        aspect-ratio: 1;
    }

    /* Círculo de fundo colorido */
    .test-item::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
        border-radius: 50%;
        z-index: 0;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(26, 188, 156, 0.3);
    }

    .test-item:hover::before {
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(26, 188, 156, 0.5);
    }

    /* Conteúdo do item */
    .test-item h4,
    .test-item p,
    .test-item a {
        position: relative;
        z-index: 1;
    }

    /* Ícone (h4) */
    .test-item h4 {
        margin: 0;
        color: white;
        font-size: 2.5rem;
        line-height: 1;
        margin-bottom: 8px;
        font-weight: 400;
    }

    /* Descrição (p) */
    .test-item p {
        margin: 0;
        color: white;
        font-size: 12px;
        line-height: 1.3;
        font-weight: 500;
        display: none;
    }

    /* Mostrar texto em hover */
    .test-item:hover p {
        display: block;
    }

    /* Botão */
    .test-item a {
        display: none;
        margin-top: 8px;
    }

    .test-item:hover a {
        display: inline-block;
    }

    /* Cores alternadas para cada seção */
    .test-item:nth-child(1)::before {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .test-item:nth-child(2)::before {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .test-item:nth-child(3)::before {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .test-item:nth-child(4)::before {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }

    .test-item:nth-child(5)::before {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }

    .test-item:nth-child(6)::before {
        background: linear-gradient(135deg, #30cfd0 0%, #330867 100%);
    }

    .test-item:nth-child(7)::before {
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    }

    .test-item:nth-child(8)::before {
        background: linear-gradient(135deg, #ff9a56 0%, #ff6a88 100%);
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 11px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.2) !important;
        color: white !important;
        border: 1px solid white !important;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-sm:hover {
        background: rgba(255, 255, 255, 0.4) !important;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
        border: none;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-secondary:hover {
        background: #5a6268;
    }

    /* Responsividade */
    @media (max-width: 1024px) {
        .test-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .test-item h4 {
            font-size: 2rem;
        }
    }

    @media (max-width: 768px) {
        .test-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .test-item h4 {
            font-size: 1.8rem;
        }

        .test-item p {
            font-size: 10px;
        }
    }

    @media (max-width: 480px) {
        .test-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .test-item {
            aspect-ratio: 1;
        }

        .test-item h4 {
            font-size: 1.6rem;
        }

        .test-item p {
            font-size: 9px;
            display: none;
        }
    }


    .command-section {
        margin-bottom: 20px;
        padding: 15px;
        background: #f0f0f0;
        border-radius: 6px;
        border-left: 4px solid #ff9800;
    }

    .command-section h4 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 13px;
    }

    .code-block {
        display: flex;
        align-items: center;
        gap: 10px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 12px;
        font-family: 'Courier New', monospace;
        font-size: 11px;
        color: #333;
        overflow-x: auto;
    }

    .code-block code {
        flex: 1;
        white-space: nowrap;
        overflow-x: auto;
    }

    .btn-copy {
        padding: 6px 10px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        font-size: 11px;
        white-space: nowrap;
        transition: background 0.2s;
    }

    .btn-copy:hover {
        background: #5568d3;
    }

    .info-table code {
        background: #f0f0f0;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 12px;
    }

    .card {
        background: white;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .card h3 {
        margin: 0 0 5px 0;
        color: #333;
        font-size: 16px;
    }

    /* Layout específico para tab "teste" */
    #teste > .card:first-child {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        margin-bottom: 20px;
    }

    #teste > .card:first-child h2 {
        color: white;
        margin: 0;
        font-size: 24px;
    }

    #teste > .card:first-child p {
        color: rgba(255,255,255,0.8);
        margin: 5px 0 0 0;
    }

    /* Esconder títulos e descrições das seções em mobile */
    @media (max-width: 768px) {
        #teste > .card > h3,
        #teste > .card > p:not(.text-muted) {
            display: none;
        }

        #teste > .card {
            padding: 15px;
            border: none;
            background: transparent;
            box-shadow: none;
        }

        #teste > .card:first-child {
            display: block;
            padding: 20px;
            margin-bottom: 30px;
        }
    }


    @media (max-width: 768px) {
        .test-grid {
            grid-template-columns: 1fr;
        }

        .code-block {
            flex-direction: column;
            align-items: flex-start;
        }

        .btn-copy {
            align-self: flex-end;
        }
    }
</style>

<script>
    // Alternar abas
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        });
    });

    // Salvar perfil
    document.getElementById('formPerfil').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('✅ Perfil salvo com sucesso!');
    });

    // Salvar configurações da igreja
    document.getElementById('formIgreja').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('✅ Configurações salvas com sucesso!');
    });

    // Salvar notificações por email
    document.getElementById('formNotificacoes').addEventListener('submit', function(e) {
        e.preventDefault();
        alert('✅ Preferências salvas com sucesso!');
    });

    // ========== CONFIGURAÇÃO TELEGRAM ==========
    
    // Carregar configuração atual ao abrir aba
    document.querySelector('[data-tab="notificacoes"]')?.addEventListener('click', function() {
        carregarConfigTelegram();
    });

    async function carregarConfigTelegram() {
        try {
            const resultado = await fazerRequisicao('obter_config_telegram', 'GET');
            if (resultado.status === 'sucesso' && resultado.dados.configurado) {
                document.getElementById('telegram_chat_id').value = resultado.dados.chat_id_parcial || '';
                mostrarStatusTelegram('✅ Bot configurado e ativo!', 'sucesso');
            }
        } catch (e) {
            console.log('Telegram ainda não configurado');
        }
    }

    // Salvar configuração do Telegram
    document.getElementById('formTelegram').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const bot_token = document.getElementById('telegram_bot_token').value.trim();
        const chat_id = document.getElementById('telegram_chat_id').value.trim();
        
        if (!bot_token || !chat_id) {
            mostrarStatusTelegram('⚠️ Preencha Bot Token e Chat ID', 'erro');
            return;
        }
        
        mostrarStatusTelegram('⏳ Salvando configuração...', 'info');
        
        try {
            const resultado = await fazerRequisicao('configurar_telegram', 'POST', {
                bot_token,
                chat_id
            });
            
            if (resultado.status === 'sucesso') {
                mostrarStatusTelegram('✅ Configuração salva! ' + resultado.mensagem, 'sucesso');
                document.getElementById('formTelegram').reset();
                setTimeout(() => carregarConfigTelegram(), 1500);
            } else {
                mostrarStatusTelegram('❌ Erro: ' + resultado.mensagem, 'erro');
            }
        } catch (erro) {
            mostrarStatusTelegram('❌ Erro ao salvar: ' + erro.message, 'erro');
        }
    });

    // Enviar relatório agora
    document.getElementById('btnEnviarRelatorio').addEventListener('click', async function() {
        mostrarStatusTelegram('📊 Enviando relatório...', 'info');
        
        try {
            const resultado = await fazerRequisicao('relatorio_diario_telegram', 'POST');
            
            if (resultado.status === 'sucesso') {
                mostrarStatusTelegram('✅ Relatório enviado com sucesso!', 'sucesso');
            } else {
                mostrarStatusTelegram('❌ Erro ao enviar: ' + resultado.mensagem, 'erro');
            }
        } catch (erro) {
            mostrarStatusTelegram('❌ Erro: ' + erro.message, 'erro');
        }
    });

    function mostrarStatusTelegram(mensagem, tipo) {
        const elemento = document.getElementById('telegrarStatus');
        elemento.textContent = mensagem;
        elemento.style.display = 'block';
        
        const cores = {
            'sucesso': '#d4edda',
            'erro': '#f8d7da',
            'info': '#d1ecf1',
            'warning': '#fff3cd'
        };
        
        elemento.style.background = cores[tipo] || '#f9f9f9';
        elemento.style.borderLeft = `4px solid ${tipo === 'sucesso' ? '#28a745' : tipo === 'erro' ? '#dc3545' : '#17a2b8'}`;
    }

    <?php if (is_admin()): ?>
    // ========== GERENCIAMENTO DE USUÁRIOS ==========
    let editandoUsuarioId = null;
    let todosUsuarios = [];

    // Carregar usuários ao abrir aba
    document.querySelector('[data-tab="usuarios"]')?.addEventListener('click', function() {
        carregarUsuarios();
    });

    async function carregarUsuarios() {
        try {
            const resposta = await fazerRequisicao('listar_usuarios', 'GET');
            if (resposta.status === 'sucesso') {
                todosUsuarios = resposta.dados;
                console.log('Usuários carregados:', todosUsuarios); // Debug
                atualizarEstatisticas(todosUsuarios);
                renderizarUsuarios(todosUsuarios);
            }
        } catch (erro) {
            mostrarNotificacao('Erro ao carregar usuários: ' + erro, 'erro');
        }
    }

    function atualizarEstatisticas(usuarios) {
        const stats = {
            admin: 0,
            pastor: 0,
            supervisor: 0,
            lider: 0
        };

        usuarios.forEach(u => {
            if (stats.hasOwnProperty(u.funcao)) {
                stats[u.funcao]++;
            }
        });

        document.getElementById('totalAdmins').textContent = stats.admin;
        document.getElementById('totalPastores').textContent = stats.pastor;
        document.getElementById('totalSupervisores').textContent = stats.supervisor;
        document.getElementById('totalLideres').textContent = stats.lider;
    }

    function renderizarUsuarios(usuarios) {
        const tbody = document.getElementById('usuariosList');
        
        if (usuarios.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px;">Nenhum usuário encontrado</td></tr>';
            return;
        }

        const badges = {
            admin: '<span class="badge-funcao badge-admin">👑 Admin</span>',
            pastor: '<span class="badge-funcao badge-pastor">✝️ Pastor</span>',
            supervisor: '<span class="badge-funcao badge-supervisor">👔 Supervisor</span>',
            lider: '<span class="badge-funcao badge-lider">⭐ Líder</span>',
            membro: '<span class="badge-funcao badge-membro">👤 Membro</span>'
        };

        tbody.innerHTML = usuarios.map(usuario => `
            <tr>
                <td>${usuario.nome}</td>
                <td>${usuario.email}</td>
                <td>${badges[usuario.funcao] || usuario.funcao}</td>
                <td><span class="badge badge-success">Ativo</span></td>
                <td>
                    <button class="btn-action btn-edit" onclick="editarUsuario(${usuario.id})" title="Editar">
                        ✏️
                    </button>
                    <button class="btn-action btn-delete" onclick="confirmarExcluirUsuario(${usuario.id}, '${usuario.nome}')" title="Excluir">
                        🗑️
                    </button>
                </td>
            </tr>
        `).join('');
    }

    // Filtros
    document.getElementById('searchUsuario')?.addEventListener('input', filtrarUsuarios);
    document.getElementById('filterFuncao')?.addEventListener('change', filtrarUsuarios);

    function filtrarUsuarios() {
        const busca = document.getElementById('searchUsuario').value.toLowerCase();
        const funcao = document.getElementById('filterFuncao').value;

        const filtrados = todosUsuarios.filter(u => {
            const matchBusca = u.nome.toLowerCase().includes(busca) || u.email.toLowerCase().includes(busca);
            const matchFuncao = !funcao || u.funcao === funcao;
            return matchBusca && matchFuncao;
        });

        renderizarUsuarios(filtrados);
    }

    function abrirModalUsuario(id = null) {
        editandoUsuarioId = id;
        
        const titulo = id ? 'Editar Usuário' : 'Novo Usuário';
        const senhaObrigatoria = id ? '' : 'required';
        const senhaHelp = id ? '<small style="color: #999;">Deixe em branco para manter a senha atual</small>' : '';
        
        let usuario = { nome: '', email: '', funcao: 'membro', permissoes: [] };
        if (id) {
            usuario = todosUsuarios.find(u => u.id == id) || usuario;
            
            console.log('Usuário encontrado:', usuario); // Debug
            console.log('Permissões antes do parse:', usuario.permissoes); // Debug
            
            if (usuario.permissoes) {
                try {
                    usuario.permissoes = typeof usuario.permissoes === 'string' ? JSON.parse(usuario.permissoes) : usuario.permissoes;
                } catch (e) {
                    console.error('Erro ao fazer parse das permissões:', e); // Debug
                    usuario.permissoes = [];
                }
            }
            
            console.log('Permissões após parse:', usuario.permissoes); // Debug
        }

        // Módulos disponíveis
        const modulos = {
            'dashboard': '📊 Dashboard',
            'igrejas': '⛪ Igrejas',
            'membros': '👥 Membros',
            'celulas': '📍 Células',
            'presenca': '✓ Presença',
            'galeria': '📸 Galeria',
            'visitantes': '👥 Visitantes',
            'cursos': '📚 Cursos',
            'eventos': '🎉 Eventos',
            'novo_convertido': '✨ Novo Convertido',
            'aprovacao_cadastros': '🔓 Aprovação de Cadastros',
            'configuracoes': '⚙️ Configurações'
        };

        const permissoesChecked = usuario.permissoes || [];
        const isAdmin = usuario.funcao === 'admin';
        const modulosHtml = Object.entries(modulos).map(([key, label]) => {
            const checked = isAdmin || permissoesChecked.includes('*') || permissoesChecked.includes(key) ? 'checked' : '';
            return `
                <label class="checkbox-permissao">
                    <input type="checkbox" name="permissoes[]" value="${key}" ${checked} ${isAdmin ? 'disabled' : ''}>
                    <span>${label}</span>
                </label>
            `;
        }).join('');

        const html = `
            <div class="modal-overlay" id="modalUsuario" onclick="fecharModal(event)">
                <div class="modal-content modal-large" onclick="event.stopPropagation()">
                    <div class="modal-header">
                        <h3>${titulo}</h3>
                        <button class="modal-close" onclick="fecharModal()">&times;</button>
                    </div>
                    <form id="formUsuario" onsubmit="salvarUsuario(event)">
                        <div class="form-row-2">
                            <div class="form-group">
                                <label>Nome Completo *</label>
                                <input type="text" name="nome" value="${usuario.nome}" required>
                            </div>
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" value="${usuario.email}" required ${id ? 'readonly' : ''}>
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label>Senha ${id ? '' : '*'}</label>
                                <input type="password" name="senha" ${senhaObrigatoria} minlength="6">
                                ${senhaHelp}
                            </div>
                            <div class="form-group">
                                <label>Função *</label>
                                <select name="funcao" required onchange="togglePermissoes(this.value)">
                                    <option value="membro" ${usuario.funcao === 'membro' ? 'selected' : ''}>👤 Membro</option>
                                    <option value="lider" ${usuario.funcao === 'lider' ? 'selected' : ''}>⭐ Líder</option>
                                    <option value="supervisor" ${usuario.funcao === 'supervisor' ? 'selected' : ''}>👔 Supervisor</option>
                                    <option value="pastor" ${usuario.funcao === 'pastor' ? 'selected' : ''}>✝️ Pastor</option>
                                    <option value="admin" ${usuario.funcao === 'admin' ? 'selected' : ''}>👑 Admin</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group" id="permissoesGroup" style="${isAdmin ? 'display:none;' : ''}">
                            <label style="font-weight: bold; margin-bottom: 10px; display: block;">Permissões de Acesso</label>
                            <div class="alert alert-info" style="margin-bottom: 15px; padding: 10px; font-size: 13px;">
                                ℹ️ Selecione as telas que este usuário poderá acessar. Admins têm acesso automático a tudo.
                            </div>
                            <div class="permissoes-grid">
                                ${modulosHtml}
                            </div>
                            <label class="checkbox-permissao" style="margin-top: 10px; border-top: 2px solid #eee; padding-top: 10px;">
                                <input type="checkbox" id="selecionarTodos" onchange="toggleTodasPermissoes(this.checked)" ${isAdmin || permissoesChecked.includes('*') ? 'checked' : ''}>
                                <span style="font-weight: bold;">✓ Selecionar Todas</span>
                            </label>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);
    }

    function togglePermissoes(funcao) {
        const permissoesGroup = document.getElementById('permissoesGroup');
        const checkboxes = document.querySelectorAll('input[name="permissoes[]"]');
        const selecionarTodos = document.getElementById('selecionarTodos');
        
        if (funcao === 'admin') {
            permissoesGroup.style.display = 'none';
            checkboxes.forEach(cb => {
                cb.checked = true;
                cb.disabled = true;
            });
            if (selecionarTodos) selecionarTodos.checked = true;
        } else {
            permissoesGroup.style.display = 'block';
            checkboxes.forEach(cb => cb.disabled = false);
        }
    }

    function toggleTodasPermissoes(checked) {
        const checkboxes = document.querySelectorAll('input[name="permissoes[]"]');
        checkboxes.forEach(cb => cb.checked = checked);
    }

    function fecharModal(event) {
        if (!event || event.target.classList.contains('modal-overlay') || event.type === 'click') {
            const modal = document.getElementById('modalUsuario');
            if (modal) modal.remove();
        }
    }

    async function salvarUsuario(event) {
        event.preventDefault();
        const form = event.target;
        const dados = {
            nome: form.nome.value,
            email: form.email.value,
            funcao: form.funcao.value
        };

        if (form.senha.value) {
            dados.senha = form.senha.value;
        }

        // Coletar permissões selecionadas
        const checkboxes = form.querySelectorAll('input[name="permissoes[]"]:checked');
        const permissoesSelecionadas = Array.from(checkboxes).map(cb => cb.value);

        console.log('Checkboxes encontrados:', form.querySelectorAll('input[name="permissoes[]"]').length); // Debug
        console.log('Checkboxes marcados:', checkboxes.length); // Debug
        console.log('Permissões selecionadas:', permissoesSelecionadas); // Debug

        // Se admin ou todas selecionadas, enviar '*'
        if (dados.funcao === 'admin' || permissoesSelecionadas.length === 12) {
            dados.permissoes = ['*'];
            console.log('Definindo permissões como [*]'); // Debug
        } else {
            dados.permissoes = permissoesSelecionadas;
            console.log('Definindo permissões específicas:', permissoesSelecionadas); // Debug
        }

        try {
            const acao = editandoUsuarioId ? 'atualizar_usuario' : 'criar_usuario';
            if (editandoUsuarioId) {
                dados.id = editandoUsuarioId;
            }

            console.log('Enviando dados:', dados); // Debug

            const resposta = await fazerRequisicao(acao, 'POST', dados);
            
            console.log('Resposta recebida:', resposta); // Debug
            
            if (resposta.status === 'sucesso') {
                mostrarNotificacao(resposta.mensagem, 'sucesso');
                fecharModal();
                carregarUsuarios();
            } else {
                mostrarNotificacao(resposta.mensagem, 'erro');
            }
        } catch (erro) {
            console.error('Erro capturado:', erro); // Debug
            mostrarNotificacao('Erro ao salvar usuário: ' + erro, 'erro');
        }
    }

    function editarUsuario(id) {
        abrirModalUsuario(id);
    }

    function confirmarExcluirUsuario(id, nome) {
        if (confirm(`⚠️ Tem certeza que deseja excluir o usuário "${nome}"?\n\nEsta ação não pode ser desfeita.`)) {
            excluirUsuario(id);
        }
    }

    async function excluirUsuario(id) {
        try {
            const resposta = await fazerRequisicao('deletar_usuario', 'POST', { id });
            
            if (resposta.status === 'sucesso') {
                mostrarNotificacao(resposta.mensagem, 'sucesso');
                carregarUsuarios();
            } else {
                mostrarNotificacao(resposta.mensagem, 'erro');
            }
        } catch (erro) {
            mostrarNotificacao('Erro ao excluir usuário: ' + erro, 'erro');
        }
    }
    <?php endif; ?>
</script>

<style>
    /* Estilos adicionais para usuários */
    .stats-mini-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-mini-card {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .stat-mini-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 24px;
    }

    .stat-mini-value {
        font-size: 32px;
        font-weight: bold;
        color: #333;
    }

    .stat-mini-label {
        font-size: 14px;
        color: #999;
    }

    .search-input, .filter-select {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .search-input {
        width: 300px;
        margin-right: 10px;
    }

    .badge-funcao {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
    }

    .badge-admin {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .badge-pastor {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }

    .badge-supervisor {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }

    .badge-lider {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        color: white;
    }

    .badge-membro {
        background: #e0e0e0;
        color: #666;
    }

    .alert-info {
        background: #e3f2fd;
        border-left: 4px solid #2196f3;
        padding: 15px;
        border-radius: 4px;
    }

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
        max-width: 700px !important;
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

    .form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .permissoes-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        max-height: 300px;
        overflow-y: auto;
    }

    .checkbox-permissao {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        border: 2px solid transparent;
    }

    .checkbox-permissao:hover {
        border-color: #667eea;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
    }

    .checkbox-permissao input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }

    .checkbox-permissao span {
        font-size: 14px;
        color: #333;
        user-select: none;
    }

    .checkbox-permissao input[type="checkbox"]:checked + span {
        font-weight: 600;
        color: #667eea;
    }

    @media (max-width: 768px) {
        .form-row-2 {
            grid-template-columns: 1fr;
        }
        
        .permissoes-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    // ========== ALTERAR SENHA ==========
    document.getElementById('formAlterarSenha').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const senhaAtual = document.getElementById('senha_atual').value;
        const senhaNova = document.getElementById('senha_nova').value;
        const confirmarSenha = document.getElementById('confirmar_senha').value;
        
        // Validações no cliente
        if (senhaNova.length < 6) {
            mostrarNotificacao('A nova senha deve ter no mínimo 6 caracteres', 'erro');
            return;
        }
        
        if (senhaNova !== confirmarSenha) {
            mostrarNotificacao('As senhas não coincidem', 'erro');
            return;
        }
        
        if (senhaAtual === senhaNova) {
            mostrarNotificacao('A nova senha deve ser diferente da senha atual', 'erro');
            return;
        }
        
        try {
            const resposta = await fazerRequisicao('alterar_senha', 'POST', {
                senha_atual: senhaAtual,
                senha_nova: senhaNova,
                confirmar_senha: confirmarSenha
            });
            
            if (resposta.status === 'sucesso') {
                mostrarNotificacao(resposta.mensagem, 'sucesso');
                // Limpar formulário
                document.getElementById('formAlterarSenha').reset();
            } else {
                mostrarNotificacao(resposta.mensagem, 'erro');
            }
        } catch (erro) {
            mostrarNotificacao('Erro ao alterar senha: ' + erro, 'erro');
        }
    });

    // Função para copiar comando para clipboard
    function copiarParaClipboard(btn) {
        const codeBlock = btn.parentElement;
        const comando = codeBlock.querySelector('code').textContent;
        
        navigator.clipboard.writeText(comando).then(() => {
            const textOriginal = btn.textContent;
            btn.textContent = '✅ Copiado!';
            btn.style.background = '#4caf50';
            
            setTimeout(() => {
                btn.textContent = textOriginal;
                btn.style.background = '';
            }, 2000);
        }).catch(err => {
            alert('Erro ao copiar: ' + err);
        });
    }
</script>
