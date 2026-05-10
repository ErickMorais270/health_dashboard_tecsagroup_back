# Health Dashboard — Backend (TecsaGroup MVP)

Laravel 12 API em PHP 8.4 com PostgreSQL, Sanctum e camadas explícitas (Controller → FormRequest → Service → Repository → Model). A integração com LLM fica isolada em `GenerateHealthRecommendationsAction`, com prompt de sistema orientado a um coach de saúde responsável.

## Links (produção — Render)

- **API (Back):** [https://health-dashboard-tecsagroup-back.onrender.com](https://health-dashboard-tecsagroup-back.onrender.com) — healthcheck JSON na raiz (`/`). Rotas da aplicação usam o prefixo **`/api`** (ex.: `GET /api/health`, `POST /api/login`).
- **Web App (Front):** [https://health-dashboard-tecsagroup-front.onrender.com](https://health-dashboard-tecsagroup-front.onrender.com)

## Stack

- PHP 8.4+, Laravel 12, PostgreSQL (SQLite em testes automatizados)
- Autenticação: [Laravel Sanctum](https://laravel.com/docs/sanctum) (tokens Bearer)
- IA: [Google Gemini](https://ai.google.dev/) (`GEMINI_API_KEY`, `GEMINI_MODEL`, `GEMINI_API_BASE`). Se a API falhar ou a chave estiver vazia, o app grava três recomendações padrão em português (fallback).

## Configuração rápida

```bash
cp .env.example .env
php artisan key:generate
# Ajuste DB_* para PostgreSQL e defina GEMINI_API_KEY (Google AI Studio)
php artisan migrate
php artisan serve
```

Rotas principais (prefixo `/api`): `POST /register`, `POST /login`, `GET /dashboard`, CRUD `/biomarkers`. Ao criar um biomarcador, a API gera e persiste três recomendações via IA.

## Testes

```bash
php artisan test
```

O fluxo em `tests/Feature/HealthDashboardFlowTest.php` usa `Http::fake()` para simular o Gemini e valida cadastro, criação de biomarcador, persistência das recomendações e resposta do dashboard.

## Erros JSON

Rotas `api/*` retornam JSON padronizado (`success`, `message`, `errors`) via `App\Http\Support\ApiExceptionRenderer` (ex.: `AiProviderException` onde ainda for lançada).

## Deploy (Docker / Render)

- **Dockerfile:** na raiz deste projeto (`health_dashboard_tecsagroup_back/`). Build local: `docker build -t health-api .`
- **Render:** ao usar um monorepo, defina **Root Directory** para esta pasta e informe o Dockerfile acima. **Port** `80`.
- **PostgreSQL (erro “connection to 127.0.0.1:5432 refused”):** dentro do Docker **não** existe Postgres em `localhost`. Você precisa das credenciais do **PostgreSQL gerenciado pelo Render**:
  - Crie um **PostgreSQL** no Render e **conecte** ao mesmo *team/workspace* que o Web Service **ou** copie a **Internal Database URL**.
  - No **Web Service** → **Environment**, defina obrigatoriamente:
    - `DB_CONNECTION=pgsql`
    - `DATABASE_URL` = URL interna do Postgres (`postgresql://...`), **ou** defina manualmente `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` com o hostname real (ex.: `dpg-xxxx.oregon-postgres.render.com`), **nunca** `127.0.0.1`/`localhost`.
  - Este projeto aceita **`DATABASE_URL`** na config `pgsql` (além de `DB_URL`), alinhado ao que o Render expõe ao vincular o banco.
  - Para conexões **externas** ao Postgres Render, pode ser necessário `DB_SSLMODE=require` (vide documentação Render).
- **Outras variáveis:** `APP_KEY` (`php artisan key:generate --show`), `APP_ENV=production`, `APP_DEBUG=false`, `GEMINI_*` se quiser IA real. O container roda `php artisan migrate --force` ao subir (*após* `config:cache`; use sempre as vars corretas no painel).
- **`.dockerignore`:** reduz o contexto de build (não envia `vendor/` local nem `.env`).

## Relatório de IA

O relatório consolidado—ferramentas utilizadas (Cursor: Composer, Agent, chat), contribuição no **backend** (camadas Laravel, Gemini, migrações, testes) e papel da revisão humana—está no **[README na raiz do monorepo](../README.md)** (`Relatório de IA`).
