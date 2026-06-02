<?php

namespace App\Http\Controllers\Disciplinary;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class DisciplinaryPortalController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->to(auth()->user()->disciplinaryPortalUrl());
    }
}
