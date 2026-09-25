# EU
Quero deixar aqui algumas informações sobre como o projeto foi desenvolvido.
Sistema é um sistema multi-tenancy, acredito que seja algo assim que voces rodam sua aplicação principal tambem, preferi fazer pq faz mais sentido pra esse contexto de teste.
Utilizei a versao mais recente do laravel(13.3) e do PHP(8.5.algumacoisa), o front utilizei Vue com Nuxt e pinia para state, dito isso como o desafio focou mais no backend e nao citou nada do front, quero ser transparente aqui e dizer que nao prestei muita atencao ao como o front foi feito, entao usei IA pesado pra poder gerar as interfaces etc, fiz um guia padrao pro front, tipo utilizar componentes e composables, pinia para dados que forem consultados globalmente e algumas bibliotecas para utilizar, mas nao otimizei nem foquei no codigo no front, claro, se precisar de uma explicação é bem provavel que eu consiga explicar dando uma olhada no codigo. Pro Backend, eu diria que cerca de 90% do codigo foi gerado, mas o processo foi diferente, dei instrucoes claras e especificas sobre cada passo da aplicação, basicamente so utilizei a IA pra escrever codigo, entao toda a parte de estrutura, logica, arquitetura e abstrações eu que decidir, como por exemplo, a utlizicação de servicos e repositorios, filas, observers, policies middleware, toda parte do banco, schemas eu que decidi e escrevi. Agora sobre o elasticsearch aq, deixar bem claro que eu utilizei a bastante tempo, e nao cheguei a me aprofundar não, entao usei bastante a IA pra seguir na logica, unico detalhe do back foi justamente o elasticSearch. 
to subindo o .env.exemplo, mas tem 2 chaves que vão precisa completar, que é a do disparo de email e a api do chat de IA, vou mandar para o Alisson no linkedin, nao precisa se preocupar com a segurança, pq o de emial é de graca e a api ta limitada em 2 dollars, entao mesmo se vazar não tem problema, dito isso se eu subir aq direto no git o openrouter vai me chingar e excluir a chave, vou gerar alguns informacoes a baixo de como rodar a aplicação local, subir com o docker pra facilitar, mas é dificil eu dar 100% de ctza que vai funcionar so subindo o docker, mas vou subir em um vps tb caso tenha algum problema para rodar ela localmente.
Outro detalhe, subi o back e front no mesmo repositorio so pra ficar mais facil mesmo de rodar ai, em um cenario real o correto é separar em 2 repositorios, nada contra usar em 1 so tb.
tem uns factories rodando, mas como fiz um esquema multi tenancy, botei um script pra cirar produtos e categorias automaticmaente quando criar uma conta nova



# Winx

Implementação do desafio técnico de gerenciamento de produtos. A API usa Laravel 13 com PHP 8.4 e PostgreSQL; a interface usa Nuxt 4 e Pinia. O ambiente local também inicia Elasticsearch e um worker de filas.

## Executar localmente

É necessário ter Docker com Compose e as portas 3000, 8000 e 5433 livres. Na raiz do repositório:

```sh
docker compose up --build
```

O Compose instala as dependências, cria `backend/.env` a partir do exemplo se necessário, gera `APP_KEY`, executa as migrations e inicia backend, frontend, PostgreSQL, Elasticsearch e worker. Aguarde os serviços terminarem de inicializar.

| Serviço | Endereço local |
| --- | --- |
| Frontend | http://localhost:3000 |
| API Laravel | http://localhost:8000 |
| PostgreSQL | localhost:5433 (banco e usuário `winx`) |

O Dockerfile do backend usa PHP 8.4; a versão do Laravel é fixada pelo `backend/composer.lock`. Os servidores `artisan serve` e `nuxt dev` do Compose são para desenvolvimento local.

### Variáveis de ambiente

O arquivo `.env.example` da raiz contém `APP_VERSION` (versão exibida na sidebar) e `APP_ENV`. Copie-o para `.env` se quiser alterar esses valores. Com `APP_ENV=local`, a área autenticada mostra um link para a aplicação publicada; em `production`, oculta esse link. O `.env` da raiz não vai para o Git.

Para configurar as integrações, copie `backend/.env.example` para `backend/.env` antes de iniciar os containers e preencha:

- `OPENROUTER_KEY` para habilitar o assistente de IA. Sem ela, essa funcionalidade retorna indisponibilidade.
- `BREVO_SMTP_LOGIN`, `BREVO_KEY` (chave SMTP) e `MAIL_FROM_ADDRESS` verificado para enviar convites e links de recuperação de senha. Sem credenciais, use `MAIL_MAILER=log` para registrar os emails no log local.
- `APP_URL` e `FRONTEND_URL` se acessar o projeto por outro endereço; os links enviados por email usam `FRONTEND_URL`.

As chaves permanecem somente no `backend/.env`, que é ignorado pelo Git. O Compose passa ao frontend apenas as variáveis públicas necessárias.

## Primeiro acesso

Abra http://localhost:3000/register. O cadastro cria uma empresa, seu usuário administrador, cinco categorias e 50 produtos iniciais vinculados à empresa. Em seguida, autentica o usuário. Também há login e recuperação de senha.

Para criar dados de demonstração sem passar pelo cadastro, execute o seeder padrão:

```sh
docker compose exec backend php artisan db:seed
```

Ele cria a empresa Winx Demo, o administrador `demo@winx.test` com senha `password123` e, usando o mesmo serviço do cadastro, cinco categorias e 50 produtos. Uma nova execução não duplica essa empresa. Essas credenciais são apenas para demonstração local; o Compose não executa o seeder automaticamente.

## API e funcionalidades

As rotas estão em `/api/v1`. A autenticação usa sessão e cookies do Laravel Sanctum; produtos, categorias, usuários, convites e dashboard ficam protegidos por `auth:sanctum` e pela verificação de conta ativa.

- Públicas: cadastro, login, solicitação e redefinição de senha, consulta e aceite de convite por token.
- Autenticadas: CRUD REST de produtos e categorias; listagem, detalhe, edição e exclusão de usuários conforme a policy; convites; alteração de email e senha do perfil; resumo do dashboard; chat de IA.
- Produtos: `GET /api/v1/products` aceita `search`, `category_id`, `status`, `availability` (`in_stock` ou `out_of_stock`), `min_price`, `max_price`, `page`, `per_page`, `sort_by` e `sort_dir`. `per_page` aceita de 1 a 100 itens. Os preços são informados em reais, com limites inclusivos.
- Detalhe completo: `GET /api/v1/products/{id}`. O catálogo também oferece `GET /api/v1/products/max-price` e `GET /api/v1/products/suggestions?q=mo`.

Cada consulta e alteração do catálogo é restrita à empresa do usuário autenticado. Produtos e categorias usam exclusão lógica. As validações de criação e edição usam Form Requests; os retornos do catálogo usam API Resources.

## Logs assíncronos e busca

Observers acompanham criação, atualização e exclusão de produtos e categorias. O Job `RecordInventoryLog` grava usuário, empresa, entidade e os campos alterados em `meta.old` e `meta.new`. O worker processa as filas `emails`, `search` e `default`.

Com `SEARCH_DRIVER=elasticsearch`, a busca textual de produtos consulta Elasticsearch para relevância, sugestões e busca por nome, descrição e categoria. Sem termo de busca, a listagem usa PostgreSQL. Alterações de produtos são indexadas pela fila `search`; por isso podem levar alguns segundos para aparecer na busca.

Em um banco novo, os produtos criados no cadastro são enviados à fila automaticamente. Execute a reindexação se restaurar um banco com produtos ou recriar o volume do Elasticsearch:

```sh
docker compose exec backend php artisan products:reindex
```

O Compose local desabilita a segurança do Elasticsearch e não expõe sua porta. Para rodar o backend sem ele, ajuste `SEARCH_DRIVER=database` nos serviços `backend` e `queue` do Compose, ou rode o Laravel fora do Compose.

## Testes

```sh
docker compose exec backend php artisan test --compact
```

A suíte usa SQLite em memória e cobre autenticação, isolamento entre empresas, CRUD, filtros, paginação, convites, usuários, logs assíncronos e busca. Os testes do Elasticsearch simulam as chamadas HTTP; o comando de reindexação acima permite verificar uma instância real.
