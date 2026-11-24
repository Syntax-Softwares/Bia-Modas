# MVC07 — Catálogo, Busca e Filtros

**Documento de planejamento e integração**
Branch: `implementar-catalogo-busca-e-filtros`
Base: `integracao_backend` @ `b0e8420`
Status: implementação concluída e testada; **sem commit** (o responsável fará o commit e o merge).
Público-alvo: agente/desenvolvedor que fará a integração final com as demais branches.

---

## 1. Objetivo e escopo

Implementar o card **MVC07 — Implementar catálogo, busca e filtros**, cobrindo RF04, RF06 e RF09 e a checklist:

1. Implementar listagem e detalhes de produtos.
2. Integrar fotos, cores, tamanhos e estoque.
3. Implementar busca e filtros por categoria e preço.
4. Implementar sugestões de pesquisa.
5. Definir e implementar ordenação por relevância.
6. Validar parâmetros e tratar resultados vazios.

**Fora do escopo (não implementar aqui):**

| Item | Responsável |
|---|---|
| Login/cadastro/sessões (MVC05) | Gabriel |
| Favoritos e carrinho (MVC08) | Thiago |
| Checkout, pedidos, pagamentos e cupons (MVC09) | Thiago |
| Provador virtual e medidas do usuário (MVC10) | Thiago |
| Página inicial / MVC04 | Gabriel |
| Avaliações de produtos (RF13) | — |

> **Carrinho e favoritos não fazem parte desta branch.** O ícone do carrinho no header continua como link para `/carrinho`, que será implementado no MVC08. O detalhe do produto usa **WhatsApp** como CTA de catálogo.

A home (`views/index.php`) **não foi alterada**. O header aponta para `/perfil` (MVC06) e `/favoritos`/`/carrinho` (MVC08).

---

## 2. Decisões tomadas (validadas com o solicitante)

| Tema | Decisão |
|---|---|
| Cores e estilos | **Adicionar `cor` e `estilo` em `produtos`** (nullable + índices), com fallback para a descrição do seed |
| Sugestões de pesquisa | `<datalist>` nativo com nomes de produtos e categorias (sem JS) |
| Paginação | 12 produtos por página, preservando filtros |
| Relevância | Score completo: nome exato > prefixo > contém > categoria > cor/estilo > descrição; desempate por novidade |
| Página inicial | Não alterar (`views/index.php` é MVC04) |
| Fotos | Galeria com `?foto=ID` (server-side, sem JS) |
| Carrinho/favoritos/checkout | **Não implementados** (MVC08/MVC09) |

---

## 3. Alterações de schema (`db.sql`)

Aditivas e compatíveis com o código existente:

```sql
-- produtos
cor VARCHAR(30) NULL,
estilo VARCHAR(30) NULL,
INDEX produtos_cor_idx (cor),
INDEX produtos_estilo_idx (estilo),
INDEX produtos_valor_idx (valor),
INDEX produtos_ativo_idx (ativo, deletado_em),

-- medidas_produtos
INDEX medidas_produtos_tamanho_idx (tamanho)
```

### Migração para banco já existente

```sql
ALTER TABLE produtos
  ADD COLUMN cor VARCHAR(30) NULL AFTER descricao,
  ADD COLUMN estilo VARCHAR(30) NULL AFTER cor,
  ADD INDEX produtos_cor_idx (cor),
  ADD INDEX produtos_estilo_idx (estilo),
  ADD INDEX produtos_valor_idx (valor),
  ADD INDEX produtos_ativo_idx (ativo, deletado_em);

ALTER TABLE medidas_produtos
  ADD INDEX medidas_produtos_tamanho_idx (tamanho);
```

### Backfill do seed existente

O `seed_db.sql` do MVC04 **não** preenche `cor`/`estilo`; ele grava ambos na descrição (`"Estilo: Elegante. Cor: Marrom."`). O repositório faz fallback pela descrição, mas o ideal é rodar:

```sql
UPDATE produtos
SET cor = NULLIF(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(descricao, 'Cor: ', -1), '.', 1)), ''),
    estilo = NULLIF(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(descricao, 'Estilo: ', -1), '.', 1)), '')
WHERE descricao IS NOT NULL AND descricao LIKE '%Cor: %';
```

Ou atualizar o `seed_db.sql` para inserir as colunas. **Novos produtos devem preencher `cor`/`estilo`.**

---

## 4. Rotas implementadas

| Método | Rota | Ação | Descrição |
|---|---|---|---|
| GET | `/categoria` | `CatalogoController::index` | Catálogo, busca, filtros, ordenação e paginação |
| GET | `/produto` | `CatalogoController::produto` | Detalhe (`id`, `foto`) |

### Parâmetros de `/categoria`

`busca` (máx. 100), `categoria`, `tamanhos[]`, `cores[]`, `estilos[]`, `preco_min`, `preco_max`, `promocoes=1`, `ordenacao` (`relevancia`, `menor-preco`, `maior-preco`, `nome`), `pagina`.

Regras de validação:

- arrays de tamanho/cor/estilo são filtrados contra os valores existentes no banco;
- `ordenacao` fora da whitelist volta para `relevancia`;
- `pagina` inválida vira 1 e é limitada ao total de páginas;
- preços inválidos/negativos são ignorados; `min > max` é invertido;
- `id`/`foto` inválidos retornam 404;
- busca vazia/inexistente retorna estado vazio com sugestões de categorias.

---

## 5. Arquivos relevantes

| Arquivo | Papel |
|---|---|
| `controllers/catalogo_controller.php` | Busca, filtros, ordenação, paginação e detalhe |
| `repositories/catalogo_repository.php` | SQL do catálogo, relevância, facetas e sugestões |
| `core/catalogo.php` | Preço, desconto, cores, URLs com filtros |
| `core/sessao.php` | Sessão e headers de segurança (mesma camada do MVC06) |
| `core/html.php` | Escape de saída |
| `core/rate_limit.php` | Rate limit de GET por IP |
| `views/catalogo.php` | Catálogo com sidebar de filtros (`<details>`) |
| `views/produto_detalhe.php` | Galeria, preço, cor, tamanho, medidas, estoque e WhatsApp |
| `views/partials/catalogo_*.php` | Header, footer, alerts e card |
| `views/erro.php` | 403/404/429/500 sem vazar detalhes |
| `public/css/catalogo.css` | Layout, filtros, paginação e detalhe |
| `db.sql` | Colunas `cor`/`estilo` + índices |

**Infra copiada do MVC06 (mesmo conteúdo, merge trivial):** `core/database.php`, `core/html.php`, `core/sessao.php`, `core/rate_limit.php`, `apache.conf`, `php-security.ini`, `Dockerfile`, `docker-compose.yml`, `.gitignore`, `.dockerignore`, `views/erro.php`.

**Modificados:** `public/index.php` (rotas + dispatch com `parse_url`), `db.sql`.

---

## 6. Catálogo: regras implementadas

- Somente `p.ativo = TRUE` e `p.deletado_em IS NULL`.
- Imagem principal: primeira de `imagens`; fallback `/img/novidades.png`.
- Categoria principal: primeira de `categorias_produtos`; fallback `Bia Modas`.
- Oferta: `ofertas` ativa, dentro da vigência; preço original calculado por `valor / (1 - percentual)`.
- Tamanho: `medidas_produtos.tamanho` (um por produto no schema atual).
- Cor/estilo: coluna `produtos.cor`/`estilo`; se vazia, extrai da descrição no padrão do seed.
- Busca: nome, descrição, cor, estilo e categoria; `LIKE` com `%`/`_` escapados.
- Relevância:
  - 100 nome exato;
  - 80 nome começa com;
  - 60 nome contém;
  - 40 categoria;
  - 30 cor/estilo;
  - 20 descrição;
  - desempate por `criado_em DESC`.
- Ordenações: relevância, menor preço, maior preço, nome A-Z.
- Paginação: 12 por página; links preservam busca/filtros/ordenação.
- Facetas: categorias com contagem, tamanhos, cores, estilos e faixas de preço fixas (Até 100, 100–150, 150–200, Acima de 200).
- Sugestões: `<datalist>` com até 30 nomes de produtos + categorias.
- Estado vazio: mensagem, link para limpar filtros e sugestões de categorias.

> **Não** há `FULLTEXT`; a busca é `LIKE`. Para catálogos grandes, avaliar índice FULLTEXT ou motor externo.

---

## 7. Detalhe do produto

- Galeria server-side com miniaturas (`?foto=ID`); foto inválida cai na primeira.
- Preço atual, preço original e economia.
- Cor (swatch) e tamanho (badge) **estáticos** — o schema tem uma cor/tamanho por produto.
- Medidas da peça (`busto`, `cintura`, `quadril`) quando existirem.
- Estoque: disponível, últimas unidades (≤5) ou esgotado.
- CTA: **"Comprar pelo WhatsApp"** quando há estoque; botão desabilitado quando esgotado.
- **Não** inclui carrinho, favoritos, provador virtual, avaliações ou parcelamento real.

---

## 8. Segurança

| Vetor | Medida |
|---|---|
| SQL injection | PDO prepared statements + `ATTR_EMULATE_PREPARES = false`; `LIKE` com escape |
| XSS | `Html::e()` em toda saída; CSP; sem JS inline |
| Flood/DDoS | 180 GET/min por IP; `429` + `Retry-After` |
| Sessão | `use_strict_mode`, `HttpOnly`, `SameSite=Lax`, `Secure` sob HTTPS, idle 30 min/absoluto 8h |
| Cabeçalhos | CSP, `X-Frame-Options: DENY`, `nosniff`, `no-referrer`, `Permissions-Policy`, `Cache-Control: no-store` |
| PHP | `display_errors=Off`, `expose_php=Off`, logs no servidor |
| Banco | App usa usuário `syntax` (não-root); porta 3306 só em `127.0.0.1` |
| Erros | Nenhum stack trace/SQLSTATE/dado sensível no HTML |

Esta branch é **somente leitura por HTTP** (GET). Não há POST/CSRF no catálogo; o CSRF segue disponível em `core/sessao.php` para as branches que têm formulários.

**Limite conhecido:** rate limit de aplicação não substitui WAF/proxy para DDoS volumétrico. Atrás de proxy, configurar `REMOTE_ADDR` corretamente (não confiar em `X-Forwarded-For` sem validação).

---

## 9. Integração com as demais branches

### MVC05 (Auth/sessão) — **obrigatório**

- A `Sessao` desta branch usa a mesma chave do `Auth`:
  - `$_SESSION['usuario']['id_usuario']`;
  - `$_SESSION['csrf_token']`;
  - `$_SESSION['flash']` (`type`/`message`);
  - cookie `BIAMODAS_SESSID`.
- Trocar `Sessao` por `Auth` (ou delegar) sem mudar controllers/views.
- O header usa `Sessao::usuario()` apenas para exibir a inicial do usuário no ícone da conta.

### MVC08 (Favoritos/carrinho)

- O header aponta para `/favoritos` e `/carrinho`; ambos **não existem** nesta branch.
- O MVC08 deve implementar as rotas e a UI; este branch não tem controller/repository/view de carrinho.
- O botão do detalhe é WhatsApp, não "adicionar ao carrinho"; o MVC08 pode adicionar o botão e o drawer.

### MVC09 (Checkout/pedidos/pagamentos)

- Nada de checkout/pagamento foi implementado.
- O MVC09 pode reaproveitar o card/detalhe e criar o fluxo de compra a partir do botão que o MVC08 definir.

### MVC04 (Home)

- `views/index.php` não foi alterado.
- O card `views/partials/catalogo_product_card.php` pode ser reaproveitado na home.
- O header/footer do catálogo são partials próprios; unificar com `site_header.php`/`footer.php` do MVC05.

### MVC06 (Perfil)

- O header aponta para `/perfil`.
- `views/erro.php`, `core/sessao.php`, `core/html.php`, `core/rate_limit.php`, `core/database.php` e a infra Docker são **idênticos** aos do MVC06; o merge deve escolher uma versão.

### Conflitos previsíveis

- `db.sql` (colunas novas) — aplicar a migração.
- `public/index.php` (rotas) — preservar todas as rotas.
- `core/database.php`, `apache.conf`, `Dockerfile`, `docker-compose.yml`, `.gitignore`, `.dockerignore` — iguais ao MVC06.
- `views/erro.php` — igual ao MVC06.

---

## 10. Roteiro de integração (passo a passo)

1. **Atualizar** `integracao_backend` e as branches MVC05/MVC06.
2. **Aplicar a migração** de `cor`/`estilo` e índices (ou recriar o volume com o `db.sql` novo).
3. **Rodar o backfill** de cor/estilo (ou atualizar o `seed_db.sql`).
4. **Merge do MVC05** primeiro (Auth/sessão), resolvendo `core/sessao.php`/`core/auth.php` e `public/index.php`.
5. **Merge do MVC06** (perfil), unificando `Sessao`, `Html`, `RateLimit`, `views/erro.php`, header/footer e infra.
6. **Unificar header/footer** com o site (MVC04/05).
7. **Deixar `/carrinho` e `/favoritos` para o MVC08** (o header já aponta para elas).
8. **Rodar lint e testes** (seção 11).
9. **Validar** que a home, o perfil e o catálogo coexistem.
10. **Merge final** em `integracao_backend` (`--no-ff`) e push. **Não** tocar na `main`.

---

## 11. Testes executados

Ambiente: Docker Compose (PHP 8.4 + Apache + MariaDB 11), schema novo, seed do MVC04 (60 produtos, 60 imagens) + segunda foto no produto 1.

- **Catálogo:** 12 cards/página, 5 páginas, contagem total 60, datalist, facetas, paginação.
- **Busca:** `vestido` → 5; categoria `vestidos` → 5; cor `marrom` → 16; termo inexistente → estado vazio + sugestões; primeiro resultado relevante.
- **Filtros:** categoria, preço 100–150, cor Preto, estilo Elegante (26), promoção (12/12 em oferta), tamanho P; combinações e chips.
- **Ordenação:** menor preço, maior preço e nome A-Z.
- **Parâmetros inválidos:** página negativa, ordenação inválida, preço `abc`, cor/tamanho inexistentes → 200 e padrão.
- **Detalhe:** 2 fotos, troca de foto por `?foto=`, cor, tamanho, SKU, CTA WhatsApp, 404 para inexistente/id inválido.
- **Segurança:** SQLi na busca/id, XSS na busca, ausência de warnings, CSP/headers, rate limit GET (429 + `Retry-After`).
- **Lint:** `php -l` em todos os arquivos, sem erros.

> O script de teste ficou fora do repositório (pasta temporária). Os dados de teste também: `storage/seed_db.sql` é ignorado pelo Git.

---

## 12. Critérios de aceite da integração

- [ ] `/categoria` lista produtos ativos com fotos, preço, cor, tamanho e estoque.
- [ ] Busca, categoria, preço, cor, estilo, tamanho e promoção funcionam combinados.
- [ ] Relevância, menor/maior preço e nome A-Z ordenam corretamente.
- [ ] Paginação preserva os filtros.
- [ ] Sugestões aparecem no input de busca.
- [ ] Estado vazio é claro e oferece saída.
- [ ] Detalhe mostra galeria, preço/oferta, cor, tamanho, medidas e estoque.
- [ ] CTA de WhatsApp funciona; produto esgotado fica indisponível.
- [ ] CSP, headers e rate limit ativos; prepared statements em todas as consultas.
- [ ] Nenhum warning/stack trace/dado sensível no HTML.
- [ ] Header aponta para `/carrinho` e `/favoritos` (a implementar no MVC08).
- [ ] Home, perfil e catálogo coexistem após o merge.
- [ ] `main` intacta.

---

## 13. Referências

- Card MVC07 (imagem enviada): RF04, RF06, RF09 e checklist.
- `README.md` (main): RF04, RF06, RF09, RF13.
- Branch `origin/Gabriel`: `seed_db.sql`, `ProdutoRepository`, views de catálogo/produto (MVC04).
- Branch `apresentacao-pi`: `categoria.html`, `produto.html`, `js/catalogo.js`, `js/categoria-filtros.js`, `js/produto.js`.
- `db.sql` @ `integracao_backend` (`e4ad840`/`b0e8420`) e migração desta branch.
