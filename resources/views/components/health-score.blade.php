<div class="flex items-center space-x-2">
    <div class="flex items-center justify-center w-8 h-8 rounded-full
        @if($color === 'success') bg-green-100 @elseif($color === 'warning') bg-yellow-100 @else bg-red-100 @endif">
        <span
            class="text-xs font-bold
            @if($color === 'success') text-green-800 @elseif($color === 'warning') text-yellow-800 @else text-red-800 @endif">
            {{ $score }}
        </span>
    </div>

    <div class="flex-1">
        <div class="w-full h-2 bg-gray-200 rounded-full">
            <div class="h-2 rounded-full transition-all duration-300
                @if($color === 'success') bg-green-500 @elseif($color === 'warning') bg-yellow-500 @else bg-red-500 @endif"
                style="width: {{ $score }}%">
            </div>
        </div>
    </div>

    <span
        class="text-sm font-medium
        @if($color === 'success') text-green-600 @elseif($color === 'warning') text-yellow-600 @else text-red-600 @endif">
        {{ $score }}/100
    </span>
</div>
