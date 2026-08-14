<?php

session_start();

require_once __DIR__ . "/config/database.php";

$erro = '';
$sucesso = '';

$modo = $_GET['modo'] ?? 'login';


/* =========================================================
   PROCESSAMENTO
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';


    /* =====================================================
       LOGIN
    ===================================================== */

    if ($acao === 'login') {

        // Agora aceita usuário OU e-mail
        $login = trim($_POST['login'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($login === '' || $senha === '') {

            $erro = 'Preencha usuário/e-mail e senha.';

        } else {


            /* =================================================
               1. PRIMEIRO VERIFICA ADMINISTRADOR
            ================================================= */

            $admin = false;

            /*
             * A conta administrativa que você criou possui:
             * id
             * nome
             * usuario
             * senha
             */

            $stmtAdmin = $pdo->prepare("
                SELECT
                    id,
                    nome,
                    usuario,
                    senha
                FROM administradores
                WHERE usuario = ?
                LIMIT 1
            ");

            $stmtAdmin->execute([$login]);

            $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);


            /*
             * SE FOR ADMIN
             */

            if (
                $admin &&
                password_verify($senha, $admin['senha'])
            ) {

                // Cria uma nova sessão por segurança
                session_regenerate_id(true);

                // Dados do administrador
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_nome'] = $admin['nome'];
                $_SESSION['admin_usuario'] = $admin['usuario'];

                // Marca que é administrador
                $_SESSION['tipo_usuario'] = 'admin';

                // Vai para o painel administrativo
                header("Location: admin/index.php");
                exit;
            }


            /* =================================================
               2. SE NÃO FOR ADMIN, VERIFICA CLIENTE
            ================================================= */

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    nome,
                    email,
                    senha
                FROM usuarios
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([$login]);

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);


            /*
             * CLIENTE ENCONTRADO
             */

            if (
                $usuario &&
                password_verify($senha, $usuario['senha'])
            ) {

                // Nova sessão
                session_regenerate_id(true);

                // Dados do cliente
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];

                // Marca que é cliente
                $_SESSION['tipo_usuario'] = 'cliente';

                // Vai para a loja
                header("Location: index.php");
                exit;

            } else {

                $erro = 'Usuário/e-mail ou senha incorretos.';
            }
        }
    }


    /* =====================================================
       CADASTRO
    ===================================================== */

    if ($acao === 'cadastro') {

        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        $modo = 'cadastro';


        if (
            $nome === '' ||
            $email === '' ||
            $senha === '' ||
            $confirmar === ''
        ) {

            $erro = 'Preencha todos os campos.';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $erro = 'Digite um e-mail válido.';

        } elseif (strlen($senha) < 6) {

            $erro = 'A senha deve ter pelo menos 6 caracteres.';

        } elseif ($senha !== $confirmar) {

            $erro = 'As senhas não são iguais.';

        } else {


            /* =================================================
               VERIFICA SE O E-MAIL JÁ EXISTE
            ================================================= */

            $stmt = $pdo->prepare("
                SELECT id
                FROM usuarios
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([$email]);


            if ($stmt->fetch()) {

                $erro = 'Este e-mail já está cadastrado.';

            } else {


                /* =================================================
                   CRIA SENHA SEGURA
                ================================================= */

                $senhaHash = password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );


                /* =================================================
                   CADASTRA CLIENTE
                ================================================= */

                $stmt = $pdo->prepare("
                    INSERT INTO usuarios
                    (nome, email, senha)
                    VALUES (?, ?, ?)
                ");

                $stmt->execute([
                    $nome,
                    $email,
                    $senhaHash
                ]);


                $sucesso = 'Cadastro realizado com sucesso!';

                $modo = 'login';
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        MBM Suplementos | Login
    </title>


    <link
        rel="stylesheet"
        href="./css/login.css?v=11"
    >

</head>


<body>


<div class="login-page">


    <!-- =====================================================
         LADO ESQUERDO
    ====================================================== -->

    <section class="brand-side">

        <div class="brand-overlay"></div>

        <div class="brand-content">


            <img
                src="./img/mbm logo sem fundo.png"
                alt="MBM Suplementos"
                class="brand-logo"
            >


            <div class="brand-line"></div>


            <span class="brand-tag">
                PERFORMANCE • FORÇA • RESULTADO
            </span>


            <h1>
                SUPLEMENTE.
                <strong>EVOLUA.</strong>
            </h1>


            <p>
                Produtos para quem leva treinamento,
                performance e evolução a sério.
            </p>


            <div class="brand-features">

                <div>
                    <b>01</b>
                    <span>Alta performance</span>
                </div>


                <div>
                    <b>02</b>
                    <span>Qualidade nos produtos</span>
                </div>


                <div>
                    <b>03</b>
                    <span>Foco nos seus resultados</span>
                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         LADO DIREITO
    ====================================================== -->

    <section class="form-side">


        <div class="login-card">


            <!-- LOGO PARA CELULAR -->

            <div class="mobile-logo">

                <img
                    src="./img/mbm logo sem fundo.png"
                    alt="MBM Suplementos"
                >

            </div>


            <!-- ERRO -->

            <?php if ($erro !== ''): ?>

                <div class="message error">

                    <?= htmlspecialchars($erro) ?>

                </div>

            <?php endif; ?>


            <!-- SUCESSO -->

            <?php if ($sucesso !== ''): ?>

                <div class="message success">

                    <?= htmlspecialchars($sucesso) ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 ABAS
            ================================================== -->

            <div class="tabs">

                <a
                    href="login.php?modo=login"
                    class="<?= $modo === 'login' ? 'active' : '' ?>"
                >
                    ENTRAR
                </a>


                <a
                    href="login.php?modo=cadastro"
                    class="<?= $modo === 'cadastro' ? 'active' : '' ?>"
                >
                    CRIAR CONTA
                </a>

            </div>


            <?php if ($modo === 'login'): ?>


                <!-- =================================================
                     LOGIN
                ================================================== -->

                <div class="form-title">

                    <span>
                        ÁREA DO CLIENTE
                    </span>


                    <h2>
                        Bem-vindo de volta
                    </h2>


                    <p>
                        Entre na sua conta para continuar.
                    </p>

                </div>


                <form method="POST">


                    <input
                        type="hidden"
                        name="acao"
                        value="login"
                    >


                    <div class="input-group">

                        <label for="login">
                            USUÁRIO OU E-MAIL
                        </label>


                        <input
                            type="text"
                            id="login"
                            name="login"
                            placeholder="Digite seu usuário ou e-mail"
                            autocomplete="username"
                            required
                        >

                    </div>


                    <div class="input-group">

                        <label for="senha">
                            SENHA
                        </label>


                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="main-button"
                    >

                        ENTRAR NA MBM

                        <span>
                            →
                        </span>

                    </button>


                </form>


                <p class="switch-text">

                    Ainda não possui uma conta?

                    <a href="login.php?modo=cadastro">
                        Criar conta
                    </a>

                </p>


            <?php else: ?>


                <!-- =================================================
                     CADASTRO
                ================================================== -->

                <div class="form-title">

                    <span>
                        NOVA CONTA
                    </span>


                    <h2>
                        Faça seu cadastro
                    </h2>


                    <p>
                        Crie sua conta e comece sua evolução.
                    </p>

                </div>


                <form method="POST">


                    <input
                        type="hidden"
                        name="acao"
                        value="cadastro"
                    >


                    <div class="input-group">

                        <label for="nome">
                            NOME
                        </label>


                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            placeholder="Seu nome"
                            autocomplete="name"
                            required
                        >

                    </div>


                    <div class="input-group">

                        <label for="email">
                            E-MAIL
                        </label>


                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="seu@email.com"
                            autocomplete="email"
                            required
                        >

                    </div>


                    <div class="input-group">

                        <label for="senha-cadastro">
                            SENHA
                        </label>


                        <input
                            type="password"
                            id="senha-cadastro"
                            name="senha"
                            placeholder="Mínimo 6 caracteres"
                            autocomplete="new-password"
                            minlength="6"
                            required
                        >

                    </div>


                    <div class="input-group">

                        <label for="confirmar">
                            CONFIRMAR SENHA
                        </label>


                        <input
                            type="password"
                            id="confirmar"
                            name="confirmar"
                            placeholder="Digite a senha novamente"
                            autocomplete="new-password"
                            minlength="6"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="main-button"
                    >

                        CRIAR MINHA CONTA

                        <span>
                            →
                        </span>

                    </button>


                </form>


                <p class="switch-text">

                    Já possui uma conta?

                    <a href="login.php?modo=login">
                        Entrar
                    </a>

                </p>


            <?php endif; ?>


            <a
                href="index.php"
                class="back-link"
            >

                ← Voltar para a loja

            </a>


        </div>

    </section>

</div>


</body>

</html>