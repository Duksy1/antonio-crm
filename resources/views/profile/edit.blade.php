@extends('layouts.app')
@section('title','Moj profil')
@section('content')
<x-page-header eyebrow="RAČUN" title="Moj profil" description="Osobni podaci, sigurnost računa i sažetak radnog prostora." />

<div class="show-layout">
    <section class="detail-card">
        <div class="hero-identity"><span class="hero-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span><div><h2>{{ $user->name }}</h2><p>{{ $user->email }}</p></div></div>
        <form class="stack-form" method="POST" action="{{ route('profile.update') }}">
            @csrf @method('PUT')
            <label>Ime i prezime<input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="255"></label>
            <label>E-mail adresa<input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255"></label>
            <x-form-errors />
            <div class="form-footer"><button class="primary-btn" type="submit">Spremi promjene</button></div>
        </form>
    </section>

    <aside>
        <section class="side-card">
            <h3>Promjena lozinke</h3>
            <form class="stack-form" method="POST" action="{{ route('profile.password') }}">
                @csrf @method('PUT')
                <label>Trenutna lozinka<input type="password" name="current_password" required autocomplete="current-password"></label>
                <label>Nova lozinka<input type="password" name="password" required autocomplete="new-password"></label>
                <label>Potvrda nove lozinke<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
                <div class="form-footer"><button class="secondary-btn" type="submit">Promijeni lozinku</button></div>
            </form>
        </section>

        <section class="side-card">
            <h3>Sažetak radnog prostora</h3>
            <div class="stat-list">
                <div class="meta-row"><span>Tvrtke</span><strong>{{ $stats['companies'] }}</strong></div>
                <div class="meta-row"><span>Kontakti</span><strong>{{ $stats['contacts'] }}</strong></div>
                <div class="meta-row"><span>Aktivne prilike</span><strong>{{ $stats['openDeals'] }}</strong></div>
                <div class="meta-row"><span>Ponude</span><strong>{{ $stats['quotes'] }}</strong></div>
                <div class="meta-row"><span>Otvoreni zadaci</span><strong>{{ $stats['openTasks'] }}</strong></div>
                <div class="meta-row"><span>Dobivena vrijednost</span><strong>€{{ number_format($stats['wonValue'], 0, ',', '.') }}</strong></div>
            </div>
            <div class="side-actions">
                <a class="text-btn" href="{{ route('activities.index', ['scope' => 'open']) }}">Moji zadaci →</a>
                <a class="text-btn" href="{{ route('activities.export') }}">⤓ Izvoz aktivnosti</a>
            </div>
        </section>
    </aside>
</div>
@endsection
