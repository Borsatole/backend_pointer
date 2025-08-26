<?php

include_once(dirname(__DIR__, 2) . '/Auth/IP/pega-ip-usuario.php');
include_once(dirname(__DIR__, 2) . '/Auth/IP/libera-ip-banco-de-dados.php');


function VerificarBloqueioIp()
{
    global $pdo;

    $ip = PegarIpDoUsuario();



    // Definir o fuso horário para o fuso horário correto, por exemplo, 'America/Sao_Paulo'
    date_default_timezone_set('America/Sao_Paulo');

    // Definições de bloqueio
    $maxTentativas = 4;
    $tempoBloqueio = 30; // Tempo de bloqueio em segundos

    // Consulta o banco para buscar as tentativas e o último horário
    $stmt = $pdo->prepare("SELECT tentativas, ultima_tentativa FROM tentativas_login WHERE ip = ?");
    $stmt->bindParam(1, $ip);
    $stmt->execute();

    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se não houver registro, o usuário não está bloqueado
    if (!$resultado) {
        return ['bloqueado' => false, 'tentativas' => 0, 'tempoRestante' => 0];
    }

    // Convertendo ultima_tentativa para timestamp
    $ultimaTentativa = strtotime($resultado['ultima_tentativa']);
    $tempoAgora = time(); // Hora atual no PHP
    $tempoDecorrido = $tempoAgora - $ultimaTentativa; // Tempo desde a última tentativa

    // Verifica se o usuário deve estar bloqueado
    if ($resultado['tentativas'] >= $maxTentativas) {
        // Se o tempo decorrido for menor que o tempo de bloqueio, o usuário ainda está bloqueado
        if ($tempoDecorrido < $tempoBloqueio) {
            // O tempo de bloqueio ainda não passou
            $tempoRestante = $tempoBloqueio - $tempoDecorrido; // Calcular o tempo restante para o desbloqueio
            return [
                'bloqueado' => true,
                'tentativas' => $resultado['tentativas'],
                'tempoRestante' => $tempoRestante
            ];
        } else {
            // O tempo de bloqueio passou, libera o usuário
            LiberarIPNoBancodedados($ip);
            return ['bloqueado' => false, 'tentativas' => 0, 'tempoRestante' => 0];
        }
    }

    // Se passou o tempo de bloqueio ou o número de tentativas é menor que o máximo
    return ['bloqueado' => false, 'tentativas' => $resultado['tentativas'], 'tempoRestante' => 0];
}

?>