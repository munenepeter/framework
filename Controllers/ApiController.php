<?php

namespace Babel\Controllers;

use Babel\Core\Mantle\Request;
use Babel\Models\Customer;

class ApiController {

    public function customers() {
        echo Request::uri();
        exit;
        echo json_encode(Customer::all());
    }
}
