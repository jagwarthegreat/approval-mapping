<?php

use Illuminate\Support\Facades\Route;

Route::prefix(config('approval-mapping.route.web_prefix', 'approval-mapping'))
    ->middleware(config('approval-mapping.route.web_middleware', ['web', 'auth']))
    ->group(function () {
        Route::view('/', 'approval-mapping::index')->name('approval-mapping.web.index');
    });
