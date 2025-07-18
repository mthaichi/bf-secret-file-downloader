<?php
echo $content;
// JS/CSSだけを出力
\Breadfish\BasicGuard\ViewRenderer::frontend('downloader.php', ['js_only' => true]);