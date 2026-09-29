-- =====================================================================
--  Bia Modas - Dados iniciais (opcional, mas recomendado)
--  Execute APÓS biamodas_schema.sql
-- =====================================================================
USE biamodas;

-- ---------------------------------------------------------------------
-- Cores (valores usados no catálogo atual + os que o código referencia)
-- ---------------------------------------------------------------------
INSERT INTO Cor (nome, hex) VALUES
    ('Preto',     '#212529'),
    ('Branco',    '#f8f9fa'),
    ('Vermelho',  '#dc3545'),
    ('Azul',      '#0d6efd'),
    ('Verde',     '#198754'),
    ('Rosa',      '#d63384'),
    ('Estampado', '#fd7e14'),
    ('Neutro',    '#6c757d'),
    ('Bege',      '#e8dcc5'),
    ('Jeans',     '#5b7fa3'),
    ('Marrom',    '#8b4513')
ON DUPLICATE KEY UPDATE hex = VALUES(hex);

-- ---------------------------------------------------------------------
-- Categorias (as mesmas exibidas em index.html)
-- ---------------------------------------------------------------------
INSERT INTO Categoria (nome, descricao) VALUES
    ('Vestidos',   'Vestidos femininos'),
    ('Blusas',     'Blusas, camisas e body'),
    ('Calças',     'Calças e pantalonas'),
    ('Saias',      'Saias'),
    ('Shorts',     'Shorts e bermudas'),
    ('Conjuntos',  'Conjuntos e blazers'),
    ('Casacos',    'Casacos e jaquetas'),
    ('Plus Size',  'Moda plus size'),
    ('Moda Praia', 'Moda praia'),
    ('Moda Íntima','Moda íntima'),
    ('Fitness',    'Moda fitness'),
    ('Infantil',   'Moda infantil'),
    ('Acessórios', 'Acessórios'),
    ('Sapatos',    'Calçados')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);

-- ---------------------------------------------------------------------
-- Usuário administrador inicial (RF10/RF11/RNF12)
-- A senha deve ser gravada como hash bcrypt/argon2 gerado pela aplicação.
-- Substitua o hash abaixo pelo real antes de usar em produção.
-- ---------------------------------------------------------------------
-- INSERT INTO Usuario (nome, cpf, email, senha_hash, data_nascimento, telefone, tipo)
-- VALUES ('Administrador Bia Modas', '00000000000', 'admin@biamodas.com.br',
--         '$2y$10$SUBSTITUA_POR_HASH_BCRYPT_REAL', '1990-01-01', '(14) 99999-9999', 'ADMIN');

-- =====================================================================
-- FIM DOS DADOS INICIAIS
-- =====================================================================
