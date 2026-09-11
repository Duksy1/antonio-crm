# UI i UX — Apex Flow CRM

Dokument opisuje vizualni jezik, komponente i obrasce ponašanja, da bi svaki novi ekran izgledao i radio kao ostatak aplikacije.

## Principi

1. **Bold, a mirno.** Hijerarhija se gradi težinom i veličinom (naslovi 500–800, oznake 800, tijelo teksta 400–600), ne bjesomučnim bojama. Boje su rezervirane za status i primarnu akciju.
2. **Puno praznog prostora.** Sadržaj diše: `page-shell` ima 38px padding i maksimalnu širinu 1580px, kartice 22–32px unutarnjeg prostora, a razmaci su višekratnici od 4px. Gustoća nikad ne dolazi od manjeg razmaka, nego od boljeg rasporeda.
3. **Jedna primarna akcija po ekranu.** Tamni gumb (`primary-btn`) je uvijek najvažnija akcija; ostalo su `secondary-btn`, `ghost-btn`, `danger-btn` i `text-btn`.
4. **Sve je na jednom mjestu, ali slojevito.** Popis → alatna traka (pretraga, sortiranje, arhiva, izvoz) → redak → detalji s vremenskom crtom. Kanban je alternativni pogled istog popisa, ne nova stranica.
5. **Nikad bez povratne informacije.** Svaka mutacija završava toast porukom, a kartica koja je premještena kratko zasvijetli (`just-moved`).

## Tokeni dizajna (`:root` u `resources/css/app.css`)

| Token | Vrijednost | Upotreba |
| --- | --- | --- |
| `--ink` | `#172321` | primarni tekst, primarni gumb |
| `--night` | `#13201e` | sidebar i tamne površine |
| `--paper` | `#f5f3ed` | pozadina aplikacije |
| `--card` | `#fffefa` | kartice, tablice, paneli |
| `--line` | `#e4e1d9` | obrubi i razdjelnici |
| `--muted` | `#78817e` | sekundarni tekst |
| `--coral` / `--coral-dark` | `#f2765e` / `#d85e48` | akcent, fokus, aktivna stavka |
| `--shadow` | `0 18px 45px rgba(30,45,40,.07)` | podizanje kartica |

## Tipografija

- **Inter** za sučelje (`font-family` u `:root`, body 14px).
- **Georgia** za display naslove i brojeve (`page-header h1` 34px, `quote-head h2` 28px, login naslov `clamp(48px, 5vw, 76px)`), uz negativan `letter-spacing` (−0.02 do −0.045em) za „editorial“ osjećaj.
- **Eyebrow oznake**: 10px, `font-weight: 800`, `letter-spacing: .16em`, velika slova — koristi ih `x-page-header` i `nav-label`.
- Brojevi u kanbanu i metrikama su serif i veliki, pa se vrijednosti čitaju na prvi pogled.

## Raspored

- Fiksni sidebar 244px (`--night`) + `workspace` s `margin-left: 244px`.
- Ljepljivi topbar 66px: hamburger (mobitel), kontekst, globalna pretraga s `Ctrl K`, brza akcija **Novi deal**.
- `page-shell` je jedini kontejner sadržaja: maks. 1580px, 38px padding.
- `page-header`: eyebrow → naslov → opis lijevo, akcije desno (`align-items: flex-end`, `gap: 24px`).
- Metrike: `metric-grid` / `metric-grid-quad`, sažetak u `stat-strip` (mala oznaka iznad velike vrijednosti).
- Kanban: vodoravno klizanje kolona, svaka kolona ima naslov s bojom faze, zbroj vrijednosti i broj kartica.

## Ključne komponente

| Komponenta / klasa | Uloga |
| --- | --- |
| `x-page-header` | identitet stranice i akcije |
| `x-view-toolbar` | prebacivanje lista ⇄ kanban i filter po statusu/fazi |
| `x-list-tools` | pretraga, „Sortiraj“, „Po stranici“, arhiva i CSV |
| `x-timeline` + `.timeline-item` | kronologija aktivnosti na detaljima |
| `x-activity-form` + `.activity-form-row` | brzi unos zadatka/poziva/sastanka/e-maila/bilješke |
| `x-status` | obojana oznaka statusa ili faze |
| `x-empty` + `.empty-state` | prazno stanje s objašnjenjem |
| `x-form-errors` | sažetak greške iznad forme |
| `.data-table` | popisi s klikabilnim zaglavljima za sortiranje |
| `.chip-btn` | filter i prečaci unutar alatne trake |
| `.toast` | potvrda uspješne akcije (`role="status"`) |

## Obrasci ponašanja

- **Sortiranje i broj zapisa** se čuvaju kroz query string, pa linkovi i izvoz zadržavaju kontekst.
- **Arhiva** je način rada popisa: uključena pokazuje obrisane zapise s akcijama Vrati i Trajno obriši.
- **CSV izvoz** uvijek poštuje aktivne filtere, pretragu i sortiranje.
- **Kanban drag & drop**: povuci karticu u kolonu → `PATCH` na server → kod greške kartica se vraća natrag i pojavi se poruka.
- **Automatski zapisi** su vizualno odvojeni i bez akcija, jer se ne mogu mijenjati.
- **Tipkovnica**: `Ctrl/⌘ + K` fokusira globalnu pretragu, `Esc` je napušta, `Tab` vodi kroz sve akcije s vidljivim fokusom.

## Pristupačnost

- Skip link „Preskoči na sadržaj“ kao prva fokusabilna stvar, cilja `#main-content`.
- `aria-current="page"` na aktivnoj stavci navigacije, `aria-label` na ikoničnim gumbima i navigaciji, `role="search"` na pretrazi, `role="status"` na toastu.
- Vidljiv fokus (`:focus-visible`, coral obrub) na svim interaktivnim elementima, uključujući inpute.
- `prefers-reduced-motion: reduce` isključuje tranzicije i animacije.
- Kontrast: tamni tekst na papiru i karti, akcent samo na tamnoj podlozi ili uz dovoljan kontrast.

## Responzivnost

| Breakpoint | Ponašanje |
| --- | --- |
| ≤ 1100px | metrike prelaze u dvije kolone, kanban i tablice se vodoravno klize |
| ≤ 760px | sidebar se otvara preko hamburgera, `list-tools` i brzi unos se slažu okomito, skraćenice tipkovnice se skrivaju |
| ≤ 430px | pojednostavljene metrike i naslovi za male ekrane |

## Gdje se što stilizira

- `resources/css/app.css`: tokeni → shell (sidebar, topbar, toast) → komponente → obrasci po stranici (dashboard, popisi, kanban, ponuda, login) → responzivnost → pristupačnost.
- `resources/js/app.js`: demo prijava, globalna pretraga (`data-global-search`), mobilna navigacija (`data-menu-toggle`) i kanban povlačenje (`data-kanban`).

## Checklist za novi ekran

1. `x-page-header` s eyebrow oznakom, naslovom, opisom i akcijom.
2. Sadržaj u karticama s `--card`, obrubom `--line` i `--shadow`.
3. Prazno stanje kroz `x-empty`, greške kroz `x-form-errors`.
4. Mutacije vraćaju `redirect()->back()->with('success', ...)` da toast ima što prikazati.
5. Provjeri 1100px, 760px i 430px, kao i fokus kroz `Tab`.
