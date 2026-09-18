<?php
/**
 * Script para criar imagem de background para a tela de login
 */

// Criar imagem com gradiente escuro (marrom/laranja)
$width = 1920;
$height = 1080;
$image = imagecreatetruecolor($width, $height);

// Cores para gradiente escuro
$darkBrown = imagecolorallocate($image, 51, 25, 0);
$mediumBrown = imagecolorallocate($image, 139, 69, 19);
$orange = imagecolorallocate($image, 204, 85, 0);
$white = imagecolorallocate($image, 255, 255, 255);

// Criar gradiente vertical (marrom escuro para laranja)
for ($y = 0; $y < $height; $y++) {
    $ratio = $y / $height;
    
    // Interpolação de cor
    $r = (int)(51 + ($orange & 0xFF0000) * $ratio / 0xFF0000);
    $g = (int)(25 + ($orange & 0x00FF00) * $ratio / 0x00FF00);
    $b = (int)(0 + ($orange & 0x0000FF) * $ratio / 0x0000FF);
    
    $color = imagecolorallocate($image, 
        (int)(51 + (204 - 51) * $ratio),
        (int)(25 + (85 - 25) * $ratio),
        (int)(0 + (0 - 0) * $ratio)
    );
    
    imageline($image, 0, $y, $width, $y, $color);
}

// Adicionar texto "IEQSEDE ROOT" no centro
$fontPath = __DIR__ . '/../assets/fonts/Arial.ttf';
$fontSize = 120;
$text = "IEQSEDE\nROOT";

// Se não existir fonte TTF, usar fonte GD built-in
if (!file_exists($fontPath)) {
    // Usar fonte built-in maior
    imagestring($image, 5, $width/2 - 150, $height/2 - 30, "IEQSEDE ROOT", $white);
} else {
    // Usar TTF para texto melhor
    $textColor = imagecolorallocate($image, 255, 255, 255);
    imagettftext($image, $fontSize, 0, $width/2 - 400, $height/2 + 50, $textColor, $fontPath, "IEQSEDE ROOT");
}

// Criar pasta se não existir
$outputDir = __DIR__ . '/../image';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Salvar a imagem
$outputFile = $outputDir . '/ieqsede_root_bg.jpg';
imagejpeg($image, $outputFile, 85);
imagedestroy($image);

echo "✅ Imagem de background criada com sucesso!\n";
echo "Arquivo: " . $outputFile . "\n";
echo "Tamanho: " . round(filesize($outputFile) / 1024) . " KB\n";
?>
