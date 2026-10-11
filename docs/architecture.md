# Arhitectură

CalorAI este un monorepo pornit integral din Docker Compose. Browserul vorbește doar cu nginx. Nginx trimite API-ul la PHP-FPM și SPA-ul la Vite. Laravel este singurul proces care deschide MySQL, OpenAI și USDA.

```text
browser
  └─ nginx :8080
       ├─ /api, /sanctum, /up, /login, /logout, /register → PHP-FPM (Laravel 13)
       └─ restul, inclusiv HMR → Node (Vite)
            └─ MySQL 8.4
```

În development, browserul vede o singură origine, `http://localhost:8080`. Cookie-ul de sesiune nu trece prin JavaScript. CORS din `backend/config/cors.php` acceptă credențiale doar de la originile din `FRONTEND_URL`.

## Autentificare

Prima parte folosește Sanctum în mod SPA, nu bearer token.

1. `GET /sanctum/csrf-cookie` pune cookie-ul `XSRF-TOKEN`.
2. `POST /login` autentifică guard-ul `web` și pornește sesiunea.
3. Cererea următoare către `/api/*` trimite cookie-ul de sesiune și headerul `X-XSRF-TOKEN`.
4. `statefulApi()` în `backend/bootstrap/app.php` tratează cererile din domeniile stateful ca sesiuni, nu ca tokenuri.

Domeniile stateful locale sunt `localhost:8080` și `127.0.0.1:8080`. `SESSION_DOMAIN` rămâne gol pe localhost. `SameSite` este `lax`.

`GET /api/user` există deja, din scheletul `install:api`, și cere `auth:sanctum`. Register, login și logout sunt contracte pentru etapa următoare; rutele nu sunt implementate.

Tokenurile Sanctum (`personal_access_tokens`) nu sunt folosite de SPA. Tabelul este migrat odată cu pachetul, pentru un client viitor, dar agentul nu emite tokenuri către UI.

## Izolare

Datele de jurnal aparțin unui singur `user_id`.

- Middleware `auth:sanctum` pe toate rutele din `routes/api.php`, cu excepția celor publice de autentificare care stau în afara grupului.
- Relații Eloquent pornite din utilizatorul autentificat.
- Policy pentru fiecare model deținut. Autorizarea eșuată pe un id străin se mapează la 404, ca să nu confirme existența resursei.
- Binding scoped pe resursele copil (`meal_items` prin `meals`).
- Catalogul `foods` este global și read-only pentru utilizatori. Doar serviciul USDA îl scrie.
- Agentul nu primește conexiunea la bază și nu are unealtă SQL. Argumentele unei unelte sunt validate, apoi executate de un serviciu PHP care aplică aceleași Policy-uri.

## Modele

Unități canonice: energie în kcal, masă în grame, greutate corporală în kilograme, timp stocat în UTC. `profiles.timezone` (implicit `Europe/Bucharest`) stabilește limitele zilei pentru „azi”, „ieri” și filtrele de jurnal. Snapshot-urile nutriționale de pe `meal_items` nu se recalculează când cache-ul USDA se actualizează. `energy_out_kcal` este suma activităților înregistrate, nu o estimare de întreținere.

### users

Tabelul `users`: `id`, `first_name` (prenume), `last_name` (nume), `email`, `email_verified_at`, `password`, `remember_token`, `timestamps`.

### profiles

Un rând per utilizator.

| Coloană | Tip | Note |
| --- | --- | --- |
| user_id | FK unique | proprietar |
| birth_date | date, null | |
| sex | enum, null | `female`, `male`, `other`, `unspecified` |
| height_cm | decimal(5,2), null | |
| activity_level | enum, null | `sedentary`, `light`, `moderate`, `active`, `very_active` |
| goal | enum | `lose`, `maintain`, `gain`; implicit `maintain` |
| calorie_target | unsigned int, null | kcal / zi |
| protein_target_g | decimal(6,1), null | |
| carbohydrate_target_g | decimal(6,1), null | |
| fat_target_g | decimal(6,1), null | |
| target_weight_kg | decimal(5,2), null | obiectivul afișat în profil |
| timezone | string | implicit `Europe/Bucharest`; definește ziua locală |

### user_settings

Un rând per utilizator. Preferințele de interfață, nu datele nutriționale.

| Coloană | Tip | Note |
| --- | --- | --- |
| user_id | FK unique | proprietar |
| locale | string | `ro` sau `en`; implicit `ro` |
| appearance | enum | `light`, `dark`, `system`; implicit `light` |
| units | enum | `metric` sau `imperial`; implicit `metric` |
| notify_meals | boolean | implicit adevărat; nu trimite notificări încă |
| notify_weight | boolean | implicit fals |
| notify_weekly | boolean | implicit adevărat |

### weight_entries

| Coloană | Tip | Note |
| --- | --- | --- |
| user_id | FK | index |
| recorded_on | date | unic împreună cu `user_id` |
| weight_kg | decimal(5,2) | |
| note | string, null | |

### foods

Cache USDA, fără `user_id`. Valorile macro sunt per 100 g.

| Coloană | Tip | Note |
| --- | --- | --- |
| fdc_id | unsigned bigint, unique | id FoodData Central |
| description | string | |
| data_type | string | |
| brand_owner | string, null | |
| brand_name | string, null | |
| serving_size | decimal(8,2), null | |
| serving_unit | string, null | |
| household_serving | string, null | |
| gram_weight | decimal(8,2), null | grame pentru o porție |
| energy_kcal | decimal(8,2), null | per 100 g |
| protein_g | decimal(8,2), null | per 100 g |
| carbohydrate_g | decimal(8,2), null | per 100 g |
| fat_g | decimal(8,2), null | per 100 g |
| fiber_g | decimal(8,2), null | per 100 g |
| fetched_at | timestamp | |

### meals

| Coloană | Tip | Note |
| --- | --- | --- |
| user_id | FK | |
| eaten_at | datetime | UTC |
| meal_type | enum | `breakfast`, `lunch`, `dinner`, `snack` |
| note | string, null | |
| original_text | text, null | textul utilizatorului |
| assumptions | text, null | ipotezele estimării |

### meal_items

| Coloană | Tip | Note |
| --- | --- | --- |
| meal_id | FK | ștergere în cascadă |
| food_id | FK, null | null pentru aliment introdus manual |
| custom_name | string, null | obligatoriu dacă `food_id` este null |
| quantity | decimal(8,2) | |
| unit | string | `g`, `ml` sau `serving` |
| grams | decimal(8,2) | |
| energy_kcal | decimal(8,2) | snapshot |
| protein_g | decimal(8,2) | snapshot |
| carbohydrate_g | decimal(8,2) | snapshot |
| fat_g | decimal(8,2) | snapshot |
| fiber_g | decimal(8,2) | snapshot |

### activity_entries

Calorii OUT.

| Coloană | Tip | Note |
| --- | --- | --- |
| user_id | FK | |
| performed_at | datetime | UTC |
| name | string | |
| duration_minutes | unsigned int, null | |
| energy_kcal | decimal(8,2) | |
| intensity | enum, null | `low`, `moderate`, `high` |
| note | string, null | |
| source | enum | `manual`, `estimated` |

### conversations, messages, agent_actions

| Tabel | Coloane esențiale |
| --- | --- |
| conversations | `user_id`, `title` null |
| messages | `conversation_id`, `role` (`user` sau `assistant`), `content`, `tool_calls` json null |
| agent_actions | `user_id`, `conversation_id` null, `tool_name`, `arguments` json, `result` json null, `status` (`succeeded`, `failed`, `rejected`) |

`agent_actions` este jurnal de audit. Clientul nu îl scrie direct.

## Servicii externe

Configurația este în `config/services.php`.

- OpenAI: `POST {OPENAI_BASE_URL}/responses`, model din `OPENAI_MODEL`. Backend-ul trimite uneltele de mai jos și execută doar apelurile din allowlist.
- USDA: `GET {USDA_BASE_URL}/v1/foods/search` și `GET {USDA_BASE_URL}/v1/food/{fdcId}`, cu `api_key` din `USDA_API_KEY`. Rezultatul se upsert-uiește în `foods` după `fdc_id`.

Nutrienții USDA folosiți la normalizare: Energy (kcal) `208`, Protein `203`, Carbohydrate `205`, Total lipid (fat) `204`, Fiber `291`. Dacă un aliment nu are un nutrient, câmpul rămâne null și itemul de jurnal nu inventează valoarea.

## Unelte pentru agent

Modelul poate cere doar aceste funcții. Fiecare primește utilizatorul din sesiune, nu un `user_id` din argumente.

| Unealtă | Argumente | Efect |
| --- | --- | --- |
| `search_food` | `query`, `page` | caută USDA, scrie cache, întoarce lista. Interogarea este în engleză |
| `preview_meal` | data locală, ora, tipul, itemii, textul original | creează o propunere, fără masă în jurnal |
| `preview_update` | `meal_id` și câmpurile noi | propunere de modificare pentru masa proprietarului |
| `preview_delete` | `meal_id` | propunere de ștergere; masa rămâne până la confirmare |
| `preview_activity` | data, numele, durata, caloriile | propunere de activitate |
| `preview_weight` | data, kilogramele | propunere de greutate |
| `get_report` | `date` sau `from`/`to` | citește istoricul sesiunii |

Modelul nu salvează, nu modifică și nu șterge. Serverul execută scrierea când utilizatorul apasă Confirmă sau trimite o confirmare scurtă (`da`, `confirmă`, `salvează`) pentru o propunere deja în așteptare. Aceeași tură nu poate și propune, și confirma. `save_meal`, `update_meal` și `delete_meal` nu sunt unelte ale modelului; un nume din afara allowlist-ului este respins și scris în `agent_actions` cu status `rejected`. Nu se adaugă unelte generice de bază de date.

## Contract API

JSON. Datele calendaristice sunt `YYYY-MM-DD`. Timestamps sunt ISO-8601. Erorile de validare sunt 422, în forma Laravel `{ message, errors }`. Neautentificat: 401. Resursă străină sau inexistentă: 404.

### Aplicație

```http
GET /api/app
```

Răspunde `{ name }`. Valoarea este `APP_NAME` din `backend/.env`. Nu cere autentificare.

### Auth

```http
GET  /sanctum/csrf-cookie
POST /register     { first_name, last_name, email, password, password_confirmation }
POST /login        { email, password, remember? }
POST /logout
POST /forgot-password { email }
POST /reset-password  { token, email, password, password_confirmation }
GET  /api/user
PUT  /api/user     { first_name?, last_name?, email? }
```

`GET /api/user` răspunde `{ id, first_name, last_name, email }`. `first_name` este prenumele, `last_name` este numele. Register, login și resetarea reușită a parolei răspund același obiect după ce sesiunea este deschisă. `remember` lipsește sau este adevărat: se păstrează și cookie-ul remember. Sesiunea și cookie-ul remember expiră după 30 de zile de inactivitate. Cookie-ul de sesiune este HttpOnly și nu se pune un token în `localStorage`.

`POST /forgot-password` răspunde mereu același mesaj, ca să nu confirme dacă adresa există. Linkul din email deschide `/reset-password` în SPA. În mediul local, cu debug pornit, răspunsul poate include `reset_url` relativ.

### Profil

```http
GET /api/profile
PUT /api/profile
```

Corpul PUT acceptă oricare dintre câmpurile modelului `profiles`, în afară de `user_id`. Dacă profilul lipsește, GET îl creează gol pentru utilizatorul curent. Numele și emailul se actualizează prin `PUT /api/user`.

### Setări

```http
GET /api/settings
PUT /api/settings
```

Corpul PUT acceptă oricare dintre câmpurile `user_settings`, în afară de `user_id`. Dacă rândul lipsește, GET îl creează cu valorile implicite.

### Greutate

```http
GET    /api/weights?from=YYYY-MM-DD&to=YYYY-MM-DD
POST   /api/weights          { recorded_on, weight_kg, note? }
PUT    /api/weights/{id}     { recorded_on?, weight_kg?, note? }
DELETE /api/weights/{id}
```

```json
{ "id": 1, "recorded_on": "2026-10-10", "weight_kg": "72.40", "note": null }
```

### Alimente

```http
GET /api/foods/search?q=mar&page=1
GET /api/foods/{id}
```

```json
{
  "id": 10,
  "fdc_id": 171477,
  "description": "Milk, reduced fat",
  "energy_kcal_per_100g": "50.00",
  "protein_g_per_100g": "3.30",
  "carbohydrate_g_per_100g": "4.80",
  "fat_g_per_100g": "1.90",
  "fiber_g_per_100g": "0.00",
  "serving": { "size": "244.00", "unit": "ml", "gram_weight": "244.00", "household": "1 cup" }
}
```

Căutarea răspunde `{ data: Food[], meta: { page, per_page } }`.

### Mese

```http
GET    /api/meals?date=YYYY-MM-DD
POST   /api/meals
GET    /api/meals/{meal}
PUT    /api/meals/{meal}
DELETE /api/meals/{meal}
POST   /api/meals/{meal}/items
PUT    /api/meal-items/{mealItem}
DELETE /api/meal-items/{mealItem}
```

```json
{
  "id": 3,
  "eaten_at": "2026-10-10T07:30:00Z",
  "meal_type": "breakfast",
  "note": null,
  "energy_kcal": "420.00",
  "items": [
    {
      "id": 9,
      "food_id": 10,
      "custom_name": null,
      "quantity": "1.00",
      "unit": "serving",
      "grams": "244.00",
      "energy_kcal": "122.00",
      "protein_g": "8.05",
      "carbohydrate_g": "11.71",
      "fat_g": "4.64",
      "fiber_g": "0.00"
    }
  ]
}
```

`energy_kcal` de pe masă este suma itemilor. Itemul manual cere `custom_name` și macro-urile; itemul USDA cere `food_id`, `quantity` și `unit`, iar serverul calculează snapshot-ul.

### Activitate

```http
GET    /api/activities?date=YYYY-MM-DD
POST   /api/activities     { performed_at, name, duration_minutes?, energy_kcal }
PUT    /api/activities/{id}
DELETE /api/activities/{id}
```

### Rezumat

```http
GET /api/summary?date=YYYY-MM-DD
GET /api/summary?from=YYYY-MM-DD&to=YYYY-MM-DD
```

```json
{
  "date": "2026-10-10",
  "calorie_target": 2200,
  "energy_in_kcal": "1680.00",
  "energy_out_kcal": "320.00",
  "net_kcal": "1360.00",
  "weight_kg": "72.40"
}
```

Intervalul răspunde `{ data: DaySummary[] }`. `net_kcal` este IN minus OUT. Nu există tabel `daily_summaries` în prima versiune; valorile se calculează la citire.

### Chat

```http
GET  /api/conversations
POST /api/conversations                 { title? }
GET  /api/conversations/{conversation}
POST /api/conversations/{conversation}/messages    { content }
```

```json
{
  "user_message": { "id": 1, "role": "user", "content": "Am mâncat un iaurt la micul dejun" },
  "assistant_message": { "id": 2, "role": "assistant", "content": "Am pregătit estimarea." },
  "proposal": { "id": 4, "kind": "meal", "status": "pending" },
  "actions": [
    { "tool_name": "preview_meal", "status": "succeeded" }
  ],
  "retry": false
}
```

Propunerea nu scrie în jurnal. `POST /api/proposals/{proposal}/confirm` o salvează și întoarce `revision_id`. `POST /api/journal-revisions/{revision}/undo` anulează ultima modificare a acelei înregistrări. O zi fără date răspunde cu zerouri, nu cu 404. `energy_out_kcal` este suma activităților înregistrate.

## Frontend

- Router: o rută `/` în această etapă. Rutele de produs (`/login`, `/journal`, `/weight`, `/chat`) se adaugă în etapele din plan.
- Pinia ține sesiunea și datele de ecran. Serverul rămâne sursa de adevăr.
- Componentele vizuale vin din shadcn-vue și folosesc tokeni semantici (`bg-background`, `text-muted-foreground`), nu culori directe. Regulile de ecran sunt în [UI.md](UI.md).
- `frontend/src/lib/api.ts` este clientul HTTP. Nu se adaugă un al doilea client cu chei încorporate.
