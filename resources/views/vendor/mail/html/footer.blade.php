<tr>
<td style="background-color: #f5f0eb; padding: 0 0 8px 0;">
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td style="height: 4px; background: linear-gradient(90deg, #f97316 0%, #ea580c 100%); border-radius: 0 0 4px 4px; font-size: 4px; line-height: 4px;">&nbsp;</td>
</tr>
<tr>
<td class="content-cell" style="padding: 28px 40px 8px 40px; text-align: center;">
    {{-- Logo footer --}}
    <p style="margin: 0 0 12px 0;">
        <a href="{{ config('app.url') }}" style="text-decoration: none; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 18px; font-weight: 900; color: #f97316; letter-spacing: -0.5px;">Rezi Studio Meublé Faya</a>
    </p>

    {{-- Liens utiles --}}
    <p style="margin: 0 0 12px 0; font-size: 12px; color: #9ca3af; line-height: 2;">
        <a href="{{ config('app.url') }}" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Accueil</a>
        &middot;
        <a href="{{ config('app.url') }}/contact" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Contact</a>
        &middot;
        <a href="{{ config('app.url') }}/privacy" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Confidentialité</a>
        &middot;
        <a href="{{ config('app.url') }}/terms" style="color: #6b7280; text-decoration: none; margin: 0 6px;">CGU</a>
    </p>

    {{ Illuminate\Mail\Markdown::parse($slot) }}

    {{-- Adresse --}}
    <p style="margin: 16px 0 0 0; font-size: 11px; color: #d1d5db; line-height: 1.6;">
        Rezi Studio Meublé Faya &mdash; Plateforme de location de résidences meublées &mdash; Abidjan, Côte d'Ivoire<br>
        &copy; {{ date('Y') }} Rezi Studio Meublé Faya. Tous droits réservés.
    </p>
</td>
</tr>
</table>
</td>
</tr>
