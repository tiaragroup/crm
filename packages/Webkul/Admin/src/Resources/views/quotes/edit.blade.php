<x-admin::layouts>
    <x-slot:title>Edit Catering Proposal</x-slot>

    <x-admin::form
        :action="route('admin.quotes.update', $quote->id).'?'.http_build_query(array_merge(request()->route()->parameters(), request()->all()))"
        method="PUT"
    >
        @include('admin::quotes.form', ['isEdit' => true])
    </x-admin::form>
</x-admin::layouts>
