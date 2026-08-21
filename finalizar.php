<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['usuario_id'])) {
    $_SESSION['redirect_depois_login'] = 'finalizar.php';
    header('Location: cadastro.php');
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];
$carrinho = $_SESSION['carrinho'] ?? [];
$erros = [];

if (!is_array($carrinho) || empty($carrinho)) {
    header('Location: carrinho.php');
    exit;
}

function moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function textoPost(string $campo): string
{
    return trim((string) ($_POST[$campo] ?? ''));
}

function buscarProdutosDoCarrinho(PDO $pdo, array $carrinho): array
{
    $ids = [];

    foreach ($carrinho as $produtoId => $quantidade) {
        $produtoId = (int) $produtoId;
        $quantidade = (int) $quantidade;

        if ($produtoId > 0 && $quantidade > 0) {
            $ids[] = $produtoId;
        }
    }

    if (empty($ids)) {
        return [];
    }

    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    /*
     * Se sua tabela usa outro nome para preço, como "valor",
     * troque "preco" na consulta e nos pontos correspondentes.
     */
    $consulta = $pdo->prepare(
        "SELECT id, nome, preco
         FROM produtos
         WHERE id IN ($marcadores)"
    );

    $consulta->execute($ids);
    $resultados = $consulta->fetchAll();
    $produtos = [];

    foreach ($resultados as $produto) {
        $id = (int) $produto['id'];
        $quantidade = (int) ($carrinho[$id] ?? 0);

        if ($quantidade < 1) {
            continue;
        }

        $preco = (float) $produto['preco'];

        $produtos[] = [
            'id' => $id,
            'nome' => $produto['nome'],
            'preco' => $preco,
            'quantidade' => $quantidade,
            'subtotal' => $preco * $quantidade
        ];
    }

    return $produtos;
}

$produtos = buscarProdutosDoCarrinho($pdo, $carrinho);

if (empty($produtos)) {
    unset($_SESSION['carrinho']);
    header('Location: carrinho.php');
    exit;
}

$subtotal = array_sum(array_column($produtos, 'subtotal'));

/*
 * Regra de exemplo:
 * frete grátis a partir de R$ 199,00;
 * abaixo disso, R$ 19,90.
 */
$frete = $subtotal >= 199.00 ? 0.00 : 19.90;
$total = $subtotal + $frete;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    $pagamentosPermitidos = ['pix', 'cartao', 'boleto'];

    if (mb_strlen($nome) < 3) {
        $erros[] = 'Informe o nome completo.';
    }

    if (!preg_match('/^[0-9()\s+-]{10,20}$/', $telefone)) {
        $erros[] = 'Informe um telefone válido.';
    }

    if (!preg_match('/^\d{5}-?\d{3}$/', $cep)) {
        $erros[] = 'Informe um CEP válido.';
    }

    if ($endereco === '' || $numero === '' || $bairro === '' || $cidade === '') {
        $erros[] = 'Preencha todos os campos obrigatórios do endereço.';
    }

    if (!preg_match('/^[A-Z]{2}$/', $estado)) {
        $erros[] = 'Informe a sigla do estado com duas letras.';
    }

    if (!in_array($pagamento, $pagamentosPermitidos, true)) {
        $erros[] = 'Selecione uma forma de pagamento válida.';
    }

    if (empty($erros)) {
        try {
            $pdo->beginTransaction();

            /*
             * Os valores são recalculados dentro da transação.
             * Nunca aceite preços enviados por campos do formulário.
             */
            $produtos = buscarProdutosDoCarrinho(
                $pdo,
                $_SESSION['carrinho'] ?? []
            );

            if (empty($produtos)) {
                throw new RuntimeException('O carrinho está vazio.');
            }

            $subtotal = array_sum(array_column($produtos, 'subtotal'));
            $frete = $subtotal >= 199.00 ? 0.00 : 19.90;
            $total = $subtotal + $frete;

            $salvarPedido = $pdo->prepare(
                'INSERT INTO pedidos (
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
                )'
            );

            $salvarPedido->execute([
                ':usuario_id' => $usuarioId,
                ':subtotal' => $subtotal,
                ':frete' => $frete,
                ':total' => $total,
                ':status' => 'Aguardando pagamento',
                ':pagamento' => $pagamento,
                ':nome_cliente' => $nome,
                ':telefone' => $telefone,
                ':cep' => $cep,
                ':endereco' => $endereco,
                ':numero' => $numero,
                ':complemento' => $complemento ?: null,
                ':bairro' => $bairro,
                ':cidade' => $cidade,
                ':estado' => $estado
            ]);

            $pedidoId = (int) $pdo->lastInsertId();

            $salvarItem = $pdo->prepare(
                'INSERT INTO pedido_itens (
                    pedido_id,
                    produto_id,
                    produto_nome,
                    quantidade,
                    preco_unitario,
                    subtotal
                ) VALUES (
                    :pedido_id,
                    :produto_id,
                    :produto_nome,
                    :quantidade,
                    :preco_unitario,
                    :subtotal
                )'
            );

            foreach ($produtos as $produto) {
                $salvarItem->execute([
                    ':pedido_id' => $pedidoId,
                    ':produto_id' => $produto['id'],
                    ':produto_nome' => $produto['nome'],
                    ':quantidade' => $produto['quantidade'],
                    ':preco_unitario' => $produto['preco'],
                    ':subtotal' => $produto['subtotal']
                ]);
            }

            $pdo->commit();

            unset($_SESSION['carrinho']);
            $_SESSION['ultimo_pedido_id'] = $pedidoId;

            header('Location: pedido_confirmado.php?id=' . $pedidoId);
            exit;
        } catch (Throwable $erro) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($erro->getMessage());
            $erros[] = 'Não foi possível concluir o pedido. Tente novamente.';
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
    <title>Finalizar compra - MBM Suplementos</title>
    <link rel="stylesheet" href="css/finalizar.css">
</head>
<body>
<header class="cabecalho">
    <a class="logo" href="index.php">MBM <span>SUPLEMENTOS</span></a>
    <a class="voltar" href="carrinho.php">← Voltar ao carrinho</a>
</header>

<main class="checkout">
    <section class="formulario">
        <div class="titulo">
            <span>Checkout seguro</span>
            <h1>Finalizar compra</h1>
            <p>Preencha seus dados para concluir o pedido.</p>
        </div>

        <?php if ($erros): ?>
            <div class="alerta" role="alert">
                <?php foreach ($erros as $erro): ?>
                    <p><?= htmlspecialchars($erro) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" id="checkout-form">
            <div class="bloco">
                <h2>1. Dados pessoais</h2>

                <div class="grade">
                    <label class="campo campo-grande">
                        <span>Nome completo</span>
                        <input
                            type="text"
                            name="nome"
                            maxlength="120"
                            autocomplete="name"
                            value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
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
                            placeholder="(85) 99999-9999"
                            value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>"
                            required
                        >
                    </label>
                </div>
            </div>

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
                            value="<?= htmlspecialchars($_POST['cep'] ?? '') ?>"
                            required
                        >
                    </label>

                    <label class="campo campo-grande">
                        <span>Endereço</span>
                        <input
                            id="endereco"
                            type="text"
                            name="endereco"
                            maxlength="150"
                            autocomplete="street-address"
                            value="<?= htmlspecialchars($_POST['endereco'] ?? '') ?>"
                            required
                        >
                    </label>

                    <label class="campo">
                        <span>Número</span>
                        <input
                            type="text"
                            name="numero"
                            maxlength="20"
                            value="<?= htmlspecialchars($_POST['numero'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($_POST['complemento'] ?? '') ?>"
                        >
                    </label>

                    <label class="campo">
                        <span>Bairro</span>
                        <input
                            id="bairro"
                            type="text"
                            name="bairro"
                            maxlength="100"
                            value="<?= htmlspecialchars($_POST['bairro'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($_POST['cidade'] ?? '') ?>"
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
                            value="<?= htmlspecialchars($_POST['estado'] ?? '') ?>"
                            required
                        >
                    </label>
                </div>
            </div>

            <div class="bloco">
                <h2>3. Forma de pagamento</h2>

                <div class="pagamentos">
                    <label class="pagamento">
                        <input
                            type="radio"
                            name="pagamento"
                            value="pix"
                            <?= ($_POST['pagamento'] ?? 'pix') === 'pix'
                                ? 'checked'
                                : '' ?>
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
                            <?= ($_POST['pagamento'] ?? '') === 'cartao'
                                ? 'checked'
                                : '' ?>
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
                            <?= ($_POST['pagamento'] ?? '') === 'boleto'
                                ? 'checked'
                                : '' ?>
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

    <aside class="resumo">
        <h2>Resumo do pedido</h2>

        <div class="lista-produtos">
            <?php foreach ($produtos as $produto): ?>
                <div class="item">
                    <div>
                        <strong>
                            <?= htmlspecialchars($produto['nome']) ?>
                        </strong>
                        <small>
                            Quantidade: <?= $produto['quantidade'] ?>
                        </small>
                    </div>

                    <span><?= moeda($produto['subtotal']) ?></span>
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
                <?= $frete === 0.0 ? 'Grátis' : moeda($frete) ?>
            </strong>
        </div>

        <div class="linha total">
            <span>Total</span>
            <strong><?= moeda($total) ?></strong>
        </div>

        <button type="submit" form="checkout-form" class="confirmar">
            CONFIRMAR PEDIDO →
        </button>

        <p class="seguranca">🔒 Seus dados estão protegidos.</p>
    </aside>
</main>

<script>
const cepInput = document.querySelector('#cep');

cepInput.addEventListener('input', function () {
    let cep = this.value.replace(/\D/g, '').slice(0, 8);

    if (cep.length > 5) {
        cep = cep.slice(0, 5) + '-' + cep.slice(5);
    }

    this.value = cep;
});

cepInput.addEventListener('blur', async function () {
    const cep = this.value.replace(/\D/g, '');

    if (cep.length !== 8) {
        return;
    }

    try {
        const resposta = await fetch(
            `[viacep.com.br](https://viacep.com.br/ws/${cep}/json/)`
        );

        if (!resposta.ok) {
            return;
        }

        const dados = await resposta.json();

        if (dados.erro) {
            return;
        }

        document.querySelector('#endereco').value = dados.logradouro || '';
        document.querySelector('#bairro').value = dados.bairro || '';
        document.querySelector('#cidade').value = dados.localidade || '';
        document.querySelector('#estado').value = dados.uf || '';
    } catch (erro) {
        console.error('Não foi possível consultar o CEP.');
    }
});
</script>
</body>
</html>
