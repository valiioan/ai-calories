# Convenții UI

Aceste reguli se aplică la tot ce se vede în browser. Interfața este Vue 3, Tailwind CSS v4 și shadcn-vue (`reka-nova`, font Geist, iconițe Lucide). Nu se introduce un al doilea sistem vizual.

Interfața este multilingvă. Limba implicită este româna. Utilizatorul o schimbă din pagina Setări, iar textele vizibile urmează limba aleasă.

## Mobilul este ecranul de bază

Interfața se proiectează întâi pentru telefon. Tableta și desktopul adaugă coloane în jurul aceluiași drum, nu un alt produs. Nu se desenează un tablou de bord și apoi se ascunde pe mobil.

Pagina nu derulează pe orizontală la nicio lățime.

### Înregistrarea unei mese

Acțiunea cea mai frecventă are un singur drum, fără ecran intermediar:

1. Aplicația se deschide direct pe captură. Câmpul de text este vizibil, în zona degetului, fără derulare.
2. Utilizatorul scrie ce a mâncat, în limbaj obișnuit, și trimite.
3. Apare estimarea AI: alimentele, cantitățile și totalul de kcal. Masa nu este încă salvată.
4. Utilizatorul confirmă. Abia atunci masa intră în jurnal. Poate și corecta textul sau o cantitate înainte de confirmare; estimarea se actualizează, iar salvarea rămâne pe același buton.

Butonul principal al estimării este „Confirmă”. Renunțarea închide estimarea și lasă câmpul gol, gata pentru următoarea masă. După confirmare, câmpul se golește și rămâne la îndemână.

Tastatura nu acoperă câmpul și nici estimarea. Navigația de jos se ascunde cât timp tastatura este deschisă, ca textul scris să rămână vizibil.

### Ecranul de captură

De sus în jos, pe telefon:

- O linie scurtă cu ziua și kcal consumate față de țintă. Nu este destinația ecranului.
- Lista compactă a meselor de azi. Estimarea nesalvată stă deasupra listei, nu într-un fir de conversație.
- Câmpul de text, fix în partea de jos.

Nu se cere tipul mesei înainte de text. Estimarea îl propune, iar utilizatorul îl poate schimba pe card, înainte de confirmare.

Greutatea, activitățile și profilul nu sunt pași între deschidere și scriere. Pe telefon, profilul și setările se deschid din avatarul din antet.

## Ecrane

### Telefon — sub 768 px

Telefonul este ținta principală. O singură coloană.

- Navigația are patru destinații, fixe jos. Nu există bara laterală de desktop. AI Chat este elementul cel mai vizibil.
- Cardurile principale sunt una sub alta. Graficele se îngustează la lățimea disponibilă.
- Editarea se deschide într-un sertar de jos.
- Butonul principal este pe toată lățimea când este acțiunea unică a ecranului.
- Țintele de atingere au cel puțin 44 × 44 px.
- Se respectă zonele sigure ale dispozitivului.
- De la 320 px în sus nu apare derulare orizontală.
- Conversația cu AI se deschide pe o rută pe tot ecranul. Câmpul de scris rămâne vizibil când tastatura este deschisă.

Capturarea mesei este această rută: textul, estimarea și confirmarea ocupă ecranul, nu un panou lateral.

### Tabletă — 768–1279 px

Tabloul are cel mult două coloane de conținut. Navigația este compactă sau o bară laterală care se poate strânge. Conversația cu AI stă pe o pagină proprie sau într-un strat care se adaptează la lățime. În bara laterală, AI Chat este prima destinație și deschide pagina conversației. De la 1280 px intrarea dispare, pentru că panoul din dreapta o înlocuiește. Cardurile și graficele folosesc spațiul rămas, fără o a treia coloană.

### Desktop — de la 1280 px

Trei zone:

- Navigație în stânga, aproximativ 220–240 px, cu destinațiile din secțiunea de mai jos.
- Conținut central, flexibil: jurnalul, rezumatul și rapoartele.
- Asistentul AI în dreapta, aproximativ 340–380 px. Peste 1440 px (Laptop L), panoul este cu 50% mai lat, 540 px.

Bara din stânga și panoul AI rămân disponibile în timp ce se schimbă conținutul central. Panoul AI se poate strânge, ca rapoartele să primească locul lui. Înregistrarea mesei nu se mută într-un formular cu multe câmpuri: în panou rămân același câmp, aceeași estimare și același „Confirmă”.

## Navigație globală

Destinația curentă are o stare activă clară, prin text și iconiță, nu doar prin culoare.

### Pe telefon

Bara de jos este fixă și are patru destinații:

- Acasă, cu iconița Home.
- Jurnal, cu iconița de carte sau de listă.
- AI Chat, acțiunea din centru. Este elementul cel mai vizibil din bară și deschide conversația pe tot ecranul.
- Progres, cu iconița de grafic.

Profilul și setările se deschid din avatarul utilizatorului, din antet, nu din bara de jos.

### Pe desktop

Bara din stânga începe cu logo-ul și numele aplicației. Sub ele:

- Dashboard
- Jurnal alimentar
- Activități
- Greutate
- Rapoarte

În partea de jos a barei:

- Setări, inclusiv limba interfeței
- Profil utilizator
- Deconectare

Pe tabletă, aceleași destinații stau într-o navigație compactă sau într-o bară laterală care se poate strânge. Nu se adaugă un al cincilea model de meniu.

## Unde stă codul

- Paginile stau în `frontend/src/views` și sunt legate în `frontend/src/router`.
- Componentele shadcn-vue stau în `frontend/src/components/ui` și se adaugă cu CLI-ul, din container:

```powershell
docker compose exec node npx shadcn-vue@latest add button
```

Nu se copiază componente din React și nu se rescriu cele generate ca să le schimbăm culorile.

- Componentele de produs, compuse din cele de mai sus, stau în `frontend/src/components`.
- Logica reutilizabilă de ecran stă în `frontend/src/composables`.
- Starea de client stă în Pinia, în `frontend/src/stores`. Serverul rămâne sursa de adevăr pentru jurnal, greutate și profil.
- `cn()` se importă din `@/lib/utils`.

Tokenii de culoare se editează doar în `frontend/src/style.css`.

## Componente, nu marcaj propriu

Înainte de un `div` stilizat, se verifică dacă există componenta în registrul shadcn-vue.


| Nevoie                   | Componentă                                                              |
| ------------------------ | ----------------------------------------------------------------------- |
| Acțiune                  | `Button`, cu varianta existentă                                         |
| Formular                 | `Input`, `Select`, `Textarea`, `Checkbox`, `Switch`, grupate cu `Field` |
| 2–7 opțiuni exclusive    | `ToggleGroup`                                                           |
| Date                     | `Card`, `Table`, `Badge`                                                |
| Navigare                 | `Sidebar`, `Tabs`, `Breadcrumb`                                         |
| Panou lateral sau dialog | `Sheet`, `Dialog`, `AlertDialog`                                        |
| Feedback                 | `sonner`, `Alert`, `Skeleton`, `Spinner`                                |
| Listă goală              | `Empty`                                                                 |
| Separare                 | `Separator`                                                             |


`Dialog`, `Sheet` și `Drawer` au întotdeauna un titlu. Dacă titlul nu trebuie văzut, primește clasa `sr-only`.

`Card` folosește `CardHeader`, `CardTitle`, `CardDescription`, `CardContent` și, când există acțiuni, `CardFooter`.

`Button` nu are stare de încărcare proprie. În timpul unei cereri, butonul este `disabled` și conține `Spinner`.

Iconițele din butoane folosesc `data-icon="inline-start"` sau `data-icon="inline-end"`. Nu li se pune clasă de dimensiune: componenta o stabilește.

Primitivele sunt Reka UI. Pentru un trigger personalizat se folosește `render`, nu `asChild`.

## Direcție vizuală

Produs SaaS de health-tech, modern: curat, elegant, primitor, cu puțin zgomot vizual. Culoarea de marcă este Emerald. Caloriile consumate sunt Coral. Consumul energetic estimat este Mint.

Valorile de mai jos sunt pentru modul luminos. În componente se folosesc doar variabilele semantice din temă, nu codurile hex. Modul întunecat va înlocui aceleași variabile în `.dark`, fără să rescrie componentele și fără clase `dark:` puse manual.

| Rol | Luminos | Variabilă | Clasă |
| --- | --- | --- | --- |
| Emerald, culoarea de marcă | `#168363` | `--primary` | `bg-primary`, `text-primary` |
| Emerald la hover | `#126E53` | `--primary-hover` | `hover:bg-primary-hover` |
| Text pe Emerald | `#FFFFFF` | `--primary-foreground` | `text-primary-foreground` |
| Mint, energie consumată estimată | `#47AF8F` | `--energy-out` | `text-energy-out`, `bg-energy-out` |
| Coral, calorii consumate | `#F0A18E` | `--energy-in` | `text-energy-in`, `bg-energy-in` |
| Fundal | `#F6F8F7` | `--background` | `bg-background` |
| Suprafață | `#FFFFFF` | `--card` | `bg-card` |
| Text principal | `#20302C` | `--foreground` | `text-foreground` |
| Text secundar | `#7C8D83` | `--muted-foreground` | `text-muted-foreground` |
| Bordură | `#E3EAE5` | `--border` | `border-border` |
| Pericol | `#DC5B62` | `--destructive` | `text-destructive`, `bg-destructive` |
| Avertisment | `#D5A146` | `--warning` | `text-warning`, `bg-warning` |

Succesul, eșecul și starea caloriilor nu se comunică doar prin culoare. Lângă Coral, Mint, pericol sau avertisment există text și o iconiță.

## Stil

Clasele exprimă rolul, nu o culoare aleasă local.

- Suprafață și text: `bg-background`, `text-foreground`, `text-muted-foreground`, `bg-card`, `border-border`.
- Acțiune principală: `bg-primary`, `text-primary-foreground`, cu `hover:bg-primary-hover`.
- Calorii consumate: `text-energy-in` sau `bg-energy-in`. Energie consumată estimată: `text-energy-out` sau `bg-energy-out`.
- Eroare: `text-destructive` sau varianta `destructive` a componentei. Avertisment: `text-warning`.
- Stare: variantele `Badge`, împreună cu text și iconiță, nu clase de genul `text-emerald-600` sau `bg-blue-500`.

Nu se adaugă culori `dark:` manual. Modul întunecat schimbă tokenii din `.dark`.

Layout-ul folosește `flex` sau `grid` cu `gap-*`. Nu se folosește `space-x-*` sau `space-y-*`.

Când lățimea și înălțimea sunt egale, se folosește `size-*`.

Textul tăiat folosește `truncate`.

Clasele condiționale se compun cu `cn()`. Nu se construiesc șiruri de clase prin concatenare manuală.

`class` pe un component shadcn-vue este pentru poziție și dimensiune, nu pentru a-i înlocui culoarea sau tipografia. Pentru o înfățișare diferită se alege o variantă existentă.

Componentele de tip overlay își gestionează singure `z-index`.

## Formulare și date

Câmpurile înrudite stau într-un `FieldGroup`. Fiecare câmp este un `Field`, cu etichetă legată prin `for` / `id`.

Eroarea de validare pune `data-invalid` pe `Field` și `aria-invalid` pe control. Textul erorii vine din răspunsul 422 al API-ului.

Valorile afișate urmează contractul din `architecture.md`: energia în kcal, greutatea în kilograme, datele calendaristice și timestamps-urile venite de la API. Interfața nu recalculează snapshot-ul nutrițional.

## Stări

Fiecare ecran sau componentă importantă arată starea care i se aplică. Starea se citește din text și din iconiță, nu doar din culoare.

- Implicit: ecranul este gata de folosit, înainte de date sau de o acțiune.
- Gol: nu există încă înregistrări. Se folosește `Empty`, cu o propoziție care spune ce lipsește și care este pasul următor.
- Se încarcă: structura ecranului rămâne vizibilă, umplută cu `Skeleton`. O acțiune în curs folosește `Spinner` și buton dezactivat.
- Cu date: listele, cardurile și graficele arată datele primite de la server.
- Eroare: cererea a eșuat. Mesajul spune ce nu a mers și oferă o reluare. Se folosesc `Alert` și `text-destructive`.
- Eroare de validare: câmpul are `data-invalid` pe `Field` și `aria-invalid` pe control, cu textul din răspunsul 422.
- Confirmare de reușită: o confirmare scurtă, prin `sonner` și text, că acțiunea s-a salvat.
- Dezactivat: controlul nu poate fi folosit. Este marcat `disabled` și nu pare doar mai șters.
- Așteaptă confirmarea: estimarea AI este vizibilă, dar masa nu este în jurnal până la „Confirmă”.

Conversația cu AI are două stări în plus.

- Cerere ambiguă: textul nu ajunge pentru o estimare. Se cere o clarificare scurtă. Nu se inventează alimente și nu se arată „Confirmă” ca și cum estimarea ar fi completă.
- Acțiune eșuată: salvarea sau acțiunea cerută nu a reușit. Se spune ce a eșuat și se poate relua. Masa nu se tratează ca salvată.

Datele de demonstrație sunt marcate explicit ca atare, ca să nu fie confundate cu jurnalul utilizatorului.

## Interacțiuni în faza de interfață

Cât se construiește interfața, ecranele merg cu date de demonstrație. Nu se fac cereri către API. Un buton sau un element de navigație are un efect vizibil în prototip, nu este doar decor.

- Navigația schimbă pagina activă și starea activă din bară.
- Schimbarea datei schimbă datele de demonstrație afișate.
- Sertarele și dialogurile se deschid și se închid.
- Formularele acceptă text și arată eroarea de validare.
- În chat se poate scrie și trimite. Răspunsul AI este un răspuns de demonstrație, stabil pentru același text.
- „Confirmă” actualizează doar starea locală de demonstrație.
- Graficele se schimbă când se schimbă filtrele de demonstrație.

Confirmarea de reușită din această fază spune că modificarea rămâne în datele de demonstrație. Nu se lasă impresia că ceva a fost salvat pe server.

## Accesibilitate

- Navigația se poate parcurge de la tastatură.
- Focusul este vizibil.
- Titlurile și controalele sunt marcate semantic.
- Fiecare câmp are o etichetă.
- Dialogul ține focusul în interior, se închide cu Escape și înapoiază focusul la elementul care l-a deschis.
- Contrastul urmărește WCAG 2.2 AA.
- Țintele de atingere au cel puțin 44 × 44 px.
- Dacă sistemul cere mișcare redusă, animațiile nu sunt esențiale pentru înțelegerea ecranului.
- Nicio informație esențială nu este transmisă doar prin culoare.
- Textul rămâne utilizabil când pagina este mărită.

## Verificare

După o schimbare vizibilă, fluxul se parcurge întâi sub 768 px, la [http://localhost:8080](http://localhost:8080), inclusiv stările din secțiunea Stări pe care schimbarea le atinge. Apoi se verifică tableta, cu cel mult două coloane, și desktopul de la 1280 px, cu navigația în stânga, conținutul la centru și asistentul în dreapta.