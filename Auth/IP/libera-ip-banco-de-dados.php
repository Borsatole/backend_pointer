<?php

// Essa função deleta o ip da tabela tentativas_login e retorna true para indicar que o ip foi removido

function LiberarIPNoBancodedados($ip)
{
    global $pdo;

    $stmt = $pdo->prepare("DELETE FROM tentativas_login WHERE ip = ?");
    $stmt->execute([$ip]);

    return true; // Indica que o IP foi removido
}

?>