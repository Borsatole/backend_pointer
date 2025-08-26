<?php

include_once(dirname(__DIR__, 2) . '/Auth/IP/pega-ip-usuario.php');

function RegistrarTentativa()
{
    $ip = PegarIpDoUsuario();
    global $pdo;

    try {
        file_put_contents("log.txt", "IP recebido: $ip\n", FILE_APPEND);

        // Verifica se o IP já está na tabela
        $stmt = $pdo->prepare("SELECT * FROM tentativas_login WHERE ip = ?");
        $stmt->execute([$ip]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Se o IP já existe, incrementa o número de tentativas
            $stmt = $pdo->prepare("UPDATE tentativas_login SET tentativas = tentativas + 1, ultima_tentativa = NOW() WHERE ip = ?");
            $stmt->execute([$ip]);
            file_put_contents("log.txt", "IP atualizado no banco.\n", FILE_APPEND);
        } else {
            // Se o IP não existe, insere um novo registro
            $stmt = $pdo->prepare("INSERT INTO tentativas_login (ip, tentativas, ultima_tentativa) VALUES (?, 1, NOW())");
            $stmt->execute([$ip]);
            file_put_contents("log.txt", "Novo IP inserido no banco.\n", FILE_APPEND);
        }

        echo json_encode([
            "success" => false,
            "message" => "Login ou senha incorretos"]);
        die();
    } catch (PDOException $e) {
        file_put_contents("log.txt", "Erro no banco: " . $e->getMessage() . "\n", FILE_APPEND);
        die(json_encode(["error" => "Erro no banco: " . $e->getMessage()]));
    }
}
?>