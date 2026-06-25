<?php

namespace Jguapin\ApprovalMapping\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ApprovalMappingWebController extends Controller
{
    public function index(): View
    {
        return view('approval-mapping::versions.index', [
            'apiBase' => url(trim(config('approval-mapping.route.web_prefix', 'approval-mapping'), '/').'/api'),
            'csrfToken' => csrf_token(),
        ]);
    }
}
