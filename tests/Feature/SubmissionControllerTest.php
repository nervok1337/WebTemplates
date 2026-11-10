<?php

namespace Tests\Feature;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SubmissionControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_form_displays_fields_and_post_action(): void
    {
        $response = $this->get(route('submissions.create'));

        $response->assertOk()
            ->assertViewIs('submissions.create')
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('submissions.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="message"', false);
    }

    public function test_valid_submission_saves_only_validated_fields_and_displays_success(): void
    {
        Storage::fake('local');
        $this->travelTo(Carbon::parse('2026-11-10T15:30:00+08:00'));
        $payload = $this->validPayload();

        $response = $this->post(route('submissions.store'), [
            ...$payload,
            'id' => '../overwrite',
            'created_at' => 'invalid date',
            'unexpected' => 'must not be saved',
        ]);

        $response->assertRedirectToRoute('submissions.create')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Заявка успешно сохранена. Спасибо за ваше обращение!');

        $files = Storage::disk('local')->files('submissions');
        $this->assertCount(1, $files);
        $stored = json_decode(Storage::disk('local')->get($files[0]), true, 512, JSON_THROW_ON_ERROR);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $stored['id']);
        $this->assertSame('submissions/'.$stored['id'].'.json', $files[0]);
        $this->assertSame([
            'id' => $stored['id'],
            ...$payload,
            'created_at' => '2026-11-10T15:30:00+08:00',
        ], $stored);

        $this->get(route('submissions.create'))->assertOk()
            ->assertSee('Заявка успешно сохранена.')
            ->assertSee(route('submissions.index'));
    }

    public function test_repeated_submissions_get_separate_files_without_overwriting(): void
    {
        Storage::fake('local');
        $this->freezeTime();

        $this->post(route('submissions.store'), $this->validPayload())->assertSessionHasNoErrors();
        $this->post(route('submissions.store'), $this->validPayload())->assertSessionHasNoErrors();

        $this->assertCount(2, Storage::disk('local')->files('submissions'));
    }

    public function test_missing_fields_display_russian_errors_without_saving(): void
    {
        Storage::fake('local');

        $response = $this->followingRedirects()->from(route('submissions.create'))->post(route('submissions.store'), []);

        $response->assertOk()->assertViewIs('submissions.create')
            ->assertSee('Заявка не сохранена.')
            ->assertSee('Заполните поле «Имя».')
            ->assertSee('Заполните поле «Электронная почта».')
            ->assertSee('Заполните поле «Сообщение».')
            ->assertSessionMissing('success');
        Storage::disk('local')->assertDirectoryEmpty('submissions');
    }

    /**
     * @param  array<string, mixed>  $invalidFields
     */
    #[DataProvider('invalidSubmissions')]
    public function test_invalid_fields_are_rejected_without_saving(array $invalidFields, string $field, string $message): void
    {
        Storage::fake('local');

        $response = $this->from(route('submissions.create'))->post(route('submissions.store'), [
            ...$this->validPayload(),
            ...$invalidFields,
        ]);

        $response->assertRedirectToRoute('submissions.create')
            ->assertSessionHasErrors([$field => $message])
            ->assertSessionMissing('success');
        Storage::disk('local')->assertDirectoryEmpty('submissions');
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}>
     */
    public static function invalidSubmissions(): array
    {
        return [
            'short name' => [['name' => 'А'], 'name', 'Поле «Имя» должно содержать не менее 2 символов.'],
            'long name' => [['name' => str_repeat('Я', 101)], 'name', 'Поле «Имя» должно содержать не более 100 символов.'],
            'array name' => [['name' => ['Анна']], 'name', 'Поле «Имя» должно быть строкой.'],
            'invalid email' => [['email' => 'wrong-email'], 'email', 'Укажите корректный адрес электронной почты.'],
            'long email' => [['email' => str_repeat('a', 64).'@'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 60).'.com'], 'email', 'Поле «Электронная почта» должно содержать не более 254 символов.'],
            'array email' => [['email' => ['anna@example.com']], 'email', 'Поле «Электронная почта» должно быть строкой.'],
            'short message' => [['message' => 'Коротко'], 'message', 'Поле «Сообщение» должно содержать не менее 10 символов.'],
            'long message' => [['message' => str_repeat('Я', 5001)], 'message', 'Поле «Сообщение» должно содержать не более 5000 символов.'],
            'array message' => [['message' => ['Сообщение']], 'message', 'Поле «Сообщение» должно быть строкой.'],
            'whitespace name' => [['name' => '   '], 'name', 'Заполните поле «Имя».'],
        ];
    }

    public function test_validation_preserves_input_and_renders_invalid_array_input_safely(): void
    {
        Storage::fake('local');

        $response = $this->followingRedirects()->from(route('submissions.create'))->post(route('submissions.store'), [
            'name' => 'Анна Иванова',
            'email' => 'wrong-email',
            'message' => ['invalid'],
        ]);

        $response->assertOk()
            ->assertSee('value="Анна Иванова"', false)
            ->assertSee('value="wrong-email"', false)
            ->assertSee('Укажите корректный адрес электронной почты.');
        Storage::disk('local')->assertDirectoryEmpty('submissions');
    }

    public function test_storage_failure_preserves_input_and_does_not_report_success(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->once()->andReturn($disk);

        $response = $this->post(route('submissions.store'), $this->validPayload());

        $response->assertRedirectToRoute('submissions.create')
            ->assertSessionHasErrors(['storage' => 'Не удалось сохранить заявку. Попробуйте ещё раз.'])
            ->assertSessionHasInput('name', 'Анна Иванова')
            ->assertSessionMissing('success');
    }

    public function test_journal_displays_empty_state_without_submissions(): void
    {
        Storage::fake('local');

        $response = $this->get(route('submissions.index'));

        $response->assertOk()->assertViewIs('submissions.index')->assertSee('Пока здесь чистый лист');
        Storage::disk('local')->assertDirectoryEmpty('submissions');
    }

    public function test_journal_displays_all_saved_fields_newest_first_and_escapes_user_content(): void
    {
        Storage::fake('local');
        $oldSubmission = [
            'id' => '710d0421-29e8-46d2-a18d-311ea40c794a',
            'name' => '<script>alert("name")</script>',
            'email' => '<script>alert("email")</script>@example.com',
            'message' => '<script>alert("message")</script>',
            'created_at' => '2026-11-09T14:25:00+08:00',
        ];
        $newSubmission = [
            'id' => 'b949767b-93da-44b7-9bf8-76a0dbe91f88',
            ...$this->validPayload(),
            'created_at' => '2026-11-10T15:30:00+08:00',
        ];
        Storage::disk('local')->put('submissions/old.json', json_encode($oldSubmission, JSON_THROW_ON_ERROR));
        Storage::disk('local')->put('submissions/new.json', json_encode($newSubmission, JSON_THROW_ON_ERROR));

        $response = $this->get(route('submissions.index'));

        $response->assertOk()->assertSee('<table', false)
            ->assertSeeInOrder(['Анна Иванова', '10.11.2026 15:30', $oldSubmission['name'], '09.11.2026 14:25']);
        foreach (['name', 'email', 'message'] as $field) {
            $response->assertSee($oldSubmission[$field])->assertDontSee($oldSubmission[$field], false)
                ->assertSee($newSubmission[$field]);
        }
        $response->assertSee($oldSubmission['id'])->assertSee($newSubmission['id']);
    }

    /**
     * @return array{name: string, email: string, message: string}
     */
    private function validPayload(): array
    {
        return [
            'name' => 'Анна Иванова',
            'email' => 'anna@example.com',
            'message' => 'Подскажите, как оставить предложение по работе приложения?',
        ];
    }
}
