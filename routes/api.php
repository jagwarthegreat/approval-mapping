<?php

use Illuminate\Support\Facades\Route;
use Jguapin\ApprovalMapping\Http\Controllers\ApprovalMappingVersionController;

Route::prefix(config('approval-mapping.route.api_prefix', 'api/v1/approval-mapping'))
    ->middleware(config('approval-mapping.route.api_middleware', ['api', 'auth:sanctum']))
    ->group(function () {
        Route::get('versions', [ApprovalMappingVersionController::class, 'index'])->name('approval-mapping.versions.index');
        Route::post('versions', [ApprovalMappingVersionController::class, 'store'])->name('approval-mapping.versions.store');
        Route::get('versions/{version}', [ApprovalMappingVersionController::class, 'show'])->name('approval-mapping.versions.show');
        Route::put('versions/{version}/activate', [ApprovalMappingVersionController::class, 'activate'])->name('approval-mapping.versions.activate');
    });
