@props(['url'])

<div style="text-align: center; margin: 25px 0;">
    <a href="{{ $url }}" style="display: inline-block; padding: 12px 28px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px;">
        {{ $slot }}
    </a>
</div>
