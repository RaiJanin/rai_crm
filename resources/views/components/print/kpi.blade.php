@props(['label', 'value', 'hint' => null])

<div class="kpi">
    <div class="kpi-label">{{ $label }}</div>
    <div class="kpi-value">{{ $value }}</div>
    @if ($hint)
        <div class="kpi-hint">{{ $hint }}</div>
    @endif
</div>
