<?php

//include session management
require_once 'session_management.php';

// Se não houver usuário logado, redirecionar para a tela de login
if (!isset($_SESSION['nome'])) {
    header("Location: login.php");
    exit;
}
// Caminho do arquivo de log (histórico)
$arquivoLog = __DIR__ . "/calculos.log";

// Função para gravar no arquivo de log
function gravarLog($arquivo, $dados) {
    $handle = fopen($arquivo, 'ab');
    if ($handle) {
        fwrite($handle, $dados . "\n");
        fclose($handle);
    }
}

if (isset($_POST['excluir_linha'])) {
    // Decodifica a linha recebida
    $linhaParaExcluir = base64_decode($_POST['excluir_linha']);
    // Chama a função que regrava o log sem a linha exata
    regravarLogSemLinha($arquivoLog, $linhaParaExcluir);
    // Recarrega o histórico
    $historico = carregarHistorico($arquivoLog);
}

// Função para carregar histórico do arquivo de log e retornar array de linhas
function carregarHistorico($arquivo) {
    if (!file_exists($arquivo)) {
        return [];
    }
    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return $linhas;
}

// Função para regravar o log sem uma linha específica (exclusão)
function regravarLogSemLinha($arquivo, $linhaExata) {
    $linhas = carregarHistorico($arquivo);

    foreach ($linhas as $key => $linha) {
        // Se a linha do arquivo for exatamente igual à que queremos excluir
        if ($linha === $linhaExata) {
            unset($linhas[$key]);
        }
    }

    // Reescreve o log sem a linha removida
    if (!empty($linhas)) {
        file_put_contents($arquivo, implode("\n", $linhas) . "\n");
    } else {
        // Se todas foram removidas ou se estava vazio
        file_put_contents($arquivo, "");
    }
}

// Inicializando variáveis do formulário
$velocidade = '';
$peso = '';
$unidade = 'gramas';
$descricao = '';
$energia = '';

// Carrega todo o histórico
$historico = carregarHistorico($arquivoLog);

// Trata exclusão via POST
if (isset($_POST['excluir']) && is_numeric($_POST['excluir'])) {
    $indiceExcluir = (int) $_POST['excluir'];
    
    // Recarrega o histórico após exclusão
    $historico = carregarHistorico($arquivoLog);
} else {
    // Caso não seja exclusão, apenas verifica se há cálculo a fazer
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['velocidade']) && !isset($_POST['excluir'])) {
        $velocidade = isset($_POST['velocidade']) ? floatval($_POST['velocidade']) : 0;
        $peso       = isset($_POST['peso']) ? floatval($_POST['peso']) : 0;
        $unidade    = isset($_POST['unidade']) ? $_POST['unidade'] : 'gramas';
        $descricao  = isset($_POST['descricao']) ? trim($_POST['descricao']) : '';

        // Conversão de peso para kg
        // 1 grama = 0.001 kg, 1 grain ~ 0.0000648 kg
        if ($unidade === 'gramas') {
            $pesoConvertido = $peso * 0.001;
        } else {
            $pesoConvertido = $peso * 0.0000648;
        }

        // Cálculo da energia cinética (E = 1/2 * m * v^2)
        $energia = 0.5 * $pesoConvertido * ($velocidade ** 2);

        // Prepara dados para log
        $dataHora = date('Y-m-d H:i:s');
        $usuario  = $_SESSION['nome'];
        $linha    = "$dataHora | Usuário: $usuario | Velocidade: $velocidade | Peso: $peso $unidade | Energia: $energia | Descrição: $descricao";

        // Grava no arquivo de log
        gravarLog($arquivoLog, $linha);

        // Recarrega histórico
        $historico = carregarHistorico($arquivoLog);
    }
}

// Filtra o histórico para mostrar apenas do usuário logado
$historico = array_filter($historico, function($linha) {
    $partes = explode("|", $linha);
    if (count($partes) < 6) return false;
    $usuarioNoLog = trim(str_replace("Usuário:", "", $partes[1]));
    return $usuarioNoLog === $_SESSION['nome'];
});

// Reorganiza os índices do array após filtrar
$historico = array_values($historico);

// Vamos criar lista de descrições para os filtros (somente do usuário logado)
$descricoesUnicas = [];
foreach ($historico as $linha) {
    $partes = explode("|", $linha);
    if (count($partes) < 6) continue;
    $descricaoTmp = trim(str_replace("Descrição:", "", $partes[5]));
    if ($descricaoTmp !== '' && !in_array($descricaoTmp, $descricoesUnicas)) {
        $descricoesUnicas[] = $descricaoTmp;
    }
}

// Verifica se houve filtro por descrição
$filtroDescricao = isset($_GET['filtroDescricao']) ? trim($_GET['filtroDescricao']) : '';
if ($filtroDescricao !== '') {
    $historico = array_filter($historico, function($linha) use ($filtroDescricao) {
        $partes = explode("|", $linha);
        if (count($partes) < 6) return false;
        $descricao = trim(str_replace("Descrição:", "", $partes[5]));
        return $descricao === $filtroDescricao;
    });
    // Reorganiza índices após o novo filtro
    $historico = array_values($historico);
}

// Cálculo das médias e do spread
$velocidadesArr = [];
$energiasArr    = [];

foreach ($historico as $linha) {
    $partes = explode("|", $linha);
    if (count($partes) < 6) continue;

    $velocidadeTmp = trim(str_replace("Velocidade:", "", $partes[2]));
    $energiaTmp    = trim(str_replace("Energia:", "", $partes[4]));

    $velocidadesArr[] = (float) $velocidadeTmp;
    $energiasArr[]    = (float) $energiaTmp;
}

$countHistorico    = count($velocidadesArr);
$mediaVelocidade   = 0;
$mediaEnergia      = 0;
$spreadVelocidade  = 0;

if ($countHistorico > 0) {
    $mediaVelocidade  = array_sum($velocidadesArr) / $countHistorico;
    $mediaEnergia     = array_sum($energiasArr) / $countHistorico;
    $spreadVelocidade = max($velocidadesArr) - min($velocidadesArr);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Calculadora de Energia em Joules</title>
    <style>
        /* Responsividade básica */
        @media (max-width: 600px) {
            .calculadora-container,
            .historico-container {
                width: 90% !important;
                margin: 10px auto !important;
            }
            table {
                font-size: 14px;
            }
            .filtro-container form {
                display: block;
            }
            .filtro-container form select,
            .filtro-container form button {
                margin-top: 10px;
                width: 100% !important;
                max-width: none !important;
            }
        }

        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            text-align: center; 
        }
        h1, h2 {
            color: #333;
        }
        .calculadora-container {
            border: 1px solid #ccc;
            padding: 20px;
            width: 350px;
            margin: 0 auto;
            border-radius: 5px;
            text-align: left; 
            display: inline-block;
        }
        label {
            display: block;
            margin-top: 10px;
        }
        input[type="number"],
        input[type="text"],
        select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            margin-top: 5px;
        }
        .botao {
            margin-top: 15px;
            padding: 10px 15px;
            background-color: #007BFF;
            color: #FFF;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        .botao:hover {
            background-color: #0056b3;
        }
        .botao-limpar {
            background-color: #6c757d;
            margin-left: 10px;
        }
        .botao-limpar:hover {
            background-color: #5a6268;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 15px;
        }
        table thead tr {
            background-color: #f2f2f2;
        }
        table th, table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ccc;
        }
        .historico-container {
            margin-top: 20px;
            width: 90%;
            margin: 20px auto;
            text-align: left;
        }
        .filtro-container {
            margin-bottom: 15px;
        }
        .filtro-container form select {
            max-width: 300px; /* Limite de largura */
        }
        .botao-excluir {
            color: #fff;
            background-color: #dc3545;
            padding: 5px 10px;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .botao-excluir:hover {
            background-color: #bb2d3b;
        }
        .resultado-energia {
            margin-top: 10px;
            font-weight: bold;
            color: #333;
        }
        tfoot tr td {
            font-weight: bold;
            background-color: #f9f9f9;
        }
    </style>
    <script>
        
    function limparCampos() {
        document.getElementById("velocidade").value = '';
        document.getElementById("peso").value = '';
        document.getElementById("descricao").value = '';
        document.getElementById("unidade").selectedIndex = 0;
    }
    </script>
</head>
<body>
<h1>Calculadora de Energia em Joules</h1>

<div class="calculadora-container">
    <form method="POST" action="">
        <label for="velocidade">Velocidade (m/s):</label>
        <input 
            type="number" 
            step="0.01" 
            name="velocidade" 
            id="velocidade" 
            value="<?php echo htmlspecialchars($velocidade); ?>" 
            required
        >

        <label for="peso">Peso:</label>
        <input 
            type="number" 
            step="0.01" 
            name="peso" 
            id="peso"
            value="<?php echo htmlspecialchars($peso); ?>" 
            required
        >

        <label for="unidade">Unidade de Peso:</label>
        <select name="unidade" id="unidade">
            <option value="gramas" <?php if($unidade === 'gramas') echo 'selected'; ?>>Gramas</option>
            <option value="grains" <?php if($unidade === 'grains') echo 'selected'; ?>>Grains</option>
        </select>

        <label for="descricao">Descrição (opcional):</label>
        <input 
            type="text" 
            name="descricao" 
            id="descricao" 
            value="<?php echo htmlspecialchars($descricao); ?>"
            placeholder="Ex: Teste..."
        >

        <button type="submit" class="botao">Calcular</button>
        <button type="button" class="botao botao-limpar" onclick="limparCampos()">Limpar</button>
    </form>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $energia !== '' && !isset($_POST['excluir'])): ?>
        <div class="resultado-energia">
            Resultado da Energia: <?php echo htmlspecialchars($energia); ?> Joules
        </div>
    <?php endif; ?>
</div>

<div class="historico-container">
    <h2>Histórico de Cálculos</h2>

    <!-- Filtro de descrição -->
    <div class="filtro-container">
        <form method="GET" action="">
            <label for="filtroDescricao">Filtrar por descrição:</label>
            <select name="filtroDescricao" id="filtroDescricao">
                <option value="">Todas</option>
                <?php foreach ($descricoesUnicas as $desc): ?>
                    <option
                        value="<?php echo htmlspecialchars($desc); ?>"
                        <?php if ($filtroDescricao === $desc) echo 'selected'; ?>
                    >
                        <?php echo htmlspecialchars($desc); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="botao">Filtrar</button>
        </form>
    </div>

    <?php if (count($historico) > 0): ?>
        <table id="dados">
            <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Usuário</th>
                <th>Velocidade (m/s)</th>
                <th>Peso</th>
                <th>Energia (J)</th>
                <th>Descrição</th>
                <th>Ação</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($historico as $indice => $linha):
                $partes = explode("|", $linha);
                if (count($partes) < 6) continue;

                $dataHora     = trim($partes[0]);
                $usuario      = trim(str_replace("Usuário:", "", $partes[1]));
                $velocidadeLog= trim(str_replace("Velocidade:", "", $partes[2]));
                $pesoLog      = trim(str_replace("Peso:", "", $partes[3]));
                $energiaLog   = trim(str_replace("Energia:", "", $partes[4]));
                $descricaoLog = trim(str_replace("Descrição:", "", $partes[5]));
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($dataHora); ?></td>
                    <td><?php echo htmlspecialchars($usuario); ?></td>
                    <td><?php echo htmlspecialchars($velocidadeLog); ?></td>
                    <td><?php echo htmlspecialchars($pesoLog); ?></td>
                    <td><?php echo htmlspecialchars($energiaLog); ?></td>
                    <td><?php echo htmlspecialchars($descricaoLog); ?></td>
                    <td>
                        <!-- Exclusão via método POST -->
                        <form method="POST" style="display:inline;">
                        <input 
                            type="hidden" 
                            name="excluir_linha" 
                            value="<?php echo base64_encode($linha); ?>" 
                            />
                            <button 
                                type="submit" 
                                class="botao-excluir"
                                onclick="return confirm('Deseja realmente excluir esta linha?');">
                                Excluir
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td style="font-weight: normal;"></td>
                    <!-- Média de Velocidade na coluna de Velocidade -->
                    <td>
                        <?php echo $countHistorico > 0 ? 'Média: ' . number_format($mediaVelocidade, 2) . ' m/s': '-'; ?>
                    </td>
                    <!-- Spread (velocidade máxima - mínima) fica abaixo do Peso, conforme solicitado -->
                    <td>
                        <?php echo $countHistorico > 0 ? 'Spread: '. number_format($spreadVelocidade, 2) . ' m/s' : '-'; ?>
                    </td>
                    <!-- Média de Energia na coluna de Energia -->
                    <td>
                        <?php echo $countHistorico > 0 ? number_format($mediaEnergia, 2).' J' : '-'; ?>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    <?php else: ?>
        <p>Nenhum cálculo encontrado para o filtro aplicado.</p>
    <?php endif; ?>
</div>
<button class="botao" onclick="baixarCSV()">
  <i class="fa fa-download"></i>
  Baixar CSV
</button>

</body>
</html>