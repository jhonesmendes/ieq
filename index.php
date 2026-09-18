<?php
// ===== LOGOUT DEVE SER PROCESSADO ANTES DE QUALQUER OUTPUT =====
// Inicia buffer de saída
ob_start();

// Desabilitar exibição de erros (log apenas)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers de cache
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

session_start();

// ===== PROCESSAR LOGOUT IMEDIATAMENTE =====
if (isset($_GET['page']) && $_GET['page'] === 'logout') {
    try {
        require_once 'config/auth.php';
        
        // Fazer logout seguro
        if (function_exists('fazer_logout')) {
            fazer_logout();
        } else {
            $_SESSION = array();
            session_destroy();
        }

        // Limpar cookies
        if (isset($_COOKIE['usuario_token'])) {
            setcookie('usuario_token', '', time() - 3600, '/', '', false, true);
        }
        setcookie('PHPSESSID', '', time() - 3600, '/', '', false, true);

        // Limpar buffer e redirecionar
        ob_end_clean();
        header('Location: index.php?page=login&logout=success', true, 302);
        exit;
    } catch (Exception $e) {
        ob_end_clean();
        header('Location: index.php?page=login&logout=success', true, 302);
        exit;
    }
}

// Agora pode incluir outras configurações (depois de logout seguro)
try {
    require_once 'config/database.php';
    require_once 'config/auth.php';
    require_once 'config/seguranca.php';
    require_once 'config/permissoes.php';
    require_once 'config/google_oauth.php';
    initDatabase();
} catch (Exception $e) {
    error_log("Erro ao incluir configs: " . $e->getMessage());
    ob_end_clean();
    die("<h1>Erro ao inicializar aplicação</h1><p>Tente novamente em alguns segundos.</p>");
}

// Roteamento simples
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Líder e líder em treinamento usam o app simplificado: só a tela de
// lançamento de presença, sem menu lateral (get_permissoes_usuario() já
// restringe o acesso deles a 'presenca'; aqui só decidimos o layout).
$app_lider_simplificado = $user_id && function_exists('is_lider_exclusivo') && is_lider_exclusivo();

// Gestor da igreja: mesma ideia, mas com o app de funções da igreja
// (get_permissoes_usuario() já restringe o acesso dele a 'igreja_app').
$app_gestor_igreja_simplificado = $user_id && function_exists('is_gestor_igreja_exclusivo') && is_gestor_igreja_exclusivo();

// Qualquer um dos dois "apps simplificados" usa o mesmo layout sem sidebar
$app_simplificado = $app_lider_simplificado || $app_gestor_igreja_simplificado;

// Páginas públicas (sem autenticação necessária)
$paginas_publicas = ['login', 'registro', 'recuperar_senha', 'redefinir_senha'];

// Se não está logado, redireciona para login
if (!$user_id && !in_array($page, $paginas_publicas)) {
    ob_end_clean();
    header('Location: index.php?page=login');
    exit;
}

// Verificar permissões de acesso à página (exceto logout)
if ($user_id && $page !== 'logout' && !pode_acessar_modulo($page)) {
    // Redirecionar para primeira página com permissão
    $modulos_permitidos = get_modulos_permitidos();
    
    if (!empty($modulos_permitidos) && $modulos_permitidos[0] !== '*') {
        $primeira_pagina = $modulos_permitidos[0];
        $_SESSION['erro'] = 'Você não tem permissão para acessar esta página.';
        header('Location: index.php?page=' . $primeira_pagina);
        exit;
    } elseif (empty($modulos_permitidos)) {
        // Usuário sem nenhuma permissão
        session_destroy();
        header('Location: index.php?page=login');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo obter_csrf_token(); ?>">
    <title>IEQ - Gestão de Células</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/logo.ico">
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/logo.ico">
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/forms.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <?php if ($app_lider_simplificado): ?>
        <link rel="stylesheet" href="assets/css/lider-app.css">
    <?php elseif ($app_gestor_igreja_simplificado): ?>
        <link rel="stylesheet" href="assets/css/igreja-app.css">
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body<?php
    if ($app_lider_simplificado) echo ' class="app-lider-simplificado"';
    elseif ($app_gestor_igreja_simplificado) echo ' class="app-igreja-simplificado"';
?>>
    <?php if ($user_id): ?>
        <div class="container-main">
            <?php if (!$app_simplificado): ?>
                <?php include 'includes/sidebar.php'; ?>
            <?php endif; ?>
            <main class="main-content">
                <?php
                // Mostrar mensagem de erro se existir
                if (isset($_SESSION['erro'])) {
                    echo '<div class="alert alert-error" style="margin-bottom: 20px;">' . $_SESSION['erro'] . '</div>';
                    unset($_SESSION['erro']);
                }
                
                switch ($page) {
                    case 'dashboard':
                        include 'pages/dashboard.php';
                        break;
                    case 'igrejas':
                        include 'pages/igrejas.php';
                        break;
                    case 'membros':
                        include 'pages/membros.php';
                        break;
                    case 'celulas':
                        include 'pages/celulas.php';
                        break;
                    case 'presenca':
                        include $app_lider_simplificado ? 'pages/presenca_lider_app.php' : 'pages/presenca.php';
                        break;
                    case 'igreja_app':
                        include 'pages/igreja_app.php';
                        break;
                    case 'galeria':
                        include 'pages/galeria.php';
                        break;
                    case 'visitantes':
                        include 'pages/visitantes.php';
                        break;
                    case 'usuarios':
                        include 'pages/usuarios.php';
                        break;
                    case 'cursos':
                        include 'pages/cursos.php';
                        break;
                    case 'eventos':
                        include 'pages/eventos.php';
                        break;
                    case 'calendario':
                        include 'pages/calendario.php';
                        break;
                    case 'novo_convertido':
                        include 'pages/novo_convertido.php';
                        break;
                    case 'aprovacao_cadastros':
                        include 'pages/aprovacao_cadastros.php';
                        break;
                    case 'configuracoes':
                        include 'pages/configuracoes.php';
                        break;
                    default:
                        include 'pages/dashboard.php';
                }
                ?>
            </main>
        </div>
    <?php else: ?>
        <?php
        if ($page === 'registro') {
            include 'pages/registro.php';
        } elseif ($page === 'recuperar_senha') {
            include 'pages/recuperar_senha.php';
        } elseif ($page === 'redefinir_senha') {
            include 'pages/redefinir_senha.php';
        } else {
            include 'pages/login.php';
        }
        ?>
    <?php endif; ?>

    <script src="assets/js/csrf-protection.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    
    <!-- Sincronizar Token CSRF no localStorage -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Capturar token do meta tag
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                const token = metaToken.getAttribute('content');
                if (token) {
                    localStorage.setItem('csrf_token', token);
                    console.log('✓ Token CSRF sincronizado no localStorage');
                }
            }
        });
    </script>
    
    <!-- Mobile Sidebar Toggle Script -->
    <?php if ($user_id): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Criar botão de toggle se não existir
            const sidebar = document.querySelector('.sidebar');
            const container = document.querySelector('.container-main');
            
            if (sidebar && !document.querySelector('.sidebar-toggle')) {
                const toggle = document.createElement('button');
                toggle.className = 'sidebar-toggle';
                toggle.innerHTML = '☰';
                toggle.setAttribute('aria-label', 'Menu');
                
                container.insertBefore(toggle, sidebar);
                
                // Event listener para toggle
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    sidebar.classList.toggle('active');
                });
                
                // Fechar sidebar ao clicar em um link
                const navItems = sidebar.querySelectorAll('.nav-item');
                navItems.forEach(item => {
                    item.addEventListener('click', function() {
                        if (window.innerWidth <= 768) {
                            sidebar.classList.remove('active');
                        }
                    });
                });
                
                // Fechar sidebar ao redimensionar para desktop
                window.addEventListener('resize', function() {
                    if (window.innerWidth > 768) {
                        sidebar.classList.remove('active');
                    }
                });
            }
        });
    </script>
    <?php endif; ?>

    <!-- Script para Mobile Menu -->
    <script>
    function toggleMobileMenu() {
        const menu = document.querySelector('.mobile-menu');
        const overlay = document.querySelector('.mobile-menu-overlay');
        
        if (menu && overlay) {
            const isActive = menu.classList.contains('active');
            menu.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    }

    function closeMobileMenu() {
        const menu = document.querySelector('.mobile-menu');
        const overlay = document.querySelector('.mobile-menu-overlay');
        
        if (menu && overlay) {
            menu.classList.remove('active');
            overlay.classList.remove('active');
        }
    }

    // Mostrar/esconder navbar mobile baseado no tamanho
    function updateMobileNavbar() {
        const navbar = document.querySelector('.mobile-navbar');
        if (navbar) {
            navbar.style.display = window.innerWidth <= 768 ? 'flex' : 'none';
        }
    }

    // Inicializar na carga
    document.addEventListener('DOMContentLoaded', function() {
        updateMobileNavbar();
    });

    // Atualizar ao redimensionar
    window.addEventListener('resize', function() {
        updateMobileNavbar();
        closeMobileMenu(); // Fechar menu ao redimensionar
    });

    // Fechar menu ao clicar fora
    document.addEventListener('click', function(e) {
        const navbar = document.querySelector('.mobile-navbar');
        const menu = document.querySelector('.mobile-menu');
        const overlay = document.querySelector('.mobile-menu-overlay');
        
        if (navbar && menu && overlay && 
            !navbar.contains(e.target) && 
            !menu.contains(e.target) &&
            e.target !== overlay) {
            closeMobileMenu();
        }
    });
    </script>
</body>
</html>
