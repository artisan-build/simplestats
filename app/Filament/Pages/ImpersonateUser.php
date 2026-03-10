<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;

class ImpersonateUser extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Impersonate User';

    protected static ?string $title = 'Impersonate User';

    protected string $view = 'filament.pages.impersonate-user';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('user_id')
                    ->label('User')
                    ->options(
                        User::query()
                            ->where('id', '!=', auth()->id())
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (User $user) => [
                                $user->id => "{$user->name} ({$user->email})",
                            ])
                    )
                    ->searchable()
                    ->required()
                    ->placeholder('Select a user to impersonate'),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('impersonate')
                    ->footer([
                        Actions::make($this->getFormActions()),
                    ]),
            ]);
    }

    public function impersonate(): void
    {
        $data = $this->form->getState();

        $user = User::findOrFail($data['user_id']);

        auth()->user()->impersonate($user);

        $this->redirect('/dashboard');
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('impersonate')
                ->label('Impersonate')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->action('impersonate'),
        ];
    }
}
