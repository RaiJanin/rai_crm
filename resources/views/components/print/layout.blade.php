@props([
    'reportType',
    'title',
    'subtitle' => null,
    'period' => null,
    'generatedAt',
])

@php
    use App\Support\ReportFormat;

    $appName = config('app.name');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>{{ $title }} · {{ $reportType }} — {{ $appName }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon-32.png" type="image/png" sizes="32x32">

        @fonts

        <style>
            :root {
                --blue: #2563eb;
                --blue-dark: #1e3a8a;
                --blue-soft: #eff6ff;
                --blue-line: #dbeafe;
                --amber: #f59e0b;
                --amber-soft: #fef3c7;
                --amber-text: #92400e;
                --green: #16a34a;
                --green-soft: #dcfce7;
                --green-text: #166534;
                --red: #dc2626;
                --red-soft: #fee2e2;
                --red-text: #991b1b;
                --text: #1f2937;
                --muted: #6b7280;
                --neutral: #f3f4f6;
            }

            * { box-sizing: border-box; }

            html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            body {
                margin: 0;
                background: #e8edf5;
                color: var(--text);
                font-family: Inter, ui-sans-serif, system-ui, sans-serif;
                font-size: 11px;
                line-height: 1.45;
                -webkit-font-smoothing: antialiased;
            }

            /* ---------- Screen: toolbar + A4 paper preview ---------- */
            .toolbar {
                position: sticky;
                top: 0;
                z-index: 10;
                background: var(--blue-dark);
                color: #fff;
            }
            .toolbar-inner {
                max-width: 210mm;
                margin: 0 auto;
                padding: 10px 0;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                font-size: 13px;
            }
            .toolbar-inner span { opacity: .8; }
            .toolbar button {
                font: 600 13px Inter, sans-serif;
                border: 0;
                border-radius: 8px;
                padding: 8px 14px;
                cursor: pointer;
            }
            .btn-print { background: var(--amber); color: var(--text); }
            .btn-print:hover { background: #d97706; }
            .btn-close { background: rgba(255,255,255,.12); color: #fff; margin-left: 6px; }
            .btn-close:hover { background: rgba(255,255,255,.2); }

            .sheet {
                width: 210mm;
                min-height: 297mm;
                margin: 24px auto 48px;
                padding: 14mm 12mm;
                background: #fff;
                box-shadow: 0 1px 3px rgb(30 58 138 / .08), 0 10px 30px rgb(30 58 138 / .10);
            }

            /* ---------- Page frame: header/footer repeat on every printed page ---------- */
            .frame { width: 100%; border-collapse: collapse; }
            .frame > thead > tr > td,
            .frame > tfoot > tr > td,
            .frame > tbody > tr > td { padding: 0; }

            .letterhead {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                padding-bottom: 10px;
                margin-bottom: 18px;
                border-bottom: 2px solid var(--blue);
            }
            .brand { display: flex; align-items: center; gap: 12px; }
            .brand img { width: 40px; height: 40px; object-fit: contain; }
            .brand-name { font-size: 15px; font-weight: 600; color: var(--blue-dark); line-height: 1.2; }
            .brand-type { font-size: 11.5px; color: var(--muted); }
            .meta { text-align: right; font-size: 11.5px; color: var(--muted); }
            .meta strong { display: block; color: var(--text); font-weight: 600; }

            .doc-title { margin-bottom: 18px; }
            .doc-title h1 { margin: 0; font-size: 22px; font-weight: 600; letter-spacing: -.01em; }
            .doc-title p { margin: 2px 0 0; font-size: 13px; color: var(--muted); }

            .footer {
                margin-top: 18px;
                padding-top: 8px;
                border-top: 1px solid var(--blue-line);
                display: flex;
                justify-content: space-between;
                font-size: 9.5px;
                color: var(--muted);
            }

            /* ---------- Content building blocks ---------- */
            .section { margin-top: 20px; }
            .section-head {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 8px;
                padding-bottom: 5px;
                border-bottom: 1px solid var(--blue-line);
            }
            .section-head h2 { margin: 0; font-size: 13px; font-weight: 600; color: var(--blue-dark); }
            .section-head p { margin: 0; font-size: 10.5px; color: var(--muted); }

            .kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
            .kpi {
                border: 1px solid var(--blue-line);
                border-radius: 8px;
                padding: 9px 11px;
                break-inside: avoid;
            }
            .kpi-label { font-size: 10px; color: var(--muted); }
            .kpi-value { font-size: 16px; font-weight: 600; margin-top: 2px; }
            .kpi-hint { font-size: 9.5px; color: var(--muted); }

            .columns { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; align-items: start; }
            .columns .section { margin-top: 20px; }

            table.data { width: 100%; border-collapse: collapse; }
            table.data th {
                background: var(--blue-soft);
                color: var(--blue-dark);
                font-weight: 600;
                font-size: 10px;
                text-align: left;
                padding: 6px 8px;
                white-space: nowrap;
            }
            table.data th:first-child { border-radius: 6px 0 0 6px; }
            table.data th:last-child { border-radius: 0 6px 6px 0; }
            table.data td {
                padding: 6px 8px;
                border-bottom: 1px solid var(--blue-line);
                vertical-align: top;
            }
            table.data tr { break-inside: avoid; }
            table.data tfoot td { font-weight: 600; border-bottom: 0; border-top: 1.5px solid var(--blue-line); }
            .num,
            table.data .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
            .nowrap { white-space: nowrap; }
            .keep { break-inside: avoid; }
            .sub { display: block; font-size: 9.5px; color: var(--muted); }
            .empty {
                padding: 12px;
                text-align: center;
                color: var(--muted);
                border: 1px dashed var(--blue-line);
                border-radius: 8px;
            }

            .badge {
                display: inline-block;
                padding: 1px 7px;
                border-radius: 999px;
                font-size: 9.5px;
                font-weight: 500;
                white-space: nowrap;
            }
            .tone-amber { background: var(--amber-soft); color: var(--amber-text); }
            .tone-green { background: var(--green-soft); color: var(--green-text); }
            .tone-red { background: var(--red-soft); color: var(--red-text); }
            .tone-blue { background: var(--blue-soft); color: var(--blue-dark); }
            .tone-gray { background: var(--neutral); color: var(--muted); }

            .bar { height: 6px; border-radius: 999px; background: var(--neutral); overflow: hidden; min-width: 60px; }
            .bar > span { display: block; height: 100%; border-radius: 999px; }
            .fill-blue { background: var(--blue); }
            .fill-dark { background: var(--blue-dark); }
            .fill-amber { background: var(--amber); }
            .fill-green { background: var(--green); }
            .fill-red { background: var(--red); }
            .fill-gray { background: #9ca3af; }

            dl.details { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px 24px; margin: 0; }
            dl.details dt { font-size: 10px; color: var(--muted); }
            dl.details dd { margin: 0; }

            /* ---------- Print ---------- */
            @page {
                size: A4;
                margin: 12mm 12mm 14mm;

                @bottom-right {
                    content: "Page " counter(page) " of " counter(pages);
                    font: 9px Inter, sans-serif;
                    color: #6b7280;
                }
            }

            @media print {
                body { background: #fff; }
                .toolbar { display: none; }
                .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
                .section-head { break-after: avoid; }
            }
        </style>
    </head>
    <body>
        <div class="toolbar">
            <div class="toolbar-inner">
                <span>Print preview · A4 portrait</span>
                <div>
                    <button type="button" class="btn-print" onclick="window.print()">Print / Save as PDF</button>
                    <button type="button" class="btn-close" onclick="window.close()">Close</button>
                </div>
            </div>
        </div>

        <main class="sheet">
            <table class="frame">
                <thead>
                    <tr>
                        <td>
                            <header class="letterhead">
                                <div class="brand">
                                    <img src="{{ asset('images/logo.png') }}" alt="{{ $appName }} logo">
                                    <div>
                                        <div class="brand-name">{{ $appName }}</div>
                                        <div class="brand-type">{{ $reportType }}</div>
                                    </div>
                                </div>
                                <div class="meta">
                                    @if ($period)
                                        <strong>{{ $period }}</strong>
                                    @endif
                                    Generated {{ ReportFormat::dateTime($generatedAt) }} by {{ auth()->user()->name }}
                                </div>
                            </header>
                        </td>
                    </tr>
                </thead>

                <tfoot>
                    <tr>
                        <td>
                            <footer class="footer">
                                <span>Confidential — prepared for internal account review</span>
                                <span>{{ $appName }}</span>
                            </footer>
                        </td>
                    </tr>
                </tfoot>

                <tbody>
                    <tr>
                        <td>
                            <div class="doc-title">
                                <h1>{{ $title }}</h1>
                                @if ($subtitle)
                                    <p>{{ $subtitle }}</p>
                                @endif
                            </div>

                            {{ $slot }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </main>

        @if (request()->boolean('autoprint'))
            <script>
                window.addEventListener('load', () => setTimeout(() => window.print(), 300));
            </script>
        @endif
    </body>
</html>
