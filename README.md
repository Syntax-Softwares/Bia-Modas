# Nome do integrantes:
<ul>Joaquim G.</ul>
<ul>Thiago Mazzi</ul>
<ul>Luiz G. Vieira</ul>
<ul>Gabriel Maciel</ul>
<ul>João V. Tiroel</ul>

<p align="left" style="font-size:28px;"><strong><em>Documentação do PI</em></strong></p>
<details>
<summary><strong>📌 Sumário</strong></summary>

- [1. Introdução](#1-introdução)
- [Objetivos](#-objetivos)
- [Metodologia](#-metodologia)
- [1.3 Cronograma](#13-cronograma)
- [2. Requisitos](#2-requisitos)
- [Requisitos funcionais](#-requisitos-funcionais)
- [Requisitos não funcionais](#-requisitos-não-funcionais)
- [3. Modelo de casos de uso](#3-modelo-de-casos-de-uso)
- [4. Modelo do banco de dados](#4-modelo-do-banco-de-dados)
- [5. Banco de dados](#5-banco-de-dados)
- [6. Diagrama de classes](#6-diagrama-de-classes)
- [7. Estudo de viabilidade](#7-estudo-de-viabilidade)
- [8. Regras de negócio (Modelo canvas)](#8-regras-de-negócio-modelo-canvas)
- [9. Design](#9-design)
- [10. Protótipo](#10-protótipo)
- [11. Aplicação](#11-aplicação)
- [11.1 Como executar localmente](#111-como-executar-localmente)
- [11.2 Módulos e branches](#112-módulos-e-branches)

</details>

Para cada semestre, do 1º ao 6º, iremos utilizar este template para documentar o PI -
incrementalmente.

# 1. Introdução
O mercado de moda plus size enfrenta um paradoxo crítico no ambiente digital: embora represente uma parcela significativa dos consumidores, a oferta de plataformas de compra adequadas ainda é escassa. Dados alarmantes revelam que **61% das pessoas plus size** relatam dificuldades persistentes para encontrar roupas que atendam às suas necessidades. 

Essa lacuna é agravada pela baixa qualidade das interfaces de venda, visto que **90% dos sites de e-commerce** negligenciam aspectos fundamentais da experiência do usuário (UX). Diante desse cenário, o presente projeto propõe o desenvolvimento de um catálogo digital especializado, focado prioritariamente em suprir essas carências por meio de um design centrado no ser humano, garantindo acessibilidade, representatividade e eficiência no processo de compra.

## • Objetivos
O projeto visa transformar a jornada de compra do consumidor plus size por meio de metas estratégicas e mensuráveis:

* **Experiência do Usuário (UX):** Desenvolver um catálogo digital que seja intuitivo, rápido e visualmente acolhedor, eliminando barreiras de navegação e promovendo um ambiente de consumo positivo.
* **Eficiência Operacional:** Reduzir em **30%** a taxa de devolução motivada por problemas de tamanho, utilizando ferramentas que auxiliem o cliente na escolha precisa das peças.
* **Desempenho Comercial:** Alcançar um aumento de **15%** na taxa de conversão durante o primeiro semestre de operação do catálogo.
* **Fidelização:** Construir uma base sólida de clientes fiéis, estabelecendo uma relação de confiança entre a marca e o público por meio da atenção às suas dores específicas.
* **Escalabilidade:** Estruturar o catálogo como uma base robusta para a futura expansão rumo a um ecossistema de e-commerce completo.
  
## • Metodologia

O projeto é desenvolvido com **PHP 8.4** e **Apache**, seguindo o padrão **MVC** (Model-View-Controller). A aplicação é organizada em `controllers`, `models`, `repositories`, `views`, `core` e `public`, com o front controller em `public/index.php`. O acesso ao banco é feito com **PDO** e **prepared statements**, e o banco utilizado é o **MariaDB 11**.

| Camada | Tecnologias |
|---|---|
| Back-end | PHP 8.4 + Apache, arquitetura MVC |
| Banco de dados | MariaDB 11 + PDO (prepared statements) |
| Front-end | HTML5, CSS3, JavaScript e Bootstrap 5 |
| Infraestrutura | Docker e Docker Compose |
| Segurança | Sessões, CSRF, rate limit, CSP e bcrypt |
| Design e processo | Figma, Scrum, Git/GitHub e GitHub Actions |

**Fluxo da requisição:** `navegador → public/index.php → router → controller → repository → PDO → MariaDB → view`.

O versionamento é feito no GitHub, com branches por módulo e integração na branch `integracao_backend`. O deploy é automatizado via GitHub Actions para uma VPS.

## 1.3 Cronograma

O MVP foi estimado em **8 a 10 semanas**, organizado em cinco fases:

| Fase | Período | Entregas principais |
|---|---|---|
| Fase 1 — Validação | Semanas 1 e 2 | Validação com usuárias reais, fornecedores, requisitos e backlog inicial. |
| Fase 2 — Fundação | Semanas 3 e 4 | Sprint 0, landing page, arquitetura, identidade visual e protótipo. |
| Fase 3 — Catálogo | Semanas 5 e 6 | Cadastro/autenticação, catálogo, busca, filtros e detalhes do produto. |
| Fase 4 — Compra | Semanas 7 e 8 | Carrinho, favoritos, checkout, pagamento e painel administrativo. |
| Fase 5 — Beta | Semanas 9 e 10 | Testes, ajustes de UX, feedback e lançamento em beta fechado. |

# 2. Requisitos

## • Requisitos funcionais

| Código | Nome | Descrição |
|--------|------|----------|
| RF01 | Barra de Pesquisa | O sistema deve disponibilizar uma barra de busca com sugestões automáticas durante a digitação e redirecionamento para a página de resultados. |
| RF02 | Filtro de Produtos | O sistema deve permitir filtrar produtos por categoria, faixa de preço, relevância, tipo de corpo, elasticidade e compressão. |
| RF03 | Cadastro de Usuário | O sistema deve permitir que a usuária realize seu cadastro fornecendo nome, CPF, RG, data de nascimento, endereço, telefone, e-mail e senha. |
| RF04 | Autenticação de Usuário | O sistema deve permitir que usuárias previamente cadastradas acessem a plataforma por meio de autenticação com e-mail e senha. |
| RF05 | Catálogo e Visualização de Produtos | O sistema deve permitir acesso ao catálogo completo e à visualização dos detalhes dos produtos: descrição, especificações, imagens, preço e avaliações. |
| RF06 | Recomendações Personalizadas | O sistema deve permitir a coleta de dados, com consentimento, para gerar recomendações de produtos e de tamanho em tempo real. |
| RF07 | Carrinho de Compras e Finalização | O sistema deve permitir adicionar produtos ao carrinho, visualizar itens e total e finalizar a compra com integração a sistemas de pagamento. |
| RF08 | Experiência de Compra e Favoritos | O sistema deve permitir selecionar produtos, escolher variações, definir quantidades e favoritar itens para uso futuro. |
| RF09 | Recuperação de Senha | O sistema deve permitir que a usuária redefina a senha por meio de um link enviado para o e-mail cadastrado. |
| RF10 | Controle de Estoque | O sistema deve controlar automaticamente o estoque dos produtos, atualizando as quantidades após vendas e cancelamentos. |
| RF11 | Histórico de Pedidos | O sistema deve permitir que a usuária visualize suas compras anteriores, com status e detalhes de cada pedido. |
| RF12 | Gestão Administrativa | O sistema deve disponibilizar uma área exclusiva para administradores, com funcionalidades de gestão. |
| RF13 | Avaliação de Produtos | O sistema deve permitir que as usuárias avaliem os produtos adquiridos, atribuindo nota e comentário. |
| RF14 | Dashboard Administrativa | O administrador deve poder realizar o CRUD de usuários e produtos, gerenciar promoções e descontos e acompanhar indicadores no painel administrativo. |

## • Requisitos não funcionais

| Código | Nome | Descrição |
|--------|------|----------|
| RNF01 | Usabilidade | O sistema deve possuir interface simples, intuitiva e acolhedora. |
| RNF02 | Desempenho | O sistema deve responder às requisições da usuária em até 2 segundos, em condições normais de uso. |
| RNF03 | Escalabilidade | O sistema deve suportar múltiplos acessos simultâneos sem degradação significativa de desempenho. |
| RNF04 | Confiabilidade | O sistema deve garantir a integridade e a consistência dos dados. |
| RNF05 | Disponibilidade | O sistema deve estar disponível 24 horas por dia, 7 dias por semana, com taxa mínima de 99%. |
| RNF06 | Responsividade | O sistema deve funcionar corretamente em desktop, tablet e dispositivos móveis. |
| RNF07 | Compatibilidade | O sistema deve ser compatível com os principais navegadores do mercado. |
| RNF08 | Manutenibilidade | O sistema deve possuir código organizado e de fácil manutenção. |
| RNF09 | Conformidade com a LGPD | O sistema deve garantir privacidade e consentimento no uso dos dados das usuárias. |
| RNF10 | Segurança de Dados | O sistema deve proteger os dados contra acessos não autorizados, incluindo criptografia de senhas e informações sensíveis. |
| RNF11 | Backup | O sistema deve realizar backups periódicos dos dados. |
| RNF12 | Controle de Acesso | O sistema deve restringir o acesso à área administrativa apenas a administradores autorizados. |
| RNF13 | Integração com APIs | O sistema deve integrar-se de forma eficiente com APIs externas, como pagamento, e-mail e serviços de recomendação. |
| RNF14 | Autenticação Segura | O sistema deve armazenar senhas criptografadas e utilizar mecanismos seguros de autenticação. |
| RNF15 | Recuperação de Falhas | O sistema deve ser capaz de se recuperar rapidamente após falhas. |

# 3. Modelo de casos de uso

O diagrama abaixo representa os atores e os casos de uso do sistema. Os atores são **Visitante**, **Cliente** e **Administrador**; o **Sistema de Pagamento** e o **Serviço de E-mail** atuam como sistemas externos.

<img src="./imgReadme/ModeloCasosDeUso.jpg" alt="Modelo de Casos de Uso" />

## 3.1 Casos de uso resumidos

| Código | Caso de uso | Atores | Descrição resumida |
|---|---|---|---|
| UC01 | Cadastrar usuário | Visitante | Cria uma conta com dados pessoais, e-mail e senha. |
| UC02 | Autenticar usuário | Visitante | Acessa a plataforma com e-mail e senha cadastrados. |
| UC03 | Recuperar senha | Visitante; Serviço de E-mail | Redefine a senha por meio de link enviado por e-mail. |
| UC04 | Pesquisar produtos | Visitante | Busca produtos com sugestões automáticas durante a digitação. |
| UC05 | Receber recomendações personalizadas | Cliente | Apresenta recomendações de produtos e de tamanho com base nos dados e preferências da cliente. |
| UC06 | Consultar catálogo | Visitante | Exibe o catálogo completo de produtos disponíveis. |
| UC07 | Visualizar detalhes do produto | Visitante | Mostra descrição, especificações, imagens, preço, medidas e avaliações. |
| UC08 | Selecionar variação e quantidade | Cliente | Permite escolher cor, tamanho e quantidade antes de adicionar ao carrinho. |
| UC09 | Gerenciar favoritos | Cliente | Permite salvar, consultar e remover produtos favoritos. |
| UC10 | Adicionar produto ao carrinho | Cliente | Insere o produto selecionado no carrinho de compras. |
| UC11 | Consultar carrinho | Cliente | Exibe itens, quantidades, subtotal, frete e total. |
| UC12 | Finalizar compra | Cliente; Sistema de Pagamento | Conclui o pedido, registra a compra e aciona o pagamento. |
| UC13 | Processar pagamento | Sistema de Pagamento | Autoriza e confirma a transação financeira. |
| UC14 | Filtrar produtos | Visitante | Refina o catálogo por categoria, preço, relevância, tipo de corpo, elasticidade e compressão. |
| UC15 | Acessar dashboard administrativa | Administrador | Acessa o painel com indicadores e atalhos de gestão. |
| UC16 | Gerenciar usuários | Administrador | Consulta, edita, ativa, desativa e exclui usuários. |
| UC17 | Gerenciar produtos | Administrador | Cadastra, edita, exclui e consulta produtos e estoque. |
| UC18 | Gerenciar promoções e descontos | Administrador | Cria e acompanha ofertas e descontos. |
| UC19 | Consultar histórico de pedidos | Cliente | Exibe compras anteriores, status e detalhes. |
| UC20 | Avaliar produto | Cliente | Atribui nota e comentário a um produto adquirido. |
| UC21 | Atualizar estoque | Sistema | Atualiza automaticamente o estoque após o pagamento aprovado. |

# 4. Modelo do banco de dados

O modelo físico atual está implementado no [`db.sql`](https://github.com/Syntax-Softwares/Bia-Modas/blob/integracao_backend/db.sql) da branch **`integracao_backend`** e possui **17 tabelas**. As imagens das seções 5.1 e 5.2 continuam sendo a visão conceitual/lógica do MVP.

| Tabela | Principais colunas | Chaves e relações |
|---|---|---|
| `medidas` | `id_medida`, `busto`, `cintura`, `quadril` | PK `id_medida`; referenciada por `usuarios.medida_id` |
| `carrinhos` | `id_carrinho`, `sessao_id` | PK; `sessao_id` único; referenciada por `usuarios.carrinho_id` e `itens_carrinho` |
| `usuarios` | `id_usuario`, `carrinho_id`, `medida_id`, `nome`, `email`, `senha_hash`, `cpf`, `telefone`, `data_nascimento`, `is_admin` | PK; `email`/`cpf` únicos; FKs para `carrinhos` e `medidas` |
| `enderecos` | `id_endereco`, `usuario_id`, `nome_endereco`, `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `uf`, `cep`, `principal` | FK `usuario_id` → `usuarios` (CASCADE) |
| `cupons` | `id_cupom`, `codigo`, `val_min`, `val_max`, `desconto`, `frete_gratis` | `codigo` único; referenciado por `compras.cupom_id` |
| `ofertas` | `id_oferta`, `percentual`, `inicio_em`, `fim_em`, `ativo` | referenciada por `produtos.oferta_id` |
| `medidas_produtos` | `id_medida_produto`, `tamanho`, `busto`, `cintura`, `quadril` | referenciada por `produtos.medida_produto_id` |
| `compras` | `id_compra`, `usuario_id`, `sessao_id`, `endereco_id`, `cupom_id`, `numero_pedido`, `valor_total`, `situacao`, `frete`, `desconto`, snapshot de destinatário/endereço | `numero_pedido` único; FKs para `usuarios`, `enderecos` e `cupons` |
| `pagamentos` | `id_pagamento`, `compra_id`, `metodo`, `status`, `valor`, `codigo_transacao` | FK `compra_id` → `compras` (CASCADE); checks de método e status |
| `produtos` | `id_produto`, `medida_produto_id`, `oferta_id`, `nome`, `sku`, `descricao`, `cor`, `estilo`, `estoque`, `valor`, `ativo` | `sku` único; FKs para `medidas_produtos` e `ofertas` |
| `categorias` | `id_categoria`, `categoria_pai_id`, `nome`, `descricao` | `nome` único; auto-relacionamento hierárquico |
| `categorias_produtos` | `id_categoria_produto`, `produto_id`, `categoria_id` | N:N; FKs para `produtos` e `categorias`; único por par |
| `imagens` | `id_imagem`, `produto_id`, `caminho_imagem` | FK `produto_id` → `produtos` (CASCADE) |
| `itens_carrinho` | `id_item_carrinho`, `carrinho_id`, `produto_id`, `quantidade`, `valor_unitario` | FKs para `carrinhos` e `produtos`; único por carrinho/produto |
| `favoritos` | `id_favorito`, `usuario_id`, `sessao_id`, `produto_id` | FK `produto_id`; único por usuário/produto e por sessão/produto |
| `itens_compra` | `id_item_compra`, `compra_id`, `produto_id`, `quantidade`, `valor_unitario`, `valor_total` | FKs para `compras` e `produtos` |
| `codigos_recuperacao_senha` | `id_codigo`, `usuario_id`, `codigo_hash`, `tentativas`, `expira_em`, `usado_em` | FK `usuario_id` → `usuarios` (CASCADE); código armazenado como hash |

### Relações principais

- `usuarios` tem relação **1:1 opcional** com `carrinhos` e `medidas`.
- `usuarios` tem relação **1:N** com `enderecos` e `compras`.
- `carrinhos` tem **1:N** `itens_carrinho`; cada item referencia um `produto`.
- `compras` tem **1:N** `itens_compra` e `pagamentos`; pode referenciar um `endereco` e um `cupom`.
- `produtos` tem relação **N:N** com `categorias` via `categorias_produtos`, **1:N** com `imagens`, **1:1** com `medidas_produtos` e **N:1** com `ofertas`.
- `favoritos` liga um usuário (ou uma sessão anônima) a produtos.
- `codigos_recuperacao_senha` liga um usuário a códigos temporários com hash, tentativas e expiração.

### Convenções do schema

- Chaves primárias `BIGINT UNSIGNED AUTO_INCREMENT`.
- `criado_em`, `alterado_em` e `deletado_em` (soft delete) na maioria das tabelas; `codigos_recuperacao_senha` usa apenas `criado_em`.
- Banco `utf8mb4` com collation `utf8mb4_unicode_ci`.
- FKs com `ON DELETE` explícito (`CASCADE`, `SET NULL` ou `RESTRICT`).
- `CHECK` para valores positivos, estoque, quantidade, faixa de cupom e método/status de pagamento.
- `compras` e `favoritos` aceitam **usuário logado ou sessão anônima** (`sessao_id`), com `CHECK` garantindo que apenas um dos dois é informado.
- `produtos` possui `cor` e `estilo` para os filtros do catálogo.

# 5. Banco de dados
## 5.1 Modelo Conceitual
<img 
    src="./imgReadme/ModeloConceitual.jpg" 
    alt="Modelo Conceitual do Banco de Dados" 
  />
  
## 5.2 Modelo Lógico
<img 
    src="./imgReadme/ModeloLogico.jpg" 
    alt="Modelo Lógico do Banco de Dados" 
  />


# 6. Diagrama de classes
<img src="./imgReadme/DiagramaDeClasses.png" />
 **Observações:** no schema físico, a relação Produto–Categoria é N:N por `categorias_produtos`; `Avaliacao` ainda não possui tabela física; `enderecos` e `cupons` são tabelas do schema físico que não aparecem no diagrama conceitual do MVP.

# 7. Estudo de Viabilidade

## 7.1 Resumo Executivo e Viabilidade Financeira

O projeto propõe o desenvolvimento de um catálogo digital de moda plus size focado em solucionar falhas graves de experiência de usuário (UX) encontradas no e-commerce tradicional (como incerteza de tamanhos e falta de representatividade). O projeto demonstra alta viabilidade técnica e econômica por meio de um modelo de desenvolvimento ágil e infraestrutura enxuta.

### Indicadores Gerais de Viabilidade

| Indicador Técnico / Financeiro | Detalhes do Escopo do Projeto | Investimento / Prazo |
|:---|:---|:---|
| **Status do Projeto** | Viável e Altamente Recomendado | Foco em Demanda Reprimida |
| **Prazo Estimado (MVP)** | Ciclo de desenvolvimento ágil da plataforma | **8 a 10 semanas** |
| **Equipe Técnica** | Alocação de engenharia interna | **5 desenvolvedores** |
| **Custo Inicial (Setup)** | Formato *Bootstrapping* (Uso de licenças open-source) | **R$ 0** |
| **Custo Mensal (Infra)** | Hospedagem da plataforma e serviços de nuvem (*Host*) | **R$ 120 / mês** |
| **ROI Esperado** | Retorno baseado na retenção e conversão digital | **6 a 12 meses** |

---

## 7.2 Contexto e Justificativa

### O Problema (As Dores de UX no E-commerce)
O e-commerce convencional falha estruturalmente ao atender o público plus size. A frustração do usuário é impulsionada por fatores técnicos negligenciados por **90% das plataformas vigentes**:
* **82%** das usuárias relatam informações de tamanho insuficientes.
* **76%** apontam falta de clareza e métricas sobre o caimento das peças.
* **21%** reclamam da ausência completa de fotos com modelos reais.

### A Oportunidade e Alinhamento Técnico
O mercado plus size nacional projeta movimentar **R$ 15 bilhões até 2027**. Como as grandes marcas mantêm grades limitadas e interfaces de compra genéricas, o software proposto se posiciona como uma solução de nicho de alto impacto. 

**Proposta de Engenharia:** Desenvolver um sistema centrado nas dores da experiência do usuário, convertendo representatividade e inteligência de caimento em componentes de software aplicados à interface.

---

## 7.3 Análise de Mercado e Tendências Técnicas
O crescimento contínuo do setor exige que as novas aplicações web incorporem tecnologias emergentes para atender aos novos comportamentos de consumo mapeados para os próximos anos:
* **Smart Sizing:** Uso de processamento de dados e IA para recomendação automatizada de tamanhos.
* **Autenticidade Visual:** UI construída com mídias dinâmicas e diversidade real (fator essencial para 83% dos usuários).
* **Interface Responsiva:** Foco no público líder do segmento (mulheres entre 26 a 40 anos), caracterizadas como nativas digitais altamente ativas em plataformas móveis.

---

## 7.4 Análise de Concorrência e Engenharia de Diferenciais

Enquanto os grandes players mantêm processos engessados e abordagens padronizadas, o escopo de software deste projeto ataca diretamente as brechas técnicas mapeadas no mercado:

| Concorrente | Limitações do Produto Atual | Solução Implementada no Projeto (Escopo MVP) |
|:---|:---|:---|
| **Grandes Redes (Ex: Renner / Shein)** | Catálogos sazonais reduzidos e fluxos de navegação genéricos. | **Filtros Avançados:** Segmentação refinada por tipo de corpo, elasticidade do tecido e compressão. |
| **Marcas Tradicionais (Ex: Marisa Plus)** | Interfaces visuais obsoletas e pouca inovação técnica. | **UI Inclusiva:** Design System focado em acessibilidade, acolhimento e alta representatividade em mídia. |
| **Mercado de E-commerce Geral** | Tabelas estáticas e guias de tamanho imprecisos. | **Guia Antropométrico:** Sistema de medidas interativo baseado na anatomia real do público-alvo. |

---

## 7.5 Especificação Técnica

### Funcionalidades do MVP
* Catálogo com busca, filtros e página de produto;
* Guia de tamanhos interativo e recomendação de tamanho;
* Cadastro, autenticação e recuperação de senha;
* Perfil, endereços e segurança;
* Favoritos;
* Carrinho persistente e checkout (em integração);
* Histórico de pedidos e avaliações (em evolução);
* Área administrativa (em evolução);
* Interface responsiva e acessível.

### Funcionalidades Futuras
* Provador virtual com IA;
* Aplicativo mobile;
* Programa de fidelidade;
* Integrações com redes sociais e marketplaces;
* IA avançada de recomendação de tamanho e produtos.

---

## 7.6 Matriz de Riscos e Mitigação do MVP

| Risco Mapeado | Probabilidade | Impacto | Ação de Mitigação (Abordagem de Engenharia) |
|:---|:---:|:---:|:---|
| **Crescimento excessivo do escopo** | Alta | Alto | Divisão em sprints semanais fixas e aplicação de priorização rígida do *backlog*. |
| **Inconsistência de tabelas e caimentos** | Média | Alto | Padronização estrutural dos dados de produtos e guia antropométrico interativo. |
| **Rotatividade de desenvolvedores (Turnover)**| Média | Alto | Manutenção de documentação técnica rigorosa e adoção de práticas de *pair programming*. |
| **Baixa adoção/engajamento inicial** | Média | Médio | Lançamento em formato Beta fechado orientado a testes contínuos com grupos de usuários reais. |

---

## 7.7 Conclusão e Recomendações

O desenvolvimento da aplicação é viável devido ao baixo investimento operacional requerido pela equipe de engenharia e à existência de uma demanda digital reprimida expressiva.

### Pontos Fortes do Projeto
* Arquitetura inicial enxuta baseada em *bootstrapping*;
* Diferenciais de UX claros frente aos grandes competidores;
* Estrutura de código escalável pronta para migrar de catálogo para e-commerce transacional.

### Próximos Passos (Plano de Ação para 4 Semanas)
* **Semana 1:** Alinhamento de Product Owner e validação do protótipo de alta fidelidade com 5 usuárias do nicho.
* **Semana 2:** Estruturação da base de dados e setup dos ativos visuais e parcerias de mídia.
* **Semana 3 (Sprint 0):** Inicialização do ambiente de desenvolvimento, definição da arquitetura do software e implementação do Design System.
* **Semana 4:** Deploy de Landing Page responsiva para captação de leads e triagem da base de usuários beta.

---

## 7.8 Anexos

### Checklist de UX Plus Size
* Fotos com modelos reais;
* Guia de medidas detalhado;
* Informações sobre caimento;
* Filtros por tipo corporal;
* Linguagem inclusiva;
* Zoom em alta resolução;
* Avaliações com fotos de clientes.

### Referências
* Associação Brasil Plus Size (ABPS);
* Fortune Business Insights;
* Baymard Institute;
* Sizebay;
* Construsite Brasil;
* Accio.
  
# 8. Regras de negócio (Modelo canvas)
<img 
    src="./imgReadme/BMCanvas Bia Modas.jpg" 
    alt="BMCanvas" 
  />

## 8.1 Blocos do Canvas

| Bloco | Conteúdo |
|---|---|
| Parcerias-chave | Time de Marketing, responsável pela divulgação, comunicação e ações promocionais. |
| Atividades-chave | Catalogação de produtos, personalização, avaliações e “Try it now” com IA. |
| Proposta de valor | Vitrine plus size, recomendações personalizadas e experiência de compra acolhedora. |
| Relacionamento com clientes | Atendimento via WhatsApp, com acompanhamento de dúvidas de tamanho, caimento e pedidos. |
| Segmentos de clientes | Mulheres de 18 a 50 anos (foco 26–40), mobile-first, que valorizam conforto, estilo e preço claro. |
| Recursos-chave | Plataforma digital, estoque, dados das clientes, integração com Tray e consentimento LGPD. |
| Canais | Instagram, Facebook, YouTube e tráfego pago. |
| Estrutura de custos | Hospedagem e banco de dados; bootstrapping, com custo mensal estimado em R$ 120. |
| Fontes de receita | Venda de roupas e frete pago pela cliente. |

# 9. Design
# Bia Modas — Design System

> Relatório completo de tipografia, paleta de cores e uso de cada elemento no projeto Bia-Modas.

---

## Fontes / Tipografias

### Importação (Google Fonts CDN)

Todas as 7 páginas HTML importam as mesmas duas famílias via CDN:

```html
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
```

| Fonte | Pesos Importados | Variável CSS | Uso |
|-------|-----------------|--------------|-----|
| **Poppins** | 300, 400, 500, 600, 700 | `--font-primary` | Corpo geral da página, formulários, botões, navegação, abas de perfil |
| **Playfair Display** | 400, 500, 600, 700 | `--font-display` | Títulos de destaque, hero banner, nomes de produto, seções |

### Onde cada fonte é usada

#### Poppins (`--font-primary`)

- `body` — texto corrido de toda a página
- `.form-control` — campos de formulário (login, registro, perfil)
- `.wizard-nav .btn` — botões de navegação do wizard de registro
- `.profile-tab` — abas do perfil do usuário

#### Playfair Display (`--font-display`)

| Elemento | Arquivo |
|----------|---------|
| Título do Hero Banner (`hero-content h1`) | `layout.css` |
| Títulos de seção (`section-header h2`) | `layout.css` |
| Título da Newsletter (`newsletter-section h2`) | `layout.css` |
| Título do overlay de categoria (`category-overlay h3`) | `components.css` |
| Título do carrinho (`cart-header h3`) | `components.css` |
| Título do card de auth (`auth-card-header h1`) | `auth.css` |
| Nome do produto (`#product-name`) | `produto.html` (inline) |
| Nome do usuário no perfil (`#profile-name`) | `usuario.html` (inline) |
| Título da página de categoria (`page-header h1`) | `categoria.html` (inline) |
| Título do offcanvas de filtros (`filter-offcanvas-header h3`) | `categoria.html` (inline) |

---

## Paleta de Cores

### Variáveis CSS (definidas em `css/base.css`)

```css
:root {
    --color-primary: #d1001f;
    --color-primary-dark: #ae031c;
    --color-secondary: #f8f9fa;
    --color-dark: #212529;
    --color-gray: #6c757d;
    --color-light-gray: #e9ecef;
    --font-primary: 'Poppins', sans-serif;
    --font-display: 'Playfair Display', serif;
}
```

---

### `--color-primary` → `#d1001f` (Vermelho de CTA e interação)

**Propósito:** Cor de CTA e destaques interativos. O vermelho institucional da marca é o `#AE031C`, aplicado em hover e na identidade visual.

| Onde é usada | Arquivo → Seletor |
|--------------|-------------------|
| Fundo da top bar (barra superior vermelha) | `layout.css` → `.top-bar` |
| Fundo do botão da search bar | `layout.css` → `.search-box button` |
| Hover dos ícones do header | `layout.css` → `.header-actions a:hover` |
| Badge do carrinho (contador) | `layout.css` → `.badge-count` |
| Hover dos links da nav | `layout.css` → `.main-nav .nav-link:hover` |
| Ícones da seção de features | `layout.css` → `.feature-icon` |
| Linha decorativa dos títulos de seção | `layout.css` → `.section-header .line` |
| Fundo da seção newsletter | `layout.css` → `.newsletter-section` |
| Hover dos links do footer | `layout.css` → `.footer-widget ul li a:hover` |
| Ícones de contato do footer | `layout.css` → `.footer-contact i` |
| Hover dos ícones sociais | `layout.css` → `.social-links a:hover` |
| Fundo dos botões primários customizados | `components.css` → `.btn-primary-custom`, `.btn-primary-solid` |
| Badge de produto ("Novo", "-31%") | `components.css` → `.product-badge` |
| Hover dos botões de ação do produto | `components.css` → `.product-actions button:hover` |
| Preço atual do produto | `components.css` → `.price-current` |
| Borda do link de categoria | `components.css` → `.category-overlay a` |
| Preço do item no carrinho | `components.css` → `.cart-item-price` |
| Total do carrinho | `components.css` → `.cart-total span:last-child` |
| Hover dos botões do carrossel | `components.css` → `.carousel-btn:hover` |
| Dot ativo do carrossel | `components.css` → `.carousel-dot.active` |
| Hover do nome da categoria no grid | `components.css` → `.category-circle-item:hover .category-circle-name` |
| Avatar do usuário logado | `auth.css` → `.auth-avatar-logged` |
| Avatar do dropdown | `auth.css` → `.auth-dropdown-avatar` |
| Step ativo/completado do wizard | `auth.css` → `.wizard-step-indicator.active .wizard-step-dot` |
| Linha do step completado | `auth.css` → `.wizard-step-indicator.completed:not(:last-child)::after` |
| Asterisco de campo obrigatório | `auth.css` → `.auth-form label .required` |
| Hover do toggle de senha | `auth.css` → `.password-toggle:hover` |
| Tag checkbox selecionada | `auth.css` → `.checkbox-tag input:checked + label` |
| Botão primário do wizard | `auth.css` → `.wizard-nav .btn-primary` |
| Aba ativa do perfil | `auth.css` → `.profile-tab.active` |

---

### `--color-primary-dark` → `#ae031c` (Vermelho institucional)

**Propósito:** Vermelho institucional da marca e estado hover/ativo dos elementos primários.

| Onde é usada | Arquivo → Seletor |
|--------------|-------------------|
| Hover do botão da search bar | `layout.css` → `.search-box button:hover` |
| Hover do botão primário sólido | `components.css` → `.btn-primary-solid:hover` |
| Hover do botão primário do wizard | `auth.css` → `.wizard-nav .btn-primary:hover` |

---

### `--color-secondary` → `#f8f9fa` (Cinza muito claro)

**Propósito:** Fundo de seções alternadas e elementos neutros.

| Onde é usada | Arquivo → Seletor |
|--------------|-------------------|
| Fundo da navegação principal | `layout.css` → `.main-nav` |
| Fundo da seção de features | `layout.css` → `.features-section` |
| Fundo dos itens do carrinho | `components.css` → `.cart-item` |
| Header do dropdown de auth | `auth.css` → `.auth-dropdown-header` |
| Hover dos itens do dropdown | `auth.css` → `.auth-dropdown-item:hover` |

---

### `--color-dark` → `#212529` (Quase preto)

**Propósito:** Texto principal, títulos e elementos escuros.

| Onde é usada | Arquivo → Seletor |
|--------------|-------------------|
| Cor do texto do body | `base.css` → `body` |
| Ícones do header (conta, favoritos, carrinho) | `layout.css` → `.header-actions a` |
| Links da navegação | `layout.css` → `.main-nav .nav-link` |
| Títulos de seção | `layout.css` → `.section-header h2` |
| Fundo do botão da newsletter | `layout.css` → `.newsletter-form button` |
| Títulos dos produtos | `components.css` → `.product-title` |
| Ícones de ação do produto | `components.css` → `.product-actions button` |
| Botões do carrossel | `components.css` → `.carousel-btn` |
| Nomes das categorias no grid | `components.css` → `.category-circle-item` |
| Botão de fechar carrinho | `components.css` → `.cart-close` |
| Título do card de auth | `auth.css` → `.auth-card-header h1` |
| Nome no dropdown | `auth.css` → `.auth-dropdown-info strong` |
| Itens do dropdown | `auth.css` → `.auth-dropdown-item` |
| Labels dos formulários | `auth.css` → `.auth-form label` |
| Botão secundário do wizard | `auth.css` → `.wizard-nav .btn-secondary` |

---

### `--color-gray` → `#6c757d` (Cinza médio)

**Propósito:** Texto secundário, descrições, preços originais, informações auxiliares.

| Onde é usada | Arquivo → Seletor |
|--------------|-------------------|
| Descrições das features | `layout.css` → `.feature-box p` |
| Subtítulos de seção | `layout.css` → `.section-header p` |
| Categoria do produto | `components.css` → `.product-category` |
| Preço original (riscado) | `components.css` → `.price-original` |
| Carrinho vazio | `components.css` → `.cart-empty` |
| Email no dropdown | `auth.css` → `.auth-dropdown-info span` |
| Labels do wizard | `auth.css` → `.wizard-step-label` |
| Toggle de senha | `auth.css` → `.password-toggle` |
| Texto do footer de auth | `auth.css` → `.auth-footer` |
| Texto auxiliar dos formulários | `auth.css` → `.form-text` |
| Abas inativas do perfil | `auth.css` → `.profile-tab` |

---

### `--color-light-gray` → `#e9ecef` (Cinza claro)

**Propósito:** Bordas, divisores, fundos de estados vazios, tracks.

| Onde é usada | Arquivo → Seletor |
|--------------|-------------------|
| Borda superior da nav | `layout.css` → `.main-nav` |
| Borda do input de busca | `layout.css` → `.search-box input` |
| Divisor do header do carrinho | `components.css` → `.cart-header` |
| Divisor do footer do carrinho | `components.css` → `.cart-footer` |
| Borda dos botões do carrossel | `components.css` → `.carousel-btn` |
| Fundo dos dots do carrossel | `components.css` → `.carousel-dot` |
| Divisor do dropdown | `auth.css` → `.auth-dropdown-divider` |
| Borda dos inputs de formulário | `auth.css` → `.auth-form .form-control` |
| Barra de força da senha | `auth.css` → `.password-strength-bar` |
| Divisor da nav do wizard | `auth.css` → `.wizard-nav` |
| Divisor do footer de auth | `auth.css` → `.auth-footer` |
| Borda das abas do perfil | `auth.css` → `.profile-tabs` |

---

## 🔧 Cores Hardcoded (Hex direto no CSS/HTML)

### Cores de fundo e texto

| Cor | Onde | Propósito |
|-----|------|-----------|
| `#ffffff` | `body`, `.main-header`, `.product-card`, dropdown de auth | Fundo branco puro para páginas, cards e componentes |

### Cores de suporte/semânticas

| Cor | Onde | Propósito |
|-----|------|-----------|
| `#28a745` | Badge de desconto (`.price-discount`) | Verde — indica economia/desconto |
| `#dc3545` | Botão remover do carrinho, erro de validação | Vermelho — ação destrutiva/erro |
| `#a71d2a` | Hover do botão remover | Vermelho escuro |
| `#25d366` | Botão "Finalizar no WhatsApp", ícone do toast | Verde WhatsApp |
| `#128c7e` | Hover do botão WhatsApp | Verde WhatsApp escuro |

### Sombras e overlays

| Valor | Onde | Propósito |
|-------|------|-----------|
| `rgba(0,0,0,0.1)` | Sombra do header | Elevação sutil |
| `rgba(0,0,0,0.6)` / `rgba(0,0,0,0.2)` | Overlay do hero banner | Gradiente escuro para legibilidade do texto |
| `rgba(0,0,0,0.08~0.15)` | Sombras dos cards | Elevação dos product cards |
| `rgba(255,255,255,0.5~0.7)` | Footer — links e copyright | Texto semi-transparente no footer escuro |

### Gradientes

| Gradiente | Onde | Propósito |
|-----------|------|-----------|
| `linear-gradient(135deg, #fff5f5 0%, #ffffff 50%, #f8f9fa 100%)` | Página de auth (`.auth-page`) | Fundo sutil rosa/branco/cinza para login/registro |

---

## Paleta do Dark Mode (`css/dark-mode.css`)

Quando `data-theme="dark"` está ativo no `<html>`:

| Elemento | Cor no Dark Mode |
|----------|-----------------|
| Fundo do body | `#0f0f0f` |
| Header principal | `#1a1a1a` |
| Cards de produto | `#1e1e1e` |
| Inputs de formulário | `#252525` |
| Bordas de cards/inputs | `#2a2a2a` / `#3a3a3a` |
| Texto principal | `#f0f0f0` |
| Texto secundário | `#a0a0a0` |
| Placeholder dos inputs | `#888` |
| Hover dos links | Mantém `--color-primary` (`#d1001f`) |

---

## Bootstrap

- **Versão:** 5.3.3 via CDN
- **Customização de tema:** **NENHUMA** — não há override de variáveis Bootstrap (`--bs-primary`, `--bs-secondary`, etc.)
- O visual da marca é feito inteiramente pelas classes customizadas e pelas 6 variáveis `--color-*` definidas em `base.css`
- Classes utilitárias do Bootstrap são usadas extensivamente (`container`, `row`, `col-*`, `d-none`, `text-center`, etc.)

---

## Resumo Visual da Paleta

```
┌─────────────────────────────────────────────────────────┐
│  PRIMARY        #d1001f  ████  Vermelho da marca        │
│  PRIMARY-DARK   #ae031c  ████  Hover primário           │
│  SECONDARY      #f8f9fa  ████  Fundo de seções neutras  │
│  DARK           #212529  ████  Texto principal          │
│  GRAY           #6c757d  ████  Texto secundário         │
│  LIGHT-GRAY     #e9ecef  ████  Bordas e divisores       │
├─────────────────────────────────────────────────────────┤
│  SUCESSO        #28a745  ████  Desconto / Válido        │
│  ERRO           #dc3545  ████  Remover / Inválido       │
│  WHATSAPP       #25d366  ████  Checkout WhatsApp        │
├─────────────────────────────────────────────────────────┤
│  DARK MODE BODY #0f0f0f  ████  Fundo escuro             │
│  DARK CARD      #1e1e1e  ████  Cards escuros            │
│  DARK INPUT     #252525  ████  Inputs escuros           │
└─────────────────────────────────────────────────────────┘
```

---


## Logo

<img src="./img/logoBiaModas.png" alt="Logo da Bia Modas" width="180">

A logo combina a letra **B** com a silhueta de uma maçã e uma folha verde. O vermelho **#AE031C** reforça a identidade da marca, enquanto o verde da folha acrescenta frescor e conexão com o universo natural. A forma arredondada e a leitura simples favorecem a aplicação em diferentes tamanhos e suportes.

# 10. Protótipo

**Protótipo (Figma):** [Bia Modas no Figma](https://www.figma.com/design/2RQ8GsM3Q9YBE8vV1GWRhu/Bia-Modas?node-id=0-1&t=GSP5pfwupi3Na32n-1)

Telas contempladas:

- Home: hero banner, categorias, produtos em destaque e newsletter.
- Catálogo: busca, filtros, ordenação e cards de produto.
- Produto: galeria, descrição, medidas, guia de tamanho, recomendação e avaliações.
- Carrinho e checkout: itens, frete, descontos, pagamento e confirmação.
- Conta: login, cadastro, perfil, favoritos e histórico de pedidos.
- Administração: dashboard, produtos, usuários, estoque e promoções.

# 11. Aplicação

**Site:** [biamodasjau.com.br](https://biamodasjau.com.br/)

**Repositório:** [github.com/orgs/Syntax-Softwares](https://github.com/orgs/Syntax-Softwares)

A aplicação utiliza **PHP 8.4 + Apache** com arquitetura **MVC**, banco **MariaDB 11** acessado via **PDO** (prepared statements) e ambiente **Docker/Docker Compose**. O front-end usa HTML5, CSS3, JavaScript e Bootstrap 5.

## 11.1 Como executar localmente

A versão com back-end PHP, schema e Docker está na branch **`integracao_backend`** (a `main` mantém o front-end/visual).

```bash
git clone https://github.com/Syntax-Softwares/Bia-Modas.git
cd Bia-Modas
git checkout integracao_backend
docker compose up -d --build
```

Acesse **http://localhost:8080**. O banco é inicializado pelo `db.sql` na primeira subida. Para recriar o banco do zero:

```bash
docker compose down -v
docker compose up -d --build
```

As credenciais de desenvolvimento estão definidas no `docker-compose.yml`.

## 11.2 Módulos e branches

| Módulo | Branch / status |
|---|---|
| Front-end visual (HTML/CSS/JS) | `main` |
| Base PHP/MVC + schema + Docker | `integracao_backend` |
| Perfil, endereços e segurança | `implementacao-perfil-e-usuario` |
| Catálogo, busca e filtros | `implementar-catalogo-busca-e-filtros` |
| Carrinho e favoritos | `mazzi/carrinhos_e_favoritos` |
| Checkout, pedidos e pagamentos | `mazzi/checkout_pedidos_e_pagamentos` |
| Recuperação de senha | `luiz/recuperacao-senha` |
| Provador virtual | `feature/provador-virtual-pagina` |
| Responsividade mobile | `melhorias/responsividade-mobile` |

Os módulos são integrados na branch `integracao_backend` antes do merge final.

## 11.3 Segurança

- PDO com **prepared statements** e `ATTR_EMULATE_PREPARES = false`.
- **CSRF** em formulários, sessões com `HttpOnly`, `SameSite=Lax` e regeneração de ID.
- **Rate limit** por IP/usuário e bloqueio de tentativas de senha.
- Senhas com **bcrypt** (`password_hash` / `password_verify`).
- Cabeçalhos **CSP**, `X-Frame-Options`, `nosniff` e `Cache-Control` para páginas autenticadas.
- Escapes de saída (`Html::e` / `htmlspecialchars`) contra XSS.

## 11.4 Deploy

O deploy é automatizado por **GitHub Actions** para uma VPS:

- `main` → `/var/www/html/`
- `apresentacao-pi` → `/var/www/html/apresentacao/`

O workflow usa `rsync` via SSH e ignora `.git` e `.github`.
