<?php
$all = implode(",", $logs);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php asset("css/main.css") ?>">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <title>System Logs</title>
</head>
<body class="bg-gray-100 min-h-screen p-4 font-['Inter']" x-data="{ activeFilter: 'all', selectedRow: null }">
    <div class="max-w-[95%] mx-auto">
        <div class="mb-4">
            <a href="/" class="inline-flex items-center px-4 py-2 text-white transition-colors bg-blue-500 rounded-lg hover:bg-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Back to Application
            </a>
        </div>

        <h1 class="mb-6 text-2xl font-bold text-gray-800">System Logs</h1>
        
        <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="p-4 border-b border-gray-200">
                <div class="flex flex-col gap-4 md:flex-row md:items-center">
                    <div class="grid grid-cols-2 gap-2 md:flex">
                        <button 
                            @click="activeFilter = 'all'" 
                            :class="{ 'ring-2 ring-gray-400': activeFilter === 'all' }"
                            class="inline-flex items-center px-4 py-2 text-gray-700 transition-colors bg-gray-100 rounded hover:bg-gray-200">
                            <span class="font-medium">All</span>
                            <span class="ml-2 px-2 py-0.5 text-xs bg-gray-200 rounded"><?= substr_count($all, 'Debug') + substr_count($all, 'Info') + substr_count($all, 'Error') + substr_count($all, 'Warning') ?></span>
                        </button>
                        <button 
                            @click="activeFilter = 'Debug'" 
                            :class="{ 'ring-2 ring-blue-400': activeFilter === 'Debug' }"
                            class="inline-flex items-center px-4 py-2 text-blue-700 transition-colors rounded bg-blue-50 hover:bg-blue-100">
                            <span class="font-medium">Debug</span>
                            <span class="ml-2 px-2 py-0.5 text-xs bg-blue-100 rounded"><?= substr_count($all, 'Debug') ?></span>
                        </button>
                        <button 
                            @click="activeFilter = 'Info'" 
                            :class="{ 'ring-2 ring-green-400': activeFilter === 'Info' }"
                            class="inline-flex items-center px-4 py-2 text-green-700 transition-colors rounded bg-green-50 hover:bg-green-100">
                            <span class="font-medium">Info</span>
                            <span class="ml-2 px-2 py-0.5 text-xs bg-green-100 rounded"><?= substr_count($all, 'Info') ?></span>
                        </button>
                        <button 
                            @click="activeFilter = 'Error'" 
                            :class="{ 'ring-2 ring-red-400': activeFilter === 'Error' }"
                            class="inline-flex items-center px-4 py-2 text-red-700 transition-colors rounded bg-red-50 hover:bg-red-100">
                            <span class="font-medium">Errors</span>
                            <span class="ml-2 px-2 py-0.5 text-xs bg-red-100 rounded"><?= substr_count($all, 'Error') ?></span>
                        </button>
                        <button 
                            @click="activeFilter = 'Warning'" 
                            :class="{ 'ring-2 ring-yellow-400': activeFilter === 'Warning' }"
                            class="inline-flex items-center px-4 py-2 text-yellow-700 transition-colors rounded bg-yellow-50 hover:bg-yellow-100">
                            <span class="font-medium">Warnings</span>
                            <span class="ml-2 px-2 py-0.5 text-xs bg-yellow-100 rounded"><?= substr_count($all, 'Warning') ?></span>
                        </button>
                    </div>

                    <div class="flex items-center gap-4 md:ml-auto">
                        <div class="relative flex-1">
                            <input 
                                type="text" 
                                id="log-search" 
                                class="w-full py-2 pl-10 pr-4 border border-gray-200 rounded focus:ring-1 focus:ring-gray-400 focus:border-gray-400" 
                                placeholder="Search logs..."
                            >
                            <svg class="absolute w-5 h-5 text-gray-400 -translate-y-1/2 left-3 top-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        
                        <form id="_delete_logs" method="post">
                            <input type="hidden" name="_delete_logs" value="<?= md5(session_get('email')) ?>">
                            <button id="submitBtn" type="submit" class="px-4 py-2 text-white transition-colors bg-red-500 rounded-lg hover:bg-red-600 focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                Delete Logs
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="overflow-y-auto" style="height: 75vh;">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 text-gray-600 bg-gray-50">
                        <tr class="border-b border-gray-200">
                            <th scope="col" class="w-32 px-4 py-3 font-medium text-left">Level</th>
                            <th scope="col" class="w-40 px-4 py-3 font-medium text-left">Time</th>
                            <th scope="col" class="w-32 px-4 py-3 font-medium text-left">Env</th>
                            <th scope="col" class="px-4 py-3 font-medium text-left">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 0; ?>
                        <?php foreach ($logs as $log) : ?>
                            <?php $log = json_decode($log); ?>
                            <tr 
                                x-show="activeFilter === 'all' || activeFilter === '<?= $log->level ?>'"
                                @click="selectedRow === <?= $count ?> ? selectedRow = null : selectedRow = <?= $count ?>" 
                                class="border-b border-gray-100 cursor-pointer hover:bg-gray-50"
                            >
                                <th scope="row" class="flex items-center px-2 py-3 md:px-6">
                                    <?php if ($log->level === "Error") : ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-red-600">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                        </svg>
                                        <span class="pl-2 font-medium text-red-600 whitespace-nowrap ">
                                            <?= $log->level; ?>
                                        </span>
                                    <?php elseif ($log->level === "Debug") : ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-blue-600">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                        </svg>
                                        <span class="pl-2 font-medium text-blue-600 whitespace-nowrap ">
                                            <?= $log->level; ?>
                                        </span>
                                    <?php elseif ($log->level === "Warning") : ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-yellow-600">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v3.75m-9.303 3.376C1.83 19.126 2.914 21 4.645 21h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 4.88c-.866-1.501-3.032-1.501-3.898 0L2.697 17.626zM12 17.25h.007v.008H12v-.008z" />
                                        </svg><span class="pl-2 font-medium text-yellow-600 whitespace-nowrap ">
                                            <?= $log->level; ?>
                                        </span>
                                    <?php else : ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-green-600">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                        </svg>

                                        <span class="font-medium text-green-600 md:pl-2 whitespace-nowrap ">
                                            <?= $log->level; ?>
                                        </span>
                                    <?php endif; ?>
                                </th>
                                <td class="px-6 py-3 font-bold">
                                    <?= $log->time; ?>
                                </td>
                                <td class="px-6 py-3">
                                    <?= app()->get('config.app.env'); ?>
                                </td>
                                <td class="px-2 py-3 md:px-6">
                                    <?php
                                    if (strstr($log->description, '<br />', true) !== false) {
                                        echo strstr($log->description, '<br />', true);
                                    } elseif (strstr($log->description, '<br>', true) !== false) {
                                        echo strstr($log->description, '<br>', true);
                                    } else {
                                        echo $log->description;
                                    }
                                    ?>
                                </td>

                            </tr>
                            <tr 
                                x-show="selectedRow === <?= $count ?> && (activeFilter === 'all' || activeFilter === '<?= $log->level ?>')"
                                class="border-b border-gray-100"
                            >
                                <?php
                                $classes = match ($log->level) {
                                    "Info" => "bg-green-50/30 text-green-800 p-4 space-y-1.5 text-sm",
                                    "Debug" => "bg-blue-50/30 text-blue-800 p-4 space-y-1.5 text-sm",
                                    "Error" => "bg-red-50/30 text-red-800 p-4 space-y-1.5 text-sm",
                                    "Warning" => "bg-yellow-50/30 text-yellow-800 p-4 space-y-1.5 text-sm",
                                }
                                ?>
                                <td class="<?= $classes ?>" colspan="4">
                                    #
                                    <?= $log->id ?? $count; ?><br>
                                    <b>*Message*</b> <i>
                                        <?= $log->description; ?>
                                    </i><br>
                                    <b>*Request*</b>
                                    <?= $log->more->method; ?>
                                    <?= $log->more->uri; ?><br>
                                    <b>*Agent*</b>
                                    <?= $log->more->agent; ?><br>
                                    <b>*User IP*</b>
                                    <?= $log->more->remote_addr; ?>
                                    <b>*User Region*</b>
                                    <?= $log->more->region; ?>
                                    <b>*User Country*</b>
                                    <?= $log->more->country; ?>
                                    <b>*User City*</b>
                                    <?= $log->more->city; ?>
                                    <b>*User Network Provider*</b>
                                    <?= $log->more->provider; ?>
                                    <b>*User Time Zone*</b>
                                    <?= $log->more->time_zone; ?>
                                </td>
                            </tr>
                            <?php $count++; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        const form = document.getElementById('_delete_logs')
        const submitBtn = document.getElementById('submitBtn');
        const url = '/system/logs/delete';

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            submitBtn.classList.add('bg-blue-400', 'hover:bg-blue-500', 'focus:ring-blue-300');
            submitBtn.classList.remove('bg-red-400', 'hover:bg-red-500', 'focus:ring-red-300');
            submitBtn.innerHTML = "Deleting Logs..."

            const formData = new FormData(form);

            axios.post(url, formData).then(response => {

                submitBtn.classList.add('bg-green-400', 'hover:bg-green-500', 'focus:ring-green-300');
                submitBtn.classList.remove('bg-blue-400', 'hover:bg-blue-500', 'focus:ring-red-300');
                submitBtn.innerHTML = "Logs Deleted";
                window.location.replace(window.location.pathname + window.location.search + window.location.hash);


                setTimeout(function() {
                    submitBtn.classList.remove('bg-green-400', 'hover:bg-green-500', 'focus:ring-green-300');
                    submitBtn.classList.add('bg-red-400', 'hover:bg-red-500', 'focus:ring-red-300');
                    submitBtn.innerHTML = "Delete Logs";
                }, 10000);
            }).catch(error => {
                submitBtn.innerHTML = "Could not Delete the logs!";
            });
        });

        //  search
        document.getElementById('log-search').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr:not([x-show])');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>

</html>
