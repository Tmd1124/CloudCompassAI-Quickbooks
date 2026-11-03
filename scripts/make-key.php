<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
echo base64_encode(random_bytes(32)), "\n";
