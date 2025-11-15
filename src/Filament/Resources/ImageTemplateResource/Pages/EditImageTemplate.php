<?php

namespace Badrshs\DynamicImageComposer\Filament\Resources\ImageTemplateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Badrshs\DynamicImageComposer\Filament\Resources\ImageTemplateResource;

class EditImageTemplate extends EditRecord
{
    protected static string $resource = ImageTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
