# Arhitektura — Apex Flow CRM

Ovaj dokument odgovara na pitanje **„gdje se što nalazi i gdje se što mijenja“**.
Ako tražiš konkretnu stranicu, kreni od tablice ruta; ako tražiš logiku, kreni od tokova na dnu.

## Slojevi

| Sloj | Lokacija | Odgovornost |
| --- | --- | --- |
| Rute | `routes/web.php`, `routes/console.php` | ulazi u aplikaciju i dnevni raspored |
| Kontroleri | `app/Http/Controllers` | orkestracija, izolacija po vlasniku, redirecti i flash poruke |
| Zajednički trait | `app/Http/Controllers/Concerns/SortsAndPaginates.php` | sortiranje, `per_page` i `archived` za sve popise |
| Validacija | `app/Http/Requests` | pravila i poruke; svi FK-ovi se provjeravaju u scopeu prijavljenog korisnika |
| Modeli | `app/Models` | relacije, castovi, `SoftDeletes`, scopeovi |
| Enumi | `app/Enums` | statusi, faze i tipovi aktivnosti + `HasOptions` (labely, boje, `<option>` lista) |
| Servisi | `app/Services` | obračun ponude, generiranje broja ponude, pisanje aktivnosti |
| Observer | `app/Observers/SystemActivityObserver.php` | automatska vremenska crta na svaku promjenu zapisa |
| Podrška | `app/Support/Csv.php` | streaming CSV izvoz |
| Naredbe | `app/Console/Commands/ExpireQuotes.php` | `quotes:expire` |
| Viewovi | `resources/views` | Blade stranice i komponente |
| Stil i skripta | `resources/css/app.css`, `resources/js/app.js` | dizajn sustav i interakcije |
| Shema i demo podaci | `database/migrations`, `database/factories`, `database/seeders` | tablice, uzorci i demo okruženje |
| Testovi | `tests/Feature`, `tests/Unit` | HTTP tokovi i čista logika |

## Ruta → kontroler → view

| Ruta (ime) | Kontroler | View | Napomena |
| --- | --- | --- | --- |
| `/` → `/dashboard` | `DashboardController` (invokable) | `dashboard.blade.php` | metrike, prognoza, agenda, vremenska crta |
| `/login`, `POST /login` | `AuthController` | `auth/login.blade.php` | rate limit `throttle:6,1`, gumb za demo podatke |
| `/search` | `SearchController` (invokable) | `search/index.blade.php` | pretraga kroz 5 entiteta |
| `/profile` | `ProfileController` | `profile/edit.blade.php` | ime, e-mail i lozinka |
| `/activities` | `ActivityController` | `activities/index.blade.php` | filteri, brzi unos, CSV izvoz, toggle |
| `/companies` | `CompanyController` | `companies/index|board|form|show` | lista, kanban, forma, detalji s vremenskom crtom |
| `/contacts` | `ContactController` | `contacts/index|board|form|show` | isto kao tvrtke |
| `/deals` | `DealController` | `deals/index|board|form|show` | kanban po fazama + shortcut u novu ponudu |
| `/quotes` | `QuoteController` | `quotes/index|board|form|show|pdf` | kanban po statusu, PDF i životni ciklus |

Svaki resursni kontroler ima i `GET .../export` (CSV), `PATCH .../restore` i `DELETE .../force` (arhiva).

## Komponente sučelja

| Komponenta | Namjena |
| --- | --- |
| `x-page-header` | naslov stranice, eyebrow oznaka, opis i akcije |
| `x-view-toolbar` | prebacivanje lista/kanban + filter statusa ili faze |
| `x-list-tools` | pretraga, sortiranje, broj zapisa po stranici, arhiva i CSV |
| `x-timeline` | vremenska crta aktivnosti na detaljnim stranicama |
| `x-activity-form` | brzi unos zadatka, poziva, sastanka, e-maila ili bilješke |
| `x-status` | obojana oznaka statusa/faze |
| `x-empty` | prazno stanje popisa |
| `x-form-errors` | validacijske poruke iznad forme |

## Glavni tokovi

1. **Automatska vremenska crta** — svaki `created/updated/deleted/restored/forceDeleted` na modelima tvrtke, kontakta, deala i ponude prolazi kroz `SystemActivityObserver`, koji preko `ActivityLogger` servisa upisuje zapis tipa `System`. Sustavski zapisi su odmah dovršeni, ne mogu se uređivati ni brisati, a toggle i destroy rute za njih vraćaju 404.
2. **Numeriranje ponuda** — `QuoteNumberGenerator` radi jedan atomski `INSERT … ON CONFLICT` nad tablicom `quote_number_counters` (zasebni brojač po godini) i vraća `AF-<godina>-<redni broj>`. Nema race conditiona ni duplih brojeva.
3. **Životni ciklus ponude** — `QuoteController@duplicate` stvara novu verziju ponude s novim brojem, `updateStatus` na `accepted` zatvara povezani deal kao dobiven, a `quotes:expire` (dnevno u 06:00) označava istekle poslane ponude i svaku promjenu zapisuje u vremensku crtu.
4. **Kanban drag & drop** — kolone nose `data-drop-field`/`data-drop-value`, kartice `data-move-url`. `app.js` šalje `FormData` s `_method=PATCH` i CSRF tokenom na `deals.stage`/`quotes.status`; kod greške kartica se vraća u početnu kolonu.
5. **Popisi i izvoz** — `SortsAndPaginates` daje sortiranje, `per_page` i `archived`; `Csv` streaming servira izvoz koji poštuje aktivne filtere i pretragu.
6. **Globalna pretraga** — `SearchController` pretražuje tvrtke, kontakte, dealove, ponude i aktivnosti; polje u zaglavlju fokusira se s `Ctrl/⌘ + K`.
7. **Nadzorna ploča** — `DashboardController` u jednom prolazu računa pipeline po fazama, ponderiranu prognozu, stopu dobitka, ovomjesečne pobjede, agendu po roku i posljednje aktivnosti.

## Kako dodati novi entitet

1. Migracija + model (`SoftDeletes`, relacija `owner()`, castovi za enum i decimale).
2. Enum sa `HasOptions` ako ima statuse ili faze.
3. `FormRequest` s pravilima i porukama; FK-ovi kroz `Rule::exists(...)->where('owner_id', ...)`.
4. Kontroler s `SortsAndPaginates`, `index/board/create/store/edit/update/destroy` + `export/restore/forceDestroy`.
5. Viewovi po uzoru na postojeće (`index`, `board`, `form`, `show`) i komponente `x-list-tools`, `x-timeline`, `x-activity-form`.
6. Registriraj observer u `AppServiceProvider` ako želiš automatsku vremensku crtu.
7. Feature test po uzoru na `tests/Feature/ActivityTimelineTest.php`.
