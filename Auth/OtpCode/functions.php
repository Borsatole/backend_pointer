<?php

function EnviarEmaileRegistrarNoSistema($Email, $ip) {
    $codigo = gerarCodigoNumerico4Digitos();

    casoExistaDeletarOtp($Email, $ip);

    if (gravarCodigoNoBanco($codigo, $Email, $ip)) {
        enviarEmail($Email, "Codigo de verificacao", $codigo);
    }
}

function gerarCodigoNumerico4Digitos() {
    $codigo = '';
    for ($i = 0; $i < 4; $i++) {
        $codigo .= rand(0, 9);
    }
    return $codigo;
}

function gravarCodigoNoBanco($codigo, $email, $ip) {
    try {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO otp_registro (codigo, email, ip) VALUES (:codigo, :email, :ip)");
        $stmt->bindParam(':codigo', $codigo);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':ip', $ip);
        $stmt->execute(); 
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }

}

function casoExistaDeletarOtp($email, $ip) {
    try {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM otp_registro WHERE email = :email AND ip = :ip");
        $stmt->bindParam(':email', $email); 
        $stmt->bindParam(':ip', $ip);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } 
    catch (PDOException $e) {
        return false; 
    }
}

function verificarCodigo($codigo, $email, $ip) {
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM otp_registro WHERE codigo = :codigo AND email = :email AND ip = :ip");
        $stmt->bindParam(':codigo', $codigo); 
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':ip', $ip);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

function cadastrarUsuario($nome, $email, $senha, $nivel, $telefone, $avatar) {
    try {
        global $pdo;
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, nivel, telefone, avatar) VALUES (:nome, :email, :senha, :nivel, :telefone, :avatar)");
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senha);
        $stmt->bindParam(':nivel', $nivel);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':avatar', $avatar);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        echo "Erro: ". $e->getMessage();
        return false;

    }
}


function verificarSeUsuarioExiste($email) {
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
        $stmt->bindParam(':email', $email);    
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage(); // Exibe erro de banco, se houver
        return false; 
    }
}



?>