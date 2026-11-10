<header>
    <a href="{{ route('home') }}">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path d="M7 3H4v18h16V3h-3M8 3h8v4H8zM8 12h8M8 16h5"/>
        </svg>
        {{ config('app.name') }}
    </a>
    <nav>
        <a href="{{ route('home') }}">Главная</a> |
        <a href="{{ route('submissions.create') }}">Новая заявка</a> |
        <a href="{{ route('submissions.index') }}">Все заявки</a>
    </nav>
    <hr>
</header>
