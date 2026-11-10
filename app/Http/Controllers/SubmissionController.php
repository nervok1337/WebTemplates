<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function index(): View
    {
        $disk = Storage::disk('local');
        $submissions = collect($disk->files('submissions'))
            ->filter(fn (string $path): bool => str_ends_with($path, '.json'))
            ->map(fn (string $path): array => json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR))
            ->sortByDesc('created_at')
            ->values();

        return view('submissions.index', compact('submissions'));
    }

    public function create(): View
    {
        return view('submissions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['bail', 'required', 'string', 'min:2', 'max:100'],
            'email' => ['bail', 'required', 'string', 'max:254', 'email'],
            'message' => ['bail', 'required', 'string', 'min:10', 'max:5000'],
        ], [
            'required' => 'Заполните поле «:attribute».',
            'string' => 'Поле «:attribute» должно быть строкой.',
            'min' => 'Поле «:attribute» должно содержать не менее :min символов.',
            'max' => 'Поле «:attribute» должно содержать не более :max символов.',
            'email.email' => 'Укажите корректный адрес электронной почты.',
        ], [
            'name' => 'Имя',
            'email' => 'Электронная почта',
            'message' => 'Сообщение',
        ]);

        $submission = [
            'id' => (string) Str::uuid(),
            ...$validated,
            'created_at' => now()->toIso8601String(),
        ];

        $saved = Storage::disk('local')->put(
            'submissions/'.$submission['id'].'.json',
            json_encode($submission, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        );

        if (! $saved) {
            return to_route('submissions.create')
                ->withInput($validated)
                ->withErrors(['storage' => 'Не удалось сохранить заявку. Попробуйте ещё раз.']);
        }

        return to_route('submissions.create')
            ->with('success', 'Заявка успешно сохранена. Спасибо за ваше обращение!');
    }
}
