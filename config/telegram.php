<?php
require_once __DIR__ . '/database.php';

// Constantes de configuração do Telegram (a partir do .env)
define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '');
define('TELEGRAM_CHAT_ID', getenv('TELEGRAM_CHAT_ID') ?: '');

/**
 * Classe para gerenciar notificações via Telegram
 */
class TelegramNotificador {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Verifica se a integração com Telegram está configurada
     */
    public function estaConfigurado() {
        return !empty(TELEGRAM_BOT_TOKEN) && !empty(TELEGRAM_CHAT_ID);
    }
    
    /**
     * Edita o arquivo .env com as configurações do Telegram
     */
    public function salvarConfiguracao($bot_token, $chat_id) {
        try {
            // Validação básica
            if (empty($bot_token) || empty($chat_id)) {
                return ['status' => 'erro', 'mensagem' => 'Token e Chat ID não podem estar vazios'];
            }
            
            // Caminho do arquivo .env
            $env_path = __DIR__ . '/../.env';
            
            // Se .env não existe, criar a partir do .env.example
            if (!file_exists($env_path)) {
                $example_path = __DIR__ . '/../.env.example';
                if (file_exists($example_path)) {
                    copy($example_path, $env_path);
                } else {
                    // Criar arquivo .env mínimo
                    file_put_contents($env_path, "DB_DRIVER=mysql\nTELEGRAM_BOT_TOKEN=\nTELEGRAM_CHAT_ID=");
                }
            }
            
            // Ler conteúdo atual
            $conteudo = file_get_contents($env_path);
            
            // Atualizar ou adicionar TELEGRAM_BOT_TOKEN
            if (strpos($conteudo, 'TELEGRAM_BOT_TOKEN=') !== false) {
                $conteudo = preg_replace('/TELEGRAM_BOT_TOKEN=.*?\n/', "TELEGRAM_BOT_TOKEN={$bot_token}\n", $conteudo);
            } else {
                $conteudo .= "\nTELEGRAM_BOT_TOKEN={$bot_token}";
            }
            
            // Atualizar ou adicionar TELEGRAM_CHAT_ID
            if (strpos($conteudo, 'TELEGRAM_CHAT_ID=') !== false) {
                $conteudo = preg_replace('/TELEGRAM_CHAT_ID=.*?\n/', "TELEGRAM_CHAT_ID={$chat_id}\n", $conteudo);
            } else {
                $conteudo .= "\nTELEGRAM_CHAT_ID={$chat_id}";
            }
            
            // Salvar arquivo .env
            if (file_put_contents($env_path, $conteudo) === false) {
                return ['status' => 'erro', 'mensagem' => 'Erro ao salvar arquivo .env. Verifique permissões.'];
            }
            
            // Tentar enviar mensagem de teste
            $teste_enviado = $this->enviarMensagem('✅ Bot do Telegram configurado com sucesso! IEQ está pronto para enviar notificações.');
            
            if ($teste_enviado) {
                return ['status' => 'sucesso', 'mensagem' => 'Configuração salva no .env e teste enviado com sucesso'];
            } else {
                return ['status' => 'sucesso', 'mensagem' => 'Configuração salva no .env, mas teste não pode ser enviado. Reinicie o servidor se necessário.'];
            }
        } catch (Exception $e) {
            error_log("Erro ao salvar Telegram: " . $e->getMessage());
            return ['status' => 'erro', 'mensagem' => 'Erro ao salvar: ' . $e->getMessage()];
        }
    }
    
    /**
     * Envia mensagem de teste com informações detalhadas
     */
    private function enviarMensagemTeste() {
        $texto = "🧪 <b>Teste de Conexão</b>\n\n";
        $texto .= "✅ Bot do Telegram está funcionando!\n";
        $texto .= "Data/Hora: " . date('d/m/Y H:i:s') . "\n";
        $texto .= "Status: <b>Conectado</b>\n\n";
        $texto .= "IEQ está pronto para enviar notificações de:\n";
        $texto .= "• ⚠️ Erros gerais\n";
        $texto .= "• 📋 Cadastros pendentes\n";
        $texto .= "• 📊 Relatórios diários";
        
        $sucesso = $this->enviarMensagem($texto);
        
        return [
            'sucesso' => $sucesso,
            'erro' => $sucesso ? '' : 'Falha ao enviar mensagem de teste. Verifique o Chat ID.'
        ];
    }
    
    /**
     * Envia uma mensagem simples
     */
    public function enviarMensagem($texto, $parse_mode = 'HTML') {
        if (!$this->estaConfigurado()) {
            return false;
        }
        
        $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
        
        $dados = [
            'chat_id' => TELEGRAM_CHAT_ID,
            'text' => $texto,
            'parse_mode' => $parse_mode
        ];
        
        return $this->fazerRequisicaoTelegram($url, $dados);
    }
    
    /**
     * Notifica sobre erro geral
     */
    public function notificarErro($titulo, $mensagem, $arquivo = '', $linha = '') {
        if (!$this->estaConfigurado()) {
            return false;
        }
        
        $texto = "⚠️ <b>Erro Geral Detectado</b>\n\n";
        $texto .= "<b>Título:</b> {$titulo}\n";
        $texto .= "<b>Mensagem:</b> {$mensagem}\n";
        if ($arquivo) {
            $texto .= "<b>Arquivo:</b> {$arquivo}\n";
        }
        if ($linha) {
            $texto .= "<b>Linha:</b> {$linha}\n";
        }
        $texto .= "<b>Data/Hora:</b> " . date('d/m/Y H:i:s') . "\n";
        $texto .= "<b>IP:</b> {$_SERVER['REMOTE_ADDR']}\n";
        
        return $this->enviarMensagem($texto);
    }
    
    /**
     * Notifica sobre cadastros pendentes de aprovação
     */
    public function notificarCadastrosPendentes() {
        if (!$this->estaConfigurado()) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total FROM usuarios 
                WHERE status_aprovacao = 'pendente'
            ");
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = $resultado['total'] ?? 0;
            
            if ($total === 0) {
                return false; // Sem cadastros pendentes
            }
            
            // Buscar detalhes
            $stmt = $this->db->prepare("
                SELECT id, nome, email, criado_em FROM usuarios 
                WHERE status_aprovacao = 'pendente' 
                ORDER BY criado_em DESC 
                LIMIT 10
            ");
            $stmt->execute();
            $cadastros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $texto = "📋 <b>Cadastros Pendentes de Aprovação</b>\n\n";
            $texto .= "Total de pendentes: <b>{$total}</b>\n\n";
            
            foreach ($cadastros as $cadastro) {
                $data = date('d/m/Y H:i', strtotime($cadastro['criado_em']));
                $texto .= "👤 <b>{$cadastro['nome']}</b>\n";
                $texto .= "   Email: {$cadastro['email']}\n";
                $texto .= "   Data: {$data}\n\n";
            }
            
            $texto .= "⏱️ <b>Acione as aprovações no painel administrativo</b>";
            
            return $this->enviarMensagem($texto);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Relatório de disparo (novos cadastros e visitantes)
     */
    public function enviarRelatorioDiario() {
        if (!$this->estaConfigurado()) {
            error_log("Telegram não configurado para enviar relatório");
            return false;
        }
        
        try {
            $hoje = date('Y-m-d');
            error_log("[Telegram] Iniciando envio de relatório para: " . $hoje);
            
            // Contar novos membros do dia
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total FROM membros 
                WHERE DATE(criado_em) = ?
            ");
            $stmt->execute([$hoje]);
            $novos_membros = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            error_log("[Telegram] Novos membros: " . $novos_membros);
            
            // Contar novos visitantes do dia (se tabela existir)
            $novos_visitantes = 0;
            try {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as total FROM visitantes 
                    WHERE DATE(data_visita) = ?
                ");
                $stmt->execute([$hoje]);
                $novos_visitantes = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            } catch (Exception $e) {
                error_log("[Telegram] Tabela visitantes não existe: " . $e->getMessage());
            }
            error_log("[Telegram] Novos visitantes: " . $novos_visitantes);
            
            // Presença por célula
            $presencas = [];
            try {
                $stmt = $this->db->prepare("
                    SELECT 
                        c.nome as celula_nome,
                        COUNT(p.id) as presentes,
                        SUM(CASE WHEN p.visitante = 1 THEN 1 ELSE 0 END) as visitantes
                    FROM celulas c
                    LEFT JOIN presencas p ON c.id = p.celula_id AND DATE(p.data_presenca) = ?
                    GROUP BY c.id
                    ORDER BY presentes DESC
                    LIMIT 10
                ");
                $stmt->execute([$hoje]);
                $presencas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("[Telegram] Erro ao buscar presenças: " . $e->getMessage());
            }
            error_log("[Telegram] Presencas encontradas: " . count($presencas));
            
            // Montar mensagem
            $texto = "📊 <b>Relatório Diário - " . date('d/m/Y') . "</b>\n\n";
            $texto .= "👥 <b>Novos Registros</b>\n";
            $texto .= "   • Membros: <b>" . $novos_membros . "</b>\n";
            $texto .= "   • Visitantes: <b>" . $novos_visitantes . "</b>\n\n";
            
            $texto .= "⛪ <b>Presença por Célula</b>\n";
            if (empty($presencas)) {
                $texto .= "   (Nenhuma presença registrada)\n";
            } else {
                foreach ($presencas as $presenca) {
                    if ($presenca['presentes'] > 0 || $presenca['visitantes'] > 0) {
                        $celula = $presenca['celula_nome'] ?? 'Sem célula';
                        $presentes = $presenca['presentes'] ?? 0;
                        $visitantes = $presenca['visitantes'] ?? 0;
                        $texto .= "   • {$celula}: {$presentes} membros + {$visitantes} visitantes\n";
                    }
                }
            }
            
            $texto .= "\n📈 Acesse o painel para mais detalhes";
            
            error_log("[Telegram] Mensagem montada, tentando enviar...");
            $resultado = $this->enviarMensagem($texto);
            error_log("[Telegram] Resultado do envio: " . ($resultado ? 'sucesso' : 'falha'));
            
            return $resultado;
        } catch (Exception $e) {
            error_log("[Telegram] ERRO ao enviar relatório: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
            return false;
        }
    }
    
    /**
     * Faz requisição para a API do Telegram
     */
    private function fazerRequisicaoTelegram($url, $dados) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        error_log("[Telegram] Enviando requisição para: " . parse_url($url, PHP_URL_PATH));
        error_log("[Telegram] Chat ID alvo: " . (isset($dados['chat_id']) ? $dados['chat_id'] : 'N/A'));
        
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("[Telegram] HTTP Code recebido: $httpcode");
        
        // Log detalhado de erros
        if ($error) {
            error_log("[Telegram] ❌ cURL Error: $error");
            return false;
        }
        
        if ($response === false || $httpcode === 0) {
            error_log("[Telegram] ❌ Requisição falhou - sem resposta");
            return false;
        }
        
        if ($httpcode !== 200) {
            error_log("[Telegram] ❌ HTTP error: $httpcode");
            error_log("[Telegram] Response: " . substr($response, 0, 500));
            return false;
        }
        
        try {
            $resultado = json_decode($response, true);
            $sucesso = isset($resultado['ok']) && $resultado['ok'] === true;
            
            if ($sucesso) {
                error_log("[Telegram] ✅ Mensagem entregue com sucesso! (message_id: " . ($resultado['result']['message_id'] ?? 'N/A') . ")");
            } else {
                error_log("[Telegram] ❌ API retornou erro: " . ($resultado['description'] ?? 'Erro desconhecido'));
                error_log("[Telegram] Resposta completa: " . json_encode($resultado));
            }
            
            return $sucesso;
        } catch (Exception $e) {
            error_log("[Telegram] ❌ JSON Parse Error: " . $e->getMessage());
            error_log("[Telegram] Response raw: $response");
            return false;
        }
    }
    
    /**
     * Obter configuração atual (sem sensíveis)
     */
    public function obterConfiguracao() {
        return [
            'configurado' => $this->estaConfigurado(),
            'chat_id_parcial' => TELEGRAM_CHAT_ID ? substr(TELEGRAM_CHAT_ID, 0, 5) . '...' : ''
        ];
    }
}

/**
 * Instância global do notificador
 */
$telegram = new TelegramNotificador();
?>
