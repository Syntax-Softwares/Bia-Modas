# Roteiro de testes — Dashboard administrativa

Testes manuais da área administrativa. Execute após subir o ambiente.

## Preparação

```bash
# Necessário quando o schema/seed mudou (recria o banco de desenvolvimento)
docker compose down -v
docker compose up -d --build
```

- Loja: http://localhost:8080
- Admin: http://localhost:8080/admin
- E-mail: `admin@biamodas.local`
- Senha: `Admin@123`
- Mailpit (e-mails de recuperação): http://localhost:8025

> O banco tem apenas dados de seed. Recriar o volume não perde dados reais.

Verificação rápida de sintaxe:

```bash
docker compose exec -T app sh -lc "find /var/www/html -path /var/www/html/vendor -prune -o -name '*.php' -print | xargs -n1 php -l"
```

## 1. Autorização

- [ ] `GET /admin` sem login → redireciona para `/login` com flash informativo.
- [ ] Cliente comum logado acessando `/admin` → página **403 Acesso negado**.
- [ ] Admin logado acessando `/admin` → painel carrega.
- [ ] Link "Painel administrativo" aparece no header e em `/conta` apenas para admin.
- [ ] Rebaixar um admin enquanto ele está logado: a próxima requisição `/admin` dele retorna 403 (o guard relê o banco).
- [ ] Excluir um usuário logado em outra sessão: a próxima requisição dele volta ao login.

## 2. CSRF e métodos

- [ ] POST administrativo sem `_csrf` → flash "Sessão expirada", nada é gravado.
- [ ] Formulários de exclusão usam `_method=DELETE` e realmente excluem.
- [ ] Formulários de edição usam `_method=PUT` e realmente atualizam.
- [ ] `GET /admin/estoque/{id}` (rota só PUT) → 405 com `Allow: PUT`.
- [ ] `GET /admin/produtos/99999/editar` → 404.

## 3. Produtos

- [ ] Criar produto sem imagem → aparece na lista e na loja (se ativo).
- [ ] Criar produto com imagem JPG/PNG/WEBP → arquivo em `public/img/produtos` e imagem exibida na lista.
- [ ] Enviar arquivo `text/plain` como imagem → erro "Envie uma imagem JPG, PNG ou WEBP" e produto não é criado.
- [ ] Enviar imagem > 2 MB → erro de tamanho.
- [ ] Editar produto e enviar nova imagem → imagem antiga some do disco e a nova aparece.
- [ ] SKU duplicado (inclusive de produto excluído) → erro de unicidade.
- [ ] Valor 0/negativo, estoque negativo, descrição > 255 → erros por campo.
- [ ] Produto sem categoria ou sem categoria principal → erro.
- [ ] Produto com 2 categorias: a principal aparece na vitrine.
- [ ] Excluir produto → some da lista, da vitrine e do site (`/produto?id=...` retorna 404).
- [ ] Busca por nome/SKU, filtro por status e por categoria funcionam.
- [ ] Paginação (20 por página) mantém os filtros na URL.

## 4. Categorias

- [ ] Criar, renomear e descrever categoria.
- [ ] Categoria com pai (opcional) lista o nome do pai.
- [ ] Categoria não pode ser pai dela mesma nem criar ciclo.
- [ ] Excluir categoria com produtos vinculados → bloqueado com mensagem.
- [ ] Excluir categoria com subcategorias → bloqueado.
- [ ] Excluir categoria sem vínculo → some da lista; o nome original fica livre para recadastro.

## 5. Tamanhos e medidas

- [ ] Criar tamanho (`P`, `38`, ...) com e sem medidas.
- [ ] Tamanho duplicado entre ativos → bloqueado.
- [ ] Medidas fora de `0 < valor <= 300` → erro.
- [ ] Excluir tamanho usado por produto → bloqueado.
- [ ] Excluir tamanho sem uso → permitido.

## 6. Usuários

- [ ] Criar cliente com senha e confirmar login em outra sessão.
- [ ] Criar administrador e confirmar acesso ao painel.
- [ ] E-mail duplicado e CPF duplicado → erros.
- [ ] Telefone/CPF com tamanho inválido → erros.
- [ ] Editar dados sem informar senha → senha atual continua valendo.
- [ ] Informar nova senha na edição → login antigo falha, novo funciona.
- [ ] Admin não consegue excluir a própria conta.
- [ ] Admin não consegue remover o próprio acesso administrativo.
- [ ] Sistema impede remover/excluir o último administrador ativo.
- [ ] Excluir usuário → some da lista e o e-mail original fica livre para novo cadastro.

## 7. Ofertas e cupons

- [ ] Criar oferta de 10% ativa e vincular a um produto → vitrine mostra preço original e desconto.
- [ ] Oferta com fim anterior ao início → erro.
- [ ] Oferta expirada/inativa não aplica desconto na loja.
- [ ] Excluir oferta vinculada → produtos ficam sem oferta (desconto desaparece).
- [ ] Criar cupom (código, desconto, faixa, frete grátis) e conferir a listagem.
- [ ] Percentual fora de 1–99 → erro.
- [ ] `val_max < val_min` → erro.
- [ ] Excluir cupom → o código original fica livre para recadastro.

## 8. Estoque

- [ ] Listar todos os produtos e usar o filtro "somente estoque baixo (≤ 5)".
- [ ] Ajustar estoque pela tela e conferir o novo valor na lista e no produto da loja.
- [ ] Estoque inválido (negativo, texto) → erro e valor anterior mantido.
- [ ] Linha de estoque baixo destacada.

## 9. Painel

- [ ] Contadores conferem com o banco (produtos ativos, estoque baixo, clientes, ofertas ativas, cupons, categorias).
- [ ] "Últimos produtos" e "Últimos usuários" mostram os cadastros mais recentes e linkam para a edição.
- [ ] Link "Ver loja" e logout funcionam.

## 10. Regressão da loja

- [ ] Home, `/categoria`, `/produto?id=1`, `/login`, `/registro`, `/conta`, `/favoritos` continuam funcionando.
- [ ] Login de cliente continua indo para `/conta`; login de admin vai para `/admin`.
- [ ] Produtos inativos/excluídos não aparecem na vitrine.
- [ ] Categoria principal é a exibida no card.

## Limpeza após os testes

As telas de exclusão usam soft delete. Para voltar ao estado do seed:

```bash
docker compose down -v
docker compose up -d --build
```
