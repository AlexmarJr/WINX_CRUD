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

As rotas autenticadas incluem o CRUD REST de `/api/v1/products` e `/api/v1/categories`. A listagem de produtos aceita `search`, `category_id`, `status`, `availability` (`in_stock` ou `out_of_stock`), `min_price`, `max_price`, `per_page`, `sort_by` e `sort_dir`. Os limites de preço são inclusivos, em reais; `max_price` deve ser maior ou igual a `min_price` quando ambos forem enviados.

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
