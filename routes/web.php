<?php

use Badrshs\DynamicImageComposer\Http\Controllers\TemplateDesignerController;
use Badrshs\DynamicImageComposer\Models\ImageTemplate;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dynamic Image Composer Routes
|--------------------------------------------------------------------------
|
| These routes provide the template designer interface and API endpoints
| for managing templates, elements, and generating images.
|
*/

Route::middleware(['web', 'auth'])->prefix('image-template/{template}')->name('image-template.')->group(function () {

    // Designer Interface
    Route::get('/designer', [TemplateDesignerController::class, 'show'])
        ->name('designer');

    // API Endpoints
    Route::get('/configuration', [TemplateDesignerController::class, 'getConfiguration'])
        ->name('get-configuration');

    Route::get('/preview', [TemplateDesignerController::class, 'preview'])
        ->name('preview');

    // Element Management
    Route::post('/elements', [TemplateDesignerController::class, 'uploadElement'])
        ->name('elements.upload');

    Route::put('/elements/{element}', [TemplateDesignerController::class, 'updateElement'])
        ->name('elements.update');

    Route::delete('/elements/{element}', [TemplateDesignerController::class, 'deleteElement'])
        ->name('elements.delete');

    // Configuration & Generation
    Route::post('/save-configuration', [TemplateDesignerController::class, 'saveConfiguration'])
        ->name('save-configuration');

    Route::post('/generate', [TemplateDesignerController::class, 'generateFinalCertificate'])
        ->name('generate');
});
