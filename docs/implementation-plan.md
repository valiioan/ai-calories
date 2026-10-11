# Plan de implementare

Etapa 0 este gata: monorepo, Sanctum configurat pentru SPA, MySQL prin Docker Compose, shell Vue și documentația. Contul, profilul, setările, jurnalul și chatul sunt legate de API. USDA și OpenAI sunt apelate doar de server. Modelul de chat este `gpt-5.4-mini`. Scrierile din chat trec printr-o propunere confirmată de utilizator.

Fiecare etapă următoare se livrează doar la cererea utilizatorului, cu teste pentru izolare și fără să lărgească scope-ul etapei. Regulile din [AGENTS.md](AGENTS.md) se aplică la toate etapele. Interfața urmează [UI.md](UI.md).

## Etapa 0 — inițializare

Făcut.

- `frontend/` Vue 3 + TypeScript + Vite, Tailwind CSS v4, Pinia, Vue Router, inițializare shadcn-vue.
- `backend/` Laravel 13, `install:api` cu Sanctum, `statefulApi()`, CORS cu credențiale, cheile OpenAI și USDA doar în configul de server.
- `docker-compose.yml` cu nginx, PHP-FPM, Node (Vite) și MySQL 8.4.
- Pașii de rulare sunt în `README.md`.
- Singura rută de aplicație este `GET /api/user`, din scheletul Sanctum. Nu are încă de unde să primească o sesiune de login.

## Etapa 1 — autentificare și izolare

- `POST /register`, `POST /login`, `POST /logout` cu guard-ul `web` și verificarea CSRF.
- Form request-uri, răspuns `{ id, first_name, last_name, email }`, parole hash-uite de cast-ul existent.
- Teste feature: login pe cookie, 401 fără sesiune la `GET /api/user`, logout invalidează sesiunea.
- Un test de izolare cu două utilizatori, pe un model mic de probă sau pe `weight_entries` dacă etapa 3 se face imediat după. Nu se trece mai departe cu o Policy ocolită.

## Etapa 2 — profil

- Migrare `profiles`, model, policy, `GET` și `PUT /api/profile`.
- Creare lazy a profilului gol.
- Validare pentru enum-uri și ținte numerice pozitive.

## Etapa 3 — greutate

- Migrare `weight_entries`, unic pe `(user_id, recorded_on)`.
- CRUD-ul din contract. Upsert dacă `recorded_on` există deja pentru același utilizator.
- Test: utilizatorul B primește 404 la id-ul utilizatorului A.

## Etapa 4 — USDA

- `UsdaClient` cu cheia din `config('services.usda.key')`.
- Căutare și detaliu, upsert în `foods`, normalizare la per 100 g.
- `GET /api/foods/search` și `GET /api/foods/{id}`, ambele autentificate.
- Fără cheie în răspuns, în frontend sau în log.
- Test cu HTTP fake, nu cu rețeaua USDA.

## Etapa 5 — mese, calorii IN

- Migrări `meals` și `meal_items`.
- Calcul de snapshot din `foods` pentru unitățile `g` și `serving`.
- Item manual când nu există `food_id`.
- Policy pe masă; itemii se autorizează prin masa părinte, cu binding scoped.
- Teste de calcul și de izolare.

## Etapa 6 — activitate, calorii OUT

- Migrare `activity_entries` și CRUD-ul din contract.
- `source` este `manual` pentru introducerea din formular. `estimated` rămâne rezervat agentului, tot prin același serviciu.

## Etapa 7 — rezumat

- Serviciu care adună IN, OUT, ținta și greutatea zilei. Fără tabel materializat.
- `GET /api/summary` pentru o zi și pentru un interval.
- Ziua fără date răspunde zerouri și `weight_kg: null`, nu 404.

## Etapa 8 — ecranele de jurnal

- Pagini Vue pentru login, profil, jurnal zilnic, greutate și activitate.
- Componente shadcn-vue adăugate prin CLI.
- Stare în Pinia, date reîncărcate din API.
- Verificare în browser pe fluxul: login, adăugare masă, adăugare greutate, reload, logout.

## Etapa 9 — agentul de chat

- Tabelele `conversations`, `messages`, `agent_actions`.
- Client Responses API în backend. Allowlist-ul de unelte din arhitectură, fără unealtă SQL și fără unelte de scriere.
- Aceleași servicii ca la etapele 3–7. Agentul pregătește propuneri; confirmarea utilizatorului scrie jurnalul.
- `POST /api/conversations/{conversation}/messages` execută uneltele de previzualizare, salvează auditul și întoarce mesajul asistentului.
- UI de chat care arată estimarea, confirmarea și anularea ultimei modificări.
- Teste: unealtă necunoscută respinsă; previzualizarea nu inserează masa; confirmarea și anularea rămân la utilizatorul sesiunii; cheile nu apar în răspuns.

## În afara acestui plan

Nu se adaugă în aceste etape: aplicație mobilă, tokenuri pentru terți, recomandări medicale, scanare de coduri de bare, sau un al doilea furnizor de modele. Acestea cer o decizie separată.
