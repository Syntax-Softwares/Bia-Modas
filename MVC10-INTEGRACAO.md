# MVC10 — Provador Virtual e Medidas

**Documento de planejamento e integração**
Branch: `main` (árvore de trabalho)
Base: `main` @ `13970cd`
Status: implementação concluída e testada localmente; alterações **sem commit**.
Card Trello: **MVC10 — Integrar provador virtual e medidas** (Thiago; o título do card está com a observação "Decidido não entregar ainda").
Origem visual: branch `apresentacao-pi` (`provador-virtual.html`, `css/provador-virtual.css`, `js/provador-virtual.js`, `model.glb`).

> Este documento cobre a parte de provador virtual e medidas do card MVC10. O card completo inclui a decisão de entrega, que permanece com o time.

---

## 1. Objetivo e escopo

Checklist do card e o que foi entregue:

| Item do checklist | Status |
|---|---|
| Integrar a tela do provador virtual ao MVC. | Entregue |
| Persistir e consultar medidas do usuário autenticado. | Entregue |
| Validar medidas e permissões de acesso. | Entregue |
| Verificar o carregamento do modelo 3D e seus recursos. | Verificado |
| Confirmar quais funcionalidades do provador podem ser implementadas com os dados disponíveis. | Respondido na seção 2 |

**Fora do escopo:** reconstrução corporal por IA, upload/armazenamento de fotos, recomendação de tamanho por medidas da peça e alterações em outras branches.

---

## 2. Decisões: o que é possível com os dados atuais

| Tema | Situação | Decisão |
|---|---|---|
| Fotos (rosto/corpo) | Capturadas no navegador; não há backend de processamento de imagem | Mantidas apenas em memória local; nada é enviado nem armazenado |
| Avatar 3D | `public/model.glb` é uma malha única (manequim), sem `skins` nem `morph targets` | Apenas exibição e rotação; as medidas não deformam o modelo |
| Medidas do usuário | Tabela `medidas` + `usuarios.medida_id` (1:1 opcional) | Consulta e gravação na própria tela do provador |
| Medidas da peça | `medidas_produtos` não existe no schema atual e `medida_*` é `NULL` no repositório | Não há recomendação de tamanho por medidas |
| Tamanho | `produto_tamanhos` e `tamanhos` existem no schema | O tamanho escolhido no produto acompanha a URL e é usado no carrinho real |
| Carrinho | `CarrinhoRepository` + `POST /carrinho/adicionar` | O botão final só aparece quando há tamanho válido; caso contrário volta ao produto |
| Câmera | `getUserMedia` | Botão "Pular e ver o avatar" permite usar o 3D mesmo sem câmera/permissão |

---

## 3. Schema

**Nenhuma alteração de schema.** Foram usadas as estruturas já existentes:

- `medidas (id_medida, busto, cintura, quadril, deletado_em)`.
- `usuarios.medida_id` (FK com `ON DELETE SET NULL`).

A gravação reaproveita o registro 1:1 do usuário; se não existir, cria e vincula. Não aceita `id_medida` vindo do formulário.

---

## 4. Rotas implementadas

| Método | Rota | Ação | Descrição |
|---|---|---|---|
| GET | `/provador-virtual` | `ProvadorController::index` | Produto ativo (`id`), tamanho opcional (`tamanho`) e medidas do usuário logado |
| POST | `/provador-virtual/medidas` | `ProvadorController::salvarMedidas` | Grava as medidas do próprio usuário (login + CSRF) |

Parâmetros:

- `id` (obrigatório): produto ativo e não removido; inválido/inexistente → 404.
- `tamanho` (opcional): validado contra `produto_tamanhos` do produto; inválido é ignorado.
- `busto`, `cintura`, `quadril`: opcionais, numéricos, `0 < valor <= 300`, arredondados em 1 casa.
- `produto_id` e `tamanho_id`: usados apenas para montar o retorno interno (`/provador-virtual?id=...&tamanho=...`), nunca como destino livre (sem open redirect).

---

## 5. Arquivos relevantes

| Arquivo | Papel |
|---|---|
| `controllers/provador_controller.php` | GET do provador, validação de produto/tamanho e POST de medidas |
| `repositories/medida_repository.php` | Consulta e gravação 1:1 em `medidas`/`usuarios.medida_id` |
| `views/provador_virtual.php` | View integrada ao header/footer e ao layout da loja |
| `public/js/provador-virtual.js` | Captura local, steps, carregamento do Three.js/GLB e carrinho real |
| `public/css/provador-virtual.css` | Layout editorial + formulário de medidas + estados de erro/pular |
| `views/produto_detalhe.php` | Botão "Provador Virtual" no detalhe do produto |
| `public/js/produto.js` | Sincroniza o tamanho selecionado com o link do provador |
| `core/sessao.php` | `cabecalhosSeguranca(bool $permitirCamera)` |
| `apache.conf` | Remove o `Permissions-Policy` global (passa a ser da aplicação) e declara `.glb` |
| `public/index.php` | Rotas MVC10 |

**Mantido:** `views/provador-virtual.html` (versão estática da branch de apresentação) permanece no repositório como referência, mas não é servido. A rota usa `views/provador_virtual.php`.

---

## 6. Fluxo

1. **Produto** → o botão "Provador Virtual" leva a `/provador-virtual?id=<produto>&tamanho=<variação>`.
2. **Passo 1** → produto real (nome, preço, imagem), tamanho selecionado, dicas e bloco "Suas medidas":
   - visitante: CTA para `/login`;
   - logado: formulário pré-preenchido com as medidas salvas.
3. **Passo 2/3** → captura de rosto/corpo via câmera. A foto fica no navegador; há fallback "Pular e ver o avatar".
4. **Passo 4** → Three.js + `/model.glb`, com rotação/zoom. Se houver tamanho válido, o botão final é "Adicionar ao carrinho" e usa o carrinho real; sem tamanho, volta ao produto.
5. **Medidas** → POST com CSRF; gravação apenas no `medida_id` do usuário da sessão; retorno para o provador com flash e valores preservados.

---

## 7. Segurança

| Vetor | Medida |
|---|---|
| Câmera em outras páginas | `Sessao::politicaPermissoes()` aplica `camera=()` no front controller; o provador chama `cabecalhosSeguranca(true)` e a substitui por `camera=(self)` |
| CSRF no POST | `Sessao::validarCsrf()` com token da sessão |
| Acesso indevido a medidas | Login obrigatório e uso exclusivo do `id_usuario` da sessão; nenhum ID de medida vem do cliente |
| Validação | Produto/tamanho validados no banco; medidas numéricas entre 1 e 300 cm; campos de texto limitados |
| SQL injection | PDO com prepared statements reais (`ATTR_EMULATE_PREPARES = false`) |
| XSS | `Html::e()` em toda saída; JSON com `JSON_HEX_TAG/AMP/APOS/QUOT` |
| Fotos | Não saem do navegador; não há endpoint de upload |
| Flood | Rate limit de GET (120/min/IP) e POST (30/min por usuário+IP) |
| CSP | `script-src` já permite `cdn.jsdelivr.net` (Three.js); `img-src data:` permite o preview; `connect-src 'self'` permite o `.glb` |
| Câmera | `getUserMedia` exige HTTPS ou `localhost` |

> O cabeçalho `Permissions-Policy` do Apache foi removido do escopo global: o PHP da aplicação define o valor por rota. Se algum proxy/CDN sobrescrever cabeçalhos, revisar a configuração de borda.

---

## 8. Testes executados

Ambiente: Docker Compose (PHP em Apache + MariaDB 11), banco recriado a partir de `db.sql` + `seed_db.sql`.

- **GET `/provador-virtual?id=1`** → 200, produto, tamanho, imagem, JSON e formulário/CTA conforme login.
- **Produto inválido/ausente** → 404.
- **Cabeçalhos** → provador `camera=(self)`; demais páginas `camera=()`; CSP presente.
- **Produto** → botão `btn-try-on` com `href=/provador-virtual?id=1&tamanho=5` após seleção automática do tamanho.
- **Medidas (logado)** → formulário pré-preenchido; POST com CSRF grava em `medidas`/`usuarios.medida_id`; flash de sucesso e valores preservados.
- **Medidas inválidas** (`301`, `0`, `abc`) → erro e valores antigos preservados; banco inalterado.
- **CSRF inválido** → 403.
- **POST sem login** → redireciona para `/login`.
- **Fluxo do navegador** → passo 1 → 2 (câmera negada) → "Pular" → passo 4; `model.glb` carregado e renderizado no canvas, sem erros no console.
- **Carrinho** → botão final adicionou o tamanho PP ao carrinho real; item e total corretos; remoção funcionou.
- **Mobile** → viewport 390×844, layout empilhado sem overflow.
- **Lint** → `php -l` em todos os arquivos alterados.

---

## 9. Critérios de aceite

- [x] A rota `/provador-virtual` é servida pelo MVC, com header/footer e proteção de sessão.
- [x] O produto e o tamanho vêm do banco; links adulterados não quebram o fluxo.
- [x] Visitante vê CTA de login; usuário logado consulta e atualiza as próprias medidas.
- [x] Medidas inválidas são recusadas com mensagem, sem gravar.
- [x] O modelo 3D carrega de `/model.glb` e pode ser girado.
- [x] O botão final usa o carrinho real quando há tamanho; caso contrário, volta ao produto.
- [x] Nenhuma foto é enviada ou armazenada.
- [x] CSP, `Permissions-Policy`, CSRF e rate limit ativos.

---

## 10. Limitações conhecidas

- **Modelo ilustrativo:** o GLB não possui morph targets/skins; as medidas não alteram a silhueta do avatar.
- **Dependência de CDN:** Three.js r128 vem de `cdn.jsdelivr.net` (sem SRI no carregamento dinâmico). Para produção offline, empacotar localmente.
- **Sem IA:** a captura de rosto/corpo é apenas uma etapa de experiência; não há reconstrução 3D a partir das fotos.
- **Sem upload:** recarregar a página descarta as fotos capturadas.
- **Base local antiga:** o ambiente de desenvolvimento estava com schema anterior ao `db.sql` atual; a base foi recriada e o dump anterior ficou em `storage/backup-pre-mvc10.sql` (diretório ignorado pelo Git).

---

## 11. Referências

- Card Trello: `MVC10 — Integrar provador virtual e medidas`.
- Branch `apresentacao-pi`: `provador-virtual.html`, `js/provador-virtual.js`, `css/provador-virtual.css`, `model.glb`.
- `MVC06-INTEGRACAO.md` / `MVC07-INTEGRACAO.md`: convenções de controller, view, segurança e documentação.
- `README.md`: stack PHP 8.4 + Apache + MariaDB 11 + Docker.
