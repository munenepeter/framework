<div class="bg-indigo-900 px-4 py-4">
  <div class="md:max-w-6xl md:mx-auto md:flex md:items-center md:justify-between" x-data="{ open: false }" x-cloak>
    <div class="flex justify-between items-center">
      <a href="#" class="inline-block py-2 text-white text-xl font-bold">Bank Transactions</a>
      <div class="inline-block cursor-pointer md:hidden" x-on:click="open = true">
        <div class="bg-gray-400 w-8 mb-2" style="height: 2px;"></div>
        <div class="bg-gray-400 w-8 mb-2" style="height: 2px;"></div>
        <div class="bg-gray-400 w-8" style="height: 2px;"></div>
      </div>
    </div>

    <div x-show="open" x-on:click.away="open = false">
      <a href="#" class="block py-2 text-gray-100">How it Works</a>
      <a href="#" class="block py-2 text-indigo-300 hover:text-gray-100">Services</a>
      <a href="#" class="block py-2 text-indigo-300 hover:text-gray-100">Blog</a>

      <div class="flex items-center justify-between pt-4">
        <a href="#" class="w-1/2 inline-block py-2 px-4 text-gray-600 hover:text-indigo-600 bg-gray-100 hover:bg-gray-200 rounded-lg text-center mr-2 font-bold">Login</a>
        <a href="#" class="w-1/2 inline-block py-2 px-4 text-white bg-red-500 hover:bg-red-600 rounded-lg text-center font-bold">Sign Up</a>
      </div>
    </div>

    <div>
      <div class="hidden md:block">
        <a href="#" class="inline-block py-1 md:py-4 text-gray-100 mr-6 font-bold">How it Works</a>
        <a href="#" class="inline-block py-1 md:py-4 text-gray-500 hover:text-gray-100 mr-6">Services</a>
        <a href="#" class="inline-block py-1 md:py-4 text-gray-500 hover:text-gray-100">Blog</a>
      </div>
    </div>
    <div class="hidden md:block">
      <a href="#" class="inline-block py-1 md:py-4 text-gray-500 hover:text-gray-100 mr-6">Login</a>
      <a href="#" class="inline-block py-2 px-4 text-gray-700 bg-white hover:bg-gray-100 rounded-lg">Sign Up</a>
    </div>
  </div>
</div>