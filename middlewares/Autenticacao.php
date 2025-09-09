<?php



require_once(dirname(__DIR__, 1) . '/middlewares/Cors.php');
require_once(dirname(__DIR__, 1) . '/Auth/Token/valida-jwt-interno.php');

$dadosheader = getallheaders();

$token = str_replace("Bearer ", "", $dadosheader["Authorization"] ?? "");

$validacao = validarToken($token);

if (!$validacao["success"]) {
    echo json_encode(
        [
            "success" => false,
            "message" => $validacao["error"]
        ]
    );
    exit;
}

$userId = $validacao["data"]["user_id"];

?>