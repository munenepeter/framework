<?php

namespace Babel\Controllers;

use Babel\Models\Customer;

class ApiController {

    public function customers() {
        echo json_encode(Customer::all());
    }
}
