<?php

use Illuminate\Support\Facades\Route;
use Jguapin\ApprovalMapping\Http\Controllers\ApprovalMappingVersionController;

Route::prefix(config('approval-mapping.route.api_prefix', 'api/v1/approval-mapping'))
    ->middleware(config('approval-mapping.route.api_middleware', ['api', 'auth:sanctum']))
    ->group(function () {
        Route::get('lookup/{type}', [ApprovalMappingVersionController::class, 'lookup'])->name('approval-mapping.lookup');
        Route::get('versions', [ApprovalMappingVersionController::class, 'index'])->name('approval-mapping.versions.index');
        Route::post('versions', [ApprovalMappingVersionController::class, 'store'])->name('approval-mapping.versions.store');
        Route::post('versions/save-as-new', [ApprovalMappingVersionController::class, 'saveAsNew'])->name('approval-mapping.versions.save-as-new');
        Route::get('versions/{version}', [ApprovalMappingVersionController::class, 'show'])->name('approval-mapping.versions.show');
        Route::put('versions/{version}', [ApprovalMappingVersionController::class, 'update'])->name('approval-mapping.versions.update');
        Route::delete('versions/{version}', [ApprovalMappingVersionController::class, 'destroy'])->name('approval-mapping.versions.destroy');
        Route::get('versions/{version}/details', [ApprovalMappingVersionController::class, 'details'])->name('approval-mapping.versions.details');
        Route::put('versions/{version}/activate', [ApprovalMappingVersionController::class, 'activate'])->name('approval-mapping.versions.activate');
        Route::put('versions/{version}/mappings-levels', [ApprovalMappingVersionController::class, 'saveMappingsLevels'])->name('approval-mapping.versions.mappings-levels');
        Route::post('versions/{version}/sync-to-module', [ApprovalMappingVersionController::class, 'syncToModule'])->name('approval-mapping.versions.sync');
    });
