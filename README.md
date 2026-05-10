# Health Dashboard — Backend (TecsaGroup MVP)

Laravel 12 API em PHP 8.4 com PostgreSQL, Sanctum e camadas explícitas (Controller → FormRequest → Service → Repository → Model). A integração com LLM fica isolada em `GenerateHealthRecommendationsAction`, com prompt de sistema orientado a um coach de saúde responsável.

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
- **Render:** ao usar um monorepo, defina **Root Directory** para esta pasta e informe o Dockerfile acima. **Port** `80` (ou mapeie a porta HTTP que o Render espera).
- **Variáveis de ambiente:** no painel do Render, configure pelo menos `APP_KEY` (gere com `php artisan key:generate --show` localmente), `APP_ENV=production`, `APP_DEBUG=false`, credenciais PostgreSQL (`DB_*` ou `DATABASE_URL`), `GEMINI_*` se quiser IA real, e `SESSION_DOMAIN`/URLs se necessário. O container executa `migrate --force` ao iniciar.
- **`.dockerignore`:** reduz o contexto de build (não envia `vendor/` local nem `.env`).

## Relatório de IA

O relatório consolidado—ferramentas utilizadas (Cursor: Composer, Agent, chat), contribuição no **backend** (camadas Laravel, Gemini, migrações, testes) e papel da revisão humana—está no **[README na raiz do monorepo](../README.md)** (`Relatório de IA`).
