<?php

namespace App\Filament\Resources\Works\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WorkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(150)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, string $state, callable $set): void {
                        if ($operation === 'create') {
                            $set('slug', \Illuminate\Support\Str::slug($state));
                        }
                    }),

                TextInput::make('slug')
                    ->required()
                    ->maxLength(150)
                    ->unique(ignoreRecord: true),

                TextInput::make('excerpt')
                    ->required()
                    ->maxLength(300)
                    ->helperText('Short description for the card (max 300 chars)')
                    ->columnSpanFull(),

                RichEditor::make('description')
                    ->required()
                    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link', 'h2', 'h3'])
                    ->columnSpanFull(),

                FileUpload::make('image')
                    ->image()
                    ->disk('public')
                    ->directory('works')
                    ->automaticallyResizeImagesMode('cover')
                    ->automaticallyResizeImagesToWidth('1200')
                    ->automaticallyResizeImagesToHeight('675')
                    ->helperText('Best result: 16:9 ratio, e.g. 765×430px or larger.')
                    ->columnSpanFull(),

                TextInput::make('url')
                    ->label('Live Site URL')
                    ->url()
                    ->maxLength(500)
                    ->placeholder('https://example.com'),

                TagsInput::make('tags')
                    ->label('Tech Tags')
                    ->placeholder('Add tag…')
                    ->suggestions(['PHP', 'TypeScript', 'Vue', 'React', 'Next.js', 'Laravel', 'Node.js', 'Tailwind', 'MySQL', 'Docker']),

                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower numbers appear first'),

                Toggle::make('is_featured')
                    ->label('Featured on Homepage'),

                DateTimePicker::make('published_at')
                    ->label('Publish Date')
                    ->helperText('Leave empty to save as draft'),
            ])
            ->columns(2);
    }
}
