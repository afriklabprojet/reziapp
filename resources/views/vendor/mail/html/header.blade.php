@props(['url'])
<tr>
<td class="header" style="padding: 36px 0 20px 0; text-align: center; background-color: #f5f0eb;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
    {{-- Logo image --}}
    <img src="{{ asset('images/logo-rezi.png') }}"
         alt="REZI"
         height="52"
         width="auto"
         style="display: inline-block; vertical-align: middle; height: 52px; width: auto; border: 0; outline: none; text-decoration: none; max-height: 52px;" />
    {{-- Fallback texte si image non chargée --}}
    <span style="display: inline-block; vertical-align: middle; margin-left: 10px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 26px; font-weight: 900; color: #f97316; letter-spacing: -1px; line-height: 1;">REZI</span>
</a>
</td>
</tr>
<tr>
<td style="padding: 0; background-color: #f5f0eb;">
<table width="570" align="center" cellpadding="0" cellspacing="0" role="presentation" style="margin: 0 auto;">
<tr>
<td style="height: 4px; background: linear-gradient(90deg, #f97316 0%, #ea580c 100%); border-radius: 4px 4px 0 0; font-size: 4px; line-height: 4px;">&nbsp;</td>
</tr>
</table>
</td>
</tr>
