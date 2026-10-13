<?php

namespace App\Filament\Pages;

use App\Jobs\SendAnnouncementToUser;
use App\Models\Announcement;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\DeletedModels\Models\DeletedModel;

class Announcements extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Announcements';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Announcements';

    protected string $view = 'filament.pages.announcements';

    public ?array $data = [];

    public ?int $loadedAnnouncementId = null;

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
                    ->live(debounce: 1000)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                        if (filled($get('subject'))) {
                            return;
                        }

                        if (preg_match('/^\s{0,3}#{1,6}\s+(.+?)\s*#*\s*$/m', (string) $state, $matches)) {
                            $set('subject', Str::limit(trim($matches[1]), 255, ''));
                        }
                    })
                    ->columnSpanFull()
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('announcements'),
                Section::make('Send to your user base')
                    ->description('The announcement goes to all verified users. Use the toggles to narrow it down.')
                    ->disabled(fn (Get $get): bool => filled($get('custom_emails')))
                    ->schema([
                        Toggle::make('admins_only')
                            ->label('Send to admins only')
                            ->helperText('Only send to admin users (useful for previewing the announcement).'),
                        Toggle::make('include_deleted_users')
                            ->label('Include deleted users')
                            ->helperText('Also send to former users stored in deleted models.'),
                    ]),
                Section::make('Or send to custom addresses only')
                    ->description('When you enter addresses here, the announcement is sent only to them. Your user base receives nothing, and recipients do not need to be users.')
                    ->schema([
                        TagsInput::make('custom_emails')
                            ->label('Custom email addresses')
                            ->helperText('Type an address and press Enter, Tab or comma to add it.')
                            ->placeholder('name@example.com')
                            ->splitKeys(['Enter', 'Tab', ',', ' '])
                            ->trim()
                            ->nestedRecursiveRules(['email'])
                            ->live()
                            ->columnSpanFull(),
                    ]),
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
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Announcement::query())
            ->heading('History')
            ->description('Load a previous announcement to edit and re-send it.')
            ->defaultSort('last_sent_at', 'desc')
            ->columns([
                TextColumn::make('subject')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_sent_at')
                    ->label('Last sent')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('load')
                    ->icon('heroicon-o-pencil-square')
                    ->action(function (Announcement $record): void {
                        $this->form->fill([
                            'subject' => $record->subject,
                            'body' => $record->body,
                        ]);
                        $this->loadedAnnouncementId = $record->id;
                    }),
                DeleteAction::make()
                    ->after(function (Announcement $record): void {
                        if ($this->loadedAnnouncementId === $record->id) {
                            $this->loadedAnnouncementId = null;
                        }
                    }),
            ])
            ->emptyStateHeading('No announcements sent yet')
            ->paginated([10, 25]);
    }

    public function send(): void
    {
        $data = $this->form->getState();
        $recipientCount = 0;

        Announcement::recordSend($data['subject'], $data['body'], $this->loadedAnnouncementId);

        $customEmails = collect($data['custom_emails'] ?? [])
            ->map(fn (string $email): string => strtolower(trim($email)))
            ->filter()
            ->unique();

        if ($customEmails->isNotEmpty()) {
            foreach ($customEmails as $email) {
                dispatch(new SendAnnouncementToUser('', $email, $data['subject'], $data['body']));
                $recipientCount++;
            }

            $this->resetAnnouncementForm();

            Notification::make()
                ->title('Announcement queued')
                ->body("Sending to {$recipientCount} custom email address(es).")
                ->success()
                ->send();

            return;
        }

        $query = User::whereNotNull('email_verified_at');

        if ($data['admins_only'] ?? false) {
            $query->where('is_admin', true);
        } elseif (! app()->isProduction()) {
            $query->where('id', auth()->id());
        }

        $users = $query->get();
        $activeEmails = $users->pluck('email')->map(fn (string $email): string => strtolower($email))->toArray();

        foreach ($users as $user) {
            dispatch(new SendAnnouncementToUser($user->name, $user->email, $data['subject'], $data['body']));
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

                dispatch(new SendAnnouncementToUser($name, $email, $data['subject'], $data['body']));
                $recipientCount++;
            }
        }

        $this->resetAnnouncementForm();

        Notification::make()
            ->title('Announcement queued')
            ->body("Sending to {$recipientCount} user(s).")
            ->success()
            ->send();
    }

    protected function resetAnnouncementForm(): void
    {
        $this->form->fill();
        $this->loadedAnnouncementId = null;
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
                ->modalDescription(function (): string {
                    $customEmails = collect($this->data['custom_emails'] ?? [])->filter();

                    if ($customEmails->isNotEmpty()) {
                        return "This will only send the announcement to the {$customEmails->count()} custom email address(es) you entered.";
                    }

                    return ($this->data['admins_only'] ?? false)
                        ? 'This will only send the announcement to admin users.'
                        : 'Are you sure you want to send this announcement to all verified users?';
                })
                ->action('send'),
        ];
    }
}
