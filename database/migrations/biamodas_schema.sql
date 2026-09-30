-- =====================================================================
--  Bia Modas - Script de criação do banco de dados
-- =====================================================================
--  SGBD alvo : MySQL 8.0 / MariaDB 10.5+ (InnoDB, utf8mb4)
--  Base      : Requisitos do README (RF01-RF14 / RNF01-RNF15)
--              + o que existe de fato na branch `apresentacao-pi`
--
--  REGRA DE DECISÃO adotada:
--    Quando o requisito e o código divergem, o REQUISITO prevalece.
--    Ex.: o RF01 exige CPF e Data de Nascimento, mas o código
--    (registro.html / auth.js) não os possui -> as colunas existem aqui.
--
--  Mapa rápido Requisito -> Tabela(s):
--    RF01 Cadastro ................ Usuario, Endereco, MedidaUsuario
--    RF02 Login ................... Usuario (email/senha_hash)
--    RF03 Recuperar senha ......... RecuperacaoSenha
--    RF04 Busca ................... Produto (FULLTEXT nome/descricao)
--    RF05 Recomendações .......... Consentimento (coleta autorizada)
--    RF06 Catálogo/detalhes ....... Produto, Imagem, MedidaProduto
--    RF07 Variações/favoritos ..... ProdutoVariacao, Favorito, ItemCarrinho
--    RF08 Carrinho/checkout ....... Carrinho, ItemCarrinho, Compra, Pagamento
--    RF09 Filtros ................. Categoria, Produto, ProdutoVariacao, Oferta
--    RF10 Dashboard admin ......... Usuario.tipo + views de apoio
--    RF11 Gestão admin ............ Usuario (CRUD), Produto, Oferta
--    RF12 Histórico de pedidos .... Compra, ItemCompra
--    RF13 Avaliação ................ AvaliacaoProduto, AvaliacaoFoto
--    RF14 Estoque .................. Produto.estoque, ProdutoVariacao.estoque,
--                                   MovimentacaoEstoque
--    RNF05 LGPD .................... Consentimento
--    RNF11 Senha segura ........... Usuario.senha_hash (bcrypt/argon2)
--    RNF12 Controle de acesso ..... Usuario.tipo = 'ADMIN'
-- =====================================================================

-- Descomente a linha abaixo para recriar o banco do zero (APAGA tudo):
-- DROP DATABASE IF EXISTS biamodas;

CREATE DATABASE IF NOT EXISTS biamodas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE biamodas;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 1. USUÁRIOS
-- =====================================================================

CREATE TABLE IF NOT EXISTS Usuario (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome              VARCHAR(150) NOT NULL,
    cpf               CHAR(11)     NOT NULL,                 -- RF01 (ausente no código)
    email             VARCHAR(150) NOT NULL,
    senha_hash        VARCHAR(255) NOT NULL,                 -- RNF11: bcrypt/argon2
    data_nascimento   DATE         NOT NULL,                 -- RF01 (ausente no código)
    telefone          VARCHAR(20)  NULL,
    tipo              ENUM('CLIENTE','ADMIN') NOT NULL DEFAULT 'CLIENTE', -- RF10/RF11/RNF12
    ativo             TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                       ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuario_cpf   (cpf),
    UNIQUE KEY uq_usuario_email (email)
) ENGINE=InnoDB;

-- Endereço completo (RF01). Separado de Usuario para permitir vários endereços.
CREATE TABLE IF NOT EXISTS Endereco (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id   INT UNSIGNED NOT NULL,
    cep          CHAR(8)      NOT NULL,
    logradouro   VARCHAR(150) NOT NULL,
    numero       VARCHAR(20)  NOT NULL,
    complemento  VARCHAR(100) NULL,
    bairro       VARCHAR(100) NOT NULL,
    cidade       VARCHAR(100) NOT NULL,
    estado       CHAR(2)      NOT NULL,
    principal    TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_endereco_usuario (usuario_id),
    CONSTRAINT fk_endereco_usuario FOREIGN KEY (usuario_id)
        REFERENCES Usuario (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Medidas do usuário (RF01). Único dado de personalização mantido.
CREATE TABLE IF NOT EXISTS MedidaUsuario (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id       INT UNSIGNED NOT NULL,
    busto            DECIMAL(5,1) NULL,
    cintura          DECIMAL(5,1) NULL,
    quadril          DECIMAL(5,1) NULL,
    altura           DECIMAL(5,1) NULL,
    tamanho_blusa    VARCHAR(5)   NULL,
    tamanho_calca    VARCHAR(5)   NULL,
    tamanho_vestido  VARCHAR(5)   NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_medida_usuario (usuario_id),
    CONSTRAINT fk_medida_usuario FOREIGN KEY (usuario_id)
        REFERENCES Usuario (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Cores usadas nas variações de produto (ProdutoVariacao.cor_id).
CREATE TABLE IF NOT EXISTS Cor (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome  VARCHAR(50)  NOT NULL,
    hex   CHAR(7)      NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cor_nome (nome)
) ENGINE=InnoDB;

-- Consentimento (RF05 + RNF05/LGPD). Base para "coleta com consentimento".
CREATE TABLE IF NOT EXISTS Consentimento (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id     INT UNSIGNED NOT NULL,
    finalidade     ENUM('DADOS_PESSOAIS','RECOMENDACOES','NEWSLETTER','MARKETING')
                   NOT NULL,
    aceito         TINYINT(1)   NOT NULL DEFAULT 0,
    versao_termos  VARCHAR(20)  NULL,
    data_hora      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_consent_usuario (usuario_id),
    CONSTRAINT fk_consent_usuario FOREIGN KEY (usuario_id)
        REFERENCES Usuario (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Recuperação de senha (RF03 - inexistente no código atual).
CREATE TABLE IF NOT EXISTS RecuperacaoSenha (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id  INT UNSIGNED NOT NULL,
    token       VARCHAR(255) NOT NULL,
    expira_em   DATETIME     NOT NULL,
    usado       TINYINT(1)   NOT NULL DEFAULT 0,
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_recuperacao_token (token),
    KEY idx_recuperacao_usuario (usuario_id),
    CONSTRAINT fk_recuperacao_usuario FOREIGN KEY (usuario_id)
        REFERENCES Usuario (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 2. CATÁLOGO
-- =====================================================================

-- Categoria hierárquica (cobre "categoria" e "tipo" usados no código).
CREATE TABLE IF NOT EXISTS Categoria (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome               VARCHAR(80)  NOT NULL,
    descricao          VARCHAR(255) NULL,
    categoria_pai_id   INT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categoria_nome (nome),
    CONSTRAINT fk_categoria_pai FOREIGN KEY (categoria_pai_id)
        REFERENCES Categoria (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Produto (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sku             VARCHAR(60)  NOT NULL,
    nome            VARCHAR(150) NOT NULL,
    descricao       TEXT         NULL,                 -- RF06
    especificacoes  TEXT         NULL,                 -- RF06 (no código é texto fixo)
    valor           DECIMAL(10,2) NOT NULL,
    estoque         INT          NOT NULL DEFAULT 0,   -- RF14 (agregado; detalhe por variação)
    ativo           TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                     ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_produto_sku (sku),
    KEY idx_produto_nome (nome),
    KEY idx_produto_ativo (ativo),
    KEY idx_produto_valor (valor),
    FULLTEXT KEY ft_produto_busca (nome, descricao)    -- RF04 (busca com sugestões)
) ENGINE=InnoDB;

-- N:N produto x categoria.
CREATE TABLE IF NOT EXISTS ProdutoCategoria (
    produto_id    INT UNSIGNED NOT NULL,
    categoria_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (produto_id, categoria_id),
    CONSTRAINT fk_pc_produto FOREIGN KEY (produto_id)
        REFERENCES Produto (id) ON DELETE CASCADE,
    CONSTRAINT fk_pc_categoria FOREIGN KEY (categoria_id)
        REFERENCES Categoria (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Variações (cor/tamanho) - RF07/RF09/RF14.
CREATE TABLE IF NOT EXISTS ProdutoVariacao (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    produto_id  INT UNSIGNED NOT NULL,
    sku         VARCHAR(80)  NOT NULL,
    cor_id      INT UNSIGNED NULL,
    tamanho     VARCHAR(5)   NOT NULL,
    estoque     INT          NOT NULL DEFAULT 0,        -- RF14
    ativo       TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_variacao_sku (sku),
    KEY idx_variacao_produto (produto_id),
    KEY idx_variacao_tamanho (tamanho),
    KEY idx_variacao_cor (cor_id),
    CONSTRAINT fk_variacao_produto FOREIGN KEY (produto_id)
        REFERENCES Produto (id) ON DELETE CASCADE,
    CONSTRAINT fk_variacao_cor FOREIGN KEY (cor_id)
        REFERENCES Cor (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Imagem (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    produto_id  INT UNSIGNED NOT NULL,
    caminho     VARCHAR(255) NOT NULL,
    ordem       INT          NOT NULL DEFAULT 0,
    principal   TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_imagem_produto (produto_id),
    CONSTRAINT fk_imagem_produto FOREIGN KEY (produto_id)
        REFERENCES Produto (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS MedidaProduto (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    produto_id  INT UNSIGNED NOT NULL,
    busto       DECIMAL(5,1) NULL,
    cintura     DECIMAL(5,1) NULL,
    quadril     DECIMAL(5,1) NULL,
    tamanho     VARCHAR(10)  NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_medida_produto (produto_id),
    CONSTRAINT fk_medida_produto FOREIGN KEY (produto_id)
        REFERENCES Produto (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Promoções/ofertas (RF11).
CREATE TABLE IF NOT EXISTS Oferta (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome         VARCHAR(80)  NULL,
    percentual   DECIMAL(5,2) NOT NULL,
    data_inicio  DATE         NOT NULL,
    data_fim     DATE         NOT NULL,
    ativo        TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    CONSTRAINT chk_oferta_percentual CHECK (percentual > 0 AND percentual <= 100)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ProdutoOferta (
    produto_id  INT UNSIGNED NOT NULL,
    oferta_id   INT UNSIGNED NOT NULL,
    PRIMARY KEY (produto_id, oferta_id),
    CONSTRAINT fk_po_produto FOREIGN KEY (produto_id)
        REFERENCES Produto (id) ON DELETE CASCADE,
    CONSTRAINT fk_po_oferta FOREIGN KEY (oferta_id)
        REFERENCES Oferta (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 3. FAVORITOS, CARRINHO E COMPRA
-- =====================================================================

CREATE TABLE IF NOT EXISTS Favorito (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id  INT UNSIGNED NOT NULL,
    produto_id  INT UNSIGNED NOT NULL,
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_favorito (usuario_id, produto_id),
    CONSTRAINT fk_favorito_usuario FOREIGN KEY (usuario_id)
        REFERENCES Usuario (id) ON DELETE CASCADE,
    CONSTRAINT fk_favorito_produto FOREIGN KEY (produto_id)
        REFERENCES Produto (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Carrinho (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id    INT UNSIGNED NULL,                    -- NULL = visitante
    data_criacao  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status        ENUM('ATIVO','FINALIZADO','ABANDONADO') NOT NULL DEFAULT 'ATIVO',
    PRIMARY KEY (id),
    KEY idx_carrinho_usuario (usuario_id),
    CONSTRAINT fk_carrinho_usuario FOREIGN KEY (usuario_id)
        REFERENCES Usuario (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ItemCarrinho (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    carrinho_id          INT UNSIGNED NOT NULL,
    produto_variacao_id  INT UNSIGNED NOT NULL,
    quantidade           INT          NOT NULL DEFAULT 1,   -- RF07 (ausente no código)
    valor_unitario       DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_item_carrinho (carrinho_id, produto_variacao_id),
    CONSTRAINT fk_ic_carrinho FOREIGN KEY (carrinho_id)
        REFERENCES Carrinho (id) ON DELETE CASCADE,
    CONSTRAINT fk_ic_variacao FOREIGN KEY (produto_variacao_id)
        REFERENCES ProdutoVariacao (id),
    CONSTRAINT chk_ic_quantidade CHECK (quantidade > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Compra (
    numero_pedido  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id     INT UNSIGNED NOT NULL,
    endereco_id    INT UNSIGNED NULL,
    data_compra    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal       DECIMAL(10,2) NOT NULL DEFAULT 0,
    frete          DECIMAL(10,2) NOT NULL DEFAULT 0,
    desconto       DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_total    DECIMAL(10,2) NOT NULL DEFAULT 0,
    status         ENUM('PENDENTE','PAGO','ENVIADO','ENTREGUE','CANCELADO')
                   NOT NULL DEFAULT 'PENDENTE',
    PRIMARY KEY (numero_pedido),
    KEY idx_compra_usuario (usuario_id),
    CONSTRAINT fk_compra_usuario FOREIGN KEY (usuario_id)
        REFERENCES Usuario (id),
    CONSTRAINT fk_compra_endereco FOREIGN KEY (endereco_id)
        REFERENCES Endereco (id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ItemCompra (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    compra_id            INT UNSIGNED NOT NULL,
    produto_variacao_id  INT UNSIGNED NOT NULL,
    quantidade           INT          NOT NULL,
    valor_unitario       DECIMAL(10,2) NOT NULL,
    valor_total          DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_item_compra (compra_id),
    CONSTRAINT fk_item_compra FOREIGN KEY (compra_id)
        REFERENCES Compra (numero_pedido) ON DELETE CASCADE,
    CONSTRAINT fk_item_compra_variacao FOREIGN KEY (produto_variacao_id)
        REFERENCES ProdutoVariacao (id),
    CONSTRAINT chk_item_compra_qtd CHECK (quantidade > 0)
) ENGINE=InnoDB;

-- Integração de pagamento (RF08). No código atual o checkout é só WhatsApp.
CREATE TABLE IF NOT EXISTS Pagamento (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    compra_id     INT UNSIGNED NOT NULL,
    metodo        ENUM('CARTAO_CREDITO','CARTAO_DEBITO','PIX','BOLETO','WHATSAPP')
                  NOT NULL,
    status        ENUM('PENDENTE','APROVADO','RECUSADO','ESTORNADO')
                  NOT NULL DEFAULT 'PENDENTE',
    transacao_id  VARCHAR(120) NULL,
    valor         DECIMAL(10,2) NOT NULL,
    data_hora     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pagamento_compra (compra_id),
    CONSTRAINT fk_pagamento_compra FOREIGN KEY (compra_id)
        REFERENCES Compra (numero_pedido) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 4. AVALIAÇÕES, ESTOQUE E EXTRAS DA BRANCH
-- =====================================================================

CREATE TABLE IF NOT EXISTS AvaliacaoProduto (          -- RF13
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    produto_id  INT UNSIGNED NOT NULL,
    usuario_id  INT UNSIGNED NOT NULL,
    nota        TINYINT      NOT NULL,
    comentario  TEXT         NULL,
    aprovado    TINYINT(1)   NOT NULL DEFAULT 0,
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_avaliacao (produto_id, usuario_id),
    CONSTRAINT chk_avaliacao_nota CHECK (nota BETWEEN 1 AND 5),
    CONSTRAINT fk_avaliacao_produto FOREIGN KEY (produto_id)
        REFERENCES Produto (id) ON DELETE CASCADE,
    CONSTRAINT fk_avaliacao_usuario FOREIGN KEY (usuario_id)
        REFERENCES Usuario (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS AvaliacaoFoto (             -- "avaliações com fotos" (README 7.8)
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    avaliacao_id  INT UNSIGNED NOT NULL,
    caminho       VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_avaliacao_foto FOREIGN KEY (avaliacao_id)
        REFERENCES AvaliacaoProduto (id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS MovimentacaoEstoque (       -- RF14
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    produto_variacao_id  INT UNSIGNED NOT NULL,
    tipo                 ENUM('ENTRADA','SAIDA','AJUSTE') NOT NULL,
    quantidade           INT          NOT NULL,
    motivo               VARCHAR(150) NULL,
    data_hora            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mov_variacao (produto_variacao_id),
    CONSTRAINT fk_mov_variacao FOREIGN KEY (produto_variacao_id)
        REFERENCES ProdutoVariacao (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- "Vistos recentemente" (feature da branch apresentacao-pi).
CREATE TABLE IF NOT EXISTS VistoRecente (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id  INT UNSIGNED NULL,                      -- NULL = visitante
    produto_id  INT UNSIGNED NOT NULL,
    visto_em    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_visto_usuario (usuario_id, visto_em),
    CONSTRAINT fk_visto_usuario FOREIGN KEY (usuario_id)
        REFERENCES Usuario (id) ON DELETE CASCADE,
    CONSTRAINT fk_visto_produto FOREIGN KEY (produto_id)
        REFERENCES Produto (id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Newsletter (feature da branch apresentacao-pi).
CREATE TABLE IF NOT EXISTS NewsletterAssinante (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email       VARCHAR(150) NOT NULL,
    ativo       TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_newsletter_email (email)
) ENGINE=InnoDB;

-- =====================================================================
-- 5. VIEWS DE APOIO (RF10 Dashboard / RF12 Histórico)
-- =====================================================================

CREATE OR REPLACE VIEW vw_catalogo_produtos AS
SELECT
    p.id, p.sku, p.nome, p.descricao, p.valor, p.estoque, p.ativo,
    (SELECT GROUP_CONCAT(c.nome ORDER BY c.nome SEPARATOR ', ')
       FROM ProdutoCategoria pc
       JOIN Categoria c ON c.id = pc.categoria_id
      WHERE pc.produto_id = p.id)                          AS categorias,
    (SELECT MIN(v.estoque)
       FROM ProdutoVariacao v
      WHERE v.produto_id = p.id AND v.ativo = 1)           AS estoque_min_variacao,
    (SELECT o.percentual
       FROM ProdutoOferta po
       JOIN Oferta o ON o.id = po.oferta_id
      WHERE po.produto_id = p.id
        AND o.ativo = 1
        AND CURDATE() BETWEEN o.data_inicio AND o.data_fim
      ORDER BY o.percentual DESC LIMIT 1)                  AS percentual_oferta
FROM Produto p;

CREATE OR REPLACE VIEW vw_historico_pedidos AS          -- RF12
SELECT
    c.numero_pedido, u.id AS usuario_id, u.nome AS cliente,
    c.data_compra, c.valor_total, c.status,
    (SELECT COUNT(*) FROM ItemCompra i WHERE i.compra_id = c.numero_pedido) AS qtd_itens
FROM Compra c
JOIN Usuario u ON u.id = c.usuario_id;

CREATE OR REPLACE VIEW vw_dashboard_vendas AS           -- RF10
SELECT
    DATE(c.data_compra) AS dia,
    COUNT(*)            AS pedidos,
    SUM(c.valor_total)  AS faturamento
FROM Compra c
WHERE c.status <> 'CANCELADO'
GROUP BY DATE(c.data_compra);

CREATE OR REPLACE VIEW vw_estoque_baixo AS              -- RF14
SELECT
    p.id AS produto_id, p.nome AS produto,
    v.id AS variacao_id, v.tamanho, v.estoque
FROM ProdutoVariacao v
JOIN Produto p ON p.id = v.produto_id
WHERE v.ativo = 1 AND v.estoque <= 10
ORDER BY v.estoque ASC;

-- =====================================================================
-- FIM DO SCRIPT
-- =====================================================================
