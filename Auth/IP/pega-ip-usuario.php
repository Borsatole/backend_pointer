<?php

function PegarIpDoUsuario()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    // Garante que o IP é válido antes de registrar
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        die(json_encode(["error" => "IP inválido detectado!"]));
    }

    return $ip;
}

?>