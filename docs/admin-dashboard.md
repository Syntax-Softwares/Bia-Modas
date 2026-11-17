# Dashboard administrativa — mapeamento e decisões

Documento de apoio do Projeto Integrador (2º semestre). Descreve como as tabelas
do banco foram comparadas com as telas administrativas, o que entrou na v1 e o
que ficou fora de escopo.

## 1. Mapeamento tabela → tela

| Tabela | Campos usados no admin | Tela / uso | v1 |
| --- | --- | --- | --- |
| `usuarios` | `id_usuario`, `nome`, `email`, `senha_hash`, `telefone`, `cpf`, `is_admin`, `deletado_em` | CRUD de usuários, promoção/rebaixamento, redefinição de senha, contadores do painel | Sim |
| `produtos` | `id_produto`, `nome`, `sku`, `descricao`, `valor`, `estoque`, `ativo`, `medida_produto_id`, `oferta_id`, `deletado_em` | CRUD de produtos + tela de estoque | Sim |
| `categorias` | `id_categoria`, `nome`, `descricao`, `categoria_pai_id`, `deletado_em` | CRUD de categorias (com pai opcional) | Sim |
| `categorias_produtos` | `produto_id`, `categoria_id`, `principal`, `deletado_em` | Seleção múltipla de categorias no produto + categoria principal | Sim |
| `medidas_produtos` | `id_medida_produto`, `tamanho`, `busto`, `cintura`, `quadril` | CRUD de tamanhos/medidas + select no produto | Sim |
| `imagens` | `produto_id`, `caminho_imagem`, `deletado_em` | Upload da imagem principal do produto | Sim |
| `ofertas` | `id_oferta`, `percentual`, `inicio_em`, `fim_em`, `ativo`, `deletado_em` | CRUD de ofertas + select no produto | Sim |
| `cupons` | `id_cupom`, `codigo`, `val_min`, `val_max`, `desconto`, `frete_gratis`, `deletado_em` | CRUD de cupons | Sim |
| `medidas` | medidas corporais do usuário | Ficam no cadastro do cliente (`/registro`, `/conta`) | Não |
| `enderecos` | endereço do usuário | Ficam no cadastro do cliente | Não |
| `carrinhos`, `itens_carrinho` | carrinho | Carrinho é finalizado no WhatsApp | Não |
| `compras`, `itens_compra` | pedidos | Sem checkout implementado; futuras métricas de venda | Não |
| `codigos_recuperacao_senha` | códigos de recuperação | Fluxo público de recuperação de senha | Não |

> Tabelas fora da v1 continuam existindo no schema; apenas não têm tela própria.

## 2. Decisões tomadas

- **Autorização:** papel único via `usuarios.is_admin`. O guard (`AdminController`)
  relê o usuário no banco a cada requisição `/admin`; esconder o menu não é
  autorização. Deslogado → `/login`; logado sem permissão → `403`.
- **Rotas:** `/admin` + recursos em português no plural
  (`/admin/produtos`, `/admin/categorias`, ...). O Router aceita `{id}` e
  `_method=PUT|DELETE` em POST (HTML só envia GET/POST).
- **Exclusão:** sempre soft delete (`deletado_em`), com confirmação no navegador;
  sem tela de lixeira na v1. O status `ativo` serve para despublicar.
- **Unicidade:**
  - SKU: validado **incluindo produtos excluídos**, porque o `UNIQUE` do banco
    também os considera.
  - E-mail de usuário: ao excluir, o e-mail é reescrito para
    `deleted+{id}+{email}` para liberar o endereço original.
  - Nome de categoria e código de cupom: ao excluir, recebem sufixo
    (`(excluída #id)` / `#del{id}`) para liberar o `UNIQUE`.
- **Produto x categorias:** seleção múltipla, com exatamente **uma principal**
  (coluna `principal` em `categorias_produtos`). A vitrine prioriza a principal.
- **Variações:** cada produto continua sendo um SKU com um tamanho
  (`medida_produto_id`); não foi criada tabela de agrupamento de variações.
- **Descontos:** `ofertas` (percentual por produto, com vigência) e `cupons`
  (código + faixa de valor + frete grátis). O banco guarda fração (`0.15`); os
  formulários usam percentual inteiro (`15`), pois `DECIMAL(5,2)` só representa
  duas casas decimais.
- **Estoque:** edição absoluta por produto com validação `0..99999`; destaque
  para `<= 5`. Sem histórico de movimentações na v1.
- **Validação:** mesmo padrão do `AuthController` — validação no controller,
  `Auth::setOld`/`setErrors`, flash global, POST-redirect-GET e CSRF em todo POST.
- **Layout:** layout próprio (`views/admin/layout.php`) com sidebar, topbar e
  `public/css/admin.css`, responsivo.

## 3. Rotas

| Método | Rota | Ação |
| --- | --- | --- |
| GET | `/admin` | Painel com contadores e últimos cadastros |
| GET | `/admin/produtos` | Lista com busca, filtros e paginação |
| GET | `/admin/produtos/novo` | Formulário de criação |
| GET | `/admin/produtos/{id}/editar` | Formulário de edição |
| POST | `/admin/produtos` | Cria produto |
| POST | `/admin/produtos/{id}` + `_method=PUT` | Atualiza produto |
| POST | `/admin/produtos/{id}` + `_method=DELETE` | Exclui (soft delete) |
| GET/POST | `/admin/categorias...` | Mesmo padrão de CRUD |
| GET/POST | `/admin/tamanhos...` | Mesmo padrão de CRUD |
| GET/POST | `/admin/usuarios...` | Mesmo padrão de CRUD |
| GET/POST | `/admin/ofertas...` | Mesmo padrão de CRUD |
| GET/POST | `/admin/cupons...` | Mesmo padrão de CRUD |
| GET | `/admin/estoque` | Lista com filtro de estoque baixo |
| POST | `/admin/estoque/{id}` + `_method=PUT` | Define estoque absoluto |

## 4. Regras de validação principais

- **Produto:** nome 3–255; SKU em maiúsculas (`A-Z`, `0-9`, `.`, `_`, `-`, até
  50) e único; valor > 0; estoque 0–99999; tamanho existente; oferta existente
  (opcional); ≥ 1 categoria e principal entre as selecionadas; imagem JPG/PNG/WEBP
  até 2 MB validada por MIME real (`finfo`).
- **Categoria:** nome 2–255 e único; pai ≠ ela mesma; sem ciclos; exclusão
  bloqueada com produtos ou subcategorias vinculadas.
- **Tamanho:** descrição 1–5 caracteres e única entre ativos; medidas opcionais
  `> 0` e `<= 300`; exclusão bloqueada se usado por produto.
- **Usuário:** nome 3–255; e-mail válido e único; telefone 10–11 dígitos se
  informado; CPF 11 dígitos se informado e único; senha ≥ 6 (obrigatória na
  criação, opcional na edição); bloqueio de autoexclusão, auto-rebaixamento e
  remoção do último administrador.
- **Oferta:** percentual inteiro 1–99; início obrigatório; fim ≥ início.
- **Cupom:** código 3–50 (`A-Z`, `0-9`, `-`, `_`) e único; desconto inteiro 1–99;
  `val_max >= val_min`.
- **Estoque:** inteiro 0–99999.

## 5. Estrutura de arquivos

```
core/router.php                     rotas literais + {param} + _method
core/upload.php                     validação/armazenamento de imagem
controllers/admin_controller.php    guard, CSRF e helpers
controllers/admin_*_controller.php  um controller por recurso
repositories/*_repository.php       acesso a dados (inclui os "admin_*")
views/admin/layout.php              layout com sidebar/topbar
views/admin/*/                      telas de cada recurso
views/403.php                       acesso negado
public/css/admin.css                estilos do painel
docs/admin-dashboard.md             este documento
TESTES.md                           roteiro manual de testes
```

## 6. Limitações e evoluções

- Sem histórico de movimentação de estoque (entradas/saídas).
- Sem agrupamento de variações (um produto = um SKU/tamanho).
- Sem telas de pedidos, carrinho, endereços ou medidas de cliente.
- Galeria com múltiplas imagens por produto (hoje uma principal).
- Métricas de venda entram quando o checkout existir.
