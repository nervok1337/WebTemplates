@extends('layouts.app')

@section('title', 'Все заявки')

@section('content')
    <h1>Все заявки</h1>

    @if ($submissions->isEmpty())
        <p>Пока здесь чистый лист.</p>
    @else
        <table border="1">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Имя</th>
                    <th scope="col">Электронная почта</th>
                    <th scope="col">Сообщение</th>
                    <th scope="col">Дата и время</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($submissions as $submission)
                    <tr>
                        <td>{{ $submission['id'] }}</td>
                        <td>{{ $submission['name'] }}</td>
                        <td>{{ $submission['email'] }}</td>
                        <td>{{ $submission['message'] }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($submission['created_at'])->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
