<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST,OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

define('PASTA_RAIZ', dirname(__DIR__, 2));

require_once dirname(__DIR__, 2) . '/Auth/Token/chave-api.php';
require_once dirname(__DIR__, 2) . '/Auth/IP/pega-ip-usuario.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function validarToken($tokenRecebido)
{
    if (!$tokenRecebido) {
        return ["success" => false, "error" => "Token não fornecido"];
    }

    try {
        $decoded = desencriptaJWT($tokenRecebido);

        if ($decoded->Ip !== PegarIpDoUsuario()) {
            return ["success" => false, "error" => "Token inválido"];
        }

        return ["success" => true, "data" => (array) $decoded];
    } catch (Exception $e) {
        $mensagemErro = $e->getMessage();

        if (strpos($mensagemErro, "Expired") !== false) {
            return ["success" => false, "error" => "Token expirado"];
        } else {
            return ["success" => false, "error" => "Token inválido"];
        }
    }
}

function desencriptaJWT($tokenRecebido)
{
    return JWT::decode($tokenRecebido, new Key(chaveApi(), 'HS256'));
}
