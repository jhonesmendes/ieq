<?php
require_once 'config/dashboard.php';
require_once 'config/eventos.php';

$user_id = $_SESSION['user_id'];
$user_funcao = $_SESSION['user_funcao'];
$user_nome = $_SESSION['user_nome'];

// Buscar dados conforme hierarquia
try {
    $total_membros = get_total_membros_visiveis($user_id, $user_funcao);
    $total_celulas = get_total_celulas_visiveis($user_id, $user_funcao);
    $total_convertidos = get_total_convertidos_visiveis($user_id, $user_funcao);
    $celulas_visiveis = get_celulas_visiveis($user_id, $user_funcao);
    $minha_celula = get_celula_usuario($user_id);
    $info_celula = get_info_celula_card($minha_celula);

    // Buscar próximos eventos
    $proximos_eventos = listar_proximos_eventos(3);

    // Buscar estatísticas detalhadas para líder
    $stats_lider = null;
    $aniversariantes_lider = [];
    if ($user_funcao === 'lider') {
        $stats_lider = get_estatisticas_celula_lider($user_id);
        $aniversariantes_lider = get_aniversariantes_mes_celula($user_id);
    }
} catch (Exception $e) {
    error_log("Erro ao carregar dados do dashboard: " . $e->getMessage());
    // Definir valores padrão para evitar erros
    $total_membros = 0;
    $total_celulas = 0;
    $total_convertidos = 0;
    $celulas_visiveis = [];
    $minha_celula = null;
    $info_celula = null;
    $proximos_eventos = [];
    $stats_lider = null;
    $aniversariantes_lider = [];
}

// Definir saudação por horário - será atualizada por JavaScript do cliente
$hora = (int)date('H');
if ($hora >= 5 && $hora < 12) {
    $saudacao = 'Bom dia';
} elseif ($hora >= 12 && $hora < 18) {
    $saudacao = 'Boa tarde';
} else {
    $saudacao = 'Boa noite';
}

// Mensagens personalizadas por função
$mensagens = [
    'admin' => 'Gerencie todas as células e acompanhe o crescimento da igreja.',
    'pastor' => 'Acompanhe o crescimento da igreja e supervisione todas as células.',
    'supervisor' => 'Monitore as células sob sua supervisão e apoie os líderes.',
    'lider' => 'Cuide bem da sua célula e acompanhe o crescimento dos membros.',
    'membro' => 'Bem-vindo! Veja as informações da sua célula e próximos eventos.'
];

$mensagem = $mensagens[$user_funcao] ?? $mensagens['membro'];
?>

<!-- Header Principal -->
<div class="dashboard-header">
    <div class="header-badge">
        <?php 
        $badges = [
            'admin' => '👑 Administrador',
            'pastor' => '🏛️ Pastor',
            'supervisor' => '⭐ Supervisor',
            'lider' => '📍 Líder de Célula',
            'membro' => '👤 Membro'
        ];
        echo $badges[$user_funcao] ?? '👤 Membro';
        ?>
    </div>
    <h1><?php echo $saudacao; ?>, <?php echo htmlspecialchars($user_nome); ?>!</h1>
    <p><?php echo $mensagem; ?></p>
</div>

<!-- NOVO: Menu de Navegação em Cards Grid -->
<div class="navigation-menu-section">
    <h2 class="section-title">🗂️ Módulos Disponíveis</h2>
    <div class="navigation-grid">
        <?php
        // Definir todos os módulos disponíveis com seus ícones, nomes e URLs
        $modulos_menu = [
            [
                'modulo' => 'dashboard',
                'icon' => '📊',
                'nome' => 'Dashboard',
                'url' => 'index.php?page=dashboard',
                'cor' => 1
            ],
            [
                'modulo' => 'membros',
                'icon' => '👥',
                'nome' => 'Membros',
                'url' => 'index.php?page=membros',
                'cor' => 2
            ],
            [
                'modulo' => 'celulas',
                'icon' => '🏠',
                'nome' => 'Células',
                'url' => 'index.php?page=celulas',
                'cor' => 3
            ],
            [
                'modulo' => 'visitantes',
                'icon' => '👋',
                'nome' => 'Visitantes',
                'url' => 'index.php?page=visitantes',
                'cor' => 4
            ],
            [
                'modulo' => 'presenca',
                'icon' => '✓',
                'nome' => 'Presença',
                'url' => 'index.php?page=presenca',
                'cor' => 5
            ],
            [
                'modulo' => 'eventos',
                'icon' => '📅',
                'nome' => 'Eventos',
                'url' => 'index.php?page=eventos',
                'cor' => 6
            ],
            [
                'modulo' => 'cursos',
                'icon' => '🎓',
                'nome' => 'Cursos',
                'url' => 'index.php?page=cursos',
                'cor' => 7
            ],
            [
                'modulo' => 'galeria',
                'icon' => '📸',
                'nome' => 'Galeria',
                'url' => 'index.php?page=galeria',
                'cor' => 8
            ],
            [
                'modulo' => 'igrejas',
                'icon' => '⛪',
                'nome' => 'Igrejas',
                'url' => 'index.php?page=igrejas',
                'cor' => 9
            ],
            [
                'modulo' => 'usuarios',
                'icon' => '🛡️',
                'nome' => 'Usuários',
                'url' => 'index.php?page=usuarios',
                'cor' => 10
            ],
            [
                'modulo' => 'aprovacao_cadastros',
                'icon' => '🔓',
                'nome' => 'Aprovação',
                'url' => 'index.php?page=aprovacao_cadastros',
                'cor' => 11
            ],
            [
                'modulo' => 'configuracoes',
                'icon' => '⚙️',
                'nome' => 'Configurações',
                'url' => 'index.php?page=configuracoes',
                'cor' => 12
            ]
        ];
        
        // Renderizar apenas os módulos que o usuário pode acessar
        foreach ($modulos_menu as $mod) {
            if (pode_acessar_modulo($mod['modulo'])) {
                $classe_cor = "nav-card-cor-" . $mod['cor'];
                echo '
                <a href="' . $mod['url'] . '" class="nav-card ' . $classe_cor . '" title="' . $mod['nome'] . '">
                    <div class="nav-card-icon">' . $mod['icon'] . '</div>
                    <div class="nav-card-name">' . $mod['nome'] . '</div>
                </a>';
            }
        }
        ?>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="stats-grid">
    <?php if ($user_funcao !== 'membro'): ?>
    <!-- Card de Membros -->
    <div class="stat-card stat-members">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $total_membros; ?></div>
            <div class="stat-label">
                <?php 
                echo $user_funcao === 'lider' ? 'Membros da Célula' : 'Membros';
                ?>
            </div>
        </div>
    </div>
    
    <!-- Card de Células -->
    <div class="stat-card stat-cells">
        <div class="stat-icon">📍</div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $total_celulas; ?></div>
            <div class="stat-label">
                <?php 
                if ($user_funcao === 'lider') {
                    echo 'Minha Célula';
                } elseif ($user_funcao === 'supervisor') {
                    echo 'Células Supervisionadas';
                } else {
                    echo 'Células Ativas';
                }
                ?>
            </div>
        </div>
    </div>
    
    <!-- Card de Convertidos -->
    <div class="stat-card stat-converts">
        <div class="stat-icon">💖</div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $total_convertidos; ?></div>
            <div class="stat-label">Convertidos (30 dias)</div>
        </div>
    </div>
    
    <?php if (in_array($user_funcao, ['admin', 'pastor'])): ?>
    <!-- Card de Líderes (apenas admin/pastor) -->
    <div class="stat-card stat-leaders">
        <div class="stat-icon">⭐</div>
        <div class="stat-info">
            <div class="stat-number" id="total-lideres">
                <?php 
                $db = getDB();
                $stmt = $db->query("SELECT COUNT(*) FROM usuarios WHERE funcao IN ('lider', 'supervisor')");
                echo $stmt->fetchColumn();
                ?>
            </div>
            <div class="stat-label">Líderes</div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($info_celula): ?>
<!-- Card Informações da Célula -->
<div class="celula-card">
    <div class="celula-card-header">
        <h3>📍 <?php echo htmlspecialchars($info_celula['nome']); ?></h3>
        <?php if ($user_funcao === 'lider'): ?>
        <span class="badge badge-lider">Você é o líder</span>
        <?php endif; ?>
    </div>
    
    <div class="celula-info-grid">
        <div class="celula-info-item">
            <div class="info-icon">⭐</div>
            <div class="info-content">
                <div class="info-label">Líder</div>
                <div class="info-value"><?php echo htmlspecialchars($info_celula['lider']); ?></div>
            </div>
        </div>
        
        <div class="celula-info-item">
            <div class="info-icon">👥</div>
            <div class="info-content">
                <div class="info-label">Membros</div>
                <div class="info-value"><?php echo $info_celula['total_membros']; ?> pessoas</div>
            </div>
        </div>
        
        <div class="celula-info-item">
            <div class="info-icon">📅</div>
            <div class="info-content">
                <div class="info-label">Reunião</div>
                <div class="info-value">
                    <?php echo ucfirst($info_celula['dia_semana']); ?> às <?php echo $info_celula['hora']; ?>
                </div>
            </div>
        </div>
        
        <div class="celula-info-item">
            <div class="info-icon">📍</div>
            <div class="info-content">
                <div class="info-label">Local</div>
                <div class="info-value">
                    <?php 
                    if ($info_celula['endereco']) {
                        echo htmlspecialchars($info_celula['endereco']);
                        if ($info_celula['bairro']) {
                            echo ', ' . htmlspecialchars($info_celula['bairro']);
                        }
                    } else {
                        echo 'Endereço não definido';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($info_celula['proxima_reuniao']): ?>
    <div class="proxima-reuniao">
        <div class="reuniao-badge">🔔 Próxima Reunião</div>
        <div class="reuniao-data">
            <?php 
            $proxima = $info_celula['proxima_reuniao'];
            $hoje = new DateTime();
            $hoje->setTime(0, 0, 0); // Normalizar para meia-noite
            
            $data_reuniao = clone $proxima;
            $data_reuniao->setTime(0, 0, 0); // Normalizar para meia-noite
            
            $diff = $hoje->diff($data_reuniao);
            $dias_diff = (int)$diff->format('%r%a'); // %r para sinal, %a para dias
            
            if ($dias_diff == 0) {
                echo '<strong>Hoje</strong> às ' . $proxima->format('H:i');
            } elseif ($dias_diff == 1) {
                echo '<strong>Amanhã</strong> às ' . $proxima->format('H:i');
            } elseif ($dias_diff > 1) {
                echo $proxima->format('d/m/Y') . ' às ' . $proxima->format('H:i');
                echo ' (' . $dias_diff . ' dias)';
            } else {
                // Data passou
                echo '<span style="color: red;">Passado</span>';
            }
            ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($user_funcao === 'lider'): ?>
<!-- Dashboard Detalhado do Líder -->
<?php 
if ($stats_lider): 
?>
<div class="lider-dashboard">
    <div class="section-header">
        <h2>📊 Estatísticas da Sua Célula</h2>
        <span class="badge badge-info">Últimos 30 dias</span>
    </div>
    
    <div class="stats-grid-mini">
        <div class="mini-stat-card">
            <div class="mini-icon">👥</div>
            <div class="mini-value"><?php echo $stats_lider['total_membros']; ?></div>
            <div class="mini-label">Membros Ativos</div>
        </div>
        
        <div class="mini-stat-card highlight-green">
            <div class="mini-icon">➕</div>
            <div class="mini-value"><?php echo $stats_lider['novos_membros']; ?></div>
            <div class="mini-label">Novos Membros</div>
        </div>
        
        <div class="mini-stat-card highlight-gold">
            <div class="mini-icon">💖</div>
            <div class="mini-value"><?php echo $stats_lider['novos_convertidos']; ?></div>
            <div class="mini-label">Convertidos</div>
        </div>
        
        <div class="mini-stat-card highlight-blue">
            <div class="mini-icon">💧</div>
            <div class="mini-value"><?php echo $stats_lider['batizados']; ?></div>
            <div class="mini-label">Batizados</div>
        </div>
        
        <div class="mini-stat-card">
            <div class="mini-icon">📅</div>
            <div class="mini-value"><?php echo $stats_lider['total_reunioes_mes']; ?></div>
            <div class="mini-label">Reuniões</div>
        </div>
        
        <div class="mini-stat-card">
            <div class="mini-icon">✓</div>
            <div class="mini-value"><?php echo $stats_lider['media_presentes']; ?></div>
            <div class="mini-label">Média Presença</div>
        </div>
        
        <div class="mini-stat-card highlight-purple">
            <div class="mini-icon">🎂</div>
            <div class="mini-value"><?php echo $stats_lider['aniversariantes_mes']; ?></div>
            <div class="mini-label">Aniversariantes</div>
        </div>
        
        <div class="mini-stat-card <?php echo $stats_lider['crescimento_percentual'] > 0 ? 'highlight-green' : ''; ?>">
            <div class="mini-icon">📈</div>
            <div class="mini-value"><?php echo $stats_lider['crescimento_percentual']; ?>%</div>
            <div class="mini-label">Crescimento</div>
        </div>
    </div>
    
    <?php 
    if (!empty($aniversariantes_lider)): 
    ?>
    <div class="aniversariantes-section">
        <h3>🎂 Aniversariantes do Mês</h3>
        <div class="aniversariantes-list">
            <?php foreach ($aniversariantes_lider as $aniv): ?>
            <div class="aniversariante-item">
                <div class="aniv-avatar">
                    <?php 
                    $iniciais = '';
                    $partes = explode(' ', $aniv['nome']);
                    if (count($partes) >= 2) {
                        $iniciais = strtoupper(substr($partes[0], 0, 1) . substr($partes[1], 0, 1));
                    } else {
                        $iniciais = strtoupper(substr($aniv['nome'], 0, 2));
                    }
                    echo htmlspecialchars($iniciais);
                    ?>
                </div>
                <div class="aniv-info">
                    <div class="aniv-name"><?php echo htmlspecialchars($aniv['nome']); ?></div>
                    <div class="aniv-data"><?php echo $aniv['dia_aniversario']; ?> de <?php 
                        $meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                                  'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                        echo $meses[$aniv['mes_aniversario']];
                    ?></div>
                </div>
                <?php if ($aniv['telefone']): ?>
                <a href="tel:<?php echo $aniv['telefone']; ?>" class="btn-contact">📞</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Seção Principal -->
<div class="dashboard-main">
    <?php if ($user_funcao !== 'membro' && $user_funcao !== 'pastor'): ?>
    <!-- Membros Recentes (não mostrar para membro comum e pastor) -->
    <div class="dashboard-section">
        <div class="section-header">
            <h3>👥 Membros Recentes</h3>
            <?php if (in_array($user_funcao, ['admin', 'pastor', 'supervisor'])): ?>
            <a href="index.php?page=membros" class="view-all">Ver todos →</a>
            <?php endif; ?>
        </div>
        <div class="members-list" id="membros-recentes">
            <?php
            $db = getDB();
            
            // Buscar membros conforme hierarquia
            if (in_array($user_funcao, ['admin', 'pastor'])) {
                $stmt = $db->query("
                    SELECT u.nome, u.telefone, m.status 
                    FROM membros m 
                    INNER JOIN usuarios u ON m.usuario_id = u.id 
                    ORDER BY m.criado_em DESC 
                    LIMIT 5
                ");
            } elseif ($user_funcao === 'supervisor') {
                $stmt = $db->prepare("
                    SELECT u.nome, u.telefone, m.status 
                    FROM membros m 
                    INNER JOIN usuarios u ON m.usuario_id = u.id
                    INNER JOIN celulas c ON m.celula_id = c.id
                    WHERE c.supervisor_id = ?
                    ORDER BY m.criado_em DESC 
                    LIMIT 5
                ");
                $stmt->execute([$user_id]);
            } elseif ($user_funcao === 'lider') {
                $stmt = $db->prepare("
                    SELECT u.nome, u.telefone, m.status 
                    FROM membros m 
                    INNER JOIN usuarios u ON m.usuario_id = u.id
                    INNER JOIN celulas c ON m.celula_id = c.id
                    WHERE c.lider_id = ?
                    ORDER BY m.criado_em DESC 
                    LIMIT 5
                ");
                $stmt->execute([$user_id]);
            }
            
            $membros_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($membros_recentes)) {
                echo '<div class="empty-state">📋 Nenhum membro registrado ainda</div>';
            } else {
                foreach ($membros_recentes as $membro) {
                    $iniciais = '';
                    $partes = explode(' ', $membro['nome']);
                    if (count($partes) >= 2) {
                        $iniciais = strtoupper(substr($partes[0], 0, 1) . substr($partes[1], 0, 1));
                    } else {
                        $iniciais = strtoupper(substr($membro['nome'], 0, 2));
                    }
                    
                    echo '<div class="member-item">';
                    echo '<div class="member-avatar">' . htmlspecialchars($iniciais) . '</div>';
                    echo '<div class="member-info">';
                    echo '<div class="member-name">' . htmlspecialchars($membro['nome']) . '</div>';
                    echo '<div class="member-phone">' . htmlspecialchars($membro['telefone'] ?: 'Sem telefone') . '</div>';
                    echo '</div>';
                    echo '<span class="member-badge badge-' . $membro['status'] . '">' . ucfirst($membro['status']) . '</span>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Próximos Eventos -->
    <div class="dashboard-section events-section">
        <div class="section-header">
            <h3>📅 <?php echo $user_funcao === 'membro' ? 'Eventos da Igreja' : 'Próximos Eventos'; ?></h3>
            <?php if (in_array($user_funcao, ['admin', 'pastor'])): ?>
            <a href="index.php?page=eventos" class="view-all">Ver todos →</a>
            <?php endif; ?>
        </div>
        <div class="events-list" id="proximos-eventos">
            <?php 
            if (empty($proximos_eventos)): 
            ?>
                <div class="empty-state">📅 Nenhum evento agendado</div>
            <?php 
            else:
                foreach ($proximos_eventos as $idx => $evento):
                    $data_evento = new DateTime($evento['data_evento']);
                    $hoje = new DateTime();
                    $hoje->setTime(0, 0, 0);
                    
                    $data_evento_normalizada = clone $data_evento;
                    $data_evento_normalizada->setTime(0, 0, 0);
                    
                    $diff = $hoje->diff($data_evento_normalizada);
                    $dias_diff = (int)$diff->format('%r%a');
                    
                    // Badge de tempo
                    if ($dias_diff == 0) {
                        $badge = '🔥 Hoje';
                    } elseif ($dias_diff == 1) {
                        $badge = '⏰ Amanhã';
                    } else {
                        $badge = '🔥 Próximo';
                    }
            ?>
            <div class="event-item">
                <div class="event-date"><?php echo $badge; ?></div>
                <div class="event-info">
                    <div class="event-title"><?php echo htmlspecialchars($evento['nome']); ?></div>
                    <div class="event-details">
                        <div class="event-time">📅 <?php echo $data_evento->format('d M • H:i'); ?></div>
                        <div class="event-location">📍 <?php echo htmlspecialchars($evento['localizacao'] ?: 'Local não definido'); ?></div>
                        <?php if ($evento['descricao']): ?>
                        <div class="event-description"><?php echo htmlspecialchars(substr($evento['descricao'], 0, 60)) . (strlen($evento['descricao']) > 60 ? '...' : ''); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php 
                endforeach;
            endif;
            ?>
        </div>
    </div>
</div>

<!-- Células em Destaque - Apenas para Admin -->
<?php if ($user_funcao === 'admin'): ?>
<div class="dashboard-section cells-section">
    <div class="section-header">
        <h3>📍 Células em Destaque</h3>
        <span class="cells-count">4 células ativas</span>
        <a href="index.php?page=celulas" class="view-all">Ver todas →</a>
    </div>
    
    <div class="cells-grid" id="celulas-destaque">
        <div class="cell-card cell-green">
            <div class="cell-header">
                <div class="cell-icon">📍</div>
                <div class="cell-title">Célula Vida Nova</div>
            </div>
            <div class="cell-category">Mista</div>
            <div class="cell-leader">👤 Carlos Oliveira</div>
            <div class="cell-schedule">🕘 Qua • 19:30</div>
            <div class="cell-location">📍 Pinheiros</div>
            <div class="cell-occupancy">Ocupação <span class="occupancy-count">0/15</span></div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Super Visão de Células - Apenas para Pastor -->
<?php if ($user_funcao === 'pastor'): ?>
<div class="dashboard-section">
    <div class="section-header">
        <h3>� Visão Geral de Células</h3>
        <p style="color: #666; font-size: 14px; margin-top: 5px;">Desempenho e crescimento de todas as suas células</p>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
        <!-- Gráfico de Membros por Célula -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <h4 style="margin: 0 0 15px 0; color: #333;">Membros por Célula</h4>
            <canvas id="chartMembros" height="80"></canvas>
        </div>
        
        <!-- Gráfico de Crescimento -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <h4 style="margin: 0 0 15px 0; color: #333;">Crescimento (30 dias)</h4>
            <canvas id="chartCrescimento" height="80"></canvas>
        </div>
    </div>
    
    <!-- Status das Células -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-top: 20px;">
        <h4 style="margin: 0 0 15px 0; color: #333;">Status de Saúde das Células</h4>
        <div id="celulas-status" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <!-- Preenchido por JavaScript -->
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Buscar dados das células
    fetch('api/index.php?acao=listar_celulas')
        .then(r => r.json())
        .then(json => {
            if (json.status === 'sucesso' && json.dados) {
                const celulas = json.dados;
                
                // Preparar dados para gráficos
                const labels = celulas.map(c => c.nome);
                const membros = celulas.map(c => c.total_membros || 0);
                const crescimento = celulas.map(c => c.novos_30_dias || 0);
                
                // Cores para as barras
                const colors = [
                    '#4e73df', '#858796', '#1cc88a', '#36b9cc',
                    '#f6c23e', '#e74c3c', '#af86c5', '#fd7e14'
                ];
                
                // Gráfico de Membros
                const ctxMembros = document.getElementById('chartMembros').getContext('2d');
                new Chart(ctxMembros, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Membros Ativos',
                            data: membros,
                            backgroundColor: colors,
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
                
                // Gráfico de Crescimento
                const ctxCrescimento = document.getElementById('chartCrescimento').getContext('2d');
                new Chart(ctxCrescimento, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Novos Membros',
                            data: crescimento,
                            backgroundColor: '#1cc88a',
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
                
                // Cards de Status
                const statusDiv = document.getElementById('celulas-status');
                statusDiv.innerHTML = '';
                
                celulas.forEach((cell, idx) => {
                    let status = '✅ Saudável';
                    let bgColor = '#d4edda';
                    let borderColor = '#1cc88a';
                    
                    if (cell.total_membros < 5) {
                        status = '🚨 Crítico';
                        bgColor = '#f8d7da';
                        borderColor = '#e74c3c';
                    } else if (cell.total_membros < 8 || cell.novos_30_dias === 0) {
                        status = '⚠️ Atenção';
                        bgColor = '#fff3cd';
                        borderColor = '#f6c23e';
                    }
                    
                    const card = document.createElement('div');
                    card.style.cssText = `
                        background: ${bgColor};
                        border-left: 4px solid ${borderColor};
                        padding: 15px;
                        border-radius: 6px;
                        font-size: 14px;
                    `;
                    card.innerHTML = `
                        <div style="font-weight: bold; margin-bottom: 8px;">${ieq.escapeHtml(cell.nome)}</div>
                        <div style="font-size: 12px; color: #666;">
                            <div>👥 ${ieq.escapeHtml(cell.total_membros || 0)} membros</div>
                            <div>📈 +${ieq.escapeHtml(cell.novos_30_dias || 0)} nos últimos 30 dias</div>
                            <div style="margin-top: 8px; font-weight: bold; color: #333;">${ieq.escapeHtml(status)}</div>
                        </div>
                    `;
                    statusDiv.appendChild(card);
                });
            }
        })
        .catch(err => console.error('Erro ao carregar células:', err));
});
</script>
<?php endif; ?>

<style>
    /* Header Principal */
    .dashboard-header {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        padding: 40px;
        border-radius: 12px;
        margin-bottom: 30px;
        position: relative;
    }

    .header-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        display: inline-block;
        margin-bottom: 15px;
    }

    .dashboard-header h1 {
        font-size: 36px;
        margin: 10px 0;
        font-weight: 600;
    }

    .dashboard-header p {
        font-size: 16px;
        opacity: 0.9;
        margin-bottom: 25px;
        line-height: 1.5;
    }

    /* ======== NOVO: Navigation Menu Section ======== */
    .navigation-menu-section {
        background: linear-gradient(135deg, #f8fafb 0%, #eef2f5 100%);
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 30px;
        border: 1px solid rgba(78, 115, 223, 0.1);
    }

    .section-title {
        text-align: center;
        font-size: 20px;
        color: #333;
        margin: 0 0 25px 0;
        font-weight: 600;
    }

    .navigation-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 15px;
    }

    .nav-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        aspect-ratio: 1;
        border-radius: 12px;
        text-decoration: none;
        color: white;
        font-weight: 600;
        font-size: 14px;
        padding: 0;
        border: none;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        text-align: center;
        line-height: 1;
        position: relative;
        overflow: hidden;
    }

    .nav-card:hover {
        transform: scale(1.12) translateY(-8px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.25);
        filter: brightness(1.1);
    }

    .nav-card-icon {
        font-size: 32px;
        margin-bottom: 8px;
        display: block;
        animation: bounce 0.6s ease-in-out;
    }

    .nav-card:hover .nav-card-icon {
        animation: bounce 0.6s ease-in-out infinite;
    }

    .nav-card-name {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: block;
    }

    /* Cores dos cards - Gradientes estilo SICOOB */
    .nav-card-cor-1 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .nav-card-cor-2 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .nav-card-cor-3 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .nav-card-cor-4 { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
    .nav-card-cor-5 { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
    .nav-card-cor-6 { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
    .nav-card-cor-7 { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
    .nav-card-cor-8 { background: linear-gradient(135deg, #ff9a56 0%, #ff6a88 100%); }
    .nav-card-cor-9 { background: linear-gradient(135deg, #ffa751 0%, #ffe259 100%); }
    .nav-card-cor-10 { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .nav-card-cor-11 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .nav-card-cor-12 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

    /* Animação de bounce */
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-6px); }
    }

    /* ======== FIM: Navigation Menu Section ======== */

    .action-buttons {
        display: flex;
        gap: 15px;
    }

    .action-buttons .btn {
        padding: 12px 24px;
        border-radius: 8px;
        border: none;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: #f39c12;
        color: white;
    }

    .btn-secondary {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    /* Cards de Estatísticas */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: all 0.3s ease;
        border: 1px solid #f0f0f0;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }

    .stat-icon {
        width: 55px;
        height: 55px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
    }

    .stat-members .stat-icon { background: linear-gradient(135deg, #fff3cd 0%, #ffe69c 100%); }
    .stat-cells .stat-icon { background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%); }
    .stat-converts .stat-icon { background: linear-gradient(135deg, #f8d7da 0%, #f5c2c7 100%); }
    .stat-leaders .stat-icon { background: linear-gradient(135deg, #e7e3ff 0%, #d4c5ff 100%); }

    .stat-number {
        font-size: 28px;
        font-weight: bold;
        color: #333;
        line-height: 1;
    }

    .stat-label {
        color: #666;
        font-size: 14px;
        margin-top: 5px;
    }

    /* Card da Célula */
    .celula-card {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 2px solid #38bdf8;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 12px rgba(56, 189, 248, 0.15);
    }

    .celula-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid rgba(56, 189, 248, 0.3);
    }

    .celula-card-header h3 {
        margin: 0;
        font-size: 22px;
        color: #0369a1;
    }

    .badge-lider {
        background: #22c55e;
        color: white;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    .celula-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .celula-info-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .info-icon {
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .info-content {
        flex: 1;
    }

    .info-label {
        font-size: 12px;
        color: #0369a1;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .info-value {
        font-size: 15px;
        color: #333;
        font-weight: 500;
        line-height: 1.4;
    }

    .proxima-reuniao {
        background: white;
        padding: 15px 20px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        margin-top: 15px;
    }

    .reuniao-badge {
        color: #0369a1;
        font-weight: 600;
        font-size: 14px;
    }

    .reuniao-data {
        color: #333;
        font-size: 15px;
        font-weight: 500;
    }

    .reuniao-data strong {
        color: #dc2626;
        font-size: 16px;
    }

    /* Seções do Dashboard */
    .dashboard-main {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 40px;
    }

    .dashboard-section {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: 1px solid #f0f0f0;
        transition: box-shadow 0.3s ease;
    }

    .dashboard-section:hover {
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
    }

    .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .section-header h3 {
        color: #333;
        font-size: 18px;
        margin: 0;
    }

    .view-all {
        color: #4e73df;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
    }

    /* Lista de Membros */
    .members-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .member-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border-radius: 8px;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .member-item:hover {
        background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
        border-color: #e0e7ff;
        transform: translateX(5px);
    }

    .member-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4e73df 0%, #667eea 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(78, 115, 223, 0.3);
    }

    .member-info {
        flex: 1;
    }

    .member-name {
        font-weight: 500;
        color: #333;
    }

    .member-phone {
        color: #666;
        font-size: 14px;
    }

    .member-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }

    .badge-member { background: #e3f2fd; color: #1976d2; }
    .badge-ativo { background: #e8f5e8; color: #4caf50; }
    .badge-inativo { background: #fce4ec; color: #c2185b; }
    .badge-leader { background: #e8f5e8; color: #4caf50; }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #999;
        font-size: 15px;
    }

    /* Lista de Eventos */
    .events-section {
        grid-column: 2;
        height: fit-content;
    }

    .events-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
        perspective: 1200px;
    }

    .event-item {
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #f39c12;
        background: linear-gradient(135deg, #fff8f0 0%, #fff4e6 100%);
        transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        box-shadow: 0 2px 6px rgba(243, 156, 18, 0.1);
        transform-style: preserve-3d;
        position: relative;
    }

    .event-item:hover {
        transform: rotateX(8deg) rotateY(-8deg) rotateZ(2deg) translateZ(20px);
        box-shadow: 
            0 8px 24px rgba(243, 156, 18, 0.3),
            0 15px 40px rgba(243, 156, 18, 0.2);
    }

    .event-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0) 100%);
        border-radius: 8px;
        opacity: 0;
        transition: opacity 0.6s ease;
        pointer-events: none;
        transform: translateZ(10px);
    }

    .event-item:hover::before {
        opacity: 1;
    }

    .event-date {
        color: #f39c12;
        font-size: 12px;
        font-weight: 500;
        margin-bottom: 8px;
        transition: all 0.6s ease;
        transform: translateZ(5px);
    }

    .event-item:hover .event-date {
        transform: translateZ(15px) rotateX(-5deg);
        text-shadow: 0 2px 4px rgba(243, 156, 18, 0.3);
    }

    .event-title {
        font-weight: 500;
        color: #333;
        margin-bottom: 8px;
        transition: all 0.6s ease;
        transform: translateZ(8px);
    }

    .event-item:hover .event-title {
        transform: translateZ(20px) rotateX(-3deg);
        text-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .event-details {
        display: flex;
        flex-direction: column;
        gap: 4px;
        transform: translateZ(3px);
        transition: all 0.6s ease;
    }

    .event-item:hover .event-details {
        transform: translateZ(15px) rotateX(-2deg);
    }

    .event-time, .event-location {
        font-size: 14px;
        color: #666;
        transition: all 0.6s ease;
    }

    .event-item:hover .event-time,
    .event-item:hover .event-location {
        color: #f39c12;
        transform: translateX(4px);
    }

    /* Células em Destaque */
    .cells-section {
        grid-column: 1 / -1;
    }

    .cells-count {
        color: #666;
        font-size: 14px;
    }

    .cells-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }

    .cell-card {
        padding: 20px;
        border-radius: 12px;
        color: white;
        position: relative;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        cursor: pointer;
    }

    .cell-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25);
    }

    .cell-green { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
    .cell-blue { background: linear-gradient(135deg, #007bff 0%, #17a2b8 100%); }
    .cell-purple { background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%); }
    .cell-orange { background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%); }
    .cell-red { background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); }
    .cell-pink { background: linear-gradient(135deg, #e83e8c 0%, #6f42c1 100%); }

    .cell-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    .cell-icon {
        font-size: 24px;
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
    }

    .cell-title {
        font-size: 18px;
        font-weight: 600;
    }

    .cell-category, .cell-leader, .cell-schedule, .cell-location {
        margin-bottom: 8px;
        opacity: 0.95;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .cell-occupancy {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.3);
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 14px;
    }

    .occupancy-count {
        font-weight: bold;
        font-size: 16px;
        background: rgba(255, 255, 255, 0.2);
        padding: 4px 12px;
        border-radius: 20px;
    }

    /* Responsivo */
    @media (max-width: 1024px) {
        .dashboard-main {
            grid-template-columns: 1fr;
        }
        
        .events-section {
            max-height: none;
        }

        .navigation-grid {
            grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
            gap: 12px;
        }
    }

    @media (max-width: 768px) {
        .dashboard-header {
            padding: 25px;
        }
        
        .dashboard-header h1 {
            font-size: 28px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }

        /* Forçar dashboard-main em 1 coluna no mobile */
        .dashboard-main {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 20px !important;
        }

        .events-section {
            grid-column: auto !important;
            grid-row: auto !important;
            max-height: none !important;
            height: auto !important;
        }

        .navigation-menu-section {
            padding: 20px;
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .navigation-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .nav-card {
            aspect-ratio: 1;
            min-height: 90px;
        }

        .nav-card-icon {
            font-size: 26px;
            margin-bottom: 6px;
        }

        .nav-card-name {
            font-size: 10px;
        }

        .nav-card:hover {
            transform: scale(1.1) translateY(-5px);
        }
    }

    @media (max-width: 480px) {
        .dashboard-header {
            padding: 20px;
        }
        
        .dashboard-header h1 {
            font-size: 22px;
        }

        .dashboard-header p {
            font-size: 14px;
        }

        .navigation-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .nav-card {
            min-height: 70px;
            aspect-ratio: 1;
            font-size: 12px;
        }

        .nav-card-icon {
            font-size: 20px;
            margin-bottom: 4px;
        }

        .nav-card-name {
            font-size: 9px;
            line-height: 1.1;
        }

        .nav-card:hover {
            transform: scale(1.08) translateY(-3px);
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .celula-card {
            padding: 15px;
        }

        .celula-info-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }
    }
</style>

<script>
    // Função para atualizar saudação baseada na hora do cliente
    function atualizarSaudacao() {
        const hora = new Date().getHours();
        let saudacao = 'Bem-vindo';
        
        if (hora >= 5 && hora < 12) {
            saudacao = 'Bom dia';
        } else if (hora >= 12 && hora < 18) {
            saudacao = 'Boa tarde';
        } else {
            saudacao = 'Boa noite';
        }
        
        // Atualizar o elemento H1 do header
        const h1 = document.querySelector('.dashboard-header h1');
        if (h1) {
            const userName = h1.textContent.split(', ')[1]?.replace('!', '') || 'Usuário';
            h1.textContent = saudacao + ', ' + userName + '!';
        }
    }
    
    // Dashboard com dados hierárquicos renderizados pelo PHP
    document.addEventListener('DOMContentLoaded', function() {
        // Atualizar saudação com base na hora do cliente
        atualizarSaudacao();
        
        // Atualizar saudação a cada minuto (para mudanças de saudação às 12:00 e 18:00)
        setInterval(atualizarSaudacao, 60000);
        
        // Animar eventos com efeito 3D na entrada
        animarEventosEntrada();
        
        console.log('✅ Dashboard carregado com dados hierárquicos do servidor');
    });
    
    // Função para animar eventos na entrada com efeito 3D
    function animarEventosEntrada() {
        const eventos = document.querySelectorAll('.event-item');
        
        eventos.forEach((evento, index) => {
            // Começar rotacionado
            evento.style.opacity = '0';
            evento.style.transform = 'rotateX(90deg) rotateY(45deg) translateZ(-50px)';
            
            // Animar para posição final
            setTimeout(() => {
                evento.style.transition = 'all 0.8s cubic-bezier(0.34, 1.56, 0.64, 1)';
                evento.style.opacity = '1';
                evento.style.transform = 'rotateX(0) rotateY(0) translateZ(0)';
            }, index * 100);
        });
    }

</script>
