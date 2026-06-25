<?php

use Illuminate\Support\Facades\Route;
use Jguapin\ApprovalMapping\Http\Controllers\ApprovalMappingVersionController;
use Jguapin\ApprovalMapping\Http\Controllers\Web\ApprovalMappingWebController;

$prefix = trim(config('approval-mapping.route.web_prefix', 'approval-mapping'), '/');
$middleware = config('approval-mapping.route.web_middleware', ['web', 'auth']);

Route::prefix($prefix)
    ->middleware($middleware)
    ->group(function () {
        Route::get('/', [ApprovalMappingWebController::class, 'index'])->name('approval-mapping.web.index');
        Route::get('/assets/approval-mapping.css', function () {
            return response()->file(__DIR__.'/../resources/css/approval-mapping.css', ['Content-Type' => 'text/css']);
        })->name('approval-mapping.web.assets.css');

        Route::prefix('api')->group(function () {
            Route::get('lookup/{type}', [ApprovalMappingVersionController::class, 'lookup'])->name('approval-mapping.web.lookup');
            Route::get('versions', [ApprovalMappingVersionController::class, 'index'])->name('approval-mapping.web.versions.index');
            Route::post('versions', [ApprovalMappingVersionController::class, 'store'])->name('approval-mapping.web.versions.store');
            Route::post('versions/save-as-new', [ApprovalMappingVersionController::class, 'saveAsNew'])->name('approval-mapping.web.versions.save-as-new');
            Route::get('versions/{version}', [ApprovalMappingVersionController::class, 'show'])->name('approval-mapping.web.versions.show');
            Route::put('versions/{version}', [ApprovalMappingVersionController::class, 'update'])->name('approval-mapping.web.versions.update');
            Route::delete('versions/{version}', [ApprovalMappingVersionController::class, 'destroy'])->name('approval-mapping.web.versions.destroy');
            Route::get('versions/{version}/details', [ApprovalMappingVersionController::class, 'details'])->name('approval-mapping.web.versions.details');
            Route::put('versions/{version}/activate', [ApprovalMappingVersionController::class, 'activate'])->name('approval-mapping.web.versions.activate');
            Route::put('versions/{version}/mappings-levels', [ApprovalMappingVersionController::class, 'saveMappingsLevels'])->name('approval-mapping.web.versions.mappings-levels');
            Route::post('versions/{version}/sync-to-module', [ApprovalMappingVersionController::class, 'syncToModule'])->name('approval-mapping.web.versions.sync');
        });
    });
