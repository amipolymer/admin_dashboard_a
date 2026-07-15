@foreach($data as $key => $value)

    @php
        $label = ucwords(str_replace('_', ' ', preg_replace('/([a-z])([A-Z])/', '$1 $2', $key)));
    @endphp

    {{-- 🔹 If value is array/object --}}
    @if(is_array($value) || is_object($value))

        <div class="mb-3">
            <h5 class="text-primary">{{ $label }}</h5>
            <div class="pl-3 border-left">

                @if(array_keys((array)$value) === range(0, count((array)$value) - 1))
                    {{-- Indexed array (like documents[]) --}}
                    
                    @foreach($value as $item)
                        <div class="mb-2 p-2 border rounded bg-light">
                            @include('components.dynamic-data', ['data' => (array)$item])
                        </div>
                    @endforeach

                @else
                    {{-- Associative array --}}
                    @include('components.dynamic-data', ['data' => (array)$value])
                @endif

            </div>
        </div>

    @else

        {{-- 🔹 Normal field --}}
        <div class="mb-2">
            <strong>{{ $label }}:</strong>

            @if(is_bool($value))
                {{ $value ? 'Yes' : 'No' }}

            {{-- Detect file/image URL --}}
            @elseif(filter_var($value, FILTER_VALIDATE_URL))
                
                @if(Str::endsWith($value, ['.jpg','.jpeg','.png','.webp']))
                    <br>
                    <img src="{{ $value }}" width="120" class="img-thumbnail mt-1">
                @else
                    <br>
                    <a href="{{ $value }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                        View File
                    </a>
                @endif

            @else
                {{ $value ?? 'N/A' }}
            @endif
        </div>

    @endif

@endforeach