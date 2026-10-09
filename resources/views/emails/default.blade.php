<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? config('app.name', 'HSCStack') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
            -ms-text-size-adjust: none;
        }
        .wrapper {
            width: 100%;
            background-color: #f1f5f9;
            padding: 40px 0;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.04), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
        }
        .header {
            padding: 24px 32px;
            text-align: center;
            background: #ffffff;
            border-bottom: 1px solid #f1f5f9;
        }
        .logo-link {
            text-decoration: none;
            display: inline-block;
        }
        .logo-img {
            width: 32px;
            height: 32px;
            border-radius: 7px;
            display: block;
            border: 0;
        }
        .logo-text {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.03em;
            line-height: 1;
        }
        .logo-text-accent {
            color: #4f46e5;
        }
        .content {
            padding: 32px 32px 24px;
            font-size: 15px;
            line-height: 1.7;
            color: #334155;
        }
        .greeting {
            font-weight: 600;
            font-size: 16px;
            color: #0f172a;
            margin-bottom: 16px;
        }
        .content p {
            margin: 0 0 16px;
        }
        .action-button-container {
            margin: 28px 0 16px;
            text-align: left;
        }
        .action-button {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            padding: 12px 24px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 32px;
            text-align: center;
            font-size: 12px;
            line-height: 1.6;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
        .footer-tagline {
            font-weight: 600;
            color: #334155;
            margin: 0 0 6px;
        }
        .footer-links {
            margin: 10px 0 14px;
        }
        .footer-links a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
            margin: 0 6px;
        }
        .footer-links a:hover {
            text-decoration: underline;
        }
        .footer-notice {
            margin: 0 0 4px;
            color: #94a3b8;
            font-size: 11px;
        }
        .footer-unsub {
            margin: 0;
            color: #94a3b8;
            font-size: 11px;
        }
        .footer-unsub a {
            color: #64748b;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <!-- HSCStack Header -->
            <div class="header">
                <a href="{{ config('app.url', 'https://hscstack.site') }}" class="logo-link" target="_blank">
                    <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                        <tr>
                            <td style="vertical-align: middle; padding-right: 10px;">
                                <img src="{{ config('app.url') }}/favicon.png" alt="HSCStack" class="logo-img" width="32" height="32" />
                            </td>
                            <td style="vertical-align: middle;">
                                <span class="logo-text">HSC<span class="logo-text-accent">Stack</span></span>
                            </td>
                        </tr>
                    </table>
                </a>
            </div>

            <!-- Email Body Content -->
            <div class="content">
                @if(!empty($greeting))
                    <div class="greeting">{{ $greeting }}</div>
                @endif

                @if(!empty($lines))
                    @foreach($lines as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                @endif

                @if(!empty($actionUrl) && !empty($actionText))
                    <div class="action-button-container">
                        <a href="{{ $actionUrl }}" class="action-button" target="_blank">
                            {{ $actionText }} &rarr;
                        </a>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="footer">
                <p class="footer-tagline">
                    HSCStack &mdash; The Open Learning Platform
                </p>
                
                <div class="footer-links">
                    <a href="{{ config('app.url', 'https://hscstack.site') }}" target="_blank">Visit Platform</a>
                    &bull;
                    <a href="{{ config('app.url') }}/blogs" target="_blank">Read Blogs</a>
                    &bull;
                    <a href="{{ config('app.url') }}/donate" target="_blank">Support Us</a>
                </div>

                <p class="footer-unsub">
                    Manage email preferences in your <a href="{{ config('app.url') }}/profile" target="_blank">Account Settings</a>, or visit our <a href="{{ config('app.url') }}/support" target="_blank">Support Center</a> if you need assistance or don't have an account.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
