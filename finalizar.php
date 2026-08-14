<?php

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

exigir_login();

if (empty($_SESSION['carrinho'])) {

    header('Location: /mbm/carrinho.php');
    exit;
}

$ids = array_keys($_SESSION['carrinho']);

$ids = array_map('intval', $ids);

$placeholders = implode(
    ',',
    array_fill(0, count($ids), '?')
);

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT *
        FROM produtos
        WHERE id IN ($placeholders)
        AND ativo = 1
        FOR UPDATE
    ");

    $stmt->execute($ids);

    $produtos = $stmt->fetchAll();

    $total = 0;

    foreach ($produtos as $produto) {

        $quantidade =
            (int) ($_SESSION['carrinho'][$produto['id']] ?? 0);

        if ($quantidade <= 0) {
            continue;
        }

        if ($quantidade > $produto['estoque']) {

            throw new Exception(
                'O produto "' .
                $produto['nome'] .
                '" não possui estoque suficiente.'
            );
        }

        $total +=
            $produto['preco'] * $quantidade;
    }


    if ($total <= 0) {
        throw new Exception('Carrinho inválido.');
    }


    /*
    |--------------------------------------------------------------------------
    | CRIAR PEDIDO
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        INSERT INTO pedidos
        (usuario_id, total, status)
        VALUES (?, ?, 'aguardando_pagamento')
    ");

    $stmt->execute([
        $_SESSION['usuario_id'],
        $total
    ]);

    $pedido_id = $pdo->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | ITENS DO PEDIDO
    |--------------------------------------------------------------------------
    */

    $stmtItem = $pdo->prepare("
        INSERT INTO pedido_itens
        (pedido_id, produto_id, quantidade, preco)
        VALUES (?, ?, ?, ?)
    ");


    $stmtEstoque = $pdo->prepare("
        UPDATE produtos
        SET estoque = estoque - ?
        WHERE id = ?
        AND estoque >= ?
    ");


    foreach ($produtos as $produto) {

        $quantidade =
            (int) ($_SESSION['carrinho'][$produto['id']] ?? 0);

        if ($quantidade <= 0) {
            continue;
        }


        $stmtItem->execute([
            $pedido_id,
            $produto['id'],
            $quantidade,
            $produto['preco']
        ]);


        $stmtEstoque->execute([
            $quantidade,
            $produto['id'],
            $quantidade
        ]);


        if ($stmtEstoque->rowCount() !== 1) {

            throw new Exception(
                'Não foi possível atualizar o estoque.'
            );
        }
    }


    $pdo->commit();


    $_SESSION['carrinho'] = [];


    header(
        'Location: /mbm/pedidos.php?sucesso=' .
        $pedido_id
    );

    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die(
        'Não foi possível finalizar o pedido: ' .
        htmlspecialchars($e->getMessage())
    );
}