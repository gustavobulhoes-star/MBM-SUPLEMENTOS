<?php

session_start();

require_once __DIR__ . "/config/database.php";


/*
|--------------------------------------------------------------------------
| INICIA CARRINHO
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}


/*
|--------------------------------------------------------------------------
| AÇÕES DO CARRINHO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';

    $produto_id = (int)($_POST['produto_id'] ?? 0);


    /*
    | AUMENTAR
    */

    if ($acao === 'aumentar' && $produto_id > 0) {

        if (isset($_SESSION['carrinho'][$produto_id])) {

            $_SESSION['carrinho'][$produto_id]++;

        }

    }


    /*
    | DIMINUIR
    */

    if ($acao === 'diminuir' && $produto_id > 0) {

        if (isset($_SESSION['carrinho'][$produto_id])) {

            $_SESSION['carrinho'][$produto_id]--;

            if ($_SESSION['carrinho'][$produto_id] <= 0) {

                unset($_SESSION['carrinho'][$produto_id]);

            }

        }

    }


    /*
    | REMOVER
    */

    if ($acao === 'remover' && $produto_id > 0) {

        unset($_SESSION['carrinho'][$produto_id]);

    }


    /*
    | LIMPAR
    */

    if ($acao === 'limpar') {

        $_SESSION['carrinho'] = [];

    }


    header("Location: carrinho.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| BUSCAR PRODUTOS
|--------------------------------------------------------------------------
*/

$produtosCarrinho = [];

$total = 0;

$quantidadeTotal = 0;


if (!empty($_SESSION['carrinho'])) {

    $ids = array_keys($_SESSION['carrinho']);

    $ids = array_map('intval', $ids);

    $ids = array_filter($ids);


    if (!empty($ids)) {

        $placeholders = implode(
            ',',
            array_fill(0, count($ids), '?')
        );


        $sql = "
            SELECT *
            FROM produtos
            WHERE id IN ($placeholders)
        ";


        $stmt = $pdo->prepare($sql);

        $stmt->execute($ids);

        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);


        foreach ($produtos as $produto) {

            $id = (int)$produto['id'];


            $quantidade = (int)(
                $_SESSION['carrinho'][$id] ?? 0
            );


            if ($quantidade <= 0) {
                continue;
            }


            $preco = (float)$produto['preco'];


            $subtotal = $preco * $quantidade;


            $produto['quantidade_carrinho'] = $quantidade;

            $produto['subtotal'] = $subtotal;


            $produtosCarrinho[] = $produto;


            $total += $subtotal;

            $quantidadeTotal += $quantidade;

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

<title>Carrinho - MBM Suplementos</title>


<style>

/* =========================================================
   RESET
========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


/* =========================================================
   VARIÁVEIS
========================================================= */

:root {

    --red: #e50914;

    --red2: #ff1822;

    --black: #080809;

    --gray: #171719;

    --gray2: #222225;

    --border: #2c2c31;

    --muted: #89898f;

}


/* =========================================================
   BODY
========================================================= */

body {

    font-family: Arial, Helvetica, sans-serif;

    background: var(--black);

    color: white;

    min-height: 100vh;

}


/* =========================================================
   HEADER
========================================================= */

header {

    height: 82px;

    background:
        linear-gradient(
            90deg,
            #050505 0%,
            #090909 60%,
            #16090a 100%
        );

    border-bottom: 1px solid rgba(255, 255, 255, .08);

    display: flex;

    align-items: center;

    position: sticky;

    top: 0;

    z-index: 100;

    box-shadow:
        0 5px 25px rgba(0, 0, 0, .45);
}


/* =========================================================
   CONTAINER DO HEADER
========================================================= */

.header-container {

    width: 92%;

    max-width: 1200px;

    height: 100%;

    margin: auto;

    display: flex;

    align-items: center;

    justify-content: space-between;
}


/* =========================================================
   LOGO
========================================================= */

.logo {

    height: 100%;

    display: flex;

    align-items: center;

    justify-content: flex-start;

    text-decoration: none;

    padding: 5px 0;

}


.logo img {

    width: 150px;

    height: auto;

    max-height: 62px;

    display: block;

    object-fit: contain;

    transform: translateY(1px);

    transition: .25s ease;
}


.logo:hover img {

    transform:
        scale(1.03)
        translateY(1px);
}


/* =========================================================
   BOTÃO VOLTAR
========================================================= */

.back {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 7px;

    color: #ddd;

    text-decoration: none;

    border: 1px solid var(--border);

    background: rgba(255, 255, 255, .02);

    padding: 10px 16px;

    min-height: 40px;

    border-radius: 7px;

    font-size: 12px;

    font-weight: 800;

    transition: .25s ease;
}


.back:hover {

    color: #fff;

    background: var(--red);

    border-color: var(--red);

    transform: translateY(-1px);

    box-shadow:
        0 5px 15px rgba(229, 9, 20, .20);
}


/* =========================================================
   CONTAINER PRINCIPAL
========================================================= */

.container {

    width: 92%;

    max-width: 1200px;

    margin: auto;

    padding: 55px 0 90px;
}


.label {

    color: var(--red);

    font-size: 11px;

    font-weight: 900;

    letter-spacing: 2px;
}


h1 {

    font-size: 42px;

    margin: 8px 0;
}


.subtitle {

    color: var(--muted);

    font-size: 14px;

    margin-bottom: 35px;
}


/* =========================================================
   LAYOUT
========================================================= */

.layout {

    display: grid;

    grid-template-columns: 1fr 350px;

    gap: 25px;
}


/* =========================================================
   CARRINHO
========================================================= */

.cart-box {

    background: var(--gray);

    border: 1px solid var(--border);

    border-radius: 12px;

    overflow: hidden;
}


.cart-header {

    padding: 20px;

    border-bottom: 1px solid var(--border);

    display: flex;

    justify-content: space-between;
}


.items {

    color: var(--muted);

    font-size: 13px;
}


/* =========================================================
   ITEM
========================================================= */

.item {

    padding: 20px;

    border-bottom: 1px solid var(--border);

    display: grid;

    grid-template-columns: 105px 1fr auto;

    gap: 18px;

    align-items: center;
}


/* =========================================================
   IMAGEM DO PRODUTO
========================================================= */

.image {

    width: 105px;

    height: 105px;

    background: var(--gray2);

    border-radius: 9px;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;
}


.image img {

    width: 100%;

    height: 100%;

    object-fit: contain;

    padding: 8px;

    display: block;
}


/* =========================================================
   NOME
========================================================= */

.name {

    font-size: 18px;

    font-weight: 900;
}


/* =========================================================
   DESCRIÇÃO
========================================================= */

.description {

    color: var(--muted);

    font-size: 12px;

    margin-top: 7px;
}


/* =========================================================
   PREÇO
========================================================= */

.price {

    color: #aaa;

    font-size: 13px;

    margin-top: 10px;
}


/* =========================================================
   AÇÕES
========================================================= */

.actions {

    display: flex;

    flex-direction: column;

    align-items: flex-end;

    gap: 12px;
}


/* =========================================================
   QUANTIDADE
========================================================= */

.quantity {

    display: flex;

    align-items: center;

    background: #0e0e10;

    border: 1px solid var(--border);

    border-radius: 7px;

    overflow: hidden;
}


.quantity form {

    margin: 0;

    padding: 0;
}


.quantity button {

    width: 35px;

    height: 35px;

    border: none;

    background: transparent;

    color: white;

    cursor: pointer;

    font-size: 18px;

    transition: .2s;
}


.quantity button:hover {

    background: var(--red);
}


.number {

    min-width: 35px;

    text-align: center;

    font-weight: bold;
}


/* =========================================================
   SUBTOTAL
========================================================= */

.subtotal {

    font-size: 19px;

    font-weight: 900;
}


/* =========================================================
   REMOVER
========================================================= */

.remove {

    border: none;

    background: transparent;

    color: #777;

    cursor: pointer;

    font-size: 11px;

    font-weight: bold;
}


.remove:hover {

    color: var(--red);
}


/* =========================================================
   FOOTER DO CARRINHO
========================================================= */

.cart-footer {

    padding: 20px;

    display: flex;

    justify-content: space-between;

    align-items: center;
}


.continue {

    color: white;

    text-decoration: none;

    font-size: 13px;

    font-weight: bold;
}


.continue:hover {

    color: var(--red);
}


.clear {

    border: 1px solid var(--border);

    background: transparent;

    color: #888;

    padding: 9px 13px;

    border-radius: 6px;

    cursor: pointer;
}


.clear:hover {

    color: var(--red);

    border-color: var(--red);
}


/* =========================================================
   RESUMO
========================================================= */

.summary {

    background: var(--gray);

    border: 1px solid var(--border);

    border-radius: 12px;

    padding: 25px;

    height: fit-content;

    position: sticky;

    top: 100px;
}


.summary h2 {

    margin-bottom: 20px;
}


.line {

    display: flex;

    justify-content: space-between;

    padding: 11px 0;

    color: #aaa;

    font-size: 13px;
}


.line strong {

    color: white;
}


.total {

    border-top: 1px solid var(--border);

    margin-top: 10px;

    padding-top: 20px;

    display: flex;

    justify-content: space-between;

    align-items: center;
}


.total strong {

    color: var(--red);

    font-size: 27px;
}


.checkout {

    display: block;

    text-align: center;

    margin-top: 22px;

    padding: 15px;

    border-radius: 7px;

    background: var(--red);

    color: white;

    text-decoration: none;

    font-weight: 900;

    font-size: 12px;

    transition: .2s;
}


.checkout:hover {

    background: var(--red2);

    transform: translateY(-1px);
}


/* =========================================================
   CARRINHO VAZIO
========================================================= */

.empty {

    background: var(--gray);

    border: 1px solid var(--border);

    border-radius: 12px;

    padding: 75px 25px;

    text-align: center;
}


.empty-icon {

    font-size: 55px;

    margin-bottom: 20px;
}


.empty h2 {

    margin-bottom: 10px;
}


.empty p {

    color: var(--muted);

    margin-bottom: 25px;
}


.shop {

    display: inline-block;

    background: var(--red);

    color: white;

    text-decoration: none;

    padding: 14px 22px;

    border-radius: 7px;

    font-weight: 900;

    font-size: 12px;
}


.shop:hover {

    background: var(--red2);
}


/* =========================================================
   FOOTER
========================================================= */

footer {

    border-top: 1px solid var(--border);

    background: #050506;

    text-align: center;

    padding: 30px;

    color: #555;

    font-size: 12px;
}


footer strong {

    color: var(--red);
}


/* =========================================================
   RESPONSIVO - TABLET
========================================================= */

@media(max-width: 900px) {

    .layout {

        grid-template-columns: 1fr;
    }


    .summary {

        position: static;
    }

}


/* =========================================================
   RESPONSIVO - CELULAR
========================================================= */

@media(max-width: 600px) {

    header {

        height: 72px;
    }


    .header-container {

        width: 90%;
    }


    .logo img {

        width: 125px;

        max-height: 52px;
    }


    .back {

        padding: 9px 11px;

        font-size: 11px;
    }


    .item {

        grid-template-columns: 80px 1fr;
    }


    .image {

        width: 80px;

        height: 80px;
    }


    .actions {

        grid-column: 2;

        align-items: flex-start;

        flex-direction: row;

        flex-wrap: wrap;
    }


    .cart-footer {

        flex-direction: column;

        align-items: flex-start;

        gap: 15px;
    }


    h1 {

        font-size: 34px;
    }

}

</style>

</head>


<body>


<!-- ======================================================
     HEADER
====================================================== -->

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


        <a
            href="produtos.php"
            class="back"
        >

            ← Continuar comprando

        </a>

    </div>

</header>


<!-- ======================================================
     CONTEÚDO
====================================================== -->

<main class="container">


    <div class="label">

        MBM SUPLEMENTOS

    </div>


    <h1>

        Meu carrinho

    </h1>


    <p class="subtitle">

        Confira seus produtos antes de finalizar.

    </p>


    <?php if (empty($produtosCarrinho)): ?>


        <div class="empty">


            <div class="empty-icon">

                🛒

            </div>


            <h2>

                Seu carrinho está vazio

            </h2>


            <p>

                Adicione suplementos para começar sua compra.

            </p>


            <a
                href="produtos.php"
                class="shop"
            >

                VER PRODUTOS

            </a>


        </div>


    <?php else: ?>


        <div class="layout">


            <!-- ==================================================
                 LISTA DOS PRODUTOS
            ================================================== -->

            <div class="cart-box">


                <div class="cart-header">

                    <strong>

                        Produtos selecionados

                    </strong>


                    <span class="items">

                        <?= $quantidadeTotal ?>

                        <?= $quantidadeTotal == 1
                            ? 'item'
                            : 'itens'
                        ?>

                    </span>

                </div>


                <?php foreach ($produtosCarrinho as $produto): ?>


                    <div class="item">


                        <!-- ==================================================
                             IMAGEM
                        ================================================== -->

<div class="image">

    <?php if (!empty($produto['imagem'])): ?>

        <img
            src="<?= htmlspecialchars($produto['imagem']) ?>"
            alt="<?= htmlspecialchars($produto['nome']) ?>"
        >

    <?php else: ?>

        <span style="font-size:40px;">
            🥤
        </span>

    <?php endif; ?>

</div>

                        <!-- ==================================================
                             INFORMAÇÕES
                        ================================================== -->

                        <div>

                            <div class="name">

                                <?= htmlspecialchars(
                                    $produto['nome']
                                ) ?>

                            </div>


                            <div class="description">

                                <?= htmlspecialchars(
                                    $produto['descricao'] ?? ''
                                ) ?>

                            </div>


                            <div class="price">

                                R$

                                <?= number_format(
                                    (float)$produto['preco'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>

                                cada

                            </div>

                        </div>


                        <!-- ==================================================
                             AÇÕES
                        ================================================== -->

                        <div class="actions">


                            <!-- QUANTIDADE -->

                            <div class="quantity">


                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="acao"
                                        value="diminuir"
                                    >

                                    <input
                                        type="hidden"
                                        name="produto_id"
                                        value="<?= (int)$produto['id'] ?>"
                                    >

                                    <button type="submit">

                                        −

                                    </button>

                                </form>


                                <span class="number">

                                    <?= $produto['quantidade_carrinho'] ?>

                                </span>


                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="acao"
                                        value="aumentar"
                                    >

                                    <input
                                        type="hidden"
                                        name="produto_id"
                                        value="<?= (int)$produto['id'] ?>"
                                    >

                                    <button type="submit">

                                        +

                                    </button>

                                </form>


                            </div>


                            <!-- SUBTOTAL -->

                            <div class="subtotal">

                                R$

                                <?= number_format(
                                    $produto['subtotal'],
                                    2,
                                    ',',
                                    '.'
                                ) ?>

                            </div>


                            <!-- REMOVER -->

                            <form method="POST">

                                <input
                                    type="hidden"
                                    name="acao"
                                    value="remover"
                                >

                                <input
                                    type="hidden"
                                    name="produto_id"
                                    value="<?= (int)$produto['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    class="remove"
                                >

                                    🗑 REMOVER

                                </button>

                            </form>


                        </div>


                    </div>


                <?php endforeach; ?>


                <!-- ==================================================
                     RODAPÉ DO CARRINHO
                ================================================== -->

                <div class="cart-footer">


                    <a
                        href="produtos.php"
                        class="continue"
                    >

                        ← Adicionar mais produtos

                    </a>


                    <form method="POST">

                        <input
                            type="hidden"
                            name="acao"
                            value="limpar"
                        >


                        <button
                            type="submit"
                            class="clear"
                        >

                            🗑 Esvaziar carrinho

                        </button>

                    </form>


                </div>


            </div>


            <!-- ==================================================
                 RESUMO
            ================================================== -->

            <aside class="summary">


                <h2>

                    Resumo do pedido

                </h2>


                <div class="line">

                    <span>

                        Produtos

                    </span>


                    <strong>

                        <?= $quantidadeTotal ?>

                    </strong>

                </div>


                <div class="line">

                    <span>

                        Subtotal

                    </span>


                    <strong>

                        R$

                        <?= number_format(
                            $total,
                            2,
                            ',',
                            '.'
                        ) ?>

                    </strong>

                </div>


                <div class="line">

                    <span>

                        Frete

                    </span>


                    <strong>

                        A calcular

                    </strong>

                </div>


                <div class="total">

                    <span>

                        TOTAL

                    </span>


                    <strong>

                        R$

                        <?= number_format(
                            $total,
                            2,
                            ',',
                            '.'
                        ) ?>

                    </strong>

                </div>


                <a
                    href="cadastro.php"
                    class="checkout"
                >

                    CONTINUAR PARA PAGAMENTO →

                </a>


            </aside>


        </div>


    <?php endif; ?>


</main>


<!-- ======================================================
     FOOTER
====================================================== -->

<footer>

    © <?= date('Y') ?>

    <strong>

        MBM Suplementos

    </strong>

    — Todos os direitos reservados.

</footer>


</body>

</html>
