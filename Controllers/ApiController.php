<?php

namespace Tabel\Controllers;

use Tabel\Core\Mantle\Request;
use Tabel\Models\Customer;

class ApiController {

    public function customers() {
        echo Request::uri();
        exit;
        echo json_encode(Customer::all());
    }
}
