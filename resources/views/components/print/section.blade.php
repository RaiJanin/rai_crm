@props(['title', 'description' => null])

<section {{ $attributes->merge(['class' => 'section']) }}>
    <div class="section-head">
        <h2>{{ $title }}</h2>
        @if ($description)
            <p>{{ $description }}</p>
        @endif
    </div>

    {{ $slot }}
</section>
