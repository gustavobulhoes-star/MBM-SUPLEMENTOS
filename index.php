<?php

session_start();

/*
|--------------------------------------------------------------------------
| LOGIN COMO PRIORIDADE
|--------------------------------------------------------------------------
| Se o usuário não estiver logado, ele será enviado para login.php.
| Se estiver logado, continua normalmente no index.php.
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

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

        header("Location: carrinho.php");
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

$produtos = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| CONTADOR DO CARRINHO
|--------------------------------------------------------------------------
*/

$quantidadeCarrinho = 0;

foreach ($_SESSION['carrinho'] as $quantidade) {

    $quantidadeCarrinho += (int)$quantidade;

}


/*
|--------------------------------------------------------------------------
| PROCURA CREATINA E WHEY
|--------------------------------------------------------------------------
*/

$creatina = null;
$whey = null;

foreach ($produtos as $produto) {

    $nome = strtolower($produto['nome']);

    /*
    | Procura creatina
    */
    if (
        $creatina === null &&
        strpos($nome, 'creatina') !== false
    ) {

        $creatina = $produto;

    }


    /*
    | Procura whey
    */
    if (
        $whey === null &&
        strpos($nome, 'whey') !== false
    ) {

        $whey = $produto;

    }

}


/*
|--------------------------------------------------------------------------
| CREATINA PADRÃO
|--------------------------------------------------------------------------
|
| Caso ainda não esteja cadastrada no banco.
|
*/

if (!$creatina) {

    $creatina = [

        'id' => 0,

        'nome' => 'Creatina Monohidratada',

        'descricao' =>
            'Creatina monohidratada para auxiliar na força, desempenho e evolução dos seus treinos.',

        'preco' => 89.90,

        'imagem' => 'img/creatina.jpg'

    ];

}


/*
|--------------------------------------------------------------------------
| WHEY PADRÃO
|--------------------------------------------------------------------------
|
| Caso ainda não esteja cadastrado no banco.
|
*/

if (!$whey) {

    $whey = [

        'id' => 0,

        'nome' => 'Whey Protein',

        'descricao' =>
            'Whey Protein para auxiliar na recuperação muscular e complementar sua ingestão de proteínas.',

        'preco' => 119.90,

        'imagem' => 'img/whey.jpg'

    ];

}


/*
|--------------------------------------------------------------------------
| IMAGENS
|--------------------------------------------------------------------------
*/

$imagemCreatina = 'img/creatina.jpg';

if (!empty($creatina['imagem'])) {

    $imagemCreatina = $creatina['imagem'];

}


$imagemWhey = 'img/whey.jpg';

if (!empty($whey['imagem'])) {

    $imagemWhey = $whey['imagem'];

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
        MBM Suplementos | Performance e Evolução
    </title>


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


        .logo {

            display: flex;

            align-items: center;

            height: 82px;

        }


        .logo img {

            width: 150px;

            height: auto;

            display: block;

            object-fit: contain;

        }


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
           HERO
        ===================================================== */

        .hero {

            min-height: 410px;

            display: flex;

            align-items: center;

            position: relative;

            overflow: hidden;

            border-bottom:
                1px solid var(--border);

            background:

                radial-gradient(
                    circle at 75% 45%,
                    rgba(229,9,20,.18),
                    transparent 35%
                ),

                linear-gradient(
                    90deg,
                    #050505 0%,
                    #090909 55%,
                    #210d0f 100%
                );

        }


        .hero-container {

            width: 92%;

            max-width: 1250px;

            margin: auto;

            position: relative;

            z-index: 2;

        }


        .hero-label {

            color: var(--red);

            font-size: 11px;

            font-weight: 900;

            letter-spacing: 3px;

            margin-bottom: 16px;

        }


        .hero h1 {

            font-size:
                clamp(48px, 6vw, 78px);

            line-height: .94;

            font-weight: 900;

            max-width: 650px;

            letter-spacing: -2px;

        }


        .hero h1 span {

            display: block;

            color: var(--red);

        }


        .hero p {

            color: #999;

            font-size: 14px;

            line-height: 1.6;

            max-width: 520px;

            margin: 22px 0 28px;

        }


        .hero-button {

            display: inline-block;

            background: var(--red);

            color: white;

            text-decoration: none;

            padding: 14px 23px;

            border-radius: 7px;

            font-size: 12px;

            font-weight: 900;

            transition: .2s;

        }


        .hero-button:hover {

            background: var(--red2);

            transform: translateY(-2px);

        }


        /* =====================================================
           BENEFÍCIOS
        ===================================================== */

        .benefits {

            width: 92%;

            max-width: 1250px;

            margin: auto;

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            border-left:
                1px solid var(--border);

            border-right:
                1px solid var(--border);

            border-bottom:
                1px solid var(--border);

        }


        .benefit {

            padding: 22px;

            background: #0c0c0e;

            border-right:
                1px solid var(--border);

        }


        .benefit:last-child {

            border-right: none;

        }


        .benefit-number {

            color: var(--red);

            font-size: 11px;

            font-weight: 900;

            margin-bottom: 7px;

        }


        .benefit strong {

            font-size: 14px;

        }


        .benefit p {

            color: #777;

            font-size: 11px;

            margin-top: 5px;

        }


        /* =====================================================
           DESTAQUES
        ===================================================== */

        .featured {

            width: 92%;

            max-width: 1250px;

            margin: auto;

            padding: 65px 0 80px;

        }


        .section-label {

            color: var(--red);

            font-size: 11px;

            font-weight: 900;

            letter-spacing: 3px;

            margin-bottom: 8px;

        }


        .featured h2 {

            font-size: 34px;

            margin-bottom: 30px;

        }


        .featured-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 22px;

        }


        .featured-product {

            background:
                linear-gradient(
                    145deg,
                    #1a1a1d,
                    #101012
                );

            border:
                1px solid var(--border);

            border-radius: 14px;

            overflow: hidden;

            transition: .3s;

            box-shadow:
                0 15px 40px
                rgba(0,0,0,.25);

        }


        .featured-product:hover {

            transform: translateY(-6px);

            border-color: var(--red);

            box-shadow:
                0 20px 45px
                rgba(229,9,20,.12);

        }


        .featured-image {

            height: 310px;

            background:
                radial-gradient(
                    circle at center,
                    #29292d 0%,
                    #151517 55%,
                    #0b0b0d 100%
                );

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;

        }


        .featured-image img {

            width: 100%;

            height: 100%;

            object-fit: contain;

            padding: 15px;

            display: block;

            transition: .4s;

        }


        .featured-product:hover
        .featured-image img {

            transform: scale(1.04);

        }


        .featured-info {

            padding: 24px;

        }


        .category {

            color: var(--red);

            font-size: 10px;

            font-weight: 900;

            letter-spacing: 2px;

        }


        .featured-info h3 {

            font-size: 23px;

            margin: 9px 0;

        }


        .featured-info p {

            color: #888;

            font-size: 13px;

            line-height: 1.6;

            min-height: 42px;

        }


        .featured-bottom {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-top: 22px;

        }


        .featured-price {

            color: white;

            font-size: 22px;

            font-weight: 900;

        }


        .featured-button {

            background: var(--red);

            color: white;

            border: none;

            text-decoration: none;

            padding: 11px 16px;

            border-radius: 7px;

            font-size: 11px;

            font-weight: 900;

            cursor: pointer;

            transition: .2s;

        }


        .featured-button:hover {

            background: var(--red2);

            transform: translateY(-2px);

        }


        /* =====================================================
           PRODUTOS
        ===================================================== */

        .products-section {

            width: 92%;

            max-width: 1250px;

            margin: auto;

            padding: 10px 0 90px;

        }


        .products-title {

            display: flex;

            align-items: end;

            justify-content: space-between;

            margin-bottom: 28px;

        }


        .products-title h2 {

            font-size: 30px;

        }


        .products-title a {

            color: var(--red);

            text-decoration: none;

            font-size: 12px;

            font-weight: 900;

        }


        .products-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 20px;

        }


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

        }


        .product-image {

            height: 220px;

            background: var(--gray2);

            display: flex;

            align-items: center;

            justify-content: center;

        }


        .product-image img {

            width: 100%;

            height: 100%;

            object-fit: contain;

            padding: 12px;

        }


        .product-info {

            padding: 18px;

        }


        .product-name {

            font-size: 16px;

            font-weight: 900;

        }


        .product-description {

            color: #777;

            font-size: 11px;

            line-height: 1.5;

            margin-top: 7px;

            min-height: 34px;

        }


        .product-price {

            font-size: 20px;

            font-weight: 900;

            margin: 15px 0;

        }


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


        @media(max-width: 800px) {

            .featured-grid {

                grid-template-columns: 1fr;

            }


            .benefits {

                grid-template-columns: 1fr;

            }


            .benefit {

                border-right: none;

                border-bottom:
                    1px solid var(--border);

            }


            .benefit:last-child {

                border-bottom: none;

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


            .hero {

                min-height: 440px;

            }


            .hero h1 {

                font-size: 48px;

            }


            .featured {

                padding-top: 50px;

            }


            .featured h2 {

                font-size: 28px;

            }


            .featured-image {

                height: 270px;

            }


            .featured-bottom {

                flex-direction: column;

                align-items: stretch;

            }


            .featured-button {

                text-align: center;

                width: 100%;

            }


            .products-grid {

                grid-template-columns: 1fr;

            }


            .products-title {

                align-items: flex-start;

                flex-direction: column;

                gap: 10px;

            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

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


<!-- =====================================================
     HERO
===================================================== -->

<section class="hero">

    <div class="hero-container">

        <div class="hero-label">
            PERFORMANCE • FORÇA • RESULTADO
        </div>


        <h1>

            SUPLEMENTE.

            <span>
                EVOLUA.
            </span>

        </h1>


        <p>

            Suplementos para quem leva
            treinamento, performance e evolução
            a sério.

        </p>


        <a
            href="produtos.php"
            class="hero-button"
        >

            VER PRODUTOS

        </a>

    </div>

</section>


<!-- =====================================================
     BENEFÍCIOS
===================================================== -->

<section class="benefits">


    <div class="benefit">

        <div class="benefit-number">
            01
        </div>

        <strong>
            Alta performance
        </strong>

        <p>
            Produtos pensados para seus objetivos.
        </p>

    </div>


    <div class="benefit">

        <div class="benefit-number">
            02
        </div>

        <strong>
            Qualidade nos produtos
        </strong>

        <p>
            Suplementação para acompanhar sua rotina.
        </p>

    </div>


    <div class="benefit">

        <div class="benefit-number">
            03
        </div>

        <strong>
            Foco nos seus resultados
        </strong>

        <p>
            Sua evolução começa com a consistência.
        </p>

    </div>


</section>


<!-- =====================================================
     CREATINA + WHEY EM DESTAQUE
===================================================== -->

<section class="featured">


    <div class="section-label">
        PRODUTOS EM DESTAQUE
    </div>


    <h2>
        Os favoritos da MBM
    </h2>


    <div class="featured-grid">


        <!-- =================================================
             CREATINA
        ================================================== -->

        <article class="featured-product">


            <div class="featured-image">

                <img
                    src="<?= htmlspecialchars($imagemCreatina) ?>"
                    alt="<?= htmlspecialchars($creatina['nome']) ?>"
                >

            </div>


            <div class="featured-info">


                <span class="category">
                    PERFORMANCE
                </span>


                <h3>

                    <?= htmlspecialchars(
                        $creatina['nome']
                    ) ?>

                </h3>


                <p>

                    <?= htmlspecialchars(
                        $creatina['descricao']
                    ) ?>

                </p>


                <div class="featured-bottom">


                    <strong class="featured-price">

                        R$

                        <?= number_format(
                            (float)$creatina['preco'],
                            2,
                            ',',
                            '.'
                        ) ?>

                    </strong>


                    <?php if ((int)$creatina['id'] > 0): ?>


                        <form method="POST">

                            <input
                                type="hidden"
                                name="acao"
                                value="adicionar"
                            >

                            <input
                                type="hidden"
                                name="produto_id"
                                value="<?= (int)$creatina['id'] ?>"
                            >

                            <button
                                type="submit"
                                class="featured-button"
                            >

                                🛒 COMPRAR

                            </button>

                        </form>


                    <?php else: ?>


                        <a
                            href="produtos.php"
                            class="featured-button"
                        >

                            VER PRODUTO

                        </a>

                    <?php endif; ?>


                </div>

            </div>

        </article>


        <!-- =================================================
             WHEY
        ================================================== -->

        <article class="featured-product">


            <div class="featured-image">

                <img
                    src="<?= htmlspecialchars($imagemWhey) ?>"
                    alt="<?= htmlspecialchars($whey['nome']) ?>"
                >

            </div>


            <div class="featured-info">


                <span class="category">
                    PROTEÍNA
                </span>


                <h3>

                    <?= htmlspecialchars(
                        $whey['nome']
                    ) ?>

                </h3>


                <p>

                    <?= htmlspecialchars(
                        $whey['descricao']
                    ) ?>

                </p>


                <div class="featured-bottom">


                    <strong class="featured-price">

                        R$

                        <?= number_format(
                            (float)$whey['preco'],
                            2,
                            ',',
                            '.'
                        ) ?>

                    </strong>


                    <?php if ((int)$whey['id'] > 0): ?>


                        <form method="POST">

                            <input
                                type="hidden"
                                name="acao"
                                value="adicionar"
                            >

                            <input
                                type="hidden"
                                name="produto_id"
                                value="<?= (int)$whey['id'] ?>"
                            >

                            <button
                                type="submit"
                                class="featured-button"
                            >

                                🛒 COMPRAR

                            </button>

                        </form>


                    <?php else: ?>


                        <a
                            href="produtos.php"
                            class="featured-button"
                        >

                            VER PRODUTO

                        </a>

                    <?php endif; ?>


                </div>

            </div>

        </article>


    </div>

</section>


<!-- =====================================================
     OUTROS PRODUTOS
===================================================== -->

<section class="products-section">


    <div class="products-title">


        <div>

            <div class="section-label">
                CONHEÇA NOSSA LINHA
            </div>

            <h2>
                Mais produtos
            </h2>

        </div>


        <a href="produtos.php">
            VER TODOS →
        </a>


    </div>


    <div class="products-grid">


        <?php

        $contador = 0;

        foreach ($produtos as $produto):


            /*
            |------------------------------------------------------
            | NÃO MOSTRAR CREATINA NEM WHEY NOVAMENTE
            |------------------------------------------------------
            */

            if (
                (int)$creatina['id'] > 0 &&
                (int)$produto['id'] === (int)$creatina['id']
            ) {
                continue;
            }


            if (
                (int)$whey['id'] > 0 &&
                (int)$produto['id'] === (int)$whey['id']
            ) {
                continue;
            }


            if ($contador >= 4) {
                break;
            }


            $contador++;


            /*
            |------------------------------------------------------
            | IMAGEM
            |------------------------------------------------------
            */

            $imagem = trim(
                $produto['imagem'] ?? ''
            );


            if ($imagem === '') {

                $imagem =
                    'img/produto-sem-imagem.jpg';

            }

        ?>


            <article class="product-card">


                <div class="product-image">


                    <img
                        src="<?= htmlspecialchars($imagem) ?>"
                        alt="<?= htmlspecialchars($produto['nome']) ?>"
                        loading="lazy"
                    >


                </div>


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


</section>


<!-- =====================================================
     FOOTER
===================================================== -->

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