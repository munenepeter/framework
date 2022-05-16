<?php

namespace Babel\Controllers;

use Babel\Core\Mantle\App;

class ApiController {

    public function users() {
        $data = App::get('database')->selectAll('Customers');
        echo json_encode($data);
    }
}
