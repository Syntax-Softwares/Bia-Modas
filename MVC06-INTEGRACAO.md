# MVC06 — Perfil e Endereços do Usuário

**Documento de planejamento e integração**
Branch: `implementacao-perfil-e-usuario`
Base: `integracao_backend` @ `b0e8420`
Status: implementação concluída e testada; **sem commit** (o responsável fará o commit e o merge).
Público-alvo: agente/desenvolvedor que fará a integração final com as demais branches.

---

## 1. Objetivo e escopo

Implementar o card **MVC06 — Implementar perfil e endereços do usuário**, cobrindo a checklist:

1. Integrar a página de usuário ao banco.
2. Consultar e atualizar dados cadastrais.
3. Criar, editar e selecionar endereços.
4. Permitir alteração de senha com validação.
5. Garantir que cada usuário só acesse os próprios dados.

**Fora do escopo (não implementar aqui):**

| Item | Responsável |
|---|---|
| Login, cadastro, sessões (MVC05) | Gabriel |
| Catálogo, busca e filtros (MVC07) | Branch `implementar-catalogo-busca-e-filtros` |
| Favoritos e carrinho (MVC08) | Thiago |
| Checkout, pedidos e pagamentos (MVC09) / RF12 | Thiago |
| Provador virtual e medidas (MVC10) | Thiago |
| Dashboard administrativa (MVC12) | — |

As abas **Medidas** e **Preferências** aparecem no visual, porém desabilitadas e sem lógica. **Não** implementar medidas/preferências/pedidos neste branch.

---

## 2. Decisões tomadas (validadas com o solicitante)

| Tema | Decisão |
|---|---|
| Abas Medidas/Preferências | Visíveis, porém desabilitadas ("em breve") |
| RF12 (histórico de pedidos) | Não incluído; pertence ao MVC09 |
| Sessão/autenticação | Camada mínima compatível com o `Auth` do MVC05 |
| Rota da página | `/perfil` (não usar `/conta` para evitar conflito) |
| CPF e data de nascimento | Incluídos; **CPF opcional** |
| Política de senha | Mínimo 8, com maiúscula, minúscula, número e símbolo |
| Endereço principal | Primeiro endereço vira principal automaticamente + botão "tornar principal" |
| Troca de e-mail | Exige senha atual |
| UI de endereços | Lista + formulário na mesma página (`?editar=ID` / `?excluir=ID`) |
| Estratégia de segurança | Prepared statements, CSRF, rate limit, sessão endurecida, CSP |

---

## 3. Rotas implementadas

Todas em `public/index.php`:

| Método | Rota | Ação | Descrição |
|---|---|---|---|
| GET | `/perfil` | `PerfilController::index` | Dados pessoais |
| GET | `/perfil/enderecos` | `PerfilController::enderecos` | Lista + formulário; aceita `?editar=ID` e `?excluir=ID` |
| GET | `/perfil/seguranca` | `PerfilController::seguranca` | Troca de senha + logout |
| POST | `/perfil/dados` | `PerfilController::atualizarDados` | Atualiza dados cadastrais |
| POST | `/perfil/senha` | `PerfilController::alterarSenha` | Troca de senha |
| POST | `/perfil/sair` | `PerfilController::sair` | Encerra a sessão |
| POST | `/perfil/enderecos/criar` | `PerfilController::criarEndereco` | Cria endereço |
| POST | `/perfil/enderecos/atualizar` | `PerfilController::atualizarEndereco` | Edita (id oculto no POST) |
| POST | `/perfil/enderecos/excluir` | `PerfilController::excluirEndereco` | Soft delete |
| POST | `/perfil/enderecos/principal` | `PerfilController::definirPrincipal` | Define principal |

> O front controller agora faz `parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)` antes do dispatch. O `apache.conf` encaminha rotas inexistentes para `public/index.php` via `mod_rewrite`.

---

## 4. Mapeamento com o banco (`db.sql`)

Sem alterações de schema. Tabelas usadas:

**`usuarios`**
`id_usuario`, `nome`, `email`, `senha_hash`, `cpf`, `telefone`, `data_nascimento`, `is_admin`, `deletado_em`.
`carrinho_id` e `medida_id` são anuláveis e não são usados aqui.

**`enderecos`**
`id_endereco`, `usuario_id`, `nome_endereco`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `uf`, `cep`, `principal`, `deletado_em`.

Regras:

- `email` e `cpf` têm UNIQUE no banco; erros `23000` são tratados sem revelar o valor duplicado.
- Exclusão de endereço é **soft delete** (`deletado_em`).
- Toda consulta/atualização filtra `usuario_id = sessão` e `deletado_em IS NULL`.
- Ao excluir o principal, o endereço ativo mais recente é promovido.
- `SELECT ... FOR UPDATE` na linha do usuário serializa criações/alterações concorrentes e evita dois endereços principais.

**Não alterado:** `medidas`, `preferencias` (não existe), `compras`, `itens_compra`.

---

## 5. Arquivos relevantes

| Arquivo | Papel |
|---|---|
| `controllers/perfil_controller.php` | Rotas, validação, autorização, CSRF, rate limit |
| `repositories/perfil_repository.php` | Dados cadastrais e hash de senha |
| `repositories/endereco_repository.php` | CRUD de endereços, principal, soft delete |
| `core/sessao.php` | Sessão, CSRF, flash, old/errors, headers |
| `core/rate_limit.php` | Rate limit em arquivo + fallback de sessão |
| `core/validacao.php` | CPF, e-mail, telefone, CEP, UF, data, política de senha |
| `core/html.php` | Escape e formatação |
| `views/perfil.php` | Página do perfil (abas e formulários) |
| `views/partials/perfil_header.php` | Header copiado do visual da `apresentacao-pi` |
| `views/partials/perfil_footer.php` | Footer copiado do visual |
| `views/partials/perfil_alerts.php` | Mensagens flash |
| `views/erro.php` | Páginas 403/429/500 sem vazar detalhes |
| `tools/dev-session.php` | Sessão de teste via CLI (fail-closed fora de dev) |
| `php-security.ini` | `display_errors=Off`, `expose_php=Off`, sessão |
| `apache.conf` | Rewrite, headers, ServerTokens, slowloris |
| `public/css/usuario.css` | Estilos das abas, endereços, erros e confirmação |

**Modificados:** `.dockerignore`, `Dockerfile`, `apache.conf`, `core/database.php`, `docker-compose.yml`, `public/css/usuario.css`, `public/index.php`.

**Novos:** `.gitignore`, `php-security.ini`, `tools/dev-session.php`, `controllers/perfil_controller.php`, `repositories/perfil_repository.php`, `repositories/endereco_repository.php`, `core/{sessao,rate_limit,validacao,html}.php`, `views/perfil.php`, `views/erro.php`, `views/partials/perfil_*.php`.

---

## 6. Contratos de integração (MVC05 / Auth)

O `Auth` do Gabriel (branch `origin/Gabriel`) usa exatamente as mesmas chaves. A integração deve preservar:

```php
$_SESSION['usuario'] = [
    'id_usuario' => (int),
    'nome'       => (string),
    'email'      => (string),
    'is_admin'   => (bool),
];

$_SESSION['csrf_token'] = '<hex 32 bytes>';
$_SESSION['flash']      = [['type' => 'success|danger|info|warning', 'message' => '...']];
$_SESSION['old']        = [/* campos do formulário, sem senhas */];
$_SESSION['errors']     = [/* campo => mensagem */];
```

- Nome do cookie/sessão: **`BIAMODAS_SESSID`**.
- `Sessao::exigirLogin()` redireciona para **`/login`**.
- CSRF: campo `_csrf`, comparação com `hash_equals`.
- Logout atual: `POST /perfil/sair`. O MVC05 tem `POST /logout`. Escolher um canônico na integração (recomendado: manter `/logout` e apontar o formulário para ele).

### Como trocar `Sessao` por `Auth` (recomendado)

1. Em `public/index.php`, chamar `Auth::start()` e remover `Sessao::iniciar()`.
2. Fazer `Sessao::usuario()` delegar para `Auth::user()` **ou** substituir as chamadas nos controllers/views por `Auth`.
3. Manter `Sessao::csrfCampo()` usando `Auth::csrfField()` se preferir.
4. Preservar `Sessao::setOld/setErros/flash` **ou** trocar pelas equivalentes do `Auth`.
5. Não chamar as duas inicializações de forma conflitante: ambas são idempotentes, mas o `session_name` precisa ser o mesmo.

---

## 7. Segurança implementada

| Vetor | Medida |
|---|---|
| SQL injection | PDO prepared statements + `ATTR_EMULATE_PREPARES = false`; zero concatenação |
| CSRF | Token de 32 bytes em todo POST; `hash_equals`; `SameSite=Lax` |
| Brute force | 5 falhas de senha por usuário+IP / 15 min e 20 por usuário / 15 min; `429` + `Retry-After: 900` |
| Flood/DDoS | 120 req/min por IP e 30 POST/min por usuário+IP; `429`; IP só como hash SHA-256 |
| IDOR | Todas as queries filtram `usuario_id` da sessão; nenhum id de usuário vem da request |
| XSS | `Html::e()` em toda saída; CSP; sem `innerHTML`/JS inline |
| Sessão | `use_strict_mode`, `HttpOnly`, `SameSite=Lax`, `Secure` sob HTTPS, idle 30 min, absoluto 8h, regeneração em troca de senha/e-mail e logout |
| Senha | bcrypt (`PASSWORD_DEFAULT`), mínimo 8 + complexidade, máximo 72 bytes, senha atual obrigatória |
| Enumeração/vazamento | Mensagens genéricas; endereço alheio não aparece; erros de banco não vão para a tela |
| Headers | CSP, `X-Frame-Options: DENY`, `nosniff`, `no-referrer`, `Permissions-Policy`, `no-store`, HSTS sob HTTPS |
| PHP | `display_errors=Off`, `expose_php=Off`, `log_errors=On` |
| Apache | `ServerTokens Prod`, `ServerSignature Off`, `TraceEnable Off`, `RequestReadTimeout` |
| Banco | App usa usuário `syntax` (não-root); porta 3306 publicada só em `127.0.0.1` |

**Limite conhecido:** rate limit na aplicação não substitui WAF/proxy para DDoS volumétrico. Em produção, colocar atrás de Cloudflare/Nginx e configurar `REMOTE_ADDR` corretamente (não confiar em `X-Forwarded-For` sem validação).

---

## 8. Pendências e pontos de atenção para a integração

### 8.1 Obrigatórias

1. **`/login` não existe neste branch.** O `tools/dev-session.php` cria sessão para testes. Após integrar o MVC05, remover a dependência do tool e validar o fluxo real de login.
2. **Rate limit no login/cadastro.** O `RateLimit` está pronto, mas o `AuthController` do MVC05 precisa usá-lo em `/login` (e idealmente em `/registro`) com mensagens genéricas.
3. **Política de senha divergente.** O cadastro do MVC05 aceita 6 caracteres; aqui exigimos 8 + complexidade. Alinhar `AuthController::register` para a mesma regra.
4. **Duas páginas de conta.** `AuthController::account()` renderiza `views/conta.php` (somente leitura). Decidir:
   - redirecionar `/conta` → `/perfil`; ou
   - substituir `conta.php` pelo `perfil.php`.
   Não manter as duas como páginas distintas.
5. **Header/footer duplicados.** `views/partials/perfil_header.php` e `perfil_footer.php` foram copiados do visual para não depender do MVC05. Na integração, trocar pelo `views/partials/site_header.php` e `footer.php` do Gabriel (ou unificar).
6. **Links futuros no header.** `/categoria` (MVC07), `/favoritos` e `/carrinho` (MVC08) ainda não existem. Ajustar quando as branches entrarem.
7. **Logout canônico.** Escolher entre `POST /perfil/sair` e `POST /logout`; manter apenas um formulário.
8. **Sessões antigas após troca de senha.** Apenas a sessão atual é regenerada. Invalidar todas as sessões exigiria coluna/tabela de versão de sessão no banco (mudança de schema). Decidir com o time.
9. **`APP_ENV` em produção.** O `tools/dev-session.php` é fail-closed: com `APP_ENV` ausente ou `production`, recusa executar. Garantir `APP_ENV=production` no deploy e/ou remover `tools/` da imagem.
10. **Permissão de `storage/`.** O rate limit grava em `storage/ratelimit`. Em Linux/produção, `chown -R www-data:www-data storage` ou usar volume nomeado. Se não puder gravar, o limite de requisições falha aberto; o limite de senha ainda usa o fallback de sessão.

### 8.2 Recomendadas

11. **CPF/nascimento no cadastro.** O RF01 pede CPF e data de nascimento, mas o MVC05 não coleta. O perfil permite preencher depois; alinhar o cadastro para coletar.
12. **E-mail de usuário soft-deleted.** O UNIQUE de `email` não considera `deletado_em`; um e-mail de conta excluída continua bloqueado. Limitação de schema, não do perfil.
13. **`core/database.php`.** A versão deste branch usa `DB_*` + prepared statements nativos. A do Gabriel usa root/root fixo. Preferir esta e garantir as variáveis no compose/secrets.
14. **`public/index.php`.** As rotas do perfil estão agrupadas e comentadas; preservar as rotas do MVC04/05/07/08/09/10 ao resolver conflito.
15. **Router.** O `Router` deste branch é o original; o do Gabriel já normaliza `parse_url`. Preferir o do Gabriel (MVC03) e manter as rotas do perfil.
16. **Tratamento de erros.** `views/erro.php` cobre 403/429/500. O MVC05 tem `404.php`/`405.php`; unificar sem perder o tratamento de exceções do `public/index.php`.
17. **Testes de regressão.** Rodar o fluxo completo com login real, CSRF real e banco integrado. Os testes usados aqui estão descritos na seção 10.

### 8.3 Não fazer

- Não alterar `db.sql` para “resolver” o perfil; o schema já foi validado.
- Não implementar medidas, preferências, favoritos, carrinho ou pedidos neste branch.
- Não fazer merge na `main`; o merge é apenas em `integracao_backend`.
- Não expor `tools/dev-session.php` via HTTP.
- Não logar senhas, tokens, CPF ou e-mail em texto puro.

---

## 9. Roteiro de integração (passo a passo)

1. **Atualizar** `integracao_backend` e a branch do MVC05.
2. **Merge** do MVC05 primeiro (Auth/sessão), resolvendo conflitos de `core/auth.php`, `core/database.php`, `public/index.php`.
3. **Trocar a sessão**: `Sessao` → `Auth` (ou delegar), mantendo as chaves da seção 6.
4. **Unificar conta**: `/conta` → `/perfil` (redirect ou substituição da view).
5. **Unificar header/footer** com os partials do MVC05.
6. **Adicionar rate limit** ao `/login` e `/registro`.
7. **Alinhar política de senha** no cadastro.
8. **Decidir invalidação de sessões** na troca de senha (se exigido, criar coluna/tabela e atualizar o login).
9. **Rodar lint + testes** (seção 10) e revisar headers/CSP no navegador.
10. **Remover/ajustar** `tools/dev-session.php` e `storage/_seed.php` do ambiente de produção.
11. **Merge final** em `integracao_backend` (`--no-ff`) e push. **Não** tocar na `main`.

---

## 10. Testes executados neste branch

Ambiente: Docker Compose (PHP 8.4 + Apache + MariaDB 11), schema do `db.sql`.

- **Funcional:** renderização do perfil, criação/edição/exclusão de endereços, troca de principal, atualização de nome/e-mail/telefone/CPF/nascimento, troca de senha, duplicidade de e-mail/CPF, logout.
- **Segurança:** POST sem CSRF → 403; endereço de outro usuário → 303 + mensagem genérica, sem vazar dados; `' OR 1=1 --` tratado como texto; `<script>` escapado; 6ª tentativa de senha → 429; 130 requisições → 429; hash bcrypt confirmado; nenhum warning/stack trace no HTML.
- **Lint:** `php -l` em todos os arquivos, sem erros.

### Comandos úteis

```powershell
cd "C:\Users\cibele note\Desktop\Bia-Modas-perfil-e-usuario"

# subir/rebuildar
docker compose up -d --build

# lint
docker compose exec app sh -c "find /var/www/html -name '*.php' -exec php -l {} \;"

# sessão de teste (imprime o cookie)
docker compose exec app php tools/dev-session.php 1

# teste rápido
curl.exe -b "BIAMODAS_SESSID=<valor>" http://localhost:8080/perfil
```

### Dados de teste

Criados por `storage/_seed.php` (ignorado pelo Git):

- Ana Silva — `ana@example.com` / `Senha@123` (id 1)
- Bruno Souza — `bruno@example.com` / `Outra@123` (id 2) — usado para testar IDOR

> `docker compose down` apaga as sessões em `/tmp`; `docker compose down -v` apaga o banco e exige rodar o seed novamente.

---

## 11. Critérios de aceite da integração

- [ ] Usuário logado acessa `/perfil` pelo header do site.
- [ ] Só vê/edita os próprios dados e endereços (testar IDOR).
- [ ] Consegue criar, editar, excluir e escolher o endereço principal (sempre no máximo um principal).
- [ ] Troca de e-mail exige senha atual; troca de senha exige senha atual e atende à política.
- [ ] Login/cadastro funcionam com a mesma sessão/CSRF do perfil.
- [ ] Rate limit ativo em login e nas ações do perfil; 429 com `Retry-After`.
- [ ] CSRF bloqueia POST sem token.
- [ ] Nenhum warning/stack trace/dado sensível no HTML.
- [ ] Headers de segurança (CSP, frame, nosniff, no-store) presentes.
- [ ] Nenhuma regressão em MVC04/05/07/08/09/10.
- [ ] `main` intacta.

---

## 12. Referências

- Checklist do card MVC06 (imagem enviada pelo solicitante).
- `README.md` (main): RF01 (cadastro) e RF12 (histórico de pedidos).
- Branch `origin/Gabriel`: `core/auth.php`, `controllers/auth_controller.php`, `repositories/usuario_repository.php`, `views/conta.php`.
- Branch `apresentacao-pi`: `usuario.html`, `css/usuario.css`, `js/usuario.js` (visual de referência).
- `db.sql` @ `integracao_backend` (`e4ad840`): schema validado.
