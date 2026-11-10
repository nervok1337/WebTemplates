@extends('layouts.app')

@section('title', 'Новая заявка')

@section('content')
    <h1>Новая заявка</h1>

    @if ($errors->any())
        <div role="alert">
            <p>Заявка не сохранена.</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('submissions.store') }}" novalidate>
        @csrf
        <p>
            <label for="name">Имя:</label><br>
            <input id="name" name="name" type="text" value="{{ is_string(old('name')) ? old('name') : '' }}" required minlength="2" maxlength="100">
        </p>
        <p>
            <label for="email">Электронная почта:</label><br>
            <input id="email" name="email" type="email" value="{{ is_string(old('email')) ? old('email') : '' }}" required maxlength="254">
        </p>
        <p>
            <label for="message">Сообщение (10–5000 символов):</label><br>
            <textarea id="message" name="message" rows="5" cols="35" required minlength="10" maxlength="5000">{{ is_string(old('message')) ? old('message') : '' }}</textarea>
        </p>
        <p>Данные заявки будут доступны всем посетителям в общем журнале.</p>
        <button type="submit">Отправить заявку</button>
    </form>
@endsection
