<?php

// definir header para json
header('Content-Type: application/json');

// retornar um codigo de nao autorizado
http_response_code(403);
exit;


?>