CREATE DATABASE IF NOT EXISTS mbm_suplementos
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE mbm_suplementos;

DROP TABLE IF EXISTS pedido_itens;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estoque INT NOT NULL DEFAULT 0,
    categoria VARCHAR(80) NOT NULL DEFAULT 'Outros',
    imagem VARCHAR(500) DEFAULT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM(
        'aguardando_pagamento',
        'pago',
        'enviado',
        'entregue',
        'cancelado'
    ) NOT NULL DEFAULT 'aguardando_pagamento',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_pedidos_usuario
        FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pedido_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    produto_id INT UNSIGNED NOT NULL,
    quantidade INT NOT NULL,
    preco DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_itens_pedido
        FOREIGN KEY (pedido_id)
        REFERENCES pedidos(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_itens_produto
        FOREIGN KEY (produto_id)
        REFERENCES produtos(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

INSERT INTO produtos
(nome, descricao, preco, estoque, categoria, imagem, ativo)
VALUES
(
    'Whey Protein 900g',
    'Whey Protein para complementar sua alimentação e rotina de treinos.',
    129.90,
    30,
    'Whey Protein',
    '',
    1
),
(
    'Creatina Monohidratada 300g',
    'Creatina monohidratada para auxiliar no desempenho durante os treinos.',
    89.90,
    50,
    'Creatina',
    '',
    1
),
(
    'Pré-Treino 300g',
    'Pré-treino para aumentar sua disposição antes do exercício.',
    99.90,
    25,
    'Pré-Treino',
    '',
    1
),
(
    'BCAA 120 Cápsulas',
    'Suplemento para complementar sua rotina esportiva.',
    69.90,
    20,
    'BCAA',
    '',
    1
);
CREATE TABLE administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

SELECT * FROM usuarios;

UPDATE produtos
SET imagem = 'img/whey.jpg'
WHERE id = 1;

SELECT id, nome, imagem
FROM produtos;

UPDATE produtos
SET imagem = 'img/creatina.jpg'
WHERE id = 2;
