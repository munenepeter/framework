<?php

use Babel\Models\Customer;

include_once 'base.view.php' ?>
<?php
$c = Customer::find($id);
 

echo '<pre>';
var_dump($c);
