<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title }}</title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background:#f4f5f7; margin:0; padding:0; color:#374151; }
  .wrapper { max-width:600px; margin:32px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,.06); }
  .header { background:#ffffff; padding:32px 40px 24px; text-align:center; border-bottom:3px solid #f97316; }
  .header img { height:56px; width:auto; vertical-align:middle; display:inline-block; border:0; }
  .header .brand { display:inline-block; vertical-align:middle; margin-left:12px; font-size:24px; font-weight:800; color:#111827; letter-spacing:-0.5px; }
  .header .brand span { color:#f97316; }
  .content { padding:36px 40px; font-size:15px; line-height:1.7; }
  .content h2 { color:#111827; font-size:20px; margin:0 0 16px; font-weight:700; }
  .content p { margin:0 0 14px; color:#4b5563; }
  .btn-wrap { text-align:center; margin:28px 0; }
  .btn { display:inline-block; padding:14px 32px; background:#f97316; color:#fff !important; text-decoration:none; border-radius:8px; font-weight:600; font-size:15px; }
  .signature { margin-top:24px; padding-top:20px; border-top:1px solid #f3f4f6; color:#6b7280; font-size:14px; }
  .footer { background:#f9fafb; border-top:1px solid #e5e7eb; padding:24px 40px; font-size:12px; color:#9ca3af; text-align:center; line-height:1.6; }
  .footer a { color:#f97316; text-decoration:none; font-weight:600; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <a href="{{ config('app.url') }}" style="text-decoration:none;">
      <img src="{{ asset('images/logo-rezi.png') }}" alt="Rezi Studio Meublé Faya">
      <span class="brand">Rezi <span>App</span></span>
    </a>
  </div>
  <div class="content">
    <h2>{{ $title }}</h2>
    <p>{!! nl2br(e($body)) !!}</p>
    @if(isset($actionUrl) && $actionUrl)
    <div class="btn-wrap">
      <a href="{{ $actionUrl }}" class="btn">Voir les détails</a>
    </div>
    @endif
    <div class="signature">
      <p style="margin:0;">Cordialement,<br><strong style="color:#111827;">L'équipe Rezi Studio Meublé Faya</strong></p>
    </div>
  </div>
  <div class="footer">
    <p style="margin:0;"><strong style="color:#111827;">Rezi Studio Meublé Faya</strong> &mdash; Résidences meublées en Côte d'Ivoire</p>
    <p style="margin:8px 0;">
      <a href="{{ config('app.url') }}">reziapp.ci</a> &middot;
      <a href="mailto:contact@reziapp.ci">contact@reziapp.ci</a>
    </p>
    <p style="margin:8px 0 0;">
      Pour ne plus recevoir ce type de notifications, <a href="{{ route('notifications.preferences') }}">modifiez vos préférences</a>.
    </p>
    <p style="margin:12px 0 0; color:#9ca3af; font-size:11px;">&copy; {{ date('Y') }} Rezi Studio Meublé Faya. Tous droits réservés &middot; Abidjan, Côte d'Ivoire</p>
  </div>
</div>
</body>
</html>
