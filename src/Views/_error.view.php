<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?php asset("imgs/favicon/error-favicon.svg") ?>" type="image/svg">
    <link rel="stylesheet" href="<?php asset("css/main.css") ?>">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">


    <title>Error <?= $code; ?></title>
</head>

<body class="bg-gradient-to-br from-rose-50 to-orange-50 font-['Inter']">
    <div class="grid h-screen place-items-center">
        <div class="w-full max-w-2xl m-2 overflow-hidden transition-all shadow-2xl rounded-xl hover:shadow-xl">
            <div class="p-8 bg-gradient-to-r from-rose-100 to-orange-100">
                <?php
                $errors = app()->get('config')[$code];

                $client_title = ucwords(str_replace('-', ' ', key($errors)));
                $client_message = trim(reset($errors), '"\'');
                ?>

                <h2 class="py-2 mb-4 text-5xl font-bold text-orange-600"><?= $code ?> - <?= $client_title; ?></h2>
                <p class="mb-4 text-lg text-purple-700"><?= $client_message; ?></p>
                <p class="text-sm text-purple-400"><?= date("D, d M Y H:i:s") ?></p>
            </div>

            <div class="p-8 space-y-6 bg-white">
                <div>
                    <h3 class="mb-2 text-lg font-semibold text-gray-800">What happened?</h3>
                    <p class="text-gray-600"><?= (app()->get('config')['app']['env'] === 'development') ? $message : $client_message; ?></p>
                </div>

                <div class="pt-4 space-y-2 border-t border-gray-100">
                    <p class="text-gray-600">Your IP: <span class="font-mono text-blue-600"><?= $_SERVER['REMOTE_ADDR'] ?></span></p>
                    <p class="text-gray-600">Log ID: <span class="font-mono text-blue-600"><?= md5(time()) ?></span></p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>