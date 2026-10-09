<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>আপনার অ্যাকাউন্ট ডিলিট করা হয়েছে</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Hind Siliguri', Arial, sans-serif;
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
            line-height: 1.8;
            color: #334155;
        }
        .greeting {
            font-weight: 700;
            font-size: 18px;
            color: #0f172a;
            margin-bottom: 16px;
        }
        .content p {
            margin: 0 0 16px;
        }
        .inline-link {
            color: #4f46e5;
            text-decoration: underline;
            font-weight: 600;
        }
        .features-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
        }
        .features-title {
            font-weight: 700;
            font-size: 15px;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .feature-item {
            margin-bottom: 10px;
            padding-left: 4px;
            line-height: 1.6;
            font-size: 14px;
        }
        .feature-item:last-child {
            margin-bottom: 0;
        }
        .feature-name {
            font-weight: 700;
            color: #1e293b;
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
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <a href="{{ $appUrl }}" class="logo-link" target="_blank">
                    <table cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto;">
                        <tr>
                            <td style="vertical-align: middle; padding-right: 10px;">
                                <img src="{{ $appUrl }}/favicon.png" alt="HSCStack" class="logo-img" width="32" height="32" />
                            </td>
                            <td style="vertical-align: middle;">
                                <span class="logo-text">HSC<span class="logo-text-accent">Stack</span></span>
                            </td>
                        </tr>
                    </table>
                </a>
            </div>

            <!-- Content -->
            <div class="content">
                <div class="greeting">আপনার অ্যাকাউন্ট ডিলিট করা হয়েছে</div>

                <p>
                    আমরা আপনার অ্যাকাউন্ট ডিলিট করার অনুরোধটি পেয়েছি। আপনার অ্যাকাউন্ট এবং এর সাথে সম্পর্কিত যাবতীয় তথ্য আমাদের
                    <a href="{{ $privacyPolicyUrl }}" class="inline-link" target="_blank">Privacy Policy</a> ও
                    <a href="{{ $termsConditionsUrl }}" class="inline-link" target="_blank">Terms &amp; Conditions</a>
                    অনুযায়ী স্থায়ীভাবে মুছে ফেলা হয়েছে।
                </p>

                <p>
                    HSCStack-এর সাথে থাকার জন্য আপনাকে ধন্যবাদ। ❤️
                </p>

                <p>
                    ভবিষ্যতে যদি আবার HSCStack ব্যবহার করতে চান, তাহলে যেকোনো সময় নতুন করে একটি অ্যাকাউন্ট তৈরি করে আমাদের প্ল্যাটফর্মে ফিরে আসতে পারেন।
                </p>

                <div class="features-box">
                    <div class="features-title">HSCStack-এর উল্লেখযোগ্য ফিচারসমূহ:</div>
                    <div class="feature-item">
                        <span class="feature-name">Resource Archive</span> &mdash; HSC ও SSC-এর Science, Arts এবং Commerce বিভাগের ক্লাস, নোট ও বিভিন্ন শিক্ষামূলক রিসোর্স।
                    </div>
                    <div class="feature-item">
                        <span class="feature-name">Forum</span> &mdash; যেকোনো প্রশ্ন করতে পারবেন এবং সহপাঠীদের কাছ থেকে উত্তর ও সহযোগিতা পেতে পারবেন।
                    </div>
                    <div class="feature-item">
                        <span class="feature-name">Global Chat</span> &mdash; সারাদেশের শিক্ষার্থীদের সাথে যোগাযোগ ও মতবিনিময়ের সুযোগ।
                    </div>
                    <div class="feature-item">
                        <span class="feature-name">Study Tracker</span> &mdash; আপনার পড়াশোনা ও সিলেবাসের অগ্রগতি সহজেই ট্র্যাক করুন।
                    </div>
                    <div class="feature-item">
                        <span class="feature-name">Community Interaction</span> &mdash; অন্যান্য শিক্ষার্থীদের সাথে যুক্ত হয়ে আলোচনা, সহযোগিতা ও জ্ঞান বিনিময় করুন।
                    </div>
                </div>

                <p style="margin-bottom: 4px;">আবারও ধন্যবাদ HSCStack-এর সাথে থাকার জন্য।</p>
                <p style="font-weight: 600; color: #1e293b; margin-top: 4px;">ভবিষ্যতে আবার দেখা হবে! 💙</p>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p class="footer-tagline">
                    HSCStack &mdash; The Open Learning Platform
                </p>
                <div class="footer-links">
                    <a href="{{ $appUrl }}" target="_blank">Visit Platform</a>
                    &bull;
                    <a href="{{ $privacyPolicyUrl }}" target="_blank">Privacy Policy</a>
                    &bull;
                    <a href="{{ $termsConditionsUrl }}" target="_blank">Terms &amp; Conditions</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
