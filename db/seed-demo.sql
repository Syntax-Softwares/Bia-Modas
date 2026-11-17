USE biamodas;

INSERT INTO medidas_produtos (tamanho, busto, cintura, quadril)
SELECT 'P', 90.0, 70.0, 96.0 WHERE NOT EXISTS (SELECT 1 FROM medidas_produtos WHERE tamanho = 'P');
INSERT INTO medidas_produtos (tamanho, busto, cintura, quadril)
SELECT 'M', 96.0, 76.0, 102.0 WHERE NOT EXISTS (SELECT 1 FROM medidas_produtos WHERE tamanho = 'M');
INSERT INTO medidas_produtos (tamanho, busto, cintura, quadril)
SELECT 'G', 102.0, 82.0, 108.0 WHERE NOT EXISTS (SELECT 1 FROM medidas_produtos WHERE tamanho = 'G');
INSERT INTO medidas_produtos (tamanho, busto, cintura, quadril)
SELECT 'GG', 108.0, 88.0, 114.0 WHERE NOT EXISTS (SELECT 1 FROM medidas_produtos WHERE tamanho = 'GG');

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

INSERT INTO produtos (medida_produto_id, oferta_id, nome, sku, descricao, estoque, valor, ativo)
SELECT m.id_medida_produto, o.id_oferta, 'Vestido Verde Plus Size', 'DEMO-001',
       'Vestido midi canelado com caimento fluido.', 12, 199.90, TRUE
FROM medidas_produtos m
LEFT JOIN ofertas o ON o.percentual = 0.15 AND o.ativo = TRUE
WHERE m.tamanho = 'G'
  AND NOT EXISTS (SELECT 1 FROM produtos WHERE sku = 'DEMO-001');

INSERT INTO produtos (medida_produto_id, oferta_id, nome, sku, descricao, estoque, valor, ativo)
SELECT m.id_medida_produto, NULL, 'Blusa Plus Size Preta', 'DEMO-002',
       'Blusa de manga longa em malha macia.', 20, 89.90, TRUE
FROM medidas_produtos m
WHERE m.tamanho = 'G'
  AND NOT EXISTS (SELECT 1 FROM produtos WHERE sku = 'DEMO-002');

INSERT INTO produtos (medida_produto_id, oferta_id, nome, sku, descricao, estoque, valor, ativo)
SELECT m.id_medida_produto, NULL, 'Blusa Plus Size Vermelha', 'DEMO-003',
       'Blusa de manga longa em malha macia.', 15, 89.90, TRUE
FROM medidas_produtos m
WHERE m.tamanho = 'G'
  AND NOT EXISTS (SELECT 1 FROM produtos WHERE sku = 'DEMO-003');

INSERT INTO produtos (medida_produto_id, oferta_id, nome, sku, descricao, estoque, valor, ativo)
SELECT m.id_medida_produto, o.id_oferta, 'Blusa Plus Size Azul', 'DEMO-004',
       'Blusa de manga longa em malha macia.', 18, 99.90, TRUE
FROM medidas_produtos m
LEFT JOIN ofertas o ON o.percentual = 0.15 AND o.ativo = TRUE
WHERE m.tamanho = 'M'
  AND NOT EXISTS (SELECT 1 FROM produtos WHERE sku = 'DEMO-004');

INSERT INTO produtos (medida_produto_id, oferta_id, nome, sku, descricao, estoque, valor, ativo)
SELECT m.id_medida_produto, NULL, 'Regata Rosa Plus Size', 'DEMO-005',
       'Regata leve para os dias quentes.', 25, 69.90, TRUE
FROM medidas_produtos m
WHERE m.tamanho = 'P'
  AND NOT EXISTS (SELECT 1 FROM produtos WHERE sku = 'DEMO-005');

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
