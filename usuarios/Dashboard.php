<?php

require_once dirname(__DIR__, 1) . '/vendor/autoload.php';
require_once dirname(__DIR__, 1) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 1) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 1) . '/conexao.php';

require_once(dirname(__DIR__, 1) . '/usuarios/BuscarDados/buscas-usuario.php');


$dadosRecebidos = file_get_contents('php://input');
$dados = json_decode($dadosRecebidos, true);


echo json_encode(
    [
        "success" => true,
        "InformacoesBasicas" => [
            "user_id" => $userId,
            "NomeDoUsuario" => buscarNome($userId),
            "Avatar" => buscarAvatar($userId),
            "email" => $validacao["data"]["email"],
            "TipoDeUsuario" => buscaCompleta($userId)[0]["nivel"] ?? "padrao"
        ],
    ]
);