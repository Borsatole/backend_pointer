<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


require_once dirname(__DIR__, 2). '/Auth/IP/pega-ip-usuario.php';
require_once dirname(__DIR__, 2). '/Auth/OtpCode/functions.php';
require_once dirname(__DIR__, 2). '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;

}

if ($_SERVER['REQUEST_METHOD']!== 'POST') {
    http_response_code(405);
    exit; 
}

$dados = $_POST;
$codigo = $dados['codigo'] ?? null;
$nome = $dados['nome']?? null;
$email = $dados['email'] ?? null;
$senha = $dados['senha'] ?? null;
$telefone = $dados['telefone'] ?? '';
$nivel = 'padrao';
$avatar = 'avatar1.png';
$ip = PegarIpDoUsuario() ?? null;

if (empty($codigo)){
    echo json_encode([
       'success' => false,
       'message' => 'Você não digitou o codigo.' 
    ]);
    exit;
}


if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || $email === 'null') {
    echo json_encode([
        'success' => false,
        'message' => 'Email invalido'
    ]);
    exit;
}

if (verificarSeUsuarioExiste($email)) {
    echo json_encode([
       'telefone' => $telefone,
       'email' => $email,
       'success' => false,
       'message' => 'Esse email ja esta cadastrado.'
    ]);
    exit;
}

$cadastro = cadastrarUsuario($nome, $email, $senha, $nivel, $telefone, $avatar);

if (!$cadastro) {
    echo json_encode([
       'success' => false,
       'message' => 'Erro ao cadastrar usuario'
    ]);
    exit;
}


if (verificarCodigo($codigo, $email, $ip)) {
    echo json_encode([
        'success' => true,
        'message' => 'Codigo verificado com sucesso'
    ]);
    exit;
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Codigo invalido'
    ]);
    exit;
}




?>