{{-- Tiêu đề trang — cùng style banner như /home ($level: 1 = h1, 2 = h2) --}}
@php
    $level = (int) ($level ?? 2);
@endphp
<header class="page-header-invest">
    @if ($level === 1)
        <h1 class="page-title-invest">{{ $title }}</h1>
    @else
        <h2 class="page-title-invest">{{ $title }}</h2>
    @endif
</header>
