<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-blue-600 dark:bg-blue-600 border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-wider hover:bg-blue-700 dark:hover:bg-blue-500 focus:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-navy-900 shadow-md shadow-blue-600/20 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
