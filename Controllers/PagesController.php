<?php

namespace Tabel\Controllers;

use Tabel\Controllers\Controller;
use Tabel\Core\Mantle\Logger;

class PagesController extends Controller {

    public function __construct() {
        //   $this->middleware('auth');
    }
    public function index() {
        
        return view('index');
    }
}