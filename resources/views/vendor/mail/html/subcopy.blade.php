<table class="subcopy" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td>
            @php
            // Auto-detect URLs and make them clickable
            $content = Illuminate\Mail\Markdown::parse($slot);

            // Pattern untuk mendeteksi URLs
            $urlPattern = '/\[([^\]]+)\]\(([^)]+)\)/'; // Markdown style [text](url)
            $httpPattern = '/(https?:\/\/[^\s<>"{}|\\^`\[\]]+)/i'; // Plain HTTP URLs

                // Convert markdown links first
                $content = preg_replace($urlPattern, '<a href="$2"
                    style="color: #4299e1; text-decoration: underline; font-weight: 500;">$1</a>', $content);

                // Convert plain URLs to clickable links
                $content = preg_replace($httpPattern, '<a href="$1"
                    style="color: #4299e1; text-decoration: underline; font-weight: 500;" target="_blank"
                    rel="noopener">$1</a>', $content);
                @endphp

                <div style="background: linear-gradient(145deg, #f1f5f9 0%, #e2e8f0 100%);
           border-radius: 12px; padding: 20px; border: 1px solid #cbd5e0;">
                    <div style="display: flex; align-items: center; margin-bottom: 12px;">
                        <div style="width: 20px; height: 20px; background: linear-gradient(145deg, #fbbf24 0%, #f59e0b 100%);
                   border-radius: 50%; margin-right: 12px; display: inline-block;"></div>
                        <span style="color: #4a5568; font-weight: 600; font-size: 14px;">Important Information</span>
                    </div>
                    <div style="color: #4a5568; font-size: 14px; line-height: 1.6;">
                        {!! $content !!}
                    </div>
                </div>
        </td>
    </tr>
</table>
