<?php
// Configurações e funções para upload de arquivos

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_PRESENCA_DIR', UPLOAD_DIR . 'presenca/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

/**
 * Redimensionar imagem mantendo proporção
 * @param string $caminho_arquivo
 * @param int $max_largura
 * @param int $max_altura
 */
function redimensionar_imagem($caminho_arquivo, $max_largura, $max_altura) {
    // Obter informações da imagem
    list($largura_orig, $altura_orig, $tipo) = getimagesize($caminho_arquivo);
    
    // Calcular novas dimensões mantendo proporção
    $ratio = min($max_largura / $largura_orig, $max_altura / $altura_orig);
    
    // Se a imagem já é menor, não redimensionar
    if ($ratio >= 1) {
        return;
    }
    
    $nova_largura = round($largura_orig * $ratio);
    $nova_altura = round($altura_orig * $ratio);
    
    // Criar imagem a partir do tipo
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            $imagem_orig = imagecreatefromjpeg($caminho_arquivo);
            break;
        case IMAGETYPE_PNG:
            $imagem_orig = imagecreatefrompng($caminho_arquivo);
            break;
        case IMAGETYPE_GIF:
            $imagem_orig = imagecreatefromgif($caminho_arquivo);
            break;
        default:
            return;
    }
    
    // Criar nova imagem
    $imagem_nova = imagecreatetruecolor($nova_largura, $nova_altura);
    
    // Preservar transparência para PNG e GIF
    if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_GIF) {
        imagealphablending($imagem_nova, false);
        imagesavealpha($imagem_nova, true);
        $transparent = imagecolorallocatealpha($imagem_nova, 255, 255, 255, 127);
        imagefilledrectangle($imagem_nova, 0, 0, $nova_largura, $nova_altura, $transparent);
    }
    
    // Redimensionar
    imagecopyresampled($imagem_nova, $imagem_orig, 0, 0, 0, 0, $nova_largura, $nova_altura, $largura_orig, $altura_orig);
    
    // Salvar imagem
    switch ($tipo) {
        case IMAGETYPE_JPEG:
            imagejpeg($imagem_nova, $caminho_arquivo, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($imagem_nova, $caminho_arquivo, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($imagem_nova, $caminho_arquivo);
            break;
    }
    
    // Liberar memória
    imagedestroy($imagem_orig);
    imagedestroy($imagem_nova);
}

/**
 * Faz upload de uma foto de reunião de célula
 * @param array $file - $_FILES['campo']
 * @param int $celula_id - ID da célula
 * @param string $data_reuniao - Data da reunião (Y-m-d)
 * @return array - ['sucesso' => bool, 'mensagem' => string, 'caminho' => string|null]
 */
function upload_foto_reuniao($file, $celula_id, $data_reuniao) {
    // Validar se o arquivo foi enviado
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['sucesso' => false, 'mensagem' => 'Nenhum arquivo foi enviado'];
    }
    
    // Validar erros de upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['sucesso' => false, 'mensagem' => 'Erro no upload: ' . $file['error']];
    }
    
    // Validar tamanho
    if ($file['size'] > MAX_FILE_SIZE) {
        $max_mb = MAX_FILE_SIZE / (1024 * 1024);
        return ['sucesso' => false, 'mensagem' => "Arquivo muito grande. Máximo: {$max_mb}MB"];
    }
    
    // Validar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, ALLOWED_MIME_TYPES)) {
        return ['sucesso' => false, 'mensagem' => 'Tipo de arquivo não permitido. Use JPG, PNG ou GIF'];
    }
    
    // Validar extensão
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['sucesso' => false, 'mensagem' => 'Extensão não permitida'];
    }
    
    // Criar pasta geral se não existir
    if (!is_dir(UPLOAD_PRESENCA_DIR)) {
        mkdir(UPLOAD_PRESENCA_DIR, 0755, true);
    }
    
    // Criar pasta específica para a célula
    $pasta_celula = UPLOAD_PRESENCA_DIR . 'celula_' . $celula_id . '/';
    if (!is_dir($pasta_celula)) {
        mkdir($pasta_celula, 0755, true);
    }
    
    // Gerar nome único (sem prefixo de célula, já está na pasta)
    $data_formatada = str_replace('-', '', $data_reuniao);
    $timestamp_unico = round(microtime(true) * 10000); // Microsegundos * 10000 para Unix timestamp em centésimos
    $novo_nome = $data_formatada . '_' . $timestamp_unico . '.' . $extension;
    $caminho_completo = $pasta_celula . $novo_nome;
    $caminho_relativo = 'uploads/presenca/celula_' . $celula_id . '/' . $novo_nome;
    
    // Mover arquivo
    if (!move_uploaded_file($file['tmp_name'], $caminho_completo)) {
        return ['sucesso' => false, 'mensagem' => 'Erro ao salvar o arquivo'];
    }
    
    // Redimensionar imagem (max 1200x900 para fotos de grupo)
    redimensionar_imagem($caminho_completo, 1200, 900);
    
    return [
        'sucesso' => true,
        'mensagem' => 'Foto enviada com sucesso',
        'caminho' => $caminho_relativo
    ];
}

