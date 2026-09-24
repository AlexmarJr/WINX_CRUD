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

O Nuxt usa sessões com cookies do Laravel Sanctum. O cadastro cria um usuário no PostgreSQL, faz login automaticamente e abre a área autenticada. O login aceita a opção de manter a sessão ativa. A API oferece `POST /api/register`, `POST /api/login`, `POST /api/logout` e `GET /api/user`.

Para executar os testes de autenticação:

```sh
docker compose exec backend php artisan test --compact tests/Feature/AuthFlowTest.php
```

O CRUD de produtos, logs e Elasticsearch ainda não foram implementados.
