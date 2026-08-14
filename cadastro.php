<?php

session_start();

require_once __DIR__ . '/config/database.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if ($nome === '' || $email === '' || $senha === '') {

        $erro = 'Preencha todos os campos.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erro = 'Digite um e-mail válido.';

    } elseif (strlen($senha) < 6) {

        $erro = 'A senha deve ter pelo menos 6 caracteres.';

    } elseif ($senha !== $confirmar) {

        $erro = 'As senhas não são iguais.';

    } else {

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

            $senha_hash = password_hash(
                $senha,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare("
                INSERT INTO usuarios
                (nome, email, senha, tipo)
                VALUES (?, ?, ?, 'cliente')
            ");

            $stmt->execute([
                $nome,
                $email,
                $senha_hash
            ]);

            $_SESSION['usuario_id'] = $pdo->lastInsertId();
            $_SESSION['usuario_nome'] = $nome;

            header('Location: /mbm/index.php');
            exit;
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

<title>Cadastro - MBM Suplementos</title>

<link
    rel="stylesheet"
    href="/mbm/assets/css/style.css"
>

</head>

<body class="auth-page">

<div class="auth-box">

    <a
        href="/mbm/index.php"
        class="logo auth-logo"
    >
        MBM <span>SUPLEMENTOS</span>
    </a>

    <h1>
        Criar conta
    </h1>

    <p class="auth-subtitle">
        Cadastre-se para realizar suas compras.
    </p>


    <?php if ($erro !== ''): ?>

        <div class="alert error">
            <?= htmlspecialchars($erro) ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <label>
            Nome completo
        </label>

        <input
            type="text"
            name="nome"
            value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
            required
        >


        <label>
            E-mail
        </label>

        <input
            type="email"
            name="email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
        >


        <label>
            Senha
        </label>

        <input
            type="password"
            name="senha"
            minlength="6"
            required
        >


        <label>
            Confirmar senha
        </label>

        <input
            type="password"
            name="confirmar"
            minlength="6"
            required
        >


        <button
            type="submit"
            class="btn full"
        >
            CRIAR CONTA
        </button>

    </form>


    <p class="auth-footer">
        Já possui uma conta?

        <a href="/mbm/login.php">
            Fazer login
        </a>
    </p>

</div>

</body>
</html>