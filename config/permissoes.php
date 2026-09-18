<?php
require_once __DIR__ . '/database.php';

// Sistema de Permissões

/**
 * Hierarquia de funções:
 * - admin: Acesso total ao sistema
 * - pastor: Acesso total exceto configurações de sistema
 * - supervisor: Gerencia múltiplas células
 * - lider: Gerencia sua própria célula
 * - lider_treinamento: Gerencia sua própria célula (mesmas permissões do líder)
 * - gestor_igreja: Gerencia só as funções da própria igreja (eventos,
 *   visitantes, conversões, batismos, reconciliação) — não é hierárquico,
 *   é um papel à parte, restrito, como o líder é restrito à célula.
 * - membro: Acesso básico apenas leitura
 */

// Verificar se usuário tem permissão
function tem_permissao($funcao_requerida) {
    if (!isset($_SESSION['user_funcao'])) {
        return false;
    }
    
    $hierarquia = [
        'admin' => 5,
        'pastor' => 4,
        'supervisor' => 3,
        'lider' => 2,
        'lider_treinamento' => 2,
        'membro' => 1
    ];
    
    $funcao_usuario = $_SESSION['user_funcao'];
    $nivel_usuario = $hierarquia[$funcao_usuario] ?? 0;
    $nivel_requerido = $hierarquia[$funcao_requerida] ?? 0;
    
    return $nivel_usuario >= $nivel_requerido;
}

// Verificar se é admin
function is_admin() {
    return isset($_SESSION['user_funcao']) && $_SESSION['user_funcao'] === 'admin';
}

// Verificar se é pastor
function is_pastor() {
    return isset($_SESSION['user_funcao']) && in_array($_SESSION['user_funcao'], ['admin', 'pastor']);
}

// Verificar se é supervisor
function is_supervisor() {
    return isset($_SESSION['user_funcao']) && in_array($_SESSION['user_funcao'], ['admin', 'pastor', 'supervisor']);
}

// Verificar se é líder
function is_lider() {
    return isset($_SESSION['user_funcao']) && in_array($_SESSION['user_funcao'], ['admin', 'pastor', 'supervisor', 'lider', 'lider_treinamento']);
}

// Verificar se é APENAS líder (não admin, pastor, supervisor ou lider_treinamento)
function is_lider_exclusivo() {
    return isset($_SESSION['user_funcao']) && in_array($_SESSION['user_funcao'], ['lider', 'lider_treinamento']);
}

// Verificar se é o papel restrito "gestor da igreja"
function is_gestor_igreja_exclusivo() {
    return isset($_SESSION['user_funcao']) && $_SESSION['user_funcao'] === 'gestor_igreja';
}

// Verifica se o usuário logado pode gerenciar os dados de uma igreja
// específica: admin/pastor/supervisor podem qualquer uma (mesmo nível de
// acesso que a tela de administração de Igrejas já dava antes); gestor_igreja
// só a própria.
function usuario_pode_gerenciar_igreja($igreja_id) {
    if (is_supervisor()) { // admin, pastor ou supervisor
        return true;
    }
    if (is_gestor_igreja_exclusivo()) {
        $igreja = function_exists('obter_igreja_do_gestor') ? obter_igreja_do_gestor() : null;
        return $igreja && (int)$igreja['id'] === (int)$igreja_id;
    }
    return false;
}

// Obter IDs das células que o líder gerencia
function obter_celulas_do_lider($user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? 0;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM celulas WHERE lider_id = ? OR lider_id_2 = ? OR lider_treinamento_id = ?");
    $stmt->execute([$user_id, $user_id, $user_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Obter célula principal do líder (primeira célula que ele lidera)
function obter_celula_principal_lider($user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? 0;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM celulas WHERE lider_id = ? OR lider_id_2 = ? OR lider_treinamento_id = ? ORDER BY criado_em ASC LIMIT 1");
    $stmt->execute([$user_id, $user_id, $user_id]);
    return $stmt->fetchColumn();
}

// Verificar se pode editar célula específica
function pode_editar_celula($celula_id) {
    // Admin e pastor podem editar qualquer célula
    if (is_pastor()) {
        return true;
    }
    
    $db = getDB();
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Supervisor pode editar células que supervisiona
    if (is_supervisor()) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM celulas WHERE id = ? AND supervisor_id = ?");
        $stmt->execute([$celula_id, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
    }
    
    // Líder pode editar apenas sua célula
    if (is_lider()) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM celulas WHERE id = ? AND (lider_id = ? OR lider_id_2 = ? OR lider_treinamento_id = ?)");
        $stmt->execute([$celula_id, $user_id, $user_id, $user_id]);
        return $stmt->fetchColumn() > 0;
    }
    
    return false;
}

// Verificar se pode ver relatório de célula
function pode_ver_celula($celula_id) {
    // Admin e pastor podem ver tudo
    if (is_pastor()) {
        return true;
    }
    
    $db = getDB();
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Supervisor pode ver células que supervisiona
    if (is_supervisor()) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM celulas WHERE id = ? AND supervisor_id = ?");
        $stmt->execute([$celula_id, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
    }
    
    // Líder pode ver sua célula
    if (is_lider()) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM celulas WHERE id = ? AND (lider_id = ? OR lider_id_2 = ? OR lider_treinamento_id = ?)");
        $stmt->execute([$celula_id, $user_id, $user_id, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            return true;
        }
    }
    
    // Membros podem ver células das quais fazem parte
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM membros m
        WHERE m.usuario_id = ? AND m.celula_id = ?
    ");
    $stmt->execute([$user_id, $celula_id]);
    return $stmt->fetchColumn() > 0;
}

// Obter células que o usuário pode gerenciar
function obter_celulas_gerenciaveis() {
    $db = getDB();
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Admin e pastor veem todas
    if (is_pastor()) {
        $stmt = $db->prepare("SELECT * FROM celulas ORDER BY nome");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Supervisor vê células que supervisiona
    if (is_supervisor()) {
        $stmt = $db->prepare("SELECT * FROM celulas WHERE supervisor_id = ? ORDER BY nome");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Líder vê apenas sua célula
    if (is_lider()) {
        $stmt = $db->prepare("SELECT * FROM celulas WHERE lider_id = ? OR lider_id_2 = ? OR lider_treinamento_id = ? ORDER BY nome");
        $stmt->execute([$user_id, $user_id, $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return [];
}

// Middleware de permissão para rotas
function require_permission($funcao_requerida, $redirect = true) {
    if (!tem_permissao($funcao_requerida)) {
        if ($redirect) {
            header('Location: index.php?page=dashboard&erro=sem_permissao');
            exit;
        }
        return false;
    }
    return true;
}

// Obter nome amigável da função
function get_funcao_label($funcao) {
    $labels = [
        'admin' => 'Administrador',
        'pastor' => 'Pastor',
        'supervisor' => 'Supervisor',
        'lider' => 'Líder',
        'lider_treinamento' => 'Líder em Treinamento',
        'gestor_igreja' => 'Gestor da Igreja',
        'membro' => 'Membro'
    ];
    return $labels[$funcao] ?? 'Membro';
}

// Obter badge HTML da função
function get_funcao_badge($funcao) {
    $badges = [
        'admin' => '<span class="badge badge-admin">👑 Admin</span>',
        'pastor' => '<span class="badge badge-pastor">✝️ Pastor</span>',
        'supervisor' => '<span class="badge badge-supervisor">👔 Supervisor</span>',
        'lider' => '<span class="badge badge-lider">⭐ Líder</span>',
        'lider_treinamento' => '<span class="badge badge-lider-treinamento">📚 Líder em Treinamento</span>',
        'gestor_igreja' => '<span class="badge badge-supervisor">⛪ Gestor da Igreja</span>',
        'membro' => '<span class="badge badge-membro">👤 Membro</span>'
    ];
    return $badges[$funcao] ?? $badges['membro'];
}

/**
 * SISTEMA DE PERMISSÕES GRANULARES POR TELA
 */

// Módulos/Telas disponíveis no sistema
function get_modulos_disponiveis() {
    return [
        'dashboard' => '📊 Dashboard',
        'igrejas' => '⛪ Igrejas',
        'membros' => '👥 Membros',
        'celulas' => '📍 Células',
        'presenca' => '✓ Presença',
        'galeria' => '📸 Galeria',
        'visitantes' => '👥 Visitantes',
        'cursos' => '📚 Cursos',
        'eventos' => '🎉 Eventos',
        'novo_convertido' => '✨ Novo Convertido',
        'aprovacao_cadastros' => '🔓 Aprovação de Cadastros',
        'configuracoes' => '⚙️ Configurações'
    ];
}

// Obter permissões do usuário logado
function get_permissoes_usuario($user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? null;
    }

    if (!$user_id) {
        return [];
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT permissoes, funcao FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        return [];
    }

    // Admin tem acesso a tudo
    if ($usuario['funcao'] === 'admin') {
        return ['*'];
    }

    // Líder e líder em treinamento usam o app simplificado de lançamento de
    // presença: independente do que estiver configurado em "permissoes" no
    // cadastro do usuário, o acesso deles fica restrito só a essa tela.
    if (in_array($usuario['funcao'], ['lider', 'lider_treinamento'], true)) {
        return ['presenca'];
    }

    // Gestor da igreja: mesma lógica, mas restrito ao app de funções da
    // igreja (eventos, visitantes, conversões, batismos, reconciliação).
    if ($usuario['funcao'] === 'gestor_igreja') {
        return ['igreja_app'];
    }

    // Decodificar permissões JSON
    if (empty($usuario['permissoes'])) {
        return [];
    }

    $permissoes = json_decode($usuario['permissoes'], true);
    return is_array($permissoes) ? $permissoes : [];
}

// Verificar se usuário tem acesso a um módulo específico
function pode_acessar_modulo($modulo) {
    // Admin sempre tem acesso
    if (is_admin()) {
        return true;
    }
    
    $permissoes = get_permissoes_usuario();
    
    // Acesso total
    if (in_array('*', $permissoes)) {
        return true;
    }
    
    // Verificar se tem permissão específica para o módulo
    return in_array($modulo, $permissoes);
}

// Obter lista de módulos que o usuário pode acessar
function get_modulos_permitidos() {
    if (is_admin()) {
        return array_keys(get_modulos_disponiveis());
    }
    
    return get_permissoes_usuario();
}

// Salvar permissões de um usuário
function salvar_permissoes_usuario($user_id, $permissoes) {
    $db = getDB();
    
    // Se é admin, sempre tem acesso total
    $stmt = $db->prepare("SELECT funcao FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usuario && $usuario['funcao'] === 'admin') {
        $permissoes = ['*'];
    }
    
    $permissoes_json = json_encode($permissoes);
    $stmt = $db->prepare("UPDATE usuarios SET permissoes = ? WHERE id = ?");
    return $stmt->execute([$permissoes_json, $user_id]);
}

// Verificar se usuário pode gerenciar aprovações de cadastros
function pode_gerenciar_aprovacoes() {
    // Admin sempre pode
    if (is_admin()) {
        return true;
    }
    
    // Verificar se tem permissão específica para aprovação de cadastros
    return pode_acessar_modulo('aprovacao_cadastros');
}
