<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('un visiteur peut ouvrir tout le parcours de mot de passe oublié', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('password.request'));

    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Recevoir un nouveau lien');

    $this->get(route('password.reset', ['token' => 'jeton-test', 'email' => 'aelys@example.test']))
        ->assertOk()
        ->assertSee('Renouveler le mot de passe')
        ->assertSee('aelys@example.test');
});

test('la demande envoie la notification française au bon compte sans révéler les adresses inconnues', function () {
    Notification::fake();

    $user = User::factory()->create([
        'name' => 'Aelys',
        'email' => 'aelys@example.test',
    ]);

    $expectedMessage = 'Si cette adresse appartient à un aventurier, un lien vient de lui être envoyé.';

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('status', $expectedMessage);

    Notification::assertSentTo($user, ResetPasswordNotification::class);

    $this->post(route('password.email'), ['email' => 'inconnu@example.test'])
        ->assertRedirect()
        ->assertSessionHas('status', $expectedMessage);

    Notification::assertCount(1);
});

test('le message de réinitialisation est personnalisé et intégralement en français', function () {
    $user = User::factory()->make([
        'name' => 'Aelys des Brumes',
        'email' => 'aelys@example.test',
    ]);
    $message = (new ResetPasswordNotification('jeton-test'))->toMail($user);
    $html = (string) app(Markdown::class)->render($message->markdown, $message->data());

    expect($message->subject)->toBe('Renouvelez votre sceau secret · Le Fil d’Ambre')
        ->and($html)->toContain('Aelys des Brumes')
        ->and($html)->toContain('Choisir un nouveau mot de passe')
        ->and($html)->toContain('Chroniques de Valdoren')
        ->and($html)->not->toContain('Reset Password')
        ->and($html)->not->toContain('Hello!')
        ->and($html)->not->toContain('All rights reserved')
        ->and($html)->not->toContain('Regards');
});

test('un jeton valide renouvelle le mot de passe puis devient inutilisable', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'aelys@example.test',
        'password' => 'Ancien1234',
    ]);
    $token = null;

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo(
        $user,
        ResetPasswordNotification::class,
        function (ResetPasswordNotification $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        },
    );

    $payload = [
        'token' => $token,
        'email' => $user->email,
        'password' => 'Nouveau1234',
        'password_confirmation' => 'Nouveau1234',
    ];

    $this->post(route('password.update'), $payload)
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Votre mot de passe a bien été renouvelé.');

    expect(Hash::check('Nouveau1234', $user->fresh()->password))->toBeTrue();

    $this->post(route('password.update'), $payload)
        ->assertSessionHasErrors('email');
});

test('un jeton invalide ne modifie jamais le mot de passe', function () {
    $user = User::factory()->create([
        'email' => 'aelys@example.test',
        'password' => 'Ancien1234',
    ]);

    $this->post(route('password.update'), [
        'token' => 'jeton-invalide',
        'email' => $user->email,
        'password' => 'Nouveau1234',
        'password_confirmation' => 'Nouveau1234',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('Ancien1234', $user->fresh()->password))->toBeTrue();
});
