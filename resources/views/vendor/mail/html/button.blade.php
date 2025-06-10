@props([
'url',
'color' => 'primary',
'align' => 'center',
])
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="{{ $align }}">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td align="{{ $align }}" style="padding: 20px 0;">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                <td style="border-radius: 12px; overflow: hidden;">
                                    <a href="{{ $url }}" class="button button-{{ $color }}" target="_blank"
                                        rel="noopener" style="background: linear-gradient(145deg,
          @if($color === 'primary' || $color === 'blue')
              #4299e1 0%, #3182ce 100%
          @elseif($color === 'success' || $color === 'green')
              #48bb78 0%, #38a169 100%
          @elseif($color === 'error' || $color === 'red')
              #f56565 0%, #e53e3e 100%
          @else
              #4299e1 0%, #3182ce 100%
          @endif
          );
          color: #ffffff;
          text-decoration: none;
          display: inline-block;
          padding: 16px 32px;
          border-radius: 12px;
          font-weight: 600;
          font-size: 16px;
          letter-spacing: 0.5px;
          box-shadow: 0 6px 20px rgba(0,0,0,0.15);
          transition: all 0.3s ease;
          border: none;
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                        <span style="text-shadow: 0 1px 2px rgba(0,0,0,0.1);">{{ $slot }}</span>
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
