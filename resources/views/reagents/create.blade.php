@extends('layouts.app')
@section('title', 'Новый химикат')
@section('content')
<section class="hero"><div class="eyebrow">ЗАЯВКА // ХИМИКАТ</div><h1>Добавить химикат_</h1><p class="lead">Новые записи публикуются после проверки администратором.</p></section>
<form class="panel form-panel" action="{{ route('reagents.store') }}" method="POST">@csrf
    <label>Название <input name="name" required maxlength="255" value="{{ old('name') }}"></label>
    <label>Категория <select name="category_id" required><option value="">Выберите категорию</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
    <label>Описание <textarea name="description" maxlength="5000">{{ old('description') }}</textarea></label>
    <label>Эффекты <textarea name="effects" maxlength="5000">{{ old('effects') }}</textarea></label>
    <label>Цвет (HEX) <input name="color" placeholder="#a6e073" pattern="#[0-9a-fA-F]{6}" value="{{ old('color') }}"></label>
    <button class="button" type="submit">Отправить на модерацию →</button>
</form>
@endsection
