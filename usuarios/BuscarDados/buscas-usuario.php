<?php


function buscaCompleta($userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

function buscarEmail($userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT email FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['email'];
}

function buscarNome($userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['nome'];
}

function buscarTelefone($userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT telefone FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['telefone'];
}

function buscarAvatar($userId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT avatar FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['avatar'];
}


?>