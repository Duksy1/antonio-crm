# Changelog

Sve značajnije promjene u projektu. Format je inspiriran *Keep a Changelog*, a projekt prati faze izrade.

## [Faza 2] — 2026-09-11 — Sloj za svakodnevni rad

### Dodano

- **Aktivnosti i vremenska crta**: modul zadataka, poziva, sastanaka, e-mailova i bilješki s rokovima, brzim unosom, pogledima Otvoreno/Danas/Kasni/Dovršeno/Sve, označavanjem jednim klikom i vremenskom crtom na svakoj detaljnoj stranici.
- **Automatski zapis promjena**: `SystemActivityObserver` i `ActivityLogger` bilježe promjene statusa tvrtki i kontakata, faze i vrijednosti deala te statusa i iznosa ponuda; sustavski zapisi su nepromjenjivi.
- **Globalna pretraga**: pretraga kroz tvrtke, kontakte, dealove, ponude i aktivnosti, s prečacem `Ctrl/⌘ + K`.
- **Arhiva i izvoz**: svaki popis ima arhivirane zapise s vraćanjem i trajnim brisanjem te CSV izvoz koji poštuje filtere.
- **Sortiranje i broj zapisa po stranici** na svim popisima, kroz trait `SortsAndPaginates`.
- **Kanban povlačenje kartica** za dealove (po fazi) i ponude (po statusu), s vraćanjem kartice ako server odbije promjenu.
- **Nadzorna ploča**: pipeline po fazama, ponderirana prognoza, stopa dobitka, ovomjesečne pobjede, agenda po roku, posljednje aktivnosti i tablica dealova.
- **Životni ciklus ponude**: dupliciranje u novu verziju, prihvaćanje koje zatvara deal kao dobiven i dnevna naredba `quotes:expire` (06:00) za istekle ponude.
- **Profil korisnika**: promjena imena, e-maila i lozinke uz provjeru trenutne lozinke.
- **Dokumentacija**: `docs/ARHITEKTURA.md`, `docs/UI.md` i ovaj changelog.
- **Pristupačnost**: skip link, `aria-current` u navigaciji, vidljiv fokus, `prefers-reduced-motion`.
- **Testovi**: `ActivityTimelineTest`, `SearchTest`, `ArchiveAndExportTest`, `ProfileTest`, `QuoteLifecycleTest`, `NavigationSmokeTest` (ukupno 39 testova).

### Promijenjeno

- Nadzorna ploča, popisi i detalji prerađeni u jedinstven vizualni jezik (bold tipografija, serif naslovi, više praznog prostora).
- Ponude dobile numeraciju `AF-<godina>-<redni broj>` i PDF u novom brendu.
- Seeder je idempotentan i puni realistične demo podatke.

## [Faza 1] — 2026-09-11 — Rebranding u Apex Flow CRM

### Promijenjeno

- Naziv projekta, aplikacije i brenda: **Antonio CRM → Apex Flow CRM**.
- `APP_NAME`, baza i korisnik baze (`apex_flow_crm`, `apex_flow_crm_test`), Docker projekt i volumen, naziv paketa u `composer.json` i `package.json`.
- CI workflow, `phpunit.xml`, Render blueprint i Docker ulazna točka prilagođeni novom nazivu.
- Sučelje prijave i PDF ponude prebrendirani; dodan gumb **Popuni demo podatke**.
- README prepisan: funkcije, demo prijava, pokretanje, testovi, zakazani zadaci i model podataka.
