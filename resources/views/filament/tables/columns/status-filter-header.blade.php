@props(['label', 'field'])

<div class="flex flex-col items-center gap-1.5">
    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $label }}</span>
    <select
        wire:change="$set('tableFilters.{{ $field }}.value', $event.target.value === '' ? null : ($event.target.value === '1' ? true : false))"
        class="w-full min-w-[70px] text-xs font-medium border-0 rounded-lg px-2 py-1.5 
               bg-gray-100 dark:bg-gray-700 
               text-gray-600 dark:text-gray-300 
               hover:bg-gray-200 dark:hover:bg-gray-600
               focus:ring-2 focus:ring-primary-500 focus:ring-offset-0
               cursor-pointer transition-all duration-200
               shadow-sm"
    >
        <option value="" class="bg-white dark:bg-gray-800">🔘 All</option>
        <option value="1" class="bg-white dark:bg-gray-800">✅ Yes</option>
        <option value="0" class="bg-white dark:bg-gray-800">❌ No</option>
    </select>
</div>
