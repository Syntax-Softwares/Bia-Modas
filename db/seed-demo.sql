USE biamodas;

-- =========================================================
-- Dados de demonstracao do catalogo (execute apos o seed principal).
-- Script idempotente: pode ser executado mais de uma vez.
-- =========================================================

SET NAMES utf8mb4;

INSERT INTO categorias (nome, descricao)
SELECT 'Vestidos', 'Vestidos e macacoes' WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE nome = 'Vestidos');
INSERT INTO categorias (nome, descricao)
SELECT 'Blusas', 'Blusas e camisetas' WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE nome = 'Blusas');
INSERT INTO categorias (nome, descricao)
SELECT 'Plus Size', 'Pecas plus size' WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE nome = 'Plus Size');
INSERT INTO categorias (nome, descricao)
SELECT 'Moda Praia', 'Biquinis, macaos e saidas de praia' WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE nome = 'Moda Praia');
INSERT INTO categorias (nome, descricao)
SELECT 'Fitness', 'Roupas para treino' WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE nome = 'Fitness');

INSERT INTO ofertas (percentual, inicio_em, fim_em, ativo)
SELECT 0.15, NOW(), NULL, TRUE WHERE NOT EXISTS (SELECT 1 FROM ofertas WHERE percentual = 0.15);

-- Produtos (SKU base; os tamanhos ficam em produto_tamanhos)
INSERT INTO produtos (oferta_id, nome, sku, descricao, cor, estilo, estoque, valor, ativo)
SELECT o.id_oferta, 'Vestido Verde Plus Size', 'DEMO-001',
       'Vestido midi canelado com caimento fluido.', 'Verde', 'Elegante', 12, 199.90, TRUE
FROM (SELECT id_oferta FROM ofertas WHERE percentual = 0.15 AND ativo = TRUE ORDER BY id_oferta LIMIT 1) o
WHERE NOT EXISTS (SELECT 1 FROM produtos WHERE sku = 'DEMO-001');

INSERT INTO produtos (oferta_id, nome, sku, descricao, cor, estilo, estoque, valor, ativo)
SELECT NULL, 'Blusa Plus Size Preta', 'DEMO-002',
       'Blusa de manga longa em malha macia.', 'Preto', 'Casual', 20, 89.90, TRUE
WHERE NOT EXISTS (SELECT 1 FROM produtos WHERE sku = 'DEMO-002');

INSERT INTO produtos (oferta_id, nome, sku, descricao, cor, estilo, estoque, valor, ativo)
SELECT NULL, 'Blusa Plus Size Vermelha', 'DEMO-003',
       'Blusa de manga longa em malha macia.', 'Vermelho', 'Casual', 15, 89.90, TRUE
WHERE NOT EXISTS (SELECT 1 FROM produtos WHERE sku = 'DEMO-003');

INSERT INTO produtos (oferta_id, nome, sku, descricao, cor, estilo, estoque, valor, ativo)
SELECT o.id_oferta, 'Blusa Plus Size Azul', 'DEMO-004',
       'Blusa de manga longa em malha macia.', 'Azul', 'Casual', 18, 99.90, TRUE
FROM (SELECT id_oferta FROM ofertas WHERE percentual = 0.15 AND ativo = TRUE ORDER BY id_oferta LIMIT 1) o
WHERE NOT EXISTS (SELECT 1 FROM produtos WHERE sku = 'DEMO-004');

INSERT INTO produtos (oferta_id, nome, sku, descricao, cor, estilo, estoque, valor, ativo)
SELECT NULL, 'Regata Rosa Plus Size', 'DEMO-005',
       'Regata leve para os dias quentes.', 'Rosa', 'Casual', 25, 69.90, TRUE
WHERE NOT EXISTS (SELECT 1 FROM produtos WHERE sku = 'DEMO-005');

-- Imagens
INSERT INTO imagens (produto_id, caminho_imagem)
SELECT p.id_produto, v.caminho
FROM (
    SELECT 'DEMO-001' AS sku, '/img/VestidoVerdePlusSize.png' AS caminho
    UNION ALL SELECT 'DEMO-002', '/img/BlusaPlusSizePreta.png'
    UNION ALL SELECT 'DEMO-003', '/img/BlusaPlusSizeVermelho.png'
    UNION ALL SELECT 'DEMO-004', '/img/BlusaPlusSizeAzul.webp'
    UNION ALL SELECT 'DEMO-005', '/img/RegataRosaPlusSize.webp'
) v
INNER JOIN produtos p ON p.sku = v.sku
LEFT JOIN imagens i ON i.produto_id = p.id_produto
WHERE i.id_imagem IS NULL;

-- Categorias
INSERT INTO categorias_produtos (produto_id, categoria_id)
SELECT p.id_produto, c.id_categoria
FROM (
    SELECT 'DEMO-001' AS sku, 'Vestidos' AS categoria
    UNION ALL SELECT 'DEMO-002', 'Blusas'
    UNION ALL SELECT 'DEMO-003', 'Blusas'
    UNION ALL SELECT 'DEMO-004', 'Blusas'
    UNION ALL SELECT 'DEMO-005', 'Plus Size'
) v
INNER JOIN produtos p ON p.sku = v.sku
INNER JOIN categorias c ON c.nome = v.categoria
LEFT JOIN categorias_produtos cp ON cp.produto_id = p.id_produto AND cp.categoria_id = c.id_categoria
WHERE cp.id_categoria_produto IS NULL;

-- Variacoes de tamanho dos produtos de demonstracao
INSERT INTO produto_tamanhos (produto_id, tamanho_id, estoque)
SELECT p.id_produto, t.id_tamanho, v.estoque
FROM (
    SELECT 'DEMO-001' AS sku, 'P' AS tamanho, 3 AS estoque
    UNION ALL SELECT 'DEMO-001', 'M', 3
    UNION ALL SELECT 'DEMO-001', 'G', 3
    UNION ALL SELECT 'DEMO-001', 'GG', 3
    UNION ALL SELECT 'DEMO-002', 'P', 5
    UNION ALL SELECT 'DEMO-002', 'M', 5
    UNION ALL SELECT 'DEMO-002', 'G', 5
    UNION ALL SELECT 'DEMO-002', 'GG', 5
    UNION ALL SELECT 'DEMO-003', 'P', 5
    UNION ALL SELECT 'DEMO-003', 'M', 5
    UNION ALL SELECT 'DEMO-003', 'G', 5
    UNION ALL SELECT 'DEMO-004', 'P', 4
    UNION ALL SELECT 'DEMO-004', 'M', 5
    UNION ALL SELECT 'DEMO-004', 'G', 5
    UNION ALL SELECT 'DEMO-004', 'GG', 4
    UNION ALL SELECT 'DEMO-005', 'PP', 8
    UNION ALL SELECT 'DEMO-005', 'P', 9
    UNION ALL SELECT 'DEMO-005', 'M', 8
) v
INNER JOIN produtos p ON p.sku = v.sku
INNER JOIN tamanhos t ON t.nome = v.tamanho
ON DUPLICATE KEY UPDATE estoque = VALUES(estoque), deletado_em = NULL;

UPDATE produtos p
SET p.estoque = (
    SELECT COALESCE(SUM(pt.estoque), 0)
    FROM produto_tamanhos pt
    WHERE pt.produto_id = p.id_produto
      AND pt.deletado_em IS NULL
)
WHERE p.sku IN ('DEMO-001', 'DEMO-002', 'DEMO-003', 'DEMO-004', 'DEMO-005');
