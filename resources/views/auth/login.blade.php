<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Prijava · {{ config('app.name', 'Apex Flow CRM') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body">
    <main class="login-stage">
        <section class="login-story">
            <a class="brand login-brand" href="/"><span><strong>Apex</strong><small>FLOW CRM</small></span></a>
            <div class="story-copy"><p class="eyebrow light">OD KONTAKTA DO DOGOVORA</p><h1>Prodaja koja ima<br><em>jasan smjer.</em></h1><p>Svi odnosi, prilike, zadaci i ponude u jednom fokusiranom radnom prostoru.</p></div>
            <div class="story-signal"><span><i></i> Pipeline uživo</span><strong>€128k</strong><small>aktivnih prilika</small></div>
            <div class="orb orb-one"></div><div class="orb orb-two"></div>
        </section>
        <section class="login-panel">
            <div class="login-form-wrap">
                <p class="eyebrow">DOBRODOŠLI NATRAG</p><h2>Prijava u {{ config('app.name', 'Apex Flow CRM') }}</h2><p class="muted">Nastavite tamo gdje ste stali.</p>
                <form method="POST" action="{{ route('login.store') }}" class="stack-form">@csrf
                    <label>E-mail adresa<input type="email" name="email" value="{{ old('email') }}" placeholder="ime@tvrtka.hr" required autofocus autocomplete="email"></label>
                    <label>Lozinka<input type="password" name="password" placeholder="••••••••" required autocomplete="current-password"></label>
                    @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    <div class="remember-row"><label class="check"><input type="checkbox" name="remember" value="1"><span></span> Zapamti me</label><span>Sigurna prijava</span></div>
                    <button class="primary-btn login-submit" type="submit">Prijavi se <span>→</span></button>
                </form>
                <div class="demo-hint">
                    <p><strong>Demo pristup</strong><span>demo@apexflow-crm.test · ApexFlow123!</span></p>
                    <button class="ghost-btn" type="button" data-demo-fill
                            data-demo-email="demo@apexflow-crm.test" data-demo-password="ApexFlow123!">Popuni demo podatke</button>
                </div>
                <p class="login-note">{{ config('app.name', 'Apex Flow CRM') }} · Privatni prodajni prostor</p>
            </div>
        </section>
    </main>
</body>
</html>
