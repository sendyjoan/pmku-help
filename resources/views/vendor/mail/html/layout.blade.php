<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>{{ config('app.name') }}</title>
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body {
                width: 100% !important;
                margin: 0 10px !important;
                border-radius: 12px !important;
            }

            .footer {
                width: 100% !important;
                padding: 20px !important;
            }

            .content-cell {
                padding: 32px 24px !important;
            }

            .header {
                padding: 24px 0 !important;
            }

            .wrapper {
                padding: 10px 0 !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
                padding: 18px 24px !important;
            }

            h1 {
                font-size: 24px !important;
            }

            .table th,
            .table td {
                padding: 12px 8px !important;
                font-size: 14px !important;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .inner-body {
                background-color: #2d3748 !important;
            }

            .content-cell {
                background: linear-gradient(145deg, #2d3748 0%, #4a5568 100%) !important;
            }

            h1,
            h2,
            h3 {
                color: #f7fafc !important;
            }

            p {
                color: #e2e8f0 !important;
            }

            .table td {
                background-color: #4a5568 !important;
                color: #e2e8f0 !important;
                border-bottom: 1px solid #718096 !important;
            }

            .table tr:nth-child(even) td {
                background-color: #2d3748 !important;
            }
        }

        /* Loading animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .inner-body {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Hover effects */
        .button:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2) !important;
        }

        .logo:hover {
            transform: scale(1.05) !important;
        }
    </style>
</head>

<body>

    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    {{ $header ?? '' }}

                    <!-- Email Body -->
                    <tr>
                        <td class="body" width="100%" cellpadding="0" cellspacing="0"
                            style="border: hidden !important;">
                            <table class="inner-body" align="center" width="600" cellpadding="0" cellspacing="0"
                                role="presentation">
                                <!-- Body content -->
                                <tr>
                                    <td class="content-cell">
                                        {{ Illuminate\Mail\Markdown::parse($slot) }}

                                        {{ $subcopy ?? '' }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{ $footer ?? '' }}
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
