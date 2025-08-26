<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once dirname(__DIR__, 2). '/E-mail/functions.php';
require_once dirname(__DIR__, 2). '/Auth/IP/pega-ip-usuario.php';
require_once dirname(__DIR__, 2). '/Auth/OtpCode/functions.php';
require_once dirname(__DIR__, 2). '/conexao.php';


$dados = json_decode(file_get_contents('php://input'), true)['dadosUsuario'];
$Email = $dados['email'] ?? null;
$ip = PegarIpDoUsuario();



if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (empty($Email) || !filter_var($Email, FILTER_VALIDATE_EMAIL) || $Email === 'null') {
    http_response_code(400);
    exit; 
}

if (verificarSeUsuarioExiste($Email)) {
    echo json_encode([
       'success' => false,
       'message' => 'Esse e-mail ja está em uso, por favor escolha outro.'
    ]);
    exit;
}

EnviarEmaileRegistrarNoSistema($Email, $ip);
gerarCodigoNumerico4Digitos($Email, $ip);

echo json_encode([
    "success" => true,
    "message" => "Codigo enviado com sucesso!",
    "dados" => $dados

])



?>