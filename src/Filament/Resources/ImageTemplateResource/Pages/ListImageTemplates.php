<?php

namespace Molham\DynamicImageComposer\Filament\Resources\ImageTemplateResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Molham\DynamicImageComposer\Filament\Resources\ImageTemplateResource;

class ListImageTemplates extends ListRecords
{
    protected static string $resource = ImageTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
