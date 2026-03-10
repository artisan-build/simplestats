<?php

use App\Filament\Pages\SendAnnouncement;
use App\Jobs\SendAnnouncementToUser;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Spatie\DeletedModels\Models\DeletedModel;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

test('send announcement page loads for admin users', function () {
    Livewire::test(SendAnnouncement::class)
        ->assertOk();
});

test('send announcement page requires subject and body', function () {
    Livewire::test(SendAnnouncement::class)
        ->fillForm([
            'subject' => '',
            'body' => '',
        ])
        ->call('send')
        ->assertHasFormErrors(['subject' => 'required', 'body' => 'required']);
});

test('send announcement dispatches jobs for verified users', function () {
    Bus::fake();

    Livewire::test(SendAnnouncement::class)
        ->fillForm([
            'subject' => 'Test Announcement',
            'body' => 'This is a test announcement.',
        ])
        ->call('send')
        ->assertNotified();

    Bus::assertDispatched(SendAnnouncementToUser::class, function ($job) {
        return $job->recipientEmail === $this->admin->email
            && $job->recipientName === $this->admin->name
            && $job->subject === 'Test Announcement'
            && $job->markdownContent === 'This is a test announcement.';
    });
});

test('send announcement includes deleted users when toggle is enabled', function () {
    Bus::fake();

    DeletedModel::create([
        'key' => '999',
        'model' => (new User)->getMorphClass(),
        'values' => ['name' => 'Deleted User', 'email' => 'deleted@example.com'],
    ]);

    Livewire::test(SendAnnouncement::class)
        ->fillForm([
            'subject' => 'Test',
            'body' => 'Content',
            'include_deleted_users' => true,
        ])
        ->call('send')
        ->assertNotified();

    Bus::assertDispatched(SendAnnouncementToUser::class, function ($job) {
        return $job->recipientEmail === 'deleted@example.com'
            && $job->recipientName === 'Deleted User';
    });
});

test('send announcement skips deleted users whose email matches an active user', function () {
    Bus::fake();

    $activeUser = User::factory()->create(['email_verified_at' => now()]);

    DeletedModel::create([
        'key' => '888',
        'model' => (new User)->getMorphClass(),
        'values' => ['name' => 'Old Account', 'email' => $activeUser->email],
    ]);

    Livewire::test(SendAnnouncement::class)
        ->fillForm([
            'subject' => 'Test',
            'body' => 'Content',
            'include_deleted_users' => true,
        ])
        ->call('send')
        ->assertNotified();

    $dispatched = collect();
    Bus::assertDispatched(SendAnnouncementToUser::class, function ($job) use ($dispatched) {
        $dispatched->push($job->recipientEmail);

        return true;
    });

    expect($dispatched->filter(fn ($email) => $email === $activeUser->email)->count())->toBe(1);
});

test('send announcement with admins_only toggle only dispatches to admin users', function () {
    Bus::fake();

    $regularUser = User::factory()->create(['email_verified_at' => now(), 'is_admin' => false]);

    Livewire::test(SendAnnouncement::class)
        ->fillForm([
            'subject' => 'Admin Preview',
            'body' => 'Testing admin-only send.',
            'admins_only' => true,
        ])
        ->call('send')
        ->assertNotified();

    $dispatched = collect();
    Bus::assertDispatched(SendAnnouncementToUser::class, function ($job) use ($dispatched) {
        $dispatched->push($job->recipientEmail);

        return true;
    });

    expect($dispatched)->toContain($this->admin->email);
    expect($dispatched)->not->toContain($regularUser->email);
});

test('non-admin users cannot access the send announcement page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/send-announcement')
        ->assertForbidden();
});
