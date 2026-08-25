<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mailSubject }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
        }
        .wrapper {
            width: 100%;
            background-color: #f8fafc;
            padding: 40px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #4f46e5;
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            padding: 28px 32px;
            text-align: left;
        }
        .header-title {
            color: #ffffff;
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.02em;
        }
        .content {
            padding: 32px;
            font-size: 15px;
            line-height: 1.65;
            color: #334155;
        }
        .content h1 { font-size: 22px; color: #0f172a; margin-top: 0; }
        .content h2 { font-size: 18px; color: #0f172a; }
        .content h3 { font-size: 16px; color: #0f172a; }
        .content p { margin: 0 0 16px; }
        .content a { color: #4f46e5; text-decoration: underline; font-weight: 500; }
        .content img { max-width: 100%; height: auto; border-radius: 8px; }
        .content blockquote {
            border-left: 4px solid #cbd5e1;
            margin: 16px 0;
            padding-left: 16px;
            color: #64748b;
            font-style: italic;
        }
        .footer {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px 32px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .footer a {
            color: #64748b;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1 class="header-title">{{ config('app.name', 'HSCStack') }}</h1>
            </div>

            <div class="content">
                @if(!empty($recipientName))
                    <p style="font-weight: 600; color: #0f172a; margin-bottom: 20px;">
                        Hello {{ $recipientName }},
                    </p>
                @endif

                {!! $mailContent !!}
            </div>

            <div class="footer">
                <p style="margin: 0 0 8px;">
                    You are receiving this email because you have an account on {{ config('app.name', 'HSCStack') }}.
                </p>
                <p style="margin: 0;">
                    To update your email preferences, visit your
                    <a href="{{ config('app.url') }}/profile">Account Settings</a>.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
