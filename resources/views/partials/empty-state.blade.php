{{-- Empty state component
     Props: icon (emoji), title, desc (optional), ctaText (optional), ctaUrl (optional)
--}}
<div class="empty-state">
    <div class="empty-state__icon">{{ $icon ?? '📭' }}</div>
    <div class="empty-state__title">{{ $title }}</div>
    @if(!empty($desc))
        <div class="empty-state__desc">{{ $desc }}</div>
    @endif
    @if(!empty($ctaText) && !empty($ctaUrl))
        <a href="{{ $ctaUrl }}" class="empty-state__cta">{{ $ctaText }}</a>
    @endif
</div>
