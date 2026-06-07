<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            height: 40px;
        }
        .logo-text {
            font-size: 28px;
            font-weight: bold;
            color: #f97316;
        }
        h1 {
            color: #0F0F0F;
            font-size: 24px;
            margin-bottom: 20px;
        }
        p {
            color: #555;
            margin-bottom: 15px;
        }
        .cta-button {
            display: inline-block;
            background: #f97316;
            color: white !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            margin: 20px 0;
        }
        .cta-button:hover {
            background: #ea580c;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #888;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <a href="https://reziapp.ci" style="text-decoration:none; display:inline-block;">
                <img src="{{ asset('images/logo-rezi.png') }}" alt="Rezi App" style="height:56px; width:auto; vertical-align:middle; display:inline-block;">
                <span style="display:inline-block; vertical-align:middle; margin-left:12px; font-size:24px; font-weight:800; color:#111827; letter-spacing:-0.5px;">Rezi <span style="color:#f97316;">App</span></span>
            </a>
        </div>

        <h1>{{ $title }}</h1>

        <p>Bonjour {{ $userName }},</p>

        <p>{!! nl2br(e($body)) !!}</p>

        @if($actionUrl)
            <p style="text-align: center;">
                <a href="{{ $actionUrl }}" class="cta-button">Voir plus</a>
            </p>
        @endif

        <div class="footer">
            <p>Merci de faire confiance à <strong>Rezi App</strong> !</p>
            <p style="color:#6b7280; margin:8px 0;">L'équipe Rezi App</p>
            <p style="margin:8px 0;">
                <a href="https://reziapp.ci" style="color: #f97316; text-decoration:none; font-weight:600;">reziapp.ci</a> &middot;
                <a href="mailto:contact@reziapp.ci" style="color:#f97316; text-decoration:none; font-weight:600;">contact@reziapp.ci</a>
            </p>
            <p style="font-size:11px; color:#9ca3af; margin-top:12px;">&copy; {{ date('Y') }} Rezi App &middot; Abidjan, Côte d'Ivoire</p>
        </div>
    </div>
</body>
</html>
