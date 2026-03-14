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
            color: #1a1a1a;
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
            <span class="logo-text">REZI</span>
        </div>
        
        <h1>{{ $title }}</h1>
        
        <p>Bonjour {{ $userName }},</p>
        
        <p>{{ $body }}</p>
        
        @if($actionUrl)
            <p style="text-align: center;">
                <a href="{{ $actionUrl }}" class="cta-button">Voir plus</a>
            </p>
        @endif
        
        <div class="footer">
            <p>Merci de faire confiance à REZI !</p>
            <p>L'équipe REZI<br>
            <a href="https://reziapp.ci" style="color: #f97316;">reziapp.ci</a></p>
        </div>
    </div>
</body>
</html>
