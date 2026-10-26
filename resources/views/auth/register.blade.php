@extends('layouts.app')
@section('title', 'Регистрация')
@section('content')
<section class="hero"><div class="eyebrow">РЕГИСТРАЦИЯ // ДОСТУП</div><h1>Создать аккаунт_</h1></section>
<form class="panel form-panel" method="POST" action="{{ route('register') }}">@csrf<label>Имя <input name="name" required maxlength="255" autocomplete="name" value="{{ old('name') }}"></label><label>Email <input name="email" type="email" required autocomplete="email" value="{{ old('email') }}"></label><label>Пароль <input name="password" type="password" required autocomplete="new-password"></label><label>Повтор пароля <input name="password_confirmation" type="password" required autocomplete="new-password"></label><div class="actions"><button class="button" type="submit">Зарегистрироваться →</button><a href="{{ route('login') }}">Уже есть аккаунт?</a></div></form>
@endsection
