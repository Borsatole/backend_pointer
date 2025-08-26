<?php
function registrahttponlycookie($token)
{
    setcookie(
        "token",           // Nome do cookie
        $token,           // Valor do cookie
        time() + 3600,   // Expiração (1 hora)
        "/",                // Caminho (disponível para todo o site)
        "",               // Domínio (padrão: mesmo domínio do servidor)
        false,            // Secure (true para HTTPS, false para HTTP)
        httponly: false           // HttpOnly (impede acesso via JavaScript)
    );
}
?>