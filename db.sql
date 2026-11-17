-- =====================================================================
-- Bia Modas - schema do banco de dados
--
-- ATENCAO: script DESTRUTIVO. O DROP DATABASE abaixo apaga o banco
-- "biamodas" inteiro. Use apenas em desenvolvimento/testes locais.
-- =====================================================================

DROP DATABASE IF EXISTS biamodas;

CREATE DATABASE biamodas
  CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE biamodas;

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Medidas do usuario (1:1 opcional com usuarios)
-- ---------------------------------------------------------------------
CREATE TABLE medidas(
  id_medida BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  busto DECIMAL(5,1) NULL,
  cintura DECIMAL(5,1) NULL,
  quadril DECIMAL(5,1) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT medidas_positivas_ck CHECK (
    (busto   IS NULL OR busto   > 0) AND
    (cintura IS NULL OR cintura > 0) AND
    (quadril IS NULL OR quadril > 0)
  )
);

-- ---------------------------------------------------------------------
-- Carrinho (1:1 opcional com usuarios)
-- ---------------------------------------------------------------------
CREATE TABLE carrinhos(
  id_carrinho BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  sessao_id CHAR(64) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT carrinhos_sessao_uk UNIQUE (sessao_id)
);

CREATE TABLE usuarios(
  id_usuario BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  carrinho_id BIGINT UNSIGNED NULL,
  medida_id BIGINT UNSIGNED NULL,
  nome VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  senha_hash VARCHAR(255) NOT NULL,
  cpf CHAR(11) NULL,
  telefone VARCHAR(20) NULL,
  data_nascimento DATE NULL,
  is_admin BOOLEAN NOT NULL DEFAULT FALSE,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT usuarios_email_uk UNIQUE (email),
  CONSTRAINT usuarios_cpf_uk UNIQUE (cpf),
  CONSTRAINT usuarios_carrinho_uk UNIQUE (carrinho_id),
  CONSTRAINT usuarios_medida_uk UNIQUE (medida_id),
  CONSTRAINT usuarios_medida_fk FOREIGN KEY (medida_id)
    REFERENCES medidas(id_medida) ON DELETE SET NULL,
  CONSTRAINT usuarios_carrinho_fk FOREIGN KEY (carrinho_id)
    REFERENCES carrinhos(id_carrinho) ON DELETE SET NULL
);

CREATE TABLE enderecos(
  id_endereco BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NOT NULL,
  nome_endereco VARCHAR(30) NOT NULL,
  logradouro VARCHAR(255) NOT NULL,
  numero VARCHAR(10) NOT NULL,
  complemento VARCHAR(60) NULL,
  bairro VARCHAR(60) NOT NULL,
  cidade VARCHAR(60) NOT NULL,
  uf CHAR(2) NOT NULL,
  cep CHAR(8) NOT NULL,
  principal BOOLEAN NOT NULL DEFAULT FALSE,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT enderecos_usuario_fk FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- Cupons de desconto
-- `desconto` e uma fracao: 0.10 = 10%
-- ---------------------------------------------------------------------
CREATE TABLE cupons(
  id_cupom BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(50) NOT NULL,
  val_min DECIMAL(10,2) NOT NULL,
  val_max DECIMAL(10,2) NOT NULL,
  desconto DECIMAL(5,2) NOT NULL,
  frete_gratis BOOLEAN NOT NULL DEFAULT FALSE,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT cupons_codigo_uk UNIQUE (codigo),
  CONSTRAINT cupons_valores_ck CHECK (
    val_min >= 0 AND
    val_max >= val_min AND
    desconto > 0 AND desconto < 1
  )
);

-- ---------------------------------------------------------------------
-- Ofertas (promocoes percentuais com vigencia)
-- `percentual` e uma fracao: 0.15 = 15%
-- ---------------------------------------------------------------------
CREATE TABLE ofertas(
  id_oferta BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  percentual DECIMAL(5,2) NOT NULL,
  inicio_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fim_em TIMESTAMP NULL,
  ativo BOOLEAN NOT NULL DEFAULT TRUE,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT ofertas_percentual_ck CHECK (percentual > 0 AND percentual < 1),
  CONSTRAINT ofertas_vigencia_ck CHECK (fim_em IS NULL OR fim_em >= inicio_em)
);

-- ---------------------------------------------------------------------
-- Especificacao de tamanho/medidas de um produto
-- ---------------------------------------------------------------------
CREATE TABLE medidas_produtos(
  id_medida_produto BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tamanho VARCHAR(5) NOT NULL,
  busto DECIMAL(5,1) NULL,
  cintura DECIMAL(5,1) NULL,
  quadril DECIMAL(5,1) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  INDEX medidas_produtos_tamanho_idx (tamanho),
  CONSTRAINT medidas_produtos_positivas_ck CHECK (
    (busto   IS NULL OR busto   > 0) AND
    (cintura IS NULL OR cintura > 0) AND
    (quadril IS NULL OR quadril > 0)
  )
);

-- ---------------------------------------------------------------------
-- Compras
-- ---------------------------------------------------------------------
CREATE TABLE compras(
  id_compra BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NULL,
  sessao_id CHAR(64) NULL,
  endereco_id BIGINT UNSIGNED NULL,
  cupom_id BIGINT UNSIGNED NULL,
  numero_pedido INT NOT NULL,
  valor_total DECIMAL(10,2) NOT NULL DEFAULT 0,
  situacao BOOLEAN NOT NULL DEFAULT FALSE,
  frete DECIMAL(10,2) NOT NULL DEFAULT 0,
  desconto DECIMAL(10,2) NOT NULL DEFAULT 0,
  destinatario VARCHAR(255) NOT NULL,
  telefone VARCHAR(20) NOT NULL,
  endereco_cep CHAR(8) NOT NULL,
  endereco_logradouro VARCHAR(255) NOT NULL,
  endereco_numero VARCHAR(10) NOT NULL,
  endereco_complemento VARCHAR(60) NULL,
  endereco_bairro VARCHAR(60) NOT NULL,
  endereco_cidade VARCHAR(60) NOT NULL,
  endereco_uf CHAR(2) NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT compras_numero_pedido_uk UNIQUE (numero_pedido),
  INDEX compras_sessao_idx (sessao_id),
  CONSTRAINT compras_dono_ck CHECK ((usuario_id IS NULL) <> (sessao_id IS NULL)),
  CONSTRAINT compras_valores_ck CHECK (
    valor_total >= 0 AND
    frete >= 0 AND
    desconto >= 0
  ),
  CONSTRAINT compras_usuario_fk FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
  CONSTRAINT compras_endereco_fk FOREIGN KEY (endereco_id)
    REFERENCES enderecos(id_endereco) ON DELETE SET NULL,
  CONSTRAINT compras_cupom_fk FOREIGN KEY (cupom_id)
    REFERENCES cupons(id_cupom) ON DELETE SET NULL
);

CREATE TABLE pagamentos(
  id_pagamento BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  compra_id BIGINT UNSIGNED NOT NULL,
  metodo VARCHAR(20) NOT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pendente',
  valor DECIMAL(10,2) NOT NULL,
  codigo_transacao VARCHAR(64) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT pagamentos_metodo_ck CHECK (metodo IN ('pix', 'cartao', 'boleto')),
  CONSTRAINT pagamentos_status_ck CHECK (status IN ('pendente', 'pago', 'cancelado')),
  CONSTRAINT pagamentos_valor_ck CHECK (valor >= 0),
  CONSTRAINT pagamentos_compra_fk FOREIGN KEY (compra_id)
    REFERENCES compras(id_compra) ON DELETE CASCADE
);

CREATE TABLE produtos(
  id_produto BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  medida_produto_id BIGINT UNSIGNED NOT NULL,
  oferta_id BIGINT UNSIGNED NULL,
  nome VARCHAR(255) NOT NULL,
  sku VARCHAR(50) NOT NULL,
  descricao VARCHAR(255) NULL,
  cor VARCHAR(30) NULL,
  estilo VARCHAR(30) NULL,
  estoque INT NOT NULL DEFAULT 0,
  valor DECIMAL(10,2) NOT NULL,
  ativo BOOLEAN NOT NULL DEFAULT TRUE,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT produtos_sku_uk UNIQUE (sku),
  CONSTRAINT produtos_valor_ck CHECK (valor > 0),
  CONSTRAINT produtos_estoque_ck CHECK (estoque >= 0),
  INDEX produtos_cor_idx (cor),
  INDEX produtos_estilo_idx (estilo),
  INDEX produtos_valor_idx (valor),
  INDEX produtos_ativo_idx (ativo, deletado_em),
  CONSTRAINT produtos_medida_fk FOREIGN KEY (medida_produto_id)
    REFERENCES medidas_produtos(id_medida_produto) ON DELETE RESTRICT,
  CONSTRAINT produtos_oferta_fk FOREIGN KEY (oferta_id)
    REFERENCES ofertas(id_oferta) ON DELETE SET NULL
);

CREATE TABLE categorias(
  id_categoria BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  categoria_pai_id BIGINT UNSIGNED NULL,
  nome VARCHAR(255) NOT NULL,
  descricao VARCHAR(255) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT categorias_nome_uk UNIQUE (nome),
  CONSTRAINT categorias_pai_fk FOREIGN KEY (categoria_pai_id)
    REFERENCES categorias(id_categoria) ON DELETE SET NULL
);

CREATE TABLE categorias_produtos(
  id_categoria_produto BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  produto_id BIGINT UNSIGNED NOT NULL,
  categoria_id BIGINT UNSIGNED NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT categorias_produtos_uk UNIQUE (produto_id, categoria_id),
  CONSTRAINT categorias_produtos_produto_fk FOREIGN KEY (produto_id)
    REFERENCES produtos(id_produto) ON DELETE CASCADE,
  CONSTRAINT categorias_produtos_categoria_fk FOREIGN KEY (categoria_id)
    REFERENCES categorias(id_categoria) ON DELETE CASCADE
);

CREATE TABLE imagens(
  id_imagem BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  produto_id BIGINT UNSIGNED NOT NULL,
  caminho_imagem VARCHAR(255) NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT imagens_produto_fk FOREIGN KEY (produto_id)
    REFERENCES produtos(id_produto) ON DELETE CASCADE
);

CREATE TABLE itens_carrinho(
  id_item_carrinho BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  carrinho_id BIGINT UNSIGNED NOT NULL,
  produto_id BIGINT UNSIGNED NOT NULL,
  quantidade INT NOT NULL,
  valor_unitario DECIMAL(10,2) NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT itens_carrinho_uk UNIQUE (carrinho_id, produto_id),
  CONSTRAINT itens_carrinho_quantidade_ck CHECK (quantidade > 0),
  CONSTRAINT itens_carrinho_valor_ck CHECK (valor_unitario >= 0),
  CONSTRAINT itens_carrinho_carrinho_fk FOREIGN KEY (carrinho_id)
    REFERENCES carrinhos(id_carrinho) ON DELETE CASCADE,
  CONSTRAINT itens_carrinho_produto_fk FOREIGN KEY (produto_id)
    REFERENCES produtos(id_produto) ON DELETE CASCADE
);

CREATE TABLE favoritos(
  id_favorito BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NULL,
  sessao_id CHAR(64) NULL,
  produto_id BIGINT UNSIGNED NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT favoritos_usuario_produto_uk UNIQUE (usuario_id, produto_id),
  CONSTRAINT favoritos_sessao_produto_uk UNIQUE (sessao_id, produto_id),
  CONSTRAINT favoritos_dono_ck CHECK ((usuario_id IS NULL) <> (sessao_id IS NULL)),
  CONSTRAINT favoritos_usuario_fk FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  CONSTRAINT favoritos_produto_fk FOREIGN KEY (produto_id)
    REFERENCES produtos(id_produto) ON DELETE CASCADE
);

CREATE TABLE itens_compra(
  id_item_compra BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  compra_id BIGINT UNSIGNED NOT NULL,
  produto_id BIGINT UNSIGNED NOT NULL,
  quantidade INT NOT NULL,
  valor_unitario DECIMAL(10,2) NOT NULL,
  valor_total DECIMAL(10,2) NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  alterado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deletado_em DATETIME NULL,
  CONSTRAINT itens_compra_quantidade_ck CHECK (quantidade > 0),
  CONSTRAINT itens_compra_valores_ck CHECK (valor_unitario >= 0 AND valor_total >= 0),
  CONSTRAINT itens_compra_compra_fk FOREIGN KEY (compra_id)
    REFERENCES compras(id_compra) ON DELETE CASCADE,
  CONSTRAINT itens_compra_produto_fk FOREIGN KEY (produto_id)
    REFERENCES produtos(id_produto) ON DELETE RESTRICT
);

-- ---------------------------------------------------------------------
-- Recuperacao de senha por codigo de 6 digitos.
-- O codigo nunca e gravado em texto puro: guardamos o hash do password_hash().
-- ---------------------------------------------------------------------
CREATE TABLE codigos_recuperacao_senha(
  id_codigo BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  usuario_id BIGINT UNSIGNED NOT NULL,
  codigo_hash VARCHAR(255) NOT NULL,
  tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expira_em DATETIME NOT NULL,
  usado_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT codigos_recuperacao_usuario_fk FOREIGN KEY (usuario_id)
    REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
  INDEX codigos_recuperacao_usuario_idx (usuario_id, usado_em)
);
