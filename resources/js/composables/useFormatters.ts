import { usePage } from '@inertiajs/vue3';

const relativeUnits: [Intl.RelativeTimeFormatUnit, number][] = [
    ['year', 31536000],
    ['month', 2592000],
    ['week', 604800],
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
];

const cache = new Map<
    string,
    Intl.NumberFormat | Intl.DateTimeFormat | Intl.RelativeTimeFormat
>();

function cached<
    T extends Intl.NumberFormat | Intl.DateTimeFormat | Intl.RelativeTimeFormat,
>(key: string, create: () => T): T {
    if (!cache.has(key)) {
        cache.set(key, create());
    }

    return cache.get(key) as T;
}

/**
 * Parse a date-only string ("2026-09-25") as a local date so it doesn't
 * shift a day in timezones west of UTC.
 */
function parseDate(value: string): Date {
    return /^\d{4}-\d{2}-\d{2}$/.test(value)
        ? new Date(`${value}T00:00:00`)
        : new Date(value);
}

/**
 * Money and date formatting using the CRM's configured currency and locale
 * (CRM_CURRENCY / CRM_LOCALE, e.g. PHP / en-PH → "₱1,250,000").
 */
export function useFormatters() {
    const page = usePage();

    const locale = () => page.props.crm.locale;
    const currency = () => page.props.crm.currency;

    function money(value: string | number | null | undefined): string {
        const formatter = cached(
            `money:${locale()}:${currency()}`,
            () =>
                new Intl.NumberFormat(locale(), {
                    style: 'currency',
                    currency: currency(),
                    currencyDisplay: 'narrowSymbol',
                    maximumFractionDigits: 0,
                }),
        );

        return formatter.format(Number(value ?? 0));
    }

    function date(value: string | null | undefined): string {
        if (!value) {
            return '—';
        }

        const formatter = cached(
            `date:${locale()}`,
            () =>
                new Intl.DateTimeFormat(locale(), {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                }),
        );

        return formatter.format(parseDate(value));
    }

    function dateTime(value: string | null | undefined): string {
        if (!value) {
            return '—';
        }

        const formatter = cached(
            `datetime:${locale()}`,
            () =>
                new Intl.DateTimeFormat(locale(), {
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                }),
        );

        return formatter.format(parseDate(value));
    }

    function relative(value: string | null | undefined): string {
        if (!value) {
            return '—';
        }

        const formatter = cached(
            `relative:${locale()}`,
            () => new Intl.RelativeTimeFormat(locale(), { numeric: 'auto' }),
        );
        const seconds = (parseDate(value).getTime() - Date.now()) / 1000;

        for (const [unit, size] of relativeUnits) {
            if (Math.abs(seconds) >= size) {
                return formatter.format(Math.round(seconds / size), unit);
            }
        }

        return 'just now';
    }

    /** Human-friendly length of time, e.g. "45 min", "6.5 h", "3.2 days". */
    function duration(hours: number | null | undefined): string {
        if (hours === null || hours === undefined) {
            return '—';
        }

        if (hours < 1) {
            return `${Math.round(hours * 60)} min`;
        }

        return hours < 48 ? `${hours} h` : `${(hours / 24).toFixed(1)} days`;
    }

    function isOverdue(value: string | null | undefined): boolean {
        if (!value) {
            return false;
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        return parseDate(value) < today;
    }

    return { money, date, dateTime, relative, duration, isOverdue };
}
