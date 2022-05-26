<?php

namespace Tabel\Controllers;

use Tabel\Models\Customer;

class PagesController {
    public function index() {

        return view('index');
    }
    public function customer($id) {
        $c = Customer::find($id);
        return view(
            'customers',
            ['customer' => $c]
        );
    }
}
