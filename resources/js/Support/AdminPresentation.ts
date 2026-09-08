export const invitationStatus = (status?: string | null) => {
    if (status === 'archived') return 'Archived';
    if (status === 'disabled') return 'Disabled';
    if (status === 'live' || status === 'active' || status === 'published' || status === 'live_unpublished_changes') return 'Live';

    return 'Setup';
};

export const paymentStatus = (status?: string | null) => ({
    paid: 'Paid',
    partially_paid: 'Partially Paid',
    unpaid: 'Unpaid',
}[status || ''] || 'Unpaid');

export const statusTone = (status?: string | null) => status === 'paid' || status === 'live' || status === 'active'
    ? 'success'
    : status === 'archived' || status === 'inactive' || status === 'disabled'
        ? 'neutral'
        : 'warning';

export const money = (value: string | number | null | undefined) => `$${Number(value || 0).toFixed(2)}`;

export const date = (value?: string | null, empty = 'Not set') => {
    if (!value) return empty;

    return new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(new Date(`${value.slice(0, 10)}T00:00:00`));
};

export const couponDiscount = (type?: string | null, value?: string | number | null) => type === 'percentage'
    ? `${Number(value || 0)}% OFF`
    : `${money(value)} OFF`;
