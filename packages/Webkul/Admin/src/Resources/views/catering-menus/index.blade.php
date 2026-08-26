<x-admin::layouts>
    <x-slot:title>Catering Menus</x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
            <div>
                <p class="text-sm text-gray-500">Products / Catering Menus</p>
                <h1 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">Catering Menus</h1>
                <p class="mt-1 text-sm text-gray-500">Create reusable menus that sales can apply to any proposal.</p>
            </div>

            @if (bouncer()->hasPermission('catering_menus.create'))
                <a href="{{ route('admin.catering.menus.create') }}" class="primary-button">Create Menu</a>
            @endif
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            @if ($menus->isEmpty())
                <div class="p-12 text-center">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">No catering menus yet</h2>
                    <p class="mt-2 text-sm text-gray-500">Create your first menu, choose its dishes, and it will appear in the proposal builder.</p>
                    @if (bouncer()->hasPermission('catering_menus.create'))
                        <a href="{{ route('admin.catering.menus.create') }}" class="primary-button mt-5 inline-flex">Create First Menu</a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-gray-950">
                            <tr><th class="px-4 py-3">Menu</th><th class="px-4 py-3">Price / person</th><th class="px-4 py-3">Minimum guests</th><th class="px-4 py-3">Dishes</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($menus as $menu)
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="px-4 py-4"><p class="font-semibold text-gray-800 dark:text-white">{{ $menu->name }}</p>@if ($menu->name_ar)<p class="mt-1 text-sm text-gray-600" dir="rtl" lang="ar">{{ $menu->name_ar }}</p>@endif<p class="mt-1 max-w-xl truncate text-xs text-gray-500">{{ $menu->description ?: 'No description' }}</p></td>
                                    <td class="px-4 py-4 font-medium">SAR {{ number_format((float) $menu->price_per_person, 2) }}</td>
                                    <td class="px-4 py-4">{{ $menu->minimum_guests ?: '—' }}</td>
                                    <td class="px-4 py-4">{{ $menu->items_count }}</td>
                                    <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $menu->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $menu->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td class="px-4 py-4"><div class="flex justify-end gap-3">
                                        @if (bouncer()->hasPermission('catering_menus.edit'))<a href="{{ route('admin.catering.menus.edit', $menu) }}" class="font-medium text-brandColor">Edit</a>@endif
                                        @if (bouncer()->hasPermission('catering_menus.delete'))
                                            <form method="POST" action="{{ route('admin.catering.menus.delete', $menu) }}" onsubmit="return confirm('Delete this catering menu?')">@csrf @method('DELETE')<button type="submit" class="font-medium text-red-600">Delete</button></form>
                                        @endif
                                    </div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-admin::layouts>
