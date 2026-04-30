<?php

namespace App\Filament\Pages;

use App\Models\PrivacySetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManagePrivacyPolicy extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static ?string $navigationLabel = 'Privacy Policy';
    protected static ?int $navigationSort = 4;
    protected string $view = 'filament.pages.manage-privacy-policy';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(PrivacySetting::current()->toArray());
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('title')
                    ->label('Page Title')
                    ->required()
                    ->maxLength(200)
                    ->columnSpanFull(),

                RichEditor::make('content')
                    ->label('Content')
                    ->toolbarButtons(['bold', 'italic', 'h2', 'h3', 'bulletList', 'orderedList', 'link'])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $setting = PrivacySetting::current();
        $setting->update($this->form->getState());

        Notification::make()
            ->title('Privacy policy saved')
            ->success()
            ->send();
    }
}
