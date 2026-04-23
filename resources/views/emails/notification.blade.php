<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title }}</title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background:#f4f5f7; margin:0; padding:0; }
  .wrapper { max-width:600px; margin:32px auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.08); }
  .header { background:#f97316; padding:28px 40px; text-align:center; }
  .header h1 { color:#fff; margin:0; font-size:22px; font-weight:700; letter-spacing:.5px; }
  .content { padding:36px 40px; color:#374151; font-size:15px; line-height:1.7; }
  .content h2 { color:#111827; font-size:18px; margin-top:0; }
  .btn { display:inline-block; margin:24px 0; padding:13px 28px; background:#f97316; color:#fff !important; text-decoration:none; border-radius:6px; font-weight:600; font-size:14px; }
  .footer { background:#f9fafb; border-top:1px solid #e5e7eb; padding:20px 40px; font-size:12px; color:#9ca3af; text-align:center; }
  .footer a { color:#f97316; text-decoration:none; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>REZI</h1>
  </div>
  <div class="content">
    <h2>{{ $title }}</h2>
    <p>{{ $body }}</p>
    @if(isset($actionUrl) && $actionUrl)
    <p><a href="{{ $actionUrl }}" class="btn">Voir les détails</a></p>
    @endif
    <p>Merci de faire confiance à REZI !</p>
    <p>Cordialement,<br>L'équipe {{ config('app.name') }}</p>
  </div>
  <div class="footer">
    <p>
      Si vous ne souhaitez plus recevoir ce type de notifications,
      <a href="{{ route('notifications.preferences') }}">modifiez vos préférences</a>.
    </p>
    <p>&copy; {{ date('Y') }} REZI — reziapp.ci</p>
  </div>
</div>
</body>
</html>
