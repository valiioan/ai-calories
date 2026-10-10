# AGENTS.md

Lucrezi la CalorAI, o aplicație web de urmărire a caloriilor, cu AI-ul ca mod principal de introducere a datelor și cu telefonul ca ecran de bază.

Sursa de adevăr pentru interfață este [UI.md](UI.md). Citește și [architecture.md](architecture.md) și [implementation-plan.md](implementation-plan.md) înainte de modificări.

## Faza curentă

Contul, profilul și setările se salvează prin API-ul Laravel, cu sesiune Sanctum în cookie HttpOnly, 30 de zile. Jurnalul, chatul, activitățile, greutatea și rapoartele rămân date locale până la o cerere explicită. Nu implementa USDA, OpenAI sau celelalte tabele decât dacă utilizatorul o cere.

Folosește date de demonstrație pentru ecranele care nu sunt încă legate de API. O acțiune din acele ecrane nu se prezintă ca salvată pe server.

## Stack obligatoriu

- Vue 3, Composition API
- TypeScript
- Vite
- Tailwind CSS v4
- shadcn-vue
- Vue Router
- Pinia
- Iconițe Lucide

Backend-ul este Laravel 13, PHP 8.3+, MySQL 8.4, Sanctum. OpenAI și USDA rămân doar pe server, când lucrul la ele este cerut explicit.

Interzis: React, Inertia ca UI principal, Supabase, Python, SQL scris sau executat de modelul AI, chei API în frontend sau în variabile `VITE_`.

## Reguli de design

- Mobilul este ținta. Lățimea de referință este 375 px și ecranul rămâne utilizabil de la 320 px.
- Se urmează [UI.md](UI.md) și referințele vizuale date de utilizator. Nu se inventează un alt aspect.
- Se refolosesc tokenii de temă și componentele shadcn-vue.
- Textele vizibile se scriu în română, limba implicită. Șirurile sunt pregătite să poată fi schimbate din Setări.
- Nu se adaugă pagini, intrări de navigație, culori sau componente majore care contrazic specificația aprobată.
- Asistentul AI rămâne experiența principală de introducere a datelor.
- Ecranele au stări clare de încărcare, de listă goală și de eroare.
- O decizie de design aprobată nu se schimbă în tăcere. Dacă lipsește sau este ambiguă, se scrie soluția propusă înainte de implementare.

## Mod de lucru

1. Citește documentația relevantă înainte de editare.
2. Identifică componentele de interfață atinse.
3. Implementează doar ce s-a cerut.
4. Rulează verificările disponibile și privește rezultatul la lățime de telefon și de desktop.
5. Raportează fișierele modificate și problemele rămase.

## Izolarea datelor

Aceste reguli se aplică când utilizatorul cere explicit lucru la backend. Nu se implementează în faza de interfață.

Fiecare înregistrare a unui utilizator este vizibilă doar proprietarului.

1. Rutele de date stau în `routes/api.php` și folosesc `auth:sanctum`.
2. Interogările pleacă de la utilizatorul autentificat (`$request->user()->weights()`), nu de la `Model::query()` fără filtru.
3. Fiecare model deținut de utilizator are Policy și `authorize()` în controller. Un id al altui utilizator răspunde 404.
4. Route model binding pentru resurse imbricate este scoped, ca un item de masă să nu poată fi deschis prin id-ul altui utilizator.
5. Uneltele agentului validează argumentele, apoi apelează aceleași servicii. Nu există unealtă de SQL, `DB::unprepared` pe text venit de la model, sau query construit din limbaj natural.

Catalogul USDA (`foods`) este date de referință comune. Scrierea lui o face doar clientul USDA din backend. Jurnalul utilizatorului păstrează un snapshot nutrițional, ca schimbările din catalog să nu rescrie istoricul.

## Secrete

`OPENAI_API_KEY` și `USDA_API_KEY` există doar în `backend/.env` și se citesc prin `config/services.php`. Nu le pune în `frontend/`, în răspunsuri JSON, în prompturi sau în loguri.

## Unde se scrie codul

- Controllere API: `backend/app/Http/Controllers/Api`
- Form request-uri: `backend/app/Http/Requests`
- Policy-uri: `backend/app/Policies`
- Client USDA: `backend/app/Services/Food`
- Client OpenAI și unelte: `backend/app/Services/Agent`
- Pagini: `frontend/src/views`
- Stare client: `frontend/src/stores`
- Componente shadcn-vue: `frontend/src/components/ui`, adăugate cu CLI-ul `shadcn-vue`, nu copiate manual din React.
- Convențiile de ecran sunt în [UI.md](UI.md).

SPA-ul autentificat trimite `credentials: 'include'` și headerul `X-XSRF-TOKEN`. Helperul este `frontend/src/lib/api.ts`. Înainte de login, clientul cere `GET /sanctum/csrf-cookie`.

## Verificare

După modificări de API, rulează testele din container: `docker compose exec php php artisan test`. După modificări de UI, aplică ecranul servit de nginx pe `http://localhost:8080` și respectă [UI.md](UI.md). Pașii locali sunt în [README.md](README.md).

Testele PHPUnit folosesc o bază SQLite în memorie și nu lasă rânduri în MySQL. Dacă o verificare în browser sau un script scrie conturi ori alte date în MySQL, șterge acele rânduri la final, împreună cu profilul și setările legate de ele. Nu șterge datele create de utilizator.
