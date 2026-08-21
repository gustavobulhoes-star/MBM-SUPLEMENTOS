<?php

require_once __DIR__ . '/config/database.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (
        $nome === '' ||
        $email === '' ||
        $senha === ''
    ) {

        $mensagem = 'Preencha todos os campos.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensagem = 'E-mail inválido.';

    } elseif (strlen($senha) < 6) {

        $mensagem =
            'A senha deve ter pelo menos 6 caracteres.';

    } else {

        $stmt = $pdo->prepare("
            SELECT id
            FROM usuarios
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $mensagem =
                'Este e-mail já está cadastrado.';

        } else {

            $senha_hash =
                password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );

            $stmt = $pdo->prepare("
                INSERT INTO usuarios
                (nome, email, senha, tipo)
                VALUES (?, ?, ?, 'admin')
            ");

            $stmt->execute([
                $nome,
                $email,
                $senha_hash
            ]);

            $mensagem =
                'Administrador criado com sucesso! ' .
                'Agora você pode entrar no painel.';
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

<title>Criar administrador</title>

<link
    rel="stylesheet"
    href="/mbm/assets/css/style.css"
>

</head>

<body class="auth-page">

<div class="auth-box">

    <div class="logo auth-logo">
        MBM <span>ADMIN</span>
    </div>

    <h1>
        Criar administrador
    </h1>

    <?php if ($mensagem !== ''): ?>

        <div class="alert success">
            <?= htmlspecialchars($mensagem) ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <label>
            Nome
        </label>

        <input
            type="text"
            name="nome"
            required
        >


        <label>
            E-mail
        </label>

        <input
            type="email"
            name="email"
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


        <button
            class="btn full"
            type="submit"
        >
            CRIAR ADMINISTRADOR
        </button>

    </form>


    <p class="auth-footer">

        <a href="/mbm/admin/login.php">
            Ir para login do administrador
        </a>

    </p>

</div>

</body>
</html>
