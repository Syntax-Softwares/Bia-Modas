# Bia Modas

Loja de moda feminina de Jaú (SP) — aplicação PHP 8.4 com Apache e MariaDB, sem
framework: router próprio, controllers, repositories com PDO e views em PHP.

## Como rodar

```bash
docker compose up -d --build
```

- Loja: <http://localhost:8080>
- Painel do e-mail de teste (Mailpit): <http://localhost:8025>

Na primeira subida o MariaDB executa `db.sql` (schema) e `seed_db.sql` (catálogo
e dados de demonstração). O front controller exige as dependências do Composer:

```bash
docker compose run --rm composer
```

## Banco de dados

Os dois scripts são **idempotentes**: podem ser executados quantas vezes forem
necessárias, sem apagar dados.

Para atualizar um banco que já existe (sem recriar o volume):

```bash
docker compose exec -T db mariadb -uroot -proot < db.sql
docker compose exec -T db mariadb -uroot -proot < seed_db.sql
```

O `db.sql` não tem mais `DROP DATABASE`. Para recriar tudo do zero:

```bash
docker compose down -v && docker compose up -d --build
```

> Atenção: `docker-entrypoint-initdb.d` só roda com o volume vazio. Em um banco
> já criado, as tabelas novas aparecem apenas se você executar os comandos acima.

## Contas de demonstração

Criadas pelo `seed_db.sql`, todas com a senha **`demo123`**:

| E-mail | Uso |
|---|---|
| `demo@biamodas.test` | Conta principal: já tem 8 favoritos e 1 avaliação própria |
| `carla@biamodas.test` | Autora das avaliações de demonstração |
| `marina@biamodas.test` | Autora das avaliações de demonstração |

## Funcionalidades

### Avaliações

- Nota de 1 a 5 estrelas e comentário opcional (até 500 caracteres).
- Uma avaliação por usuário por produto (`UNIQUE(usuario_id, produto_id)`),
  sempre com login.
- Publicação direta, sem moderação. O autor pode **editar** e **remover** a
  própria avaliação; a remoção é soft delete (`deletado_em`) e um novo envio
  reaproveita a mesma linha.
- A média e a contagem aparecem ao lado do nome do produto e na seção
  `#avaliacoes`, que lista as mais recentes primeiro.

### Favoritos

- Só para usuários logados. O coração aparece nos cards do catálogo e na página
  do produto; a troca é feita via `fetch` (`POST /favoritos/alternar`) com CSRF
  no header `X-CSRF-Token`, sem recarregar a página.
- `/favoritos` lista as peças salvas, na ordem em que foram favoritadas.

### Recomendações

- **Para Você** (home): usa as 3 peças favoritadas mais recentes, pega as
  categorias delas via `categorias_produtos` (N:N), exclui o que já está
  favoritado e ordena por oferta ativa e proximidade de preço com a média dos
  favoritos. Sem favoritos, mostra **Mais desejados** (produtos mais
  favoritados da loja); sem nenhum favorito no banco, cai para as novidades.
- **Você também pode gostar** (página do produto): produtos que compartilham
  alguma categoria com o atual, oferta primeiro e preço próximo. Se sobrarem
  menos de 4, completa com produtos na mesma faixa de preço.

O seed desativa produtos de nome repetido (`ativo = FALSE`), mantendo o de menor
id, para a recomendação não sugerir a mesma peça várias vezes.

## Estrutura

```
core/           router, sessão/auth, CSRF, helpers, e-mail e conexão PDO
controllers/    home, categoria, produto, favoritos, avaliação, auth e recuperação de senha
repositories/   SQL das entidades (produto, favorito, avaliação, usuário, códigos)
views/          templates PHP + partials (header, footer, card de produto, alertas)
public/         document root, CSS e JS
db.sql          schema idempotente
seed_db.sql     catálogo e dados de demonstração idempotentes
```
