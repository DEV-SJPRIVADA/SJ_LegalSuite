<?php

namespace App\Http\Controllers\Licitaciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class LicitacionesPortalController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->to(auth()->user()->licitacionesPortalUrl());
    }
}
