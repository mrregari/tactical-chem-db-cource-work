@extends('layouts.app')
@section('title', 'Вход')
@section('content')
<section class="hero"><div class="eyebrow">ИДЕНТИФИКАЦИЯ // ДОСТУП</div><h1>Вход в систему_</h1></section>
<form class="panel form-panel" method="POST" action="{{ route('login') }}">@csrf<label>Email <input name="email" type="email" autocomplete="email" required value="{{ old('email') }}"></label><label>Пароль <input name="password" type="password" autocomplete="current-password" required></label><label><input style="width:auto" type="checkbox" name="remember" value="1"> Запомнить меня</label><div class="actions"><button class="button" type="submit">Войти →</button><a href="{{ route('register') }}">Создать аккаунт</a></div></form>
@endsection
