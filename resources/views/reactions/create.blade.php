@extends('layouts.app')
@section('title', 'Новый рецепт')
@section('content')
<section class="hero"><div class="eyebrow">ЗАЯВКА // РЕАКЦИЯ</div><h1>Предложить рецепт_</h1><p class="lead">Укажите ингредиенты и количество на одну единицу готового продукта. Рецепт появится после модерации.</p></section>
<form class="panel form-panel" action="{{ route('reactions.store') }}" method="POST">@csrf
<label>Название рецепта <input name="name" required maxlength="255" value="{{ old('name') }}"></label>
<label>Результат <select name="result_reagent_id" required><option value="">Выберите реагент</option>@foreach($reagents as $reagent)<option value="{{ $reagent->id }}" @selected(old('result_reagent_id') == $reagent->id)>{{ $reagent->name }}</option>@endforeach</select></label>
<label>Описание <textarea name="description" maxlength="5000">{{ old('description') }}</textarea></label>
<h2>Ингредиенты // ед. на 1 ед. результата</h2><div class="grid">@foreach($reagents as $reagent)<label>{{ $reagent->name }} <input type="number" step="0.01" min="0.01" max="10000" name="ingredients[{{ $reagent->id }}]" value="{{ old('ingredients.'.$reagent->id) }}" placeholder="—"></label>@endforeach</div>
<p class="muted">Пустые поля не учитываются.</p><button class="button" type="submit">Отправить на модерацию →</button></form>
@endsection
