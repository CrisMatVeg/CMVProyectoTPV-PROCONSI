<?php
$file = 'view/vProductos.php';
$content = file_get_contents($file);

$tokens = token_get_all($content);
$stack = [];
$line = 1;

foreach ($tokens as $token) {
    if (is_array($token)) {
        $type = $token[0];
        $text = $token[1];
        $line = $token[2];
        
        if ($type === T_IF && strpos(substr($content, strpos($content, $text, 0)), ':') !== false) {
            // Simplified check for colon syntax
            $stack[] = ['type' => 'if', 'line' => $line];
        }
        if ($type === T_FOREACH && strpos(substr($content, strpos($content, $text, 0)), ':') !== false) {
            $stack[] = ['type' => 'foreach', 'line' => $line];
        }
        if ($type === T_ENDIF) {
            array_pop($stack);
        }
        if ($type === T_ENDFOREACH) {
            array_pop($stack);
        }
    } else {
        if ($token === '{') $stack[] = ['type' => '{', 'line' => $line];
        if ($token === '}') array_pop($stack);
    }
}

echo "Stack at end: " . json_encode($stack) . "\n";
