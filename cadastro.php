<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/database.php';

if (!$pdo instanceof PDO) {
    die('Erro: conexão com o banco de dados não foi criada.');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$erros = [];
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $senha = (string) ($_POST['senha'] ?? '');
    $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | VALIDAÇÕES
    |--------------------------------------------------------------------------
    */
    if (mb_strlen($nome) < 3) {
        $erros[] = 'Informe seu nome completo (mínimo 3 caracteres).';
    }

    if (!$email) {
        $erros[] = 'Informe um e-mail válido.';
    }

    if (strlen($senha) < 6) {
        $erros[] = 'A senha deve ter no mínimo 6 caracteres.';
    }

    if ($senha !== $confirmarSenha) {
        $erros[] = 'As senhas não conferem.';
    }

    /*
    |--------------------------------------------------------------------------
    | SALVA NO BANCO
    |--------------------------------------------------------------------------
    */
    if (empty($erros)) {
        try {
            // Verifica se o e-mail já existe no banco
            $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmtCheck->execute([':email' => $email]);

            if ($stmtCheck->fetch()) {
                $erros[] = 'Este e-mail já está cadastrado.';
            } else {
                // Cria a hash segura da senha
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

                $sql = "
                    INSERT INTO usuarios (nome, email, senha)
                    VALUES (:nome, :email, :senha)
                ";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':senha' => $senhaHash
                ]);

                $novoUsuarioId = (int) $pdo->lastInsertId();

                // Autentica o usuário na sessão
                $_SESSION['usuario_id'] = $novoUsuarioId;
                $_SESSION['usuario_nome'] = $nome;

                // Se havia um redirecionamento pendente (ex: checkout/finalizar.php)
                $redirect = $_SESSION['redirect_depois_login'] ?? 'index.php';
                unset($_SESSION['redirect_depois_login']);

                header('Location: ' . $redirect);
                exit;
            }
        } catch (Throwable $erro) {
            error_log('Erro ao cadastrar usuário: ' . $erro->getMessage());
            $erros[] = 'Erro ao criar conta. Tente novamente mais tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta - MBM Suplementos</title>
</head>
<body>

<header>
    <a href="index.php">MBM SUPLEMENTOS</a>
</header>

<main>
    <h1>Criar conta</h1>
    <p>Cadastre-se para realizar suas compras.</p>

    <?php if (!empty($erros)): ?>
        <div style="color: red; margin-bottom: 15px;">
            <?php foreach ($erros as $erro): ?>
                <p><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>
            Nome completo
            <input 
                type="text" 
                name="nome" 
                value="<?= htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                required
            >
        </label>

        <label>
            E-mail
            <input 
                type="email" 
                name="email" 
                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                required
            >
        </label>

        <label>
            Senha
            <input type="password" name="senha" required>
        </label>

        <label>
            Confirmar senha
            <input type="password" name="confirmar_senha" required>
        </label>

        <button type="submit">CRIAR CONTA</button>
    </form>

    <p>Já possui uma conta? <a href="login.php">Fazer login</a></p>
</main>

</body>
</html>
