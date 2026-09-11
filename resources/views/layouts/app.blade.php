<!doctype html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pregled') · {{ config('app.name', 'Apex Flow CRM') }}</title>
    <meta name="theme-color" content="#14201f">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-body">
    <a class="skip-link" href="#main-content">Preskoči na sadržaj</a>
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <span><strong>Apex</strong><small>FLOW CRM</small></span>
        </div>

        <nav class="main-nav" aria-label="Glavna navigacija">
            <p class="nav-label">Radni prostor</p>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" aria-current="{{ request()->routeIs('dashboard') ? 'page' : 'false' }}">
                <svg viewBox="0 0 24 24"><path d="M4 13h6V4H4v9Zm0 7h6v-4H4v4Zm10 0h6v-9h-6v9Zm0-16v4h6V4h-6Z"/></svg><span>Pregled</span>
            </a>
            <a href="{{ route('companies.index') }}" class="nav-item {{ request()->routeIs('companies.*') ? 'active' : '' }}" aria-current="{{ request()->routeIs('companies.*') ? 'page' : 'false' }}">
                <svg viewBox="0 0 24 24"><path d="M4 21V7l8-4 8 4v14h-6v-5h-4v5H4Zm3-3h2v-2H7v2Zm0-4h2v-2H7v2Zm0-4h2V8H7v2Zm4 4h2v-2h-2v2Zm0-4h2V8h-2v2Zm4 4h2v-2h-2v2Zm0-4h2V8h-2v2Z"/></svg><span>Tvrtke</span>
            </a>
            <a href="{{ route('contacts.index') }}" class="nav-item {{ request()->routeIs('contacts.*') ? 'active' : '' }}" aria-current="{{ request()->routeIs('contacts.*') ? 'page' : 'false' }}">
                <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0H5Z"/></svg><span>Kontakti</span>
            </a>
            <a href="{{ route('deals.board') }}" class="nav-item {{ request()->routeIs('deals.*') ? 'active' : '' }}" aria-current="{{ request()->routeIs('deals.*') ? 'page' : 'false' }}">
                <svg viewBox="0 0 24 24"><path d="M9 4h6l2 3h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h3l2-3Zm1 3h4l-1-1h-2l-1 1Zm-6 5v6h16v-6a20 20 0 0 1-8 2 20 20 0 0 1-8-2Zm0-3v1a18 18 0 0 0 16 0V9H4Z"/></svg><span>Dealovi</span>
            </a>
            <a href="{{ route('quotes.index') }}" class="nav-item {{ request()->routeIs('quotes.*') ? 'active' : '' }}" aria-current="{{ request()->routeIs('quotes.*') ? 'page' : 'false' }}">
                <svg viewBox="0 0 24 24"><path d="M6 2h9l5 5v15H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm8 2v5h4l-4-5ZM8 13v2h8v-2H8Zm0 4v2h6v-2H8Z"/></svg><span>Ponude</span>
            </a>
            <a href="{{ route('activities.index') }}" class="nav-item {{ request()->routeIs('activities.*') ? 'active' : '' }}" aria-current="{{ request()->routeIs('activities.*') ? 'page' : 'false' }}">
                <svg viewBox="0 0 24 24"><path d="M9 4h9a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8.83a2 2 0 0 1 .59-1.42L7.17 4.8A2 2 0 0 1 8.59 4H9Zm1 4H7.4L6 9.4V10h4V8Zm-1 5v2h8v-2H9Zm0 4v2h5v-2H9Z"/></svg><span>Aktivnosti</span>
            </a>
            <p class="nav-label nav-label-spaced">Račun</p>
            <a href="{{ route('profile.edit') }}" class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}" aria-current="{{ request()->routeIs('profile.*') ? 'page' : 'false' }}">
                <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-3.3 0-6 1.8-6 4v2h12v-2c0-2.2-2.7-4-6-4Z"/></svg><span>Moj profil</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a class="user-chip" href="{{ route('profile.edit') }}"><span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span><span><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->email }}</small></span></a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout-btn" type="submit" aria-label="Odjava"><svg viewBox="0 0 24 24"><path d="M10 17v3H4V4h6v3h2V2H2v20h10v-5h-2Zm5-9-1.4 1.4 1.6 1.6H8v2h7.2l-1.6 1.6L15 16l4-4-4-4Z"/></svg></button></form>
        </div>
    </aside>

    <main class="workspace" id="main-content">
        <header class="topbar">
            <button class="mobile-menu" data-menu-toggle aria-label="Otvori navigaciju"><span></span><span></span></button>
            <div class="topbar-context"><span class="status-dot"></span><span>Prodajni prostor</span></div>
            <form class="global-search" method="GET" action="{{ route('search') }}" role="search">
                <svg viewBox="0 0 24 24"><path d="m19.6 21-6.3-6.3a7 7 0 1 1 1.4-1.4l6.3 6.3-1.4 1.4ZM8 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z"/></svg>
                <input type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Pretraži sve…" data-global-search autocomplete="off" aria-label="Globalna pretraga">
                <kbd>Ctrl K</kbd>
            </form>
            <a href="{{ route('deals.create') }}" class="quick-add"><span>＋</span> Novi deal</a>
        </header>

        <div class="page-shell">
            @if (session('success'))<div class="toast" role="status"><span>✓</span>{{ session('success') }}</div>@endif
            @yield('content')
        </div>
    </main>
    <div class="sidebar-overlay" data-menu-toggle></div>
    @stack('scripts')
</body>
</html>
