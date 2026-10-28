@php
    $htmlName = preg_replace('/\.([^\.]+)/', '[$1]', $name);
    $fieldValue = old($name, $value ?? '');
@endphp
<label class="flex flex-col gap-1 text-sm">
    <span>{{ trans('admin::tax.'.($label ?? last(explode('.', $name)))) }}</span>
    @if (isset($options))
        <select name="{{ $htmlName }}" class="rounded border border-gray-300 bg-white p-2 text-gray-900" @required($required ?? false)>
            @foreach ($options as $key => $text)
                <option value="{{ $key }}" @selected((string) $fieldValue === (string) $key)>{{ $text }}</option>
            @endforeach
        </select>
    @else
        <input name="{{ $htmlName }}" type="{{ $type ?? 'text' }}" value="{{ $fieldValue }}" class="rounded border border-gray-300 bg-white p-2 text-gray-900" @required($required ?? false) maxlength="{{ $maxlength ?? 255 }}">
    @endif
</label>

