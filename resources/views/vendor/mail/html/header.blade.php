@props(['url'])
<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block;">
            @if (trim($slot) === 'Laravel')
            <img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
            @elseif (trim($slot) === 'Helper' || trim($slot) === config('app.name'))
            {{-- Cek dulu apakah ada logo app --}}
            @if(config('app.logo') || env('APP_LOGO'))
            <div style="text-align: center;">
                <img src="{{ config('app.logo') ?? env('APP_LOGO') }}" class="logo" alt="{{ config('app.name') }} Logo"
                    style="height: 60px; width: auto; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: all 0.3s ease;">
                <div style="margin-top: 12px;">
                    <span
                        style="font-size: 18px; font-weight: 600; color: rgba(255, 255, 255, 0.9); text-shadow: 0 2px 4px rgba(0,0,0,0.1); letter-spacing: 0.5px;">
                        {{ $slot }}
                    </span>
                </div>
            </div>
            @else
            {{-- Jika tidak ada logo, tampilkan nama app dengan styling yang bagus --}}
            <div style="text-align: center; padding: 20px;">
                <div style="display: inline-block; background: linear-gradient(145deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.1) 100%);
                           border-radius: 16px; padding: 20px 32px; backdrop-filter: blur(10px);
                           box-shadow: 0 8px 32px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.2);">
                    <span
                        style="font-size: 32px; font-weight: 700; color: #ffffff; text-shadow: 0 2px 4px rgba(0,0,0,0.2);
                                 letter-spacing: -0.5px; background: linear-gradient(145deg, #ffffff 0%, rgba(255,255,255,0.8) 100%);
                                 -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                        {{ $slot }}
                    </span>
                    <div style="margin-top: 8px; height: 3px; background: linear-gradient(90deg, #4299e1 0%, #3182ce 50%, #2b77cb 100%);
                               border-radius: 2px; opacity: 0.8;"></div>
                </div>
            </div>
            @endif
            @else
            {{-- Fallback untuk nama lain --}}
            <div style="text-align: center; padding: 16px;">
                <span style="font-size: 24px; font-weight: 700; color: #ffffff; text-shadow: 0 2px 4px rgba(0,0,0,0.1);
                             letter-spacing: 0.5px;">
                    {{ $slot }}
                </span>
            </div>
            @endif
        </a>
    </td>
</tr>
