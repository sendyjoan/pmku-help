<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            {{ config('app.name') }}
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    <div style="position: relative;">
        <!-- Welcome Section -->
        <div style="text-align: center; margin-bottom: 32px;">
            <div style="display: inline-block; background: linear-gradient(145deg, #4299e1 0%, #3182ce 100%);
                   color: white; padding: 8px 20px; border-radius: 20px; font-size: 14px;
                   font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase;
                   box-shadow: 0 4px 12px rgba(66, 153, 225, 0.3);">
                Notification
            </div>
        </div>

        <!-- Main Content -->
        <div style="background: linear-gradient(145deg, #f8fafc 0%, #edf2f7 100%);
               border-radius: 12px; padding: 32px; margin-bottom: 24px;
               border: 1px solid #e2e8f0; position: relative; overflow: hidden;">

            <!-- Decorative element -->
            <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px;
                   background: linear-gradient(90deg, #4299e1 0%, #3182ce 50%, #2b77cb 100%);"></div>

            {!! $slot !!}
        </div>
    </div>

    {{-- Subcopy --}}
    @isset($subcopy)
    <x-slot:subcopy>
        <x-mail::subcopy>
            <div style="background: linear-gradient(145deg, #f1f5f9 0%, #e2e8f0 100%);
           border-radius: 12px; padding: 20px; border: 1px solid #cbd5e0;">
                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                    <div style="width: 20px; height: 20px; background: linear-gradient(145deg, #fbbf24 0%, #f59e0b 100%);
                   border-radius: 50%; margin-right: 12px; display: inline-block;"></div>
                    <span style="color: #4a5568; font-weight: 600; font-size: 14px;">Important Information</span>
                </div>
                {{ $subcopy }}
            </div>
        </x-mail::subcopy>
    </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
            © {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
