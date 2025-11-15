<?php

namespace Badrshs\DynamicImageComposer\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Badrshs\DynamicImageComposer\Models\ImageTemplate;

class ImageTemplateResource extends Resource
{
    protected static ?string $model = ImageTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Image Composer';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpan(1),
                ]),

                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('background_image')
                    ->label('Background Image')
                    ->required()
                    ->image()
                    ->disk(config('dynamic-image-composer.disk'))
                    ->directory(config('dynamic-image-composer.templates_directory'))
                    ->imageEditor()
                    ->maxSize(10240)
                    ->columnSpanFull(),

                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('width')
                        ->numeric()
                        ->default(2480)
                        ->required(),

                    Forms\Components\TextInput::make('height')
                        ->numeric()
                        ->default(3508)
                        ->required(),
                ]),

                Forms\Components\KeyValue::make('settings')
                    ->label('Additional Settings')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('background_image')
                    ->label('Preview')
                    ->disk(config('dynamic-image-composer.disk'))
                    ->size(80),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('elements_count')
                    ->label('Elements')
                    ->counts('elements'),

                Tables\Columns\TextColumn::make('width')
                    ->label('Dimensions')
                    ->formatStateUsing(fn($record) => "{$record->width}x{$record->height}"),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('designer')
                    ->label('Designer')
                    ->icon('heroicon-o-paint-brush')
                    ->url(fn($record): string => route('image-template.designer', ['template' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \Badrshs\DynamicImageComposer\Filament\Resources\ImageTemplateResource\Pages\ListImageTemplates::route('/'),
            'create' => \Badrshs\DynamicImageComposer\Filament\Resources\ImageTemplateResource\Pages\CreateImageTemplate::route('/create'),
            'edit' => \Badrshs\DynamicImageComposer\Filament\Resources\ImageTemplateResource\Pages\EditImageTemplate::route('/{record}/edit'),
        ];
    }
}
