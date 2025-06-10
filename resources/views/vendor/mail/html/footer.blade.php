<tr>
    <td>
        <table class="footer" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td class="content-cell" align="center" style="padding: 32px 20px;">
                    <div style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px);
           border-radius: 16px; padding: 24px; border: 1px solid rgba(255, 255, 255, 0.2);
           box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);">

                        <!-- Social links or app info can go here -->
                        <div style="margin-bottom: 16px;">
                            <div style="height: 2px; background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
                   margin: 0 auto; width: 60px; border-radius: 1px;"></div>
                        </div>

                        <!-- Main footer content -->
                        <div
                            style="color: rgba(255, 255, 255, 0.9); font-size: 14px; line-height: 1.6; text-align: center;">
                            {{ Illuminate\Mail\Markdown::parse($slot) }}
                        </div>

                        <!-- Additional footer info -->
                        <div
                            style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.2);">
                            <p
                                style="color: rgba(255, 255, 255, 0.7); font-size: 12px; margin: 4px 0; text-align: center;">
                                Powered by {{ config('app.name') }}
                            </p>
                            <p
                                style="color: rgba(255, 255, 255, 0.6); font-size: 11px; margin: 4px 0; text-align: center;">
                                This email was sent from a notification-only address that cannot accept incoming email.
                            </p>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </td>
</tr>
