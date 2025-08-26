<?php

// Define Headers
header("Access-Control-Allow-Origin: *");
// header(header: "Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods:*");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");




require_once(__DIR__ . '/../conexao.php');
include_once(__DIR__ . '/IP/pega-ip-usuario.php');
include_once(__DIR__ . '/IP/libera-ip-banco-de-dados.php');
include(__DIR__ . '/IP/verifica-se-ip-esta-bloqueado.php');
include_once(__DIR__ . '/RateLimit/registra-tentativa-login-errado.php');
include_once(__DIR__ . '/Token/cria-jwt.php');

// Função para validar login e senha
function validaLoginSenha($login, $senha)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, email FROM usuarios WHERE email = ? AND senha = ?");
        $stmt->execute([$login, $senha]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result : false;
    } catch (PDOException $e) {
        return false;
    }
}

// Verifica se o método de requisição é POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false,
    'message' => 'Método não permitido']);
    exit;
}

// Verifica se o IP está bloqueado
$verificaIp = VerificarBloqueioIp();
if ($verificaIp['bloqueado']) {
    echo json_encode([
        'success' => false,
        'message' => 'Você está fazendo muitas tentativas. Aguarde ' . $verificaIp['tempoRestante'] . ' segundos para tentar novamente.'
    ]);
    exit;
}

// Captura e valida os dados recebidos
$dadosRecebidos = file_get_contents('php://input');
if (!$dadosRecebidos) {
    echo json_encode([
        'success' => false,
        'message' => 'Nenhum dado recebido']);
    exit;
}

$dados = json_decode($dadosRecebidos, true);
if (empty($dados['email']) || empty($dados['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Preencha todos os campos'
    ]);
    exit;
}

// Sanitiza os inputs
$email = filter_var($dados['email'], FILTER_SANITIZE_EMAIL);
$password = htmlspecialchars($dados['password']);

// Valida login e senha
$dadosLogin = validaLoginSenha($email, $password);

if (!$dadosLogin) {
    RegistrarTentativa();
    echo json_encode([
        'success' => false,
        'message' => 'Login ou senha incorretos'
    ]);
    exit;
}

// Login bem-sucedido, gera token JWT
echo json_encode([
    'success' => true,
    'JWT' => criaToken($dadosLogin['email'], $dadosLogin['id']),
    'message' => 'Logado com sucesso'
]);

?>