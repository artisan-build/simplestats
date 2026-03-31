<?php

namespace App\Filament\Pages;

use App\Jobs\SendAnnouncementToUser;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Spatie\DeletedModels\Models\DeletedModel;

class Announcements extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Announcements';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Announcements';

    protected string $view = 'filament.pages.announcements';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('subject')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. What\'s new in SimpleStats'),
                MarkdownEditor::make('body')
                    ->required()
                    ->columnSpanFull()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('announcements'),
                Toggle::make('admins_only')
                    ->label('Send to admins only')
                    ->helperText('Only send to admin users (useful for previewing the announcement).'),
                Toggle::make('include_deleted_users')
                    ->label('Include deleted users')
                    ->helperText('Also send to former users stored in deleted models.'),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('send')
                    ->footer([
                        Actions::make($this->getFormActions()),
                    ]),
            ]);
    }

    public function send(): void
    {
        $data = $this->form->getState();
        $recipientCount = 0;

        $query = User::whereNotNull('email_verified_at');

        if ($data['admins_only'] ?? false) {
            $query->where('is_admin', true);
        } elseif (app()->environment() !== 'production') {
            $query->where('id', auth()->id());
        }

        $users = $query->get();
        $activeEmails = $users->pluck('email')->map(fn (string $email) => strtolower($email))->toArray();

        foreach ($users as $user) {
            SendAnnouncementToUser::dispatch($user->name, $user->email, $data['subject'], $data['body']);
            $recipientCount++;
        }

        if ($data['include_deleted_users'] ?? false) {
            $deletedUsers = DeletedModel::query()
                ->where('model', (new User)->getMorphClass())
                ->get();

            foreach ($deletedUsers as $deletedUser) {
                $email = $deletedUser->values['email'] ?? null;
                $name = $deletedUser->values['name'] ?? '';

                if (! $email || in_array(strtolower($email), $activeEmails, true)) {
                    continue;
                }

                SendAnnouncementToUser::dispatch($name, $email, $data['subject'], $data['body']);
                $recipientCount++;
            }
        }

        $this->form->fill();

        Notification::make()
            ->title('Announcement queued')
            ->body("Sending to {$recipientCount} user(s).")
            ->success()
            ->send();
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('send')
                ->label('Send to all users')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('Send announcement')
                ->modalDescription(fn () => ($this->data['admins_only'] ?? false)
                    ? 'This will only send the announcement to admin users.'
                    : 'Are you sure you want to send this announcement to all verified users?')
                ->action('send'),
        ];
    }
}
