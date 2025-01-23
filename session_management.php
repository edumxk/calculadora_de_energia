<?php
session_start();



// Exemplo de tratamento de logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Página Inicial</title>
    <!-- Exemplo de uso de ícones do Font Awesome -->
    <link 
        rel="stylesheet" 
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" 
        
    />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f9f9f9;
        }
        h3 {
            text-align: center;
            margin-bottom: 30px;
        }
        .logout-container {
            text-align: center;
            margin-top: 20px;
        }
        .logout-container form button {
            padding: 10px 15px;
            background-color: #dc3545;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 1em;
        }
        .logout-container form button:hover {
            background-color: #c82333;
        }
        .rodape {
            margin-top: 40px;
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }
        .rodape a {
            margin: 0 10px;
            color: #333;
            text-decoration: none;
            font-size: 1.2em;
        }
        .rodape a:hover {
            color: #007BFF;
        }
        .rodape p {
            margin-top: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <h3>Bem-vindo(a), <?php echo htmlspecialchars($_SESSION['nome'] ?? 'Usuário'); ?></h3>

    <!-- Conteúdo principal da sua página -->

    <!-- Botão de logout -->
    <div class="logout-container">
        <form method="POST">
            <button type="submit" name="logout">Logout</button>
        </form>
    </div>

    <!-- Rodapé com ícones de redes sociais e créditos -->
    <footer class="rodape">
        <a href="https://instagram.com/tirodepressao.to" target="_blank" rel="noopener">
            <i class="fab fa-instagram"></i>
        </a>
        <a href="https://youtube.com/@eduardopatrick2567" target="_blank" rel="noopener">
            <i class="fab fa-youtube"></i>
        </a>

        <a href="https://github.com/edumxk" target="_blank" rel="noopener">
            <i class="fa-brands fa-github"></i>
        </a> 
    </footer>
</body>
</html>

<script>
// Exemplo de inserção da variável PHP no JavaScript
let statusSessao = "<?php echo $_SESSION['nome'] ?? ''; ?>";
const logoutContainer = document.querySelector('.logout-container');
// Verifica se a sessão está vazia
if (!statusSessao) {
  // Oculta a div se a sessão não estiver definida
  if (logoutContainer) {
    logoutContainer.style.display = 'none';
  }
}else
    logoutContainer.style.display = 'block';

</script>