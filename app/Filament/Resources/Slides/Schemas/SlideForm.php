<?php

namespace App\Filament\Resources\Slides\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SlideForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->columnSpanFull(),
                FileUpload::make('image_url')
                    ->label('Image')
                    ->image()
                    ->disk('public')
                    ->directory('slides')
                    ->visibility('public')
                    ->required(),
                TextInput::make('route')
                    ->maxLength(255)
                    ->helperText('Optional. e.g. facebook, instagram, etc.'),
                TextInput::make('button_text')
                    ->maxLength(255),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->nullable(),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->nullable(),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
