<?php

use Babel\Core\Mantle\Validator;




echo '<pre>';

var_dump($e);
echo '<br>';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form action="" method="post">
        <input type="hidden" token="<?=uniqid(23);?>">
        <input type="text" name="name" placeholder="">
        <input type="text" name="email" placeholder="">
        <input type="password" name="password" placeholder="">
        <input type="text" name="role" placeholder="">
        <button type="submit">Submit</button>
    </form>
</body>

</html>