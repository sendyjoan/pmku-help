<div class="p-4 bg-white border border-gray-200 rounded-lg">
    <div class="flex flex-col items-center space-y-4">
        <div class="w-24 h-24 overflow-hidden border-2 rounded-full border-primary-500">
            @if($getRecord() && $getRecord()->avatar_url)
            <img src="{{ $getRecord()->avatar_url }}" alt="{{ $getRecord()->name ?? 'User avatar' }}"
                class="object-cover w-full h-full" />
            @else
            <div class="flex items-center justify-center w-full h-full bg-gray-200">
                <span class="text-gray-500">No avatar</span>
            </div>
            @endif
        </div>

        <div class="text-center">
            <h3 class="text-sm font-medium text-gray-700">Current Profile Picture</h3>
            <p class="mt-1 text-xs text-gray-500">This image is stored on Cloudinary</p>
        </div>

        <button type="button" wire:click="removeAvatar" class="text-sm text-red-600 hover:text-red-800 hover:underline">
            Remove Current Picture
        </button>
    </div>
</div>
