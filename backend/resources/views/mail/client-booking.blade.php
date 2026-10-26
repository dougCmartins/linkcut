{{-- Client e-mails about a booking, with the barbershop's own look: its photo and name on top, its color on the
     button, and "linkcut" only in the footer. Same content as Laravel's notification e-mail. --}}
<x-mail::layout>
<x-slot:header>
<tr>
<td class="header" style="padding: 25px 0; text-align: center;">
<a href="{{ $bookingUrl }}" style="display: inline-block; text-decoration: none; color: #18181b;">
@if ($logoUrl)
<img src="{{ $logoUrl }}" alt="" width="64" height="64" style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; display: block; margin: 0 auto 10px;">
@endif
<span style="font-size: 20px; font-weight: bold;">{{ $barbershop }}</span>
</a>
</td>
</tr>
</x-slot:header>

# {{ $greeting }}

@foreach ($introLines as $line)
{{ $line }}

@endforeach
@isset($actionText)
<table class="action" align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<a href="{{ $actionUrl }}" class="button" target="_blank" rel="noopener" style="background-color: {{ $accent }}; border-color: {{ $accent }}; border-style: solid; border-width: 10px 22px; border-radius: 999px; color: {{ $accentText }}; display: inline-block; font-weight: bold; text-decoration: none;">{{ $actionText }}</a>
</td>
</tr>
</table>
@endisset

@foreach ($outroLines as $line)
{{ $line }}

@endforeach

<x-slot:footer>
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
{{ __('client_mail.sent_by', ['barbershop' => $barbershop]) }}
</td>
</tr>
</table>
</td>
</tr>
</x-slot:footer>
</x-mail::layout>
