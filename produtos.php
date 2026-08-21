<?php

session_start();

require_once __DIR__ . "/config/database.php";


/*
|--------------------------------------------------------------------------
| INICIA O CARRINHO
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}


/*
|--------------------------------------------------------------------------
| ADICIONAR PRODUTO AO CARRINHO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'adicionar') {

        $produto_id = (int)($_POST['produto_id'] ?? 0);

        if ($produto_id > 0) {

            $stmt = $pdo->prepare("
                SELECT id
                FROM produtos
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([$produto_id]);

            if ($stmt->fetch()) {

                if (isset($_SESSION['carrinho'][$produto_id])) {

                    $_SESSION['carrinho'][$produto_id]++;

                } else {

                    $_SESSION['carrinho'][$produto_id] = 1;

                }

            }
        }

        header("Location: produtos.php");
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| BUSCA OS PRODUTOS
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT *
    FROM produtos
    ORDER BY id DESC
");

$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CONTADOR DO CARRINHO
|--------------------------------------------------------------------------
*/

$quantidadeCarrinho = 0;

foreach ($_SESSION['carrinho'] as $quantidade) {

    $quantidadeCarrinho += (int)$quantidade;

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

    <title>Produtos | MBM Suplementos</title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        :root {

            --red: #e50914;
            --red2: #ff1822;
            --black: #080809;
            --black2: #050506;
            --gray: #151517;
            --gray2: #1d1d20;
            --border: #2c2c31;
            --muted: #888;
            --white: #fff;

        }


        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: var(--black);

            color: var(--white);

            min-height: 100vh;

        }


        /* =====================================================
           HEADER
        ===================================================== */

        header {

            height: 82px;

            background:
                linear-gradient(
                    90deg,
                    #060606 0%,
                    #0b0b0d 60%,
                    #16090a 100%
                );

            border-bottom:
                1px solid
                rgba(255,255,255,.08);

            display: flex;

            align-items: center;

            position: sticky;

            top: 0;

            z-index: 1000;

            box-shadow:
                0 5px 25px
                rgba(0,0,0,.35);

        }


        .header-container {

            width: 92%;

            max-width: 1250px;

            margin: auto;

            display: flex;

            align-items: center;

            justify-content: space-between;

        }


        /* =====================================================
           LOGO
        ===================================================== */

        .logo {

            display: flex;

            align-items: center;

            text-decoration: none;

            height: 82px;

        }


        .logo img {

            width: 150px;

            height: auto;

            display: block;

            object-fit: contain;

        }


        /* =====================================================
           MENU
        ===================================================== */

        nav {

            display: flex;

            align-items: center;

            gap: 28px;

        }


        nav a {

            color: #d8d8d8;

            text-decoration: none;

            font-size: 13px;

            font-weight: 800;

            transition: .2s;

        }


        nav a:hover {

            color: var(--red);

        }


        /* =====================================================
           CARRINHO
        ===================================================== */

        .cart {

            display: flex;

            align-items: center;

            gap: 7px;

            background: var(--red);

            color: white !important;

            padding: 11px 16px;

            border-radius: 7px;

            box-shadow:
                0 5px 18px
                rgba(229,9,20,.20);

        }


        .cart:hover {

            background: var(--red2);

        }


        .cart-count {

            background: white;

            color: var(--red);

            min-width: 20px;

            height: 20px;

            padding: 2px 5px;

            border-radius: 20px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            font-size: 10px;

            font-weight: 900;

        }


        /* =====================================================
           PÁGINA
        ===================================================== */

        .products-section {

            width: 92%;

            max-width: 1250px;

            margin: auto;

            padding: 70px 0 100px;

        }


        .section-label {

            color: var(--red);

            font-size: 11px;

            font-weight: 900;

            letter-spacing: 3px;

            margin-bottom: 8px;

        }


        .products-title {

            display: flex;

            align-items: end;

            justify-content: space-between;

            margin-bottom: 35px;

        }


        .products-title h1 {

            font-size: 38px;

            font-weight: 900;

        }


        .products-title p {

            color: #777;

            font-size: 13px;

            margin-top: 8px;

        }


        /* =====================================================
           GRID
        ===================================================== */

        .products-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;

        }


        /* =====================================================
           CARD
        ===================================================== */

        .product-card {

            background: var(--gray);

            border:
                1px solid var(--border);

            border-radius: 11px;

            overflow: hidden;

            transition: .25s;

        }


        .product-card:hover {

            transform: translateY(-5px);

            border-color: var(--red);

            box-shadow:
                0 15px 35px
                rgba(229,9,20,.10);

        }


        /* =====================================================
           IMAGEM
        ===================================================== */

        .product-image {

            height: 260px;

            background:
                radial-gradient(
                    circle at center,
                    #29292d 0%,
                    #1d1d20 55%,
                    #111113 100%
                );

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;

        }


        .product-image img {

            width: 100%;

            height: 100%;

            object-fit: contain;

            padding: 15px;

            display: block;

            transition: .4s;

        }


        .product-card:hover
        .product-image img {

            transform: scale(1.05);

        }


        /* =====================================================
           INFORMAÇÕES
        ===================================================== */

        .product-info {

            padding: 18px;

        }


        .product-name {

            font-size: 17px;

            font-weight: 900;

            line-height: 1.3;

        }


        .product-description {

            color: #777;

            font-size: 11px;

            line-height: 1.5;

            margin-top: 7px;

            min-height: 38px;

        }


        .product-price {

            font-size: 21px;

            font-weight: 900;

            margin: 17px 0;

        }


        /* =====================================================
           BOTÃO
        ===================================================== */

        .buy-button {

            width: 100%;

            border: none;

            background: var(--red);

            color: white;

            padding: 12px;

            border-radius: 7px;

            cursor: pointer;

            font-size: 11px;

            font-weight: 900;

            transition: .2s;

        }


        .buy-button:hover {

            background: var(--red2);

        }


        /* =====================================================
           SEM PRODUTOS
        ===================================================== */

        .no-products {

            background: var(--gray);

            border: 1px solid var(--border);

            border-radius: 10px;

            padding: 50px;

            text-align: center;

            color: #777;

        }


        /* =====================================================
           FOOTER
        ===================================================== */

        footer {

            border-top:
                1px solid var(--border);

            background: var(--black2);

            padding: 38px 20px;

            text-align: center;

            color: #555;

            font-size: 12px;

        }


        footer strong {

            color: var(--red);

        }


        footer p {

            margin-top: 7px;

            color: #444;

        }


        /* =====================================================
           RESPONSIVO
        ===================================================== */

        @media(max-width: 1000px) {

            .products-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media(max-width: 600px) {

            header {

                height: 72px;

            }


            .logo {

                height: 72px;

            }


            .logo img {

                width: 125px;

            }


            nav {

                gap: 10px;

            }


            nav a:not(.cart) {

                display: none;

            }


            .cart {

                padding: 10px 12px;

            }


            .products-section {

                padding-top: 45px;

            }


            .products-title {

                display: block;

            }


            .products-title h1 {

                font-size: 30px;

            }


            .products-grid {

                grid-template-columns: 1fr;

            }


            .product-image {

                height: 280px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header>

    <div class="header-container">


        <a
            href="index.php"
            class="logo"
        >

            <img
                src="img/mbm logo sem fundo.png"
                alt="MBM Suplementos"
            >

        </a>


        <nav>

            <a href="index.php">
                Início
            </a>


            <a href="produtos.php">
                Produtos
            </a>


            <a href="login.php">
                Entrar
            </a>


            <a
                href="carrinho.php"
                class="cart"
            >

                🛒

                <span>
                    Carrinho
                </span>


                <?php if ($quantidadeCarrinho > 0): ?>

                    <span class="cart-count">
                        <?= $quantidadeCarrinho ?>
                    </span>

                <?php endif; ?>

            </a>

        </nav>

    </div>

</header>


<!-- =========================================================
     PRODUTOS
========================================================= -->

<section class="products-section">


    <div class="products-title">

        <div>

            <div class="section-label">
                CONHEÇA NOSSA LINHA
            </div>

            <h1>
                Nossos produtos
            </h1>

            <p>
                Suplementos para performance, força e evolução.
            </p>

        </div>

    </div>


    <?php if (count($produtos) > 0): ?>


        <div class="products-grid">


            <?php foreach ($produtos as $produto): ?>


                <?php

                /*
                |--------------------------------------------------------------------------
                | DEFINE A IMAGEM DO PRODUTO
                |--------------------------------------------------------------------------
                */

                $imagem = trim($produto['imagem'] ?? '');


                /*
                | Se o produto for creatina,
                | usa obrigatoriamente img/creatina.jpg
                */

                if (
                    stripos($produto['nome'], 'creatina') !== false
                ) {

                    $imagem = 'img/creatina.jpg';

                }


                /*
                | Caso o produto não tenha imagem
                */

                if ($imagem === '') {

                    $imagem = 'img/produto-sem-imagem.jpg';

                }

                ?>


                <article class="product-card">


                    <!-- IMAGEM -->

                    <div class="product-image">

                        <img
                            src="<?= htmlspecialchars($imagem) ?>"
                            alt="<?= htmlspecialchars($produto['nome']) ?>"
                            loading="lazy"
                        >

                    </div>


                    <!-- INFORMAÇÕES -->

                    <div class="product-info">


                        <div class="product-name">

                            <?= htmlspecialchars(
                                $produto['nome']
                            ) ?>

                        </div>


                        <div class="product-description">

                            <?= htmlspecialchars(
                                $produto['descricao'] ?? ''
                            ) ?>

                        </div>


                        <div class="product-price">

                            R$

                            <?= number_format(
                                (float)$produto['preco'],
                                2,
                                ',',
                                '.'
                            ) ?>

                        </div>


                        <!-- BOTÃO COMPRAR -->

                        <form method="POST">


                            <input
                                type="hidden"
                                name="acao"
                                value="adicionar"
                            >


                            <input
                                type="hidden"
                                name="produto_id"
                                value="<?= (int)$produto['id'] ?>"
                            >


                            <button
                                type="submit"
                                class="buy-button"
                            >

                                🛒 COMPRAR

                            </button>


                        </form>


                    </div>


                </article>


            <?php endforeach; ?>


        </div>


    <?php else: ?>


        <div class="no-products">

            Nenhum produto cadastrado ainda.

        </div>


    <?php endif; ?>


</section>


<!-- =========================================================
     FOOTER
========================================================= -->

<footer>

    <div>

        © <?= date('Y') ?>

        <strong>
            MBM Suplementos
        </strong>

        — Todos os direitos reservados.

    </div>


    <p>
        Performance • Força • Resultado
    </p>

</footer>


</body>

</html>
