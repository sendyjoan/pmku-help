<div class="flex items-center justify-center">
    @if(!empty($user->avatar_url))
    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
        class="{{ $class ?? 'w-6 h-6 rounded-full bg-gray-200 bg-cover bg-center' }}" loading="lazy" />
    @else
    <div class="{{ $class ?? 'w-6 h-6 rounded-full bg-gray-200 flex items-center justify-center text-xs text-gray-600' }}"
        style="background-color: {{ randomColor($user->id) }}">
        <span class="font-medium text-white">{{ getInitials($user->name) }}</span>
    </div>
    @endif
</div>

@php
function randomColor($id) {
$colors = [
'#4f46e5', // Indigo
'#0284c7', // Sky
'#0891b2', // Cyan
'#0d9488', // Teal
'#059669', // Emerald
'#16a34a', // Green
'#65a30d', // Lime
'#ca8a04', // Yellow
'#ea580c', // Orange
'#dc2626', // Red
'#db2777', // Pink
'#9333ea', // Purple
];

return $colors[$id % count($colors)];
}

function getInitials($name) {
$words = explode(' ', $name);
$initials = '';

foreach ($words as $word) {
if (!empty($word)) {
$initials .= mb_substr($word, 0, 1);
if (strlen($initials) >= 2) break;
}
}

return mb_strtoupper($initials);
}
@endphp
