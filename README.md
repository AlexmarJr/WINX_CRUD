# EU
Quero deixar aqui algumas informações sobre como o projeto foi desenvolvido.
Sistema é um sistema multi-tenancy, acredito que seja algo assim que voces rodam sua aplicação principal tambem, preferi fazer pq faz mais sentido pra esse contexto de teste.
Utilizei a versao mais recente do laravel(13.3) e do PHP(8.5.algumacoisa), o front utilizei Vue com Nuxt e pinia para state, dito isso como o desafio focou mais no backend e nao citou nada do front, quero ser transparente aqui e dizer que nao prestei muita atencao ao como o front foi feito, entao usei IA pesado pra poder gerar as interfaces etc, fiz um guia padrao pro front, tipo utilizar componentes e composables, pinia para dados que forem consultados globalmente e algumas bibliotecas para utilizar, mas nao otimizei nem foquei no codigo no front, claro, se precisar de uma explicação é bem provavel que eu consiga explicar dando uma olhada no codigo. Pro Backend, eu diria que cerca de 90% do codigo foi gerado, mas o processo foi diferente, dei instrucoes claras e especificas sobre cada passo da aplicação, basicamente so utilizei a IA pra escrever codigo, entao toda a parte de estrutura, logica, arquitetura e abstrações eu que decidir, como por exemplo, a utlizicação de servicos e repositorios, filas, observers, policies middleware, toda parte do banco, schemas eu que decidi e escrevi. Agora sobre o elasticsearch aq, deixar bem claro que eu utilizei a bastante tempo, e nao cheguei a me aprofundar não, entao usei bastante a IA pra seguir na logica, unico detalhe do back foi justamente o elasticSearch. 
to subindo o .env.exemplo, mas tem 2 chaves que vão precisa completar, que é a do disparo de email e a api do chat de IA, vou mandar para o Alisson no linkedin, nao precisa se preocupar com a segurança, pq o de emial é de graca e a api ta limitada em 2 dollars, entao mesmo se vazar não tem problema, dito isso se eu subir aq direto no git o openrouter vai me chingar e excluir a chave, vou gerar alguns informacoes a baixo de como rodar a aplicação local, subir com o docker pra facilitar, mas é dificil eu dar 100% de ctza que vai funcionar so subindo o docker, mas vou subir em um vps tb caso tenha algum problema para rodar ela localmente.
Outro detalhe, subi o back e front no mesmo repositorio so pra ficar mais facil mesmo de rodar ai, em um cenario real o correto é separar em 2 repositorios, nada contra usar em 1 so tb.
tem uns factories rodando, mas como fiz um esquema multi tenancy, botei um script pra cirar produtos e categorias automaticmaente quando criar uma conta nova



# Winx

Base do desafio técnico: Laravel 13, Nuxt 4 com Pinia e PostgreSQL em Docker.

## Iniciar

Com Docker Desktop ativo:

```sh
docker compose up --build
```

- Laravel: http://localhost:8000
- Nuxt: http://localhost:3000
- PostgreSQL: localhost:5433 (banco `winx`, usuário `winx`, senha `winx_local_dev`)

O Compose instala dependências, cria `backend/.env` e a chave da aplicação quando necessário, e executa as migrations ao iniciar.

## Acesso

- Página inicial: http://localhost:3000/
- Cadastro: http://localhost:3000/register
- Login: http://localhost:3000/login
- Área autenticada: http://localhost:3000/dashboard

O Nuxt usa sessões com cookies do Laravel Sanctum. O cadastro cria a empresa e o usuário no PostgreSQL, adiciona 5 categorias e 50 produtos iniciais para essa empresa, faz login automaticamente e abre a área autenticada. O login aceita a opção de manter a sessão ativa. A API é versionada em `/api/v1` e oferece `POST /api/v1/register`, `POST /api/v1/login`, `POST /api/v1/logout` e `GET /api/v1/user`.

As rotas autenticadas incluem o CRUD REST de `/api/v1/products` e `/api/v1/categories`, além da listagem e do detalhe de usuários da empresa em `/api/v1/users`. A listagem de produtos aceita `search`, `category_id`, `status`, `availability` (`in_stock` ou `out_of_stock`), `min_price`, `max_price`, `per_page`, `sort_by` e `sort_dir`. Os limites de preço são inclusivos, em reais; `max_price` deve ser maior ou igual a `min_price` quando ambos forem enviados.

## Busca de produtos

O Compose inicia um Elasticsearch 9.5.4 de nó único, acessível apenas pela rede interna dos containers. A busca com `search` em `GET /api/v1/products` usa nome, descrição e categoria, ordena por relevância quando não há ordenação explícita e respeita empresa, categoria, status e faixa de preço. `GET /api/v1/products/suggestions?q=mo` retorna até cinco sugestões de nomes da empresa autenticada. Sem `search`, a listagem continua usando PostgreSQL.

Após subir o ambiente pela primeira vez, indexe os produtos que já existem no banco:

```sh
docker compose exec backend php artisan products:reindex
```

O comando reconstrói o índice a partir do PostgreSQL. Novas criações, atualizações e exclusões entram na fila `search` automaticamente. A fila `default` grava os logs de alterações; o serviço `queue` processa ambas. A busca pode levar alguns segundos para refletir uma alteração.

Para usar o backend sem Elasticsearch, defina `SEARCH_DRIVER=database` e rode Laravel fora do Compose, ou ajuste essa variável nos serviços `backend` e `queue` do Compose.

## Testes

```sh
docker compose exec backend php artisan test --compact
```

Elasticsearch é uma instância local de desenvolvimento; a configuração do Compose não expõe sua porta nem ativa autenticação. Para produção, configure um cluster protegido e `ELASTICSEARCH_URL`/`ELASTICSEARCH_API_KEY`.
