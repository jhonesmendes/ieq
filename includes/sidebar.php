<?php
require_once __DIR__ . '/../config/permissoes.php';
$modulos_permitidos = get_modulos_permitidos();
?>

<!-- MOBILE NAVBAR (Visível apenas em mobile) -->
<nav class="mobile-navbar">
    <button class="mobile-navbar-menu" onclick="toggleMobileMenu()">☰</button>
    <div class="mobile-navbar-title">IEQ</div>
    <a href="index.php?page=logout" class="mobile-navbar-logout" title="Sair do Sistema">🚪</a>
</nav>

<!-- MOBILE MENU OVERLAY -->
<div class="mobile-menu-overlay" onclick="closeMobileMenu()"></div>

<!-- MOBILE MENU LATERAL -->
<div class="mobile-menu">
    <?php
    // Menu estruturado por seção
    $menu_sections = [
        'Principal' => [
            ['modulo' => 'dashboard', 'icon' => '📊', 'nome' => 'Dashboard'],
            ['modulo' => 'membros', 'icon' => '👥', 'nome' => 'Membros'],
            ['modulo' => 'celulas', 'icon' => '🏠', 'nome' => 'Células'],
            ['modulo' => 'visitantes', 'icon' => '👋', 'nome' => 'Visitantes']
        ],
        'Gestão' => [
            ['modulo' => 'presenca', 'icon' => '✓', 'nome' => 'Presença'],
            ['modulo' => 'eventos', 'icon' => '📅', 'nome' => 'Eventos'],
            ['modulo' => 'cursos', 'icon' => '🎓', 'nome' => 'Cursos'],
            ['modulo' => 'galeria', 'icon' => '📸', 'nome' => 'Galeria']
        ],
        'Sistema' => [
            ['modulo' => 'igrejas', 'icon' => '⛪', 'nome' => 'Igrejas'],
            ['modulo' => 'usuarios', 'icon' => '🛡️', 'nome' => 'Usuários'],
            ['modulo' => 'aprovacao_cadastros', 'icon' => '🔓', 'nome' => 'Aprovação'],
            ['modulo' => 'configuracoes', 'icon' => '⚙️', 'nome' => 'Configurações']
        ]
    ];

    $page = $_GET['page'] ?? '';
    $first = true;

    foreach ($menu_sections as $section => $items) {
        if (!$first) {
            echo '<div class="mobile-menu-divider"></div>';
        }
        
        foreach ($items as $item) {
            if (pode_acessar_modulo($item['modulo'])) {
                $active = ($page === $item['modulo']) ? 'active' : '';
                echo '
                <a href="index.php?page=' . $item['modulo'] . '" class="mobile-menu-item ' . $active . '" onclick="closeMobileMenu()">
                    <span class="mobile-menu-icon">' . $item['icon'] . '</span>
                    <span class="mobile-menu-label">' . $item['nome'] . '</span>
                </a>';
            }
        }
        $first = false;
    }
    ?>
</div>

<aside class="sidebar">
    <div class="sidebar-header">
        <img src="assets/img/logo.ico" alt="IEQ Logo" class="sidebar-logo">
        <h1>IEQ Gestão</h1>
    </div>

    <nav class="sidebar-nav">
        <!-- SEÇÃO PRINCIPAL -->
        <?php if (pode_acessar_modulo('dashboard') || pode_acessar_modulo('membros') || pode_acessar_modulo('celulas') || pode_acessar_modulo('visitantes')): ?>
        <div class="nav-section-label">Principal</div>
        <ul>
            <?php if (pode_acessar_modulo('dashboard')): ?>
            <li><a href="index.php?page=dashboard" class="nav-item <?php echo (isset($page) && $page === 'dashboard') ? 'active' : ''; ?>">
                <span class="icon">📊</span> Dashboard
            </a></li>
            <?php endif; ?>
            
            <?php if (pode_acessar_modulo('membros')): ?>
            <li><a href="index.php?page=membros" class="nav-item <?php echo (isset($page) && $page === 'membros') ? 'active' : ''; ?>">
                <span class="icon">👥</span> Membros
            </a></li>
            <?php endif; ?>
            
            <?php if (pode_acessar_modulo('celulas')): ?>
            <li><a href="index.php?page=celulas" class="nav-item <?php echo (isset($page) && $page === 'celulas') ? 'active' : ''; ?>">
                <span class="icon">🏠</span> Células
            </a></li>
            <?php endif; ?>

            <?php if (pode_acessar_modulo('visitantes')): ?>
            <li><a href="index.php?page=visitantes" class="nav-item <?php echo (isset($page) && $page === 'visitantes') ? 'active' : ''; ?>">
                <span class="icon">👋</span> Visitantes
            </a></li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>

        <!-- SEÇÃO GESTÃO -->
        <?php if (pode_acessar_modulo('eventos') || pode_acessar_modulo('cursos') || pode_acessar_modulo('presenca') || pode_acessar_modulo('galeria')): ?>
        <div class="nav-section-label">Gestão</div>
        <ul>
            <?php if (pode_acessar_modulo('presenca')): ?>
            <li><a href="index.php?page=presenca" class="nav-item <?php echo (isset($page) && $page === 'presenca') ? 'active' : ''; ?>">
                <span class="icon">✓</span> Presença
            </a></li>
            <?php endif; ?>

            <?php if (pode_acessar_modulo('eventos')): ?>
            <li><a href="index.php?page=eventos" class="nav-item <?php echo (isset($page) && $page === 'eventos') ? 'active' : ''; ?>">
                <span class="icon">📅</span> Eventos
            </a></li>
            <?php endif; ?>
            
            <?php if (pode_acessar_modulo('cursos')): ?>
            <li><a href="index.php?page=cursos" class="nav-item <?php echo (isset($page) && $page === 'cursos') ? 'active' : ''; ?>">
                <span class="icon">🎓</span> Cursos
            </a></li>
            <?php endif; ?>

            <?php if (pode_acessar_modulo('galeria')): ?>
            <li><a href="index.php?page=galeria" class="nav-item <?php echo (isset($page) && $page === 'galeria') ? 'active' : ''; ?>">
                <span class="icon">📸</span> Galeria
            </a></li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>

        <!-- SEÇÃO SISTEMA -->
        <?php if (pode_acessar_modulo('configuracoes') || pode_acessar_modulo('igrejas') || pode_acessar_modulo('usuarios')): ?>
        <div class="nav-section-label">Sistema</div>
        <ul>
            <?php if (pode_acessar_modulo('igrejas')): ?>
            <li><a href="index.php?page=igrejas" class="nav-item <?php echo (isset($page) && $page === 'igrejas') ? 'active' : ''; ?>">
                <span class="icon">⛪</span> Igrejas
            </a></li>
            <?php endif; ?>

            <?php if (pode_acessar_modulo('usuarios')): ?>
            <li><a href="index.php?page=usuarios" class="nav-item <?php echo (isset($page) && $page === 'usuarios') ? 'active' : ''; ?>">
                <span class="icon">🛡️</span> Usuários
            </a></li>
            <?php endif; ?>

            <?php if (pode_acessar_modulo('aprovacao_cadastros')): ?>
            <li><a href="index.php?page=aprovacao_cadastros" class="nav-item <?php echo (isset($page) && $page === 'aprovacao_cadastros') ? 'active' : ''; ?>">
                <span class="icon">🔓</span> Aprovação de Cadastros
            </a></li>
            <?php endif; ?>
            
            <?php if (pode_acessar_modulo('configuracoes')): ?>
            <li><a href="index.php?page=configuracoes" class="nav-item <?php echo (isset($page) && $page === 'configuracoes') ? 'active' : ''; ?>">
                <span class="icon">⚙️</span> Configurações
            </a></li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <?php 
                $nome = $_SESSION['user_nome'] ?? 'U';
                $partes = explode(' ', $nome);
                if (count($partes) >= 2) {
                    echo strtoupper(substr($partes[0], 0, 1) . substr($partes[1], 0, 1));
                } else {
                    echo strtoupper(substr($nome, 0, 2));
                }
                ?>
            </div>
            <div class="user-details">
                <p class="user-name"><?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Usuário'); ?></p>
                <span class="user-role-badge <?php echo $_SESSION['user_funcao'] ?? 'membro'; ?>">
                    <?php 
                    $badges_map = [
                        'admin' => 'Admin',
                        'pastor' => 'Pastor',
                        'supervisor' => 'Supervisor',
                        'lider' => 'Líder',
                        'membro' => 'Membro'
                    ];
                    echo $badges_map[$_SESSION['user_funcao'] ?? 'membro'];
                    ?>
                </span>
            </div>
        </div>
        <a href="index.php?page=logout" class="logout-btn" title="Sair do Sistema">
            <span class="icon">🚪</span> Sair
        </a>
    </div>
</aside>