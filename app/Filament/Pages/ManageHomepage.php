<?php

namespace App\Filament\Pages;

use App\Models\HomeSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageHomepage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;
    protected static ?string $navigationLabel = 'Homepage';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.manage-homepage';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(HomeSetting::current()->toArray());
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('hero_headline')
                    ->label('Hero Headline')
                    ->required()
                    ->maxLength(100),

                TextInput::make('hero_tagline')
                    ->label('Hero Tagline')
                    ->required()
                    ->maxLength(100)
                    ->helperText('Shown below the headline, e.g. "Full-Stack Developer"'),

                RichEditor::make('hero_description')
                    ->label('Hero Description')
                    ->toolbarButtons(['bold', 'italic', 'link'])
                    ->columnSpanFull(),

                RichEditor::make('about_text')
                    ->label('About Text')
                    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link'])
                    ->columnSpanFull(),

                TagsInput::make('skills')
                    ->label('Skills / Tech Stack')
                    ->placeholder('Add a skill…')
                    ->columnSpanFull(),

                TextInput::make('cv_url')
                    ->label('CV / Résumé URL')
                    ->url()
                    ->maxLength(500),
            ])
            ->columns(2)
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
        $setting = HomeSetting::current();
        $setting->update($this->form->getState());

        Notification::make()
            ->title('Homepage saved')
            ->success()
            ->send();
    }
}
