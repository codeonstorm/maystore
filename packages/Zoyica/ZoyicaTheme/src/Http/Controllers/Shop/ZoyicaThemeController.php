<?php

namespace Zoyica\ZoyicaTheme\Http\Controllers\Shop;

use Illuminate\View\View;
use Webkul\Shop\Http\Controllers\Controller;

class ZoyicaThemeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('zoyicatheme::shop.index');
    }
}
