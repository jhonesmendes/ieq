<?php
/**
 * Script para visualizar logs de login
 * Mostra as tentativas de login com motivo específico do erro
 * 
 * ACESSO: Apenas usuários logados como ADMIN
 */

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação (opcional: remover essas 8 linhas se quiser acesso livre)
// Descomentar para proteger com autenticação:
/*
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_funcao'])) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(401);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Não autenticado']);
    exit;
}

if ($_SESSION['user_funcao'] !== 'admin' && $_SESSION['user_funcao'] !== 'pastor') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode(['status' => 'erro', 'mensagem' => 'Permissão negada. Apenas admin/pastor podem acessar.']);
    exit;
}
*/

// Definir header JSON
header('Content-Type: application/json; charset=utf-8');

// Verificar se arquivo de log existe
$caminho_log = __DIR__ . '/../data/seguranca.log';

if (!file_exists($caminho_log)) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Arquivo de log não encontrado',
        'caminho' => $caminho_log
    ]);
    exit;
}

// Ler arquivo de log
$linhas = file($caminho_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if (!$linhas) {
    echo json_encode([
        'status' => 'vazio',
        'mensagem' => 'Nenhum log registrado',
        'total' => 0
    ]);
    exit;
}

// Processar logs
$logs = [];
$totais = [
    'falhas_login' => 0,
    'logins_sucesso' => 0,
    'emails_unicos' => [],
    'motivos' => [],
    'logs_antigos' => 0,
    'logs_novos' => 0
];

foreach ($linhas as $linha) {
    // Padrão: [2026-03-10 12:34:56] [IP: 127.0.0.1] [USER: anonimo] [TIPO_LOG] {"dados":"json"}
    
    if (preg_match('/\[([\d\-\s:]+)\]\s+\[IP:\s+([^\]]+)\]\s+\[USER:\s+([^\]]+)\]\s+\[([A-Z_]+)\]\s+(.+)/', $linha, $matches)) {
        $datetime = $matches[1];
        $ip = $matches[2];
        $user = $matches[3];
        $tipo = $matches[4];
        $dados_json = $matches[5];
        
        // Decodificar JSON
        $dados = json_decode($dados_json, true) ?? [];
        
        // Determinar se é log antigo ou novo
        $tem_motivo = isset($dados['motivo']) && $dados['motivo'] !== 'N/A';
        $tipo_log = $tem_motivo ? 'novo' : 'antigo';
        
        // Se é log antigo sem motivo, gerar uma mensagem genérica
        $motivo = $dados['motivo'] ?? 'desconhecido';
        $mensagem = $dados['mensagem'] ?? ($tipo === 'FALHA_LOGIN' ? 'Verificar credenciais' : 'N/A');
        
        $log_item = [
            'data' => $datetime,
            'ip' => $ip,
            'tipo' => $tipo,
            'email' => $dados['email'] ?? 'N/A',
            'motivo' => $motivo,
            'mensagem' => $mensagem,
            'formato' => $tipo_log,
            'user_id' => $dados['user_id'] ?? 'N/A'
        ];
        
        $logs[] = $log_item;
        
        // Estatísticas
        if ($tipo === 'FALHA_LOGIN') {
            $totais['falhas_login']++;
            if ($tem_motivo) {
                $motivo_chave = $dados['motivo'];
                $totais['motivos'][$motivo_chave] = ($totais['motivos'][$motivo_chave] ?? 0) + 1;
            }
        } elseif ($tipo === 'LOGIN_SUCESSO') {
            $totais['logins_sucesso']++;
        }
        
        if (isset($dados['email'])) {
            $totais['emails_unicos'][$dados['email']] = true;
        }
        
        // Contar logs antigos vs novos
        if ($tipo_log === 'novo') {
            $totais['logs_novos']++;
        } else {
            $totais['logs_antigos']++;
        }
    }
}

// Reverter para mostrar eventos mais recentes primeiro
$logs = array_reverse($logs);

// Estatísticas finais
$totais['emails_unicos'] = count($totais['emails_unicos']);

echo json_encode([
    'status' => 'sucesso',
    'total_registros' => count($logs),
    'aviso' => 'Logs antigos não possuem motivo detalhado. Novos logins mostrarão motivos específicos (usuario_nao_encontrado, senha_incorreta, erro_banco_dados)',
    'resumo' => [
        'logs_com_motivo_detalhado' => $totais['logs_novos'],
        'logs_antigos_sem_motivo' => $totais['logs_antigos'],
        'proximo_passo' => 'Fazer novo login para registrar com motivo detalhado'
    ],
    'estatisticas' => $totais,
    'logs' => array_slice($logs, 0, 100), // Últimos 100 registros
    'notas' => [
        'usuario_nao_encontrado' => 'Email não cadastrado no sistema',
        'senha_incorreta' => 'Senha informada está errada',
        'erro_banco_dados' => 'Problema ao conectar com banco de dados',
        'desconhecido' => 'Log antigo (sem motivo detalhado registrado)'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
