# Antonio CRM

Antonio CRM je fokusiran prodajni radni prostor izrađen u Laravelu. Objedinjuje tvrtke, kontakte, dealove i ponude, a svaki entitet ima klasični popis, detaljni prikaz, CRUD sučelje i kanban pregled po statusima.

## Tehnologije

- PHP 8.3+ i Laravel 13
- PostgreSQL 17+ (produkcija: PostgreSQL 18)
- Blade, Tailwind CSS 4 i Vite
- Brick Math za determinističku decimalnu aritmetiku
- DOMPDF za generiranje ponuda u PDF-u
- PHPUnit 12, Laravel HTTP testovi i Laravel Pint

## Funkcionalnosti

- sigurna session autentikacija s throttlingom prijave
- CRUD za tvrtke, kontakte, dealove i ponude
- kanban prikaz i brza promjena statusa/faze za svaki entitet
- shortcut iz kartice deala u predpopunjenu novu ponudu
- ponude s dinamičkim stavkama, popustom, PDV-om i preciznim server-side decimalnim izračunom
- atomsko generiranje godišnjih brojeva ponuda prilagođeno paralelnim zahtjevima
- PDF export ponude
- pretraga i filtriranje prilagođeni PostgreSQL-u (`ILIKE`)
- vlasnička izolacija zapisa i zaštita od pristupa tuđim entitetima
- soft delete/arhiviranje poslovnih zapisa
- responzivno sučelje za desktop i mobilne uređaje
- realistični demo podaci i CI workflow

## Demo prijava

- E-mail: `demo@antonio-crm.test`
- Lozinka: `Antonio123!`

Seeder je idempotentan: demo korisnika ažurira, a demo poslovne podatke ne duplicira ako već postoje.

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

Statusi su PHP backed enum tipovi. Relacije koriste strane ključeve, indekse i PostgreSQL decimalne stupce za novčane vrijednosti. Financijski izračuni ponude koriste decimalnu aritmetiku i eksplicitno `HALF_UP` zaokruživanje unutar transakcije; vrijednosti iz browsera ne smatraju se izvorom istine. Brojevi ponuda dodjeljuju se atomskim PostgreSQL counterom odvojenim po godini.

## Struktura važnih direktorija

```text
app/Enums          domenski statusi i faze
app/Http           kontroleri i validacijski request objekti
app/Models         Eloquent modeli i relacije
app/Services       server-side obračun ponude
resources/views    originalni Blade UI i PDF predložak
tests/Feature      end-to-end HTTP testovi CRM tokova
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
