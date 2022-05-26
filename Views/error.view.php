<?php

use Babel\Core\Mantle\App; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" href="../static/imgs/favicon/error-favicon.svg" type="image/gif">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" integrity="sha512-wnea99uKIC3TJF7v4eKk4Y+lMz2Mklv18+r4na2Gn1abDRPPOeef95xTzdwGD9e6zXJBteMIhZ1+68QC5byJZw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Error <?= $code; ?></title>
</head>

<body>



    <div class="grid place-items-center h-screen">

        <div class="space-y-4  text-center">
            <?php if (ENV === 'development') : ?>
                <h2 class="text-4xl"><?= $code; ?></h2>
                <p><?= $message; ?></p>
            <?php endif; ?>
            <?php if (ENV === 'production') : ?>
                <?php
                $errors = App::get('config')['codes'][$code];
                ?>
                <h2 class="text-4xl"><?= $errors[0]; ?></h2>
                <p><?= $errors[1]; ?></p>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>