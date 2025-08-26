<?php
require_once dirname(__DIR__, 1) . '/vendor/autoload.php';
require_once dirname(__DIR__, 1) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 1) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 1) . '/conexao.php';

// Forçar mês/ano para testes (simulação)
$anoAtual = (int) date("Y");
$mesAtual = (int) date("m"); 
$hoje = new DateTime();      

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

try {
    $sql = "SELECT * FROM financeiro_contas_fixas";
    $stmt = $pdo->query($sql);
    $contasFixas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($contasFixas as $conta) {
        $ultimaExecucao = $conta['ultima_execucao'] ? new DateTime($conta['ultima_execucao']) : null;
        $dataFim = $conta['data_fim'] ? new DateTime($conta['data_fim']) : null;
        $recorrenciaMeses = (int)($conta['recorrencia'] ?? 1);

        // Se já tem data fim e já passou, ignora
        if ($dataFim && $dataFim < $hoje) {
            continue;
        }

        // Se nunca foi executado, pode rodar direto
        $podeExecutar = false;
        if (!$ultimaExecucao) {
            $podeExecutar = true;
        } else {
            // Calcula a próxima execução esperada
            $proximaExecucao = (clone $ultimaExecucao)->modify("+{$recorrenciaMeses} month");

            // Só executa se já passou do mês esperado
            if ((int)$proximaExecucao->format("Y") < $anoAtual ||
                ((int)$proximaExecucao->format("Y") == $anoAtual && (int)$proximaExecucao->format("m") <= $mesAtual)) {
                $podeExecutar = true;
            }
        }

        if (!$podeExecutar) {
            continue;
        }

        // Calcula a data de vencimento
        $dataVencimento = DateTime::createFromFormat("Y-m-d", "$anoAtual-$mesAtual-01");
        $dataVencimento->setDate($anoAtual, $mesAtual, $conta['dia_vencimento']);

        // Se a data de vencimento for inválida (ex: 31/02), ajusta para o último dia do mês
        if ($dataVencimento->format("m") != $mesAtual) {
            $dataVencimento = new DateTime("$anoAtual-$mesAtual-last day of this month");
        }

        // Evita duplicados: verifica se já existe conta no mês atual com mesma categoria e data
        $sqlCheck = "SELECT COUNT(*) FROM financeiro_contas_a_pagar 
                     WHERE categoria = :categoria AND data_vencimento = :data_vencimento";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([
            ":categoria" => $conta['categoria'],
            ":data_vencimento" => $dataVencimento->format("Y-m-d")
        ]);
        $jaExiste = $stmtCheck->fetchColumn();

        if ($jaExiste) {
            continue;
        }

        // Insere a conta
        $sqlInsert = "INSERT INTO financeiro_contas_a_pagar 
            (nome, descricao, categoria, valor, data_vencimento) 
            VALUES (:nome, :descricao, :categoria, :valor, :data_vencimento)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            ":nome" => $conta['nome'],
            ":descricao" => $conta['descricao'],
            ":categoria" => $conta['categoria'],
            ":valor" => $conta['valor'],
            ":data_vencimento" => $dataVencimento->format("Y-m-d")
        ]);

        // Atualiza ultima_execucao
        $sqlUpdate = "UPDATE financeiro_contas_fixas SET ultima_execucao = :ultima WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ":ultima" => $hoje->format("Y-m-d"),
            ":id" => $conta['id']
        ]);
    }

    echo json_encode(["success" => true, "message" => "Contas fixas processadas com sucesso"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
