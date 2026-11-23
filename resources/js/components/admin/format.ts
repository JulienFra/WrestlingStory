// Display helpers shared by the admin pages. The AI costs are tiny: they keep 4 to 6 decimals.

const integer = new Intl.NumberFormat('fr-FR');

export function formatCost(dollars: number): string {
    const amount = dollars.toLocaleString('fr-FR', {
        minimumFractionDigits: 4,
        maximumFractionDigits: 6,
    });

    return `${amount} $`;
}

export function formatInteger(value: number): string {
    return integer.format(value);
}

export function formatPercent(rate: number): string {
    const percent = (rate * 100).toLocaleString('fr-FR', {
        maximumFractionDigits: 1,
    });

    return `${percent} %`;
}

export function formatDate(date: string): string {
    const [year, month, day] = date.split('-');

    return `${day}/${month}/${year}`;
}

export function tierLabel(tier: string | null): string {
    if (tier === null) {
        return '—';
    }

    return tier === 'pro' ? 'Pro' : 'Gratuit';
}
