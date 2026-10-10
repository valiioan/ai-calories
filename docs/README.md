# CalorAI

Aplicație web multi-user pentru calorii IN/OUT și greutate. Această etapă conține monorepo-ul, configurația de development și documentația. Funcționalitățile de produs nu sunt implementate.

Tot stack-ul local rulează în Docker:

| Serviciu | Container | Rol |
| --- | --- | --- |
| nginx | `calorai-nginx` | Intrarea unică, portul `8080` |
| php | `calorai-php` | Laravel 13, PHP 8.4, PHP-FPM |
| node | `calorai-node` | Vite pentru SPA-ul Vue 3 |
| mysql | `calorai-mysql` | MySQL 8.4 |

- [Arhitectură](architecture.md)
- [Plan de implementare](implementation-plan.md)
- [Convenții UI](UI.md)
- [Reguli pentru agenți](AGENTS.md)

## Cerințe

Docker Desktop, cu Docker Compose. PHP și Node nu trebuie instalate pe calculator ca să pornești aplicația.

## Pornire

1. Pornește Docker Desktop.
2. Din rădăcina repo-ului:

```powershell
cd D:\Work\CalorAI
docker compose up -d --build
```

Prima pornire construiește imaginea PHP, instalează dependențele npm și rulează migrările. Poate dura câteva minute. Nginx pornește după ce PHP și Vite răspund.

Dacă `backend/vendor` lipsește, containerul PHP rulează `composer install` înainte de PHP-FPM. La un volum nou, instalarea din frontend poate dura câteva minute. După ce `vendor` există, pornirile următoare sar peste Composer. Pentru dependențe PHP noi:

```powershell
docker compose exec php composer install
```

3. Deschide http://localhost:8080

- SPA și API: http://localhost:8080
- Healthcheck Laravel: http://localhost:8080/up

Nginx trimite `/api`, `/sanctum`, `/up`, `/login`, `/logout` și `/register` către PHP. Restul cererilor, inclusiv reîncărcarea Vite, merg către Node. Browserul folosește o singură origine, deci cookie-ul de sesiune Sanctum rămâne pe `localhost:8080`.

Numele afișat al aplicației este `APP_NAME` din `backend/.env`. Interfața, titlul paginii și emailurile îl citesc de acolo.

Completează `OPENAI_API_KEY` și `USDA_API_KEY` doar în `backend/.env`. Fișierul este ignorat de git. După modificare:

```powershell
docker compose up -d --force-recreate php
```

Cheia `APP_KEY` este deja generată în `.env`. Nu rula `key:generate` din nou.

Dacă portul 3306 este ocupat de MySQL din XAMPP, oprește serviciul XAMPP înainte de `docker compose up`. Nu conecta aplicația la MariaDB 10.1.

## Comenzi utile

```powershell
docker compose ps
docker compose logs -f php
docker compose logs -f node
docker compose exec php php artisan migrate
docker compose exec php php artisan test
docker compose stop
docker compose up -d
```

## Credențiale MySQL

Din containere, hostul bazei este `mysql`. De pe calculator, pentru un client grafic, hostul este `127.0.0.1`.

| Setare | În Docker | De pe calculator |
| --- | --- | --- |
| Host | `mysql` | `127.0.0.1` |
| Port | `3306` | `3306` |
| Bază | `calorai` | `calorai` |
| Utilizator | `calorai` | `calorai` |
| Parolă | `calorai` | `calorai` |
| Root | `root` | `root` |

Sunt valori doar pentru development, definite în `docker-compose.yml` și `backend/.env.example`.
