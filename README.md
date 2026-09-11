# Apex Flow CRM

Apex Flow CRM je fokusiran prodajni radni prostor izgrađen u Laravelu. Objedinjuje tvrtke, kontakte, dealove, ponude i aktivnosti u jedan tok rada: svaki zapis ima popis, detaljni prikaz s vremenskom crtom, CRUD sučelje i kanban pregled s povlačenjem kartica.

## Tehnologije

- PHP 8.4+ i Laravel 13
- PostgreSQL 17+ (produkcija: PostgreSQL 18)
- Blade, Tailwind CSS 4 i Vite
- Brick Math za determinističku decimalnu aritmetiku
- DOMPDF za generiranje ponuda u PDF-u
- PHPUnit 12, Laravel HTTP testovi i Laravel Pint

## Funkcionalnosti

**Prodajni tok**

- CRUD za tvrtke, kontakte, dealove i ponude, uz vlasničku izolaciju zapisa
- kanban ploče za svaki entitet s drag & drop promjenom statusa ili faze
- shortcut iz kartice deala u predpopunjenu novu ponudu
- ponude s dinamičkim stavkama, popustom, PDV-om i preciznim server-side decimalnim izračunom
- atomsko generiranje godišnjih brojeva ponuda (`AF-2026-0001`) prilagođeno paralelnim zahtjevima
- životni ciklus ponude: dupliciranje u novu verziju, prihvaćanje koje automatski zatvara priliku kao dobivenu i dnevna naredba `quotes:expire` za istekle ponude
- PDF izvoz ponude i obračun dostupan iz svake ponude

**Aktivnosti i vremenska crta**

- modul aktivnosti (poziv, sastanak, e-mail, zadatak, bilješka) s rokovima i brzim unosom
- automatsko bilježenje promjena: statusi tvrtki i kontakata, faza i vrijednost prilike, status i iznos ponude
- pogledi „Otvoreno / Danas / Kasni / Dovršeno / Sve” i označavanje zadataka jednim klikom
- vremenska crta na svakoj detaljnoj stranici tvrtke, kontakta, deala i ponude

**Pregled i produktivnost**

- nadzorna ploča s pipelineom, ponderiranom prognozom, stopom dobitka i planom zadataka po roku
- globalna pretraga u zaglavlju (Ctrl/⌘ + K) kroz tvrtke, kontakte, dealove, ponude i aktivnosti
- arhiva s vraćanjem zapisa i trajnim brisanjem
- CSV izvoz svih popisa koji poštuje aktivne filtere i pretragu
- sortiranje i odabir broja zapisa po stranici na svakom popisu
- profil korisnika s promjenom imena, e-maila i lozinke
- responzivno sučelje za desktop i mobilne uređaje, realistični demo podaci i CI workflow
- pristupačnost: skip link, `aria-current` u navigaciji, vidljiv fokus na tipkovnici i poštovanje `prefers-reduced-motion`

## Dokumentacija

- [`docs/ARHITEKTURA.md`](docs/ARHITEKTURA.md) — slojevi, tablica ruta → kontroler → view, komponente, glavni tokovi i recept za novi entitet
- [`docs/UI.md`](docs/UI.md) — dizajn tokeni, tipografija, raspored, obrasci ponašanja, pristupačnost i responzivnost
- [`CHANGELOG.md`](CHANGELOG.md) — što je dodano u Fazi 1 (rebranding) i Fazi 2 (sloj za svakodnevni rad)

## Demo prijava

- E-mail: `demo@apexflow-crm.test`
- Lozinka: `ApexFlow123!`

Na ekranu za prijavu nalazi se gumb **Popuni demo podatke** koji upisuje ove vjerodajnice.

Seeder je idempotentan: demo korisnika ažurira, a demo poslovne podatke (tvrtke, kontakti, dealovi, ponude, aktivnosti) ne duplicira ako već postoje.

## Lokalno pokretanje

Potrebni su PHP 8.3+, Composer, Node.js i Docker. Nakon kloniranja repozitorija izradite `.env` kopiranjem datoteke `.env.example`, a zatim pokrenite:

```bash
composer install
npm ci
docker compose up -d
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Aplikacija će biti dostupna na `http://127.0.0.1:8000`, a za prijavu se koriste gore navedeni demo podaci.

## Testovi

Testovi koriste zasebnu PostgreSQL bazu `apex_flow_crm_test`.

```bash
docker compose exec postgres createdb -U apex_flow_crm apex_flow_crm_test
php artisan test
vendor/bin/pint --test
npm run build
```

Ako ekstenziju na Windowsu učitavate samo za pojedinu naredbu:

```powershell
php -d extension=pdo_pgsql vendor/bin/phpunit
```

Test suite pokriva prijavu, cijeli tijek izrade svih entiteta, obračun i numeriranje ponuda, kanban promjenu faze, vlasničku izolaciju, PDF izvoz, modul aktivnosti s automatskim zapisima, globalnu pretragu, arhivu, CSV izvoz, promjenu profila te renderiranje svih glavnih stranica.

## Zakazani zadaci

Naredba `php artisan quotes:expire` označava poslane ponude kojima je istekao `valid_until` i zapisuje promjenu u vremensku crtu. U `routes/console.php` zakazana je dnevno u 06:00, pa je u produkciji dovoljno pokrenuti `php artisan schedule:work` (ili cron unos `php artisan schedule:run`).

## Model podataka

- `users` → vlasnici svih CRM zapisa
- `companies` → imaju kontakte, dealove, ponude i aktivnosti
- `contacts` → pripadaju tvrtki i povezuju se s dealovima/ponudama
- `deals` → povezuju tvrtku i kontakt, imaju prodajnu fazu i vrijednost
- `quotes` → pripadaju dealu/tvrtki/kontaktu i imaju više `quote_items`
- `activities` → zadaci i vremenska crta, opcionalno povezani s tvrtkom, kontaktom, dealom i ponudom

Statusi su PHP backed enum tipovi (`CompanyStatus`, `ContactStatus`, `DealStage`, `QuoteStatus`, `ActivityType`) sa zajedničkim `HasOptions` traitom za forme i prikaze. Relacije koriste strane ključeve, indekse i PostgreSQL decimalne stupce za novčane vrijednosti. Financijski izračuni ponude koriste decimalnu aritmetiku i eksplicitno `HALF_UP` zaokruživanje unutar transakcije; vrijednosti iz browsera ne smatraju se izvorom istine. Brojevi ponuda (`AF-<godina>-<redni broj>`) dodjeljuju se atomskim PostgreSQL counterom odvojenim po godini.

Promjene zapisa bilježi `SystemActivityObserver` preko `ActivityLogger` servisa, pa vremensku crtu nije potrebno puniti ručno. Sustavski zapisi se odmah smatraju dovršenima i ne mogu se uređivati ni brisati iz sučelja.

## Struktura važnih direktorija

```text
app/Enums          domenski statusi, faze i tipovi aktivnosti
app/Http           kontroleri, validacijski request objekti i trait za sortiranje
app/Models         Eloquent modeli i relacije
app/Observers      automatsko bilježenje promjena u vremensku crtu
app/Services       obračun i numeriranje ponuda te ActivityLogger
app/Support        pomoćne klase (CSV izvoz)
app/Console        naredba quotes:expire
resources/views    Blade UI (uključujući komponente za timeline, listu alata i brzi unos)
resources/css      dizajn sustav u jednoj datoteci (tokeni, komponente, responzivnost)
resources/js       interakcije: demo prijava, globalna pretraga, mobilna navigacija, kanban
docs               arhitektura i UI dokumentacija
tests/Feature      end-to-end HTTP testovi CRM tokova
```

## Sigurnosne napomene

- CSRF zaštita na svim mutacijama
- regeneracija session ID-a nakon prijave
- rate limit na login endpointu
- validacija svih foreign key vrijednosti u scopeu prijavljenog korisnika
- novčani ukupni iznosi nikada se ne prihvaćaju iz forme, nego se ponovno računaju na serveru
- sustavske aktivnosti i tuđi zapisi vraćaju 404, a trajno brisanje je dostupno samo vlasniku
- produkcija se pokreće s `APP_DEBUG=false`

---

Naziv aplikacije: **Apex Flow CRM**
