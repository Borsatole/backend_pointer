<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST,OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");



require_once dirname(__DIR__, 2) . '/Auth/Token/chave-api.php';
require_once dirname(__DIR__, 2) . '/Auth/IP/pega-ip-usuario.php';
require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['erroMessage' => 'Método não permitido']);
    exit();
}

// Captura e valida os dados recebidos
$dadosRecebidos = file_get_contents('php://input');
if (empty($dadosRecebidos)) {
    echo json_encode(['erroMessage' => 'Erro ao acessar o sistema']);
    exit();
}

$dados = json_decode($dadosRecebidos, true);

// $chaveSecreta = "z3X!p@v9&LuGm8T#b7KdQ2Y^cWjN6R4s";
// Onde tem ChaveApi ficava $chaveSecreta

$tokenRecebido = $dados["token"] ?? null; // O token recebido do usuário

if (!$tokenRecebido) {
    echo json_encode(["success" => false, "error" => "Token não fornecido"]);
    exit();
}

try {

    // $decoded = JWT::decode($tokenRecebido, new Key(chaveApi(), 'HS256'));
    $decoded = desencriptaJWT($tokenRecebido);
    // Substituido por uma função reutilizavel

    if ($decoded->Ip !== PegarIpDoUsuario()) {
        echo json_encode(["success" => false, "error" => "Token inválido"]);
        exit();
    }

    echo json_encode(["success" => true, "data" => (array) $decoded]);
} catch (Exception $e) {
    $mensagemErro = $e->getMessage();

    if (strpos($mensagemErro, "Expired") !== false) {
        echo json_encode(["success" => false, "error" => "Token expirado"]);
    } else {
        echo json_encode(["success" => false, "error" => "Token inválido"]);
    }
}


function desencriptaJWT($tokenRecebido)
{

    return JWT::decode($tokenRecebido, new Key(chaveApi(), 'HS256'));
}
