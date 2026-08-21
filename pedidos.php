<?php

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

exigir_login();

$stmt = $pdo->prepare("
    SELECT *
    FROM pedidos
    WHERE usuario_id = ?
    ORDER BY id DESC
");

$stmt->execute([
    $_SESSION['usuario_id']
]);

$pedidos = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Meus pedidos - MBM Suplementos</title>

<link
    rel="stylesheet"
    href="/mbm/assets/css/style.css"
>

</head>

<body>

<header class="header">

<div class="container header-content">

    <a href="/mbm/index.php" class="logo">
        MBM <span>SUPLEMENTOS</span>
    </a>

    <nav class="menu">

        <a href="/mbm/index.php">
            Loja
        </a>

        <a href="/mbm/carrinho.php">
            Carrinho
        </a>

        <a href="/mbm/logout.php">
            Sair
        </a>

    </nav>

</div>

</header>


<main class="section">

<div class="container">

    <div class="section-title left">

        <p>
            SUA CONTA
        </p>

        <h1>
            Meus pedidos
        </h1>

    </div>


    <?php if (isset($_GET['sucesso'])): ?>

        <div class="alert success">

            Pedido #<?= (int) $_GET['sucesso'] ?>
            criado com sucesso!

        </div>

    <?php endif; ?>


    <?php if (empty($pedidos)): ?>

        <div class="empty">

            <h2>
                Você ainda não fez nenhum pedido.
            </h2>

            <br>

            <a
                href="/mbm/index.php"
                class="btn"
            >
                IR PARA A LOJA
            </a>

        </div>

    <?php else: ?>

        <div class="orders">

            <?php foreach ($pedidos as $pedido): ?>

                <div class="order-card">

                    <div>

                        <strong>
                            Pedido #<?= $pedido['id'] ?>
                        </strong>

                        <p>
                            <?= date(
                                'd/m/Y H:i',
                                strtotime($pedido['criado_em'])
                            ) ?>
                        </p>

                    </div>


                    <div>

                        <span class="status">
                            <?= htmlspecialchars(
                                $pedido['status']
                            ) ?>
                        </span>

                    </div>


                    <strong>

                        R$
                        <?= number_format(
                            $pedido['total'],
                            2,
                            ',',
                            '.'
                        ) ?>

                    </strong>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

</main>

</body>
</html>
