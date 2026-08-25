@php
    $isEdit = $menu->exists;
    $selected = collect($selectedProductIds)->map(fn ($id) => (int) $id)->all();
    $inputClass = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 outline-none transition focus:border-brandColor dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300';
@endphp

<x-admin::layouts>
    <x-slot:title>{{ $isEdit ? 'Edit Catering Menu' : 'Create Catering Menu' }}</x-slot>

    <form method="POST" action="{{ $isEdit ? route('admin.catering.menus.update', $menu) : route('admin.catering.menus.store') }}">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="flex flex-col gap-4">
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                <div><p class="text-sm text-gray-500"><a href="{{ route('admin.catering.menus.index') }}" class="hover:text-brandColor">Catering Menus</a> / {{ $isEdit ? 'Edit' : 'Create' }}</p><h1 class="mt-1 text-xl font-bold text-gray-800 dark:text-white">{{ $isEdit ? 'Edit Catering Menu' : 'Create Catering Menu' }}</h1></div>
                <div class="flex gap-2"><a href="{{ route('admin.catering.menus.index') }}" class="secondary-button">Cancel</a><button type="submit" class="primary-button">{{ $isEdit ? 'Save Changes' : 'Create Menu' }}</button></div>
            </div>

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700"><p class="font-semibold">Please correct the menu information.</p><ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <div class="grid grid-cols-[minmax(0,1fr)_340px] gap-4 max-xl:grid-cols-1">
                <div class="flex flex-col gap-4">
                    <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                        <header class="mb-4 border-b border-gray-200 pb-3 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white">Menu Details</h2><p class="text-xs text-gray-500">This information is copied into a proposal when sales applies the menu.</p></header>
                        <div class="grid grid-cols-2 gap-4 max-md:grid-cols-1">
                            <div class="col-span-2 max-md:col-span-1"><label class="mb-1.5 block text-sm font-medium">Menu name *</label><input name="name" required value="{{ old('name', $menu->name) }}" placeholder="Example: Menu One" class="{{ $inputClass }}"></div>
                            <div class="col-span-2 max-md:col-span-1"><label class="mb-1.5 block text-sm font-medium">Client-facing description</label><textarea name="description" rows="3" placeholder="Short description of the menu" class="{{ $inputClass }}">{{ old('description', $menu->description) }}</textarea></div>
                            <div><label class="mb-1.5 block text-sm font-medium">Price per person (SAR) *</label><input type="number" name="price_per_person" required min="0" step="0.01" value="{{ old('price_per_person', $menu->price_per_person ?? 0) }}" class="{{ $inputClass }}"></div>
                            <div><label class="mb-1.5 block text-sm font-medium">Minimum guests</label><input type="number" name="minimum_guests" min="1" value="{{ old('minimum_guests', $menu->minimum_guests) }}" placeholder="Optional" class="{{ $inputClass }}"></div>
                            <div class="col-span-2 max-md:col-span-1"><label class="mb-1.5 block text-sm font-medium">Setup description</label><textarea name="setup_description" rows="3" placeholder="Buffet tables, linens, equipment, setup style…" class="{{ $inputClass }}">{{ old('setup_description', $menu->setup_description) }}</textarea></div>
                            <div class="col-span-2 max-md:col-span-1"><label class="mb-1.5 block text-sm font-medium">Service inclusions <span class="font-normal text-gray-500">(one per line)</span></label><textarea name="service_inclusions" rows="4" placeholder="Waiters&#10;Supervisors&#10;Serving equipment" class="{{ $inputClass }}">{{ old('service_inclusions', $menu->service_inclusions) }}</textarea></div>
                        </div>
                    </section>

                    <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                        <header class="mb-4 border-b border-gray-200 pb-3 dark:border-gray-800"><h2 class="font-semibold text-gray-800 dark:text-white">Choose Dishes</h2><p class="text-xs text-gray-500">Select dishes from your product catalog. They are already grouped into proposal sections.</p></header>
                        <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">
                            @foreach ($categories as $category)
                                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                                    <div class="mb-3 flex items-center justify-between border-b border-gray-100 pb-2 dark:border-gray-800"><h3 class="font-semibold text-gray-800 dark:text-white">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} · {{ $category->name }}</h3><span class="text-xs text-gray-500">{{ $category->products->count() }} available</span></div>
                                    <div class="flex flex-col gap-2">
                                        @forelse ($category->products as $product)
                                            <label class="flex cursor-pointer items-start gap-3 rounded-md p-2 hover:bg-gray-50 dark:hover:bg-gray-950"><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array((int) $product->id, $selected, true)) class="mt-1 h-4 w-4 accent-brandColor"><span><span class="block text-sm font-medium text-gray-800 dark:text-white">{{ $product->name }}</span>@if ($product->description)<span class="mt-0.5 block text-xs text-gray-500">{{ $product->description }}</span>@endif</span></label>
                                        @empty
                                            <p class="py-3 text-sm text-gray-500">No active dishes in this category.</p>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-4 text-xs text-gray-500">Need another dish? Create it under Products, assign its catering category, then return here.</p>
                    </section>
                </div>

                <aside class="h-fit rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 xl:sticky xl:top-4">
                    <h2 class="font-semibold text-gray-800 dark:text-white">Availability</h2>
                    <p class="mt-1 text-xs text-gray-500">Inactive menus stay saved but do not appear in the proposal builder.</p>
                    <input type="hidden" name="is_active" value="0">
                    <label class="mt-4 flex cursor-pointer items-center gap-3 rounded-md border border-gray-200 p-4 dark:border-gray-800"><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $menu->exists ? $menu->is_active : true)) class="h-4 w-4 accent-brandColor"><span><span class="block text-sm font-semibold">Active menu</span><span class="text-xs text-gray-500">Available to sales users</span></span></label>
                    <div class="mt-5 rounded-md bg-amber-50 p-4 text-xs leading-5 text-amber-800">Changes affect future proposals. Existing proposals keep their own saved menu snapshot.</div>
                </aside>
            </div>
        </div>
    </form>
</x-admin::layouts>
