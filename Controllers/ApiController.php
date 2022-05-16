<?php

namespace Babel\Controllers;

use Babel\Core\Mantle\App;

class ApiController {

    public function customers() {
        $data = App::get('database')->query('Select * from Customers');
        echo json_encode($data);
    }
}
