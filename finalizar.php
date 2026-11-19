<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

/*
|--------------------------------------------------------------------------
| CONFIGURAÇÃO DO BANCO
|--------------------------------------------------------------------------
*/

if (!isset($pdo) || !$pdo instanceof PDO) {
    die('Erro: conexão com o banco de dados não encontrada.');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| FUNÇÕES
|--------------------------------------------------------------------------
*/

function moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function textoPost(string $campo): string
{
    return trim((string)($_POST[$campo] ?? ''));
}

function escapar($valor): string
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

function buscarProdutosDoCarrinho(PDO $pdo, array $carrinho): array
{
    $ids = [];

    foreach ($carrinho as $produtoId => $quantidade) {

        $produtoId = (int)$produtoId;
        $quantidade = (int)$quantidade;

        if ($produtoId > 0 && $quantidade > 0) {
            $ids[$produtoId] = $quantidade;
        }
    }

    if (empty($ids)) {
        return [];
    }

    $marcadores = implode(
        ',',
        array_fill(0, count($ids), '?')
    );

    $sql = "
        SELECT
            id,
            nome,
            preco
        FROM produtos
        WHERE id IN ($marcadores)
    ";

    $consulta = $pdo->prepare($sql);

    $consulta->execute(array_keys($ids));

    $resultados = $consulta->fetchAll();

    $produtos = [];

    foreach ($resultados as $produto) {

        $id = (int)$produto['id'];

        $quantidade = $ids[$id] ?? 0;

        if ($quantidade <= 0) {
            continue;
        }

        $preco = (float)$produto['preco'];

        $produtos[] = [
            'id' => $id,
            'nome' => (string)$produto['nome'],
            'preco' => $preco,
            'quantidade' => $quantidade,
            'subtotal' => $preco * $quantidade
        ];
    }

    return $produtos;
}

/*
|--------------------------------------------------------------------------
| VERIFICA LOGIN (OPCIONAL / SUPORTE A COMPRA SEM CADASTRO)
|--------------------------------------------------------------------------
*/

$usuario_id = null;

if (!empty($_SESSION['usuario_id'])) {

    $idSessao = (int)$_SESSION['usuario_id'];

    $stmtUsuario = $pdo->prepare("
        SELECT id
        FROM usuarios
        WHERE id = :id
        LIMIT 1
    ");

    $stmtUsuario->execute([
        ':id' => $idSessao
    ]);

    if ($stmtUsuario->fetch()) {
        $usuario_id = $idSessao;
    } else {
        unset($_SESSION['usuario_id']);
    }
}

/*
|--------------------------------------------------------------------------
| PROTEÇÃO CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['token_checkout'])) {

    $_SESSION['token_checkout'] = bin2hex(
        random_bytes(32)
    );
}

$tokenCheckout = $_SESSION['token_checkout'];

/*
|--------------------------------------------------------------------------
| VERIFICA CARRINHO
|--------------------------------------------------------------------------
*/

$carrinho = $_SESSION['carrinho'] ?? [];

if (!is_array($carrinho) || empty($carrinho)) {

    header('Location: carrinho.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| BUSCA PRODUTOS
|--------------------------------------------------------------------------
*/

$erros = [];

try {

    $produtos = buscarProdutosDoCarrinho(
        $pdo,
        $carrinho
    );

} catch (Throwable $erro) {

    error_log(
        'Erro ao buscar produtos: ' . $erro->getMessage()
    );

    die('Não foi possível carregar os produtos do carrinho.');
}

if (empty($produtos)) {

    header('Location: carrinho.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| CALCULA VALORES
|--------------------------------------------------------------------------
*/

$subtotal = array_sum(
    array_column($produtos, 'subtotal')
);

$frete = $subtotal >= 199.00 ? 0.00 : 19.90;

$total = $subtotal + $frete;

/*
|--------------------------------------------------------------------------
| DADOS DO FORMULÁRIO
|--------------------------------------------------------------------------
*/

$nome = textoPost('nome');
$telefone = textoPost('telefone');

$cep = textoPost('cep');
$endereco = textoPost('endereco');
$numero = textoPost('numero');
$complemento = textoPost('complemento');

$bairro = textoPost('bairro');
$cidade = textoPost('cidade');
$estado = strtoupper(textoPost('estado'));

$pagamento = textoPost('pagamento');

/*
|--------------------------------------------------------------------------
| PROCESSA PEDIDO
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | VALIDA TOKEN
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_POST['token_checkout']) ||
        !hash_equals(
            $_SESSION['token_checkout'],
            (string)$_POST['token_checkout']
        )
    ) {

        $erros[] = 'Sessão expirada. Atualize a página e tente novamente.';
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDA DADOS PESSOAIS
    |--------------------------------------------------------------------------
    */

    $nomeTamanho = function_exists('mb_strlen')
        ? mb_strlen($nome, 'UTF-8')
        : strlen($nome);

    if ($nomeTamanho < 3 || $nomeTamanho > 255) {

        $erros[] = 'Informe seu nome completo.';
    }

    $telefoneLimpo = preg_replace('/\D/', '', $telefone);

    if (
        strlen($telefoneLimpo) < 10 ||
        strlen($telefoneLimpo) > 11
    ) {

        $erros[] = 'Informe um telefone válido.';
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDA CEP
    |--------------------------------------------------------------------------
    */

    $cepLimpo = preg_replace('/\D/', '', $cep);

    if (strlen($cepLimpo) !== 8) {

        $erros[] = 'Informe um CEP válido.';
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDA ENDEREÇO
    |--------------------------------------------------------------------------
    */

    if ($endereco === '' || strlen($endereco) > 255) {

        $erros[] = 'Informe um endereço válido.';
    }

    if ($numero === '' || strlen($numero) > 20) {

        $erros[] = 'Informe o número do endereço.';
    }

    if (strlen($complemento) > 100) {

        $erros[] = 'O complemento deve ter até 100 caracteres.';
    }

    if ($bairro === '' || strlen($bairro) > 100) {

        $erros[] = 'Informe o bairro.';
    }

    if ($cidade === '' || strlen($cidade) > 100) {

        $erros[] = 'Informe a cidade.';
    }

    if (!preg_match('/^[A-Z]{2}$/', $estado)) {

        $erros[] = 'Informe uma UF válida.';
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDA PAGAMENTO
    |--------------------------------------------------------------------------
    */

    $pagamentosPermitidos = [
        'pix',
        'cartao',
        'boleto'
    ];

    if (!in_array($pagamento, $pagamentosPermitidos, true)) {

        $erros[] = 'Selecione uma forma de pagamento.';
    }

    /*
    |--------------------------------------------------------------------------
    | SALVA PEDIDO
    |--------------------------------------------------------------------------
    */

    if (empty($erros)) {

        try {

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | BUSCA NOVAMENTE OS PRODUTOS
            |--------------------------------------------------------------------------
            */

            $carrinhoAtual = $_SESSION['carrinho'] ?? [];

            $produtos = buscarProdutosDoCarrinho(
                $pdo,
                $carrinhoAtual
            );

            if (empty($produtos)) {

                throw new RuntimeException(
                    'O carrinho está vazio.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | RECALCULA VALORES
            |--------------------------------------------------------------------------
            */

            $subtotal = array_sum(
                array_column($produtos, 'subtotal')
            );

            $frete = $subtotal >= 199.00 ? 0.00 : 19.90;

            $total = $subtotal + $frete;

            /*
            |--------------------------------------------------------------------------
            | INSERE PEDIDO
            |--------------------------------------------------------------------------
            */

            $sqlPedido = "
                INSERT INTO pedidos (
                    usuario_id,
                    subtotal,
                    frete,
                    total,
                    status,
                    pagamento,
                    nome_cliente,
                    telefone,
                    cep,
                    endereco,
                    numero,
                    complemento,
                    bairro,
                    cidade,
                    estado
                ) VALUES (
                    :usuario_id,
                    :subtotal,
                    :frete,
                    :total,
                    :status,
                    :pagamento,
                    :nome_cliente,
                    :telefone,
                    :cep,
                    :endereco,
                    :numero,
                    :complemento,
                    :bairro,
                    :cidade,
                    :estado
                )
            ";

            $salvarPedido = $pdo->prepare($sqlPedido);

            $salvarPedido->execute([

                ':usuario_id' => $usuario_id,

                ':subtotal' => $subtotal,

                ':frete' => $frete,

                ':total' => $total,

                ':status' => 'aguardando_pagamento',

                ':pagamento' => $pagamento,

                ':nome_cliente' => $nome,

                ':telefone' => $telefoneLimpo,

                ':cep' => $cepLimpo,

                ':endereco' => $endereco,

                ':numero' => $numero,

                ':complemento' => $complemento !== ''
                    ? $complemento
                    : null,

                ':bairro' => $bairro,

                ':cidade' => $cidade,

                ':estado' => $estado

            ]);

            /*
            |--------------------------------------------------------------------------
            | PEGA ID DO PEDIDO
            |--------------------------------------------------------------------------
            */

            $pedidoId = (int)$pdo->lastInsertId();

            if ($pedidoId <= 0) {

                throw new RuntimeException(
                    'Não foi possível obter o ID do pedido.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | INSERE ITENS DO PEDIDO
            |--------------------------------------------------------------------------
            |
            | CORREÇÃO DO ERRO SQL 1064
            |
            */

            $sqlItem = "
                INSERT INTO pedido_itens (
                    pedido_id,
                    produto_id,
                    quantidade,
                    preco,
                    subtotal
                ) VALUES (
                    :pedido_id,
                    :produto_id,
                    :quantidade,
                    :preco,
                    :subtotal
                )
            ";

            $salvarItem = $pdo->prepare($sqlItem);

            foreach ($produtos as $produto) {

                $salvarItem->execute([

                    ':pedido_id' => $pedidoId,

                    ':produto_id' => $produto['id'],

                    ':quantidade' => $produto['quantidade'],

                    ':preco' => $produto['preco'],

                    ':subtotal' => $produto['subtotal']

                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | FINALIZA TRANSAÇÃO
            |--------------------------------------------------------------------------
            */

            $pdo->commit();

            /*
            |--------------------------------------------------------------------------
            | LIMPA CARRINHO
            |--------------------------------------------------------------------------
            */

            unset($_SESSION['carrinho']);

            $_SESSION['ultimo_pedido_id'] = $pedidoId;

            unset($_SESSION['token_checkout']);

            /*
            |--------------------------------------------------------------------------
            | REDIRECIONA
            |--------------------------------------------------------------------------
            */

            header(
                'Location: pedido_confirmado.php?id=' . $pedidoId
            );

            exit;

        } catch (Throwable $erro) {

            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }

            error_log(
                'ERRO AO FINALIZAR PEDIDO: ' .
                $erro->getMessage()
            );

            $erros[] =
                'Não foi possível finalizar seu pedido. Verifique os dados e tente novamente.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Finalizar compra - MBM Suplementos</title>

    <link rel="stylesheet" href="css/finalizar.css">

</head>

<body>

<header class="cabecalho">

    <a class="logo" href="index.php">
        MBM <span>SUPLEMENTOS</span>
    </a>

    <a class="voltar" href="carrinho.php">
        ← Voltar ao carrinho
    </a>

</header>

<main class="checkout">

<section class="formulario">

    <div class="titulo">

        <span>Checkout seguro</span>

        <h1>Finalizar compra</h1>

        <p>Preencha seus dados para concluir o pedido.</p>

    </div>

    <?php if (!empty($erros)): ?>

        <div class="alerta" role="alert">

            <?php foreach ($erros as $erro): ?>

                <p><?= escapar($erro) ?></p>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

    <form method="POST" id="checkout-form">

        <input
            type="hidden"
            name="token_checkout"
            value="<?= escapar($tokenCheckout) ?>"
        >

        <!-- DADOS PESSOAIS -->

        <div class="bloco">

            <h2>1. Dados pessoais</h2>

            <div class="grade">

                <label class="campo campo-grande">

                    <span>Nome completo</span>

                    <input
                        type="text"
                        name="nome"
                        maxlength="255"
                        autocomplete="name"
                        value="<?= escapar($nome) ?>"
                        required
                    >

                </label>

                <label class="campo">

                    <span>Telefone</span>

                    <input
                        type="tel"
                        name="telefone"
                        maxlength="20"
                        autocomplete="tel"
                        placeholder="(11) 99999-9999"
                        value="<?= escapar($telefone) ?>"
                        required
                    >

                </label>

            </div>

        </div>

        <!-- ENDEREÇO -->

        <div class="bloco">

            <h2>2. Endereço de entrega</h2>

            <div class="grade">

                <label class="campo">

                    <span>CEP</span>

                    <input
                        id="cep"
                        type="text"
                        name="cep"
                        maxlength="9"
                        inputmode="numeric"
                        autocomplete="postal-code"
                        placeholder="00000-000"
                        value="<?= escapar($cep) ?>"
                        required
                    >

                </label>

                <label class="campo campo-grande">

                    <span>Endereço</span>

                    <input
                        id="endereco"
                        type="text"
                        name="endereco"
                        maxlength="255"
                        autocomplete="street-address"
                        value="<?= escapar($endereco) ?>"
                        required
                    >

                </label>

                <label class="campo">

                    <span>Número</span>

                    <input
                        type="text"
                        name="numero"
                        maxlength="20"
                        value="<?= escapar($numero) ?>"
                        required
                    >

                </label>

                <label class="campo">

                    <span>Complemento</span>

                    <input
                        type="text"
                        name="complemento"
                        maxlength="100"
                        placeholder="Opcional"
                        value="<?= escapar($complemento) ?>"
                    >

                </label>

                <label class="campo">

                    <span>Bairro</span>

                    <input
                        id="bairro"
                        type="text"
                        name="bairro"
                        maxlength="100"
                        value="<?= escapar($bairro) ?>"
                        required
                    >

                </label>

                <label class="campo">

                    <span>Cidade</span>

                    <input
                        id="cidade"
                        type="text"
                        name="cidade"
                        maxlength="100"
                        autocomplete="address-level2"
                        value="<?= escapar($cidade) ?>"
                        required
                    >

                </label>

                <label class="campo campo-estado">

                    <span>UF</span>

                    <input
                        id="estado"
                        type="text"
                        name="estado"
                        maxlength="2"
                        autocomplete="address-level1"
                        value="<?= escapar($estado) ?>"
                        required
                    >

                </label>

            </div>

        </div>

        <!-- PAGAMENTO -->

        <div class="bloco">

            <h2>3. Forma de pagamento</h2>

            <div class="pagamentos">

                <label class="pagamento">

                    <input
                        type="radio"
                        name="pagamento"
                        value="pix"
                        <?= ($pagamento === 'pix' || $pagamento === '') ? 'checked' : '' ?>
                        required
                    >

                    <span>

                        <strong>PIX</strong>

                        <small>Confirmação rápida</small>

                    </span>

                </label>

                <label class="pagamento">

                    <input
                        type="radio"
                        name="pagamento"
                        value="cartao"
                        <?= $pagamento === 'cartao' ? 'checked' : '' ?>
                    >

                    <span>

                        <strong>Cartão</strong>

                        <small>Crédito ou débito</small>

                    </span>

                </label>

                <label class="pagamento">

                    <input
                        type="radio"
                        name="pagamento"
                        value="boleto"
                        <?= $pagamento === 'boleto' ? 'checked' : '' ?>
                    >

                    <span>

                        <strong>Boleto</strong>

                        <small>Vencimento em 3 dias</small>

                    </span>

                </label>

            </div>

            <p class="aviso-pagamento">
                O pagamento será gerado após a confirmação do pedido.
            </p>

        </div>

        <button class="botao-mobile" type="submit">

            Confirmar pedido - <?= moeda($total) ?>

        </button>

    </form>

</section>

<!-- RESUMO DO PEDIDO -->

<aside class="resumo">

    <h2>Resumo do pedido</h2>

    <div class="lista-produtos">

        <?php foreach ($produtos as $produto): ?>

            <div class="item">

                <div>

                    <strong>
                        <?= escapar($produto['nome']) ?>
                    </strong>

                    <small>
                        Quantidade: <?= (int)$produto['quantidade'] ?>
                    </small>

                </div>

                <span>
                    <?= moeda((float)$produto['subtotal']) ?>
                </span>

            </div>

        <?php endforeach; ?>

    </div>

    <div class="linha">

        <span>Subtotal</span>

        <strong><?= moeda($subtotal) ?></strong>

    </div>

    <div class="linha">

        <span>Frete</span>

        <strong>
            <?= $frete == 0.00 ? 'Grátis' : moeda($frete) ?>
        </strong>

    </div>

    <div class="linha total">

        <span>Total</span>

        <strong><?= moeda($total) ?></strong>

    </div>

    <button
        type="submit"
        form="checkout-form"
        class="confirmar"
    >

        CONFIRMAR PEDIDO →

    </button>

    <p class="seguranca">
        🔒 Seus dados estão protegidos.
    </p>

</aside>

</main>

<!-- BUSCA AUTOMÁTICA DE CEP -->

<script>

const cepInput = document.querySelector('#cep');

/*
|--------------------------------------------------------------------------
| MÁSCARA CEP
|--------------------------------------------------------------------------
*/

cepInput.addEventListener('input', function () {

    let cep = this.value
        .replace(/\D/g, '')
        .slice(0, 8);

    if (cep.length > 5) {

        cep = cep.slice(0, 5) + '-' + cep.slice(5);
    }

    this.value = cep;

});

/*
|--------------------------------------------------------------------------
| CONSULTA VIA CEP
|--------------------------------------------------------------------------
*/

cepInput.addEventListener('blur', async function () {

    const cep = this.value.replace(/\D/g, '');

    if (cep.length !== 8) {
        return;
    }

    try {

        const resposta = await fetch(
            `https://viacep.com.br/ws/${cep}/json/`
        );

        if (!resposta.ok) {
            return;
        }

        const dados = await resposta.json();

        if (dados.erro) {

            alert('CEP não encontrado.');

            return;
        }

        document.querySelector('#endereco').value =
            dados.logradouro || '';

        document.querySelector('#bairro').value =
            dados.bairro || '';

        document.querySelector('#cidade').value =
            dados.localidade || '';

        document.querySelector('#estado').value =
            dados.uf || '';

    } catch (erro) {

        console.error('Erro ao consultar CEP:', erro);

    }

});

/*
|--------------------------------------------------------------------------
| UF MAIÚSCULA
|--------------------------------------------------------------------------
*/

document.querySelector('#estado').addEventListener(
    'input',
    function () {

        this.value = this.value
            .toUpperCase()
            .replace(/[^A-Z]/g, '')
            .slice(0, 2);

    }
);

</script>

</body>
</html>
