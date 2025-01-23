<?php
session_start();

// Caminho do arquivo de usuários
$arquivoUsuarios = __DIR__ . "/usuarios.log";

// Função para carregar usuários
function carregarUsuarios($arquivo) {
    if (!file_exists($arquivo)) {
        return [];
    }
    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return $linhas;
}

// Verifica se o formulário de login foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioInformado = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $senhaInformada   = isset($_POST['data_nascimento']) ? trim($_POST['data_nascimento']) : '';

    $usuariosExistentes = carregarUsuarios($arquivoUsuarios);
    $encontrou = false;

    foreach ($usuariosExistentes as $u) {
        list($usuarioArquivo, $senhaArquivo) = explode("|", $u);
        if ($usuarioArquivo === $usuarioInformado && $senhaArquivo === $senhaInformada) {
            $encontrou = true;
            break;
        }
    }

    if ($encontrou) {
        // Faz login
        $_SESSION['nome'] = $usuarioInformado;
        header("Location: index.php");
        exit;
    } else {
        $erro = "Credenciais inválidas. Verifique o usuário e a data de nascimento.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            background-color: #f9f9f9;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.0em;
            color: #333;
        }
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            border: 2px solid #ccc;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="date"] {
            width: 100%;
            padding: 10px;
            margin-top: 7px;
            box-sizing: border-box;
            font-size: 1em;
        }
        button {
            margin-top: 20px;
            padding: 12px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 1em;
        }
        button:hover {
            background-color: #0056b3;
        }
        .erro {
            color: red;
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>
<body>

<h1>Login</h1>

<div class="form-container">
    <form method="POST" action="">
        <label for="usuario">Nome de Usuário:</label>
        <input type="text" name="usuario" id="usuario" placeholder="Digite o usuário" required>

        <label for="data_nascimento">Data de Nascimento (calendário):</label>
        <input type="date" name="data_nascimento" id="data_nascimento" required>
        
        <button type="submit">Entrar</button>

        <?php if (isset($erro)): ?>
            <div class="erro"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>
    </form>
</div>

</body>
</html>