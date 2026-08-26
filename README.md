# Antonio CRM

Antonio CRM je fokusiran prodajni radni prostor izrađen u Laravelu. Objedinjuje tvrtke, kontakte, dealove i ponude, a svaki entitet ima klasični popis, detaljni prikaz, CRUD sučelje i kanban pregled po statusima.

## Tehnologije

- PHP 8.3+ i Laravel 13
- PostgreSQL 17
- Blade, Tailwind CSS 4 i Vite
- DOMPDF za generiranje ponuda u PDF-u
- PHPUnit 12, Laravel HTTP testovi i Laravel Pint
- Docker/Render konfiguracija za deployment

## Funkcionalnosti

- sigurna session autentikacija s throttlingom prijave
- CRUD za tvrtke, kontakte, dealove i ponude
- kanban prikaz i brza promjena statusa/faze za svaki entitet
- shortcut iz kartice deala u predpopunjenu novu ponudu
- ponude s dinamičkim stavkama, popustom, PDV-om i server-side izračunom
- PDF export ponude
- pretraga i filtriranje prilagođeni PostgreSQL-u (`ILIKE`)
- vlasnička izolacija zapisa i zaštita od pristupa tuđim entitetima
- soft delete/arhiviranje poslovnih zapisa
- responzivno sučelje za desktop i mobilne uređaje
- realistični demo podaci i CI workflow

## Brzo pokretanje

Preduvjeti: PHP 8.3+, Composer, Node.js 22+, Docker Desktop te omogućena PHP ekstenzija `pdo_pgsql`.

```bash
git clone <repository-url>
cd antonio-crm
cp .env.example .env
composer install
npm install
docker compose up -d postgres
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Aplikacija je dostupna na `http://127.0.0.1:8000`.

Na Windowsu, ako je `pdo_pgsql` DLL prisutan ali nije uključen u `php.ini`, za jednokratne CLI naredbe može se koristiti:

```powershell
php -d extension=pdo_pgsql artisan migrate --seed
```

Za kontinuirani lokalni razvoj frontend asseta pokrenite `npm run dev` u zasebnom terminalu.

## Demo prijava

- E-mail: `demo@antonio-crm.test`
- Lozinka: `Antonio123!`

Seeder je idempotentan: demo korisnika ažurira, a demo poslovne podatke ne duplicira ako već postoje.

## Testovi

Testovi koriste zasebnu PostgreSQL bazu `antonio_crm_test`.

```bash
docker compose exec postgres createdb -U antonio_crm antonio_crm_test
php artisan test
vendor/bin/pint --test
npm run build
```

Ako ekstenziju na Windowsu učitavate samo za pojedinu naredbu:

```powershell
php -d extension=pdo_pgsql vendor/bin/phpunit
```

Test suite pokriva prijavu, cijeli tijek izrade svih entiteta, obračun ponude, kanban promjenu faze, vlasničku izolaciju i PDF export.

## Model podataka

- `users` → vlasnici svih CRM zapisa
- `companies` → imaju kontakte, dealove i ponude
- `contacts` → pripadaju tvrtki i povezuju se s dealovima/ponudama
- `deals` → povezuju tvrtku i kontakt, imaju prodajnu fazu
- `quotes` → pripadaju dealu/tvrtki/kontaktu i imaju više `quote_items`

Statusi su PHP backed enum tipovi. Relacije koriste strane ključeve, indekse i PostgreSQL decimalne stupce za novčane vrijednosti. Financijski izračuni ponude izvode se na serveru unutar transakcije; vrijednosti iz browsera ne smatraju se izvorom istine.

## Produkcijski deployment

Repozitorij sadrži multi-stage `Dockerfile` i `render.yaml` blueprint. Najjednostavniji deployment na Renderu:

1. Pushajte projekt na GitHub/GitLab.
2. U Renderu odaberite **New → Blueprint** i povežite repozitorij.
3. Render će iz `render.yaml` izraditi web servis i PostgreSQL bazu.
4. Postavite `APP_URL` na dodijeljeni javni URL.
5. Nakon prvog deploya provjerite `/login` i prijavite se demo podacima.

Docker entrypoint automatski izvršava `php artisan migrate --force`. `SEED_DEMO_DATA=true` uključuje idempotentni demo seed; za stvarnu produkciju nakon predaje preporučuje se promijeniti lozinku i postaviti vrijednost na `false`.

Obavezne produkcijske varijable:

```dotenv
APP_NAME="Antonio CRM"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://vas-url.example
DB_CONNECTION=pgsql
DB_URL=postgresql://user:password@host:5432/database
SESSION_DRIVER=database
CACHE_STORE=database
```

## Struktura važnih direktorija

```text
app/Enums          domenski statusi i faze
app/Http           kontroleri i validacijski request objekti
app/Models         Eloquent modeli i relacije
app/Services       server-side obračun ponude
resources/views    originalni Blade UI i PDF predložak
tests/Feature      end-to-end HTTP testovi CRM tokova
docker             produkcijski entrypoint
```

## Sigurnosne napomene

- CSRF zaštita na svim mutacijama
- regeneracija session ID-a nakon prijave
- rate limit na login endpointu
- validacija svih foreign key vrijednosti u scopeu prijavljenog korisnika
- novčani ukupni iznosi nikada se ne prihvaćaju iz forme, nego se ponovno računaju na serveru
- produkcija se pokreće s `APP_DEBUG=false`

---

Naziv aplikacije: **Antonio CRM**
