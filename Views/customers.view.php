<?php

use Babel\Models\Customer;

include_once 'base.view.php' ?>
<?php
$c = Customer::find($id);
 
echo '<pre>';
print_r($c[0]);
exit;
build_table_from_object($c);



echo '<pre>';
var_dump($c);
