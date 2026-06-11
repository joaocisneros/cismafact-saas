@props([
    'id' => 'chart-' . uniqid(),
    'title' => '',
    'data' => [],
    'height' => 'h-48',
])

@if(count($data) > 0)
<div class="bg-white rounded-xl shadow-sm p-6" x-data="chartBar()">
    @if($title)
        <h3 class="text-sm font-semibold text-gray-700 mb-4">{{ $title }}</h3>
    @endif

    <div class="{{ $height }} flex items-end gap-2" x-ref="chart">
        @php
            $max = max(array_column($data, 'value')) ?: 1;
        @endphp

        @foreach($data as $item)
            @php
                $percentage = ($item['value'] / $max) * 100;
            @endphp
            <div class="flex-1 flex flex-col items-center gap-1">
                <span class="text-xs text-gray-500" x-text="{{ $item['value'] }}"></span>
                <div class="w-full rounded-t-md transition-all duration-500"
                     style="height: {{ $percentage }}%"
                     :class="hovered === {{ $loop->index }} ? 'bg-blue-700' : 'bg-blue-500'"
                     x-on:mouseenter="hovered = {{ $loop->index }}"
                     x-on:mouseleave="hovered = null">
                </div>
                <span class="text-xs text-gray-500 text-center leading-tight">{{ $item['label'] }}</span>
            </div>
        @endforeach
    </div>
</div>
@endif

<script>
function chartBar() {
    return {
        hovered: null
    }
}
</script>
