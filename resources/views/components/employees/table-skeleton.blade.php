@props(['rows' => 8])

@for ($i = 0; $i < $rows; $i++)
    <tr wire:key="emp-skeleton-{{ $i }}" class="animate-pulse">
        <td class="w-8 px-2 py-2">
            <div class="mx-auto h-3 w-3 rounded bg-slate-200 dark:bg-white/10"></div>
        </td>
        <td class="px-3 py-2">
            <div class="flex items-center gap-2">
                <div class="h-7 w-7 shrink-0 rounded-full bg-slate-200 dark:bg-white/10"></div>
                <div class="h-3 w-48 rounded bg-slate-200 dark:bg-white/10"></div>
            </div>
        </td>
        <td class="hidden px-3 py-2 md:table-cell">
            <div class="h-3 w-32 rounded bg-slate-200 dark:bg-white/10"></div>
        </td>
        <td class="px-3 py-2">
            <div class="h-4 w-12 rounded-full bg-slate-200 dark:bg-white/10"></div>
        </td>
        <td class="px-3 py-2">
            <div class="ml-auto h-3 w-8 rounded bg-slate-200 dark:bg-white/10"></div>
        </td>
    </tr>
@endfor
