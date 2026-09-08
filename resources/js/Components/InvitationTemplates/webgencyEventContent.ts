// Adapt the existing Event presenter to the three visual layouts without importing demo fixtures.
export function webgencyEventContent(source: any, theme: 'dolce' | 'blossom' | 'sacred') {
    if (source.reference_demo) return source;
    const event = source.event || {};
    const content = source.content || {};
    const date = new Date(event.date_iso ? `${event.date_iso}T00:00:00` : event.main_date || '');
    const valid = Number.isFinite(date.getTime());
    const introduction = event.description || '';
    return {
        ...source,
        event: { ...event, activities: event.activities || [], date_label: event.main_date || '', map_url: event.address ? `https://maps.google.com/maps?q=${encodeURIComponent([event.venue,event.address].filter(Boolean).join(', '))}&output=embed` : null },
        content: {
            ...content,
            introduction: theme === 'sacred' ? introduction : introduction ? introduction.split(/\n\s*\n/) : [],
            blessing: [], dress_intro: event.dress_code || '', ladies: '', gentlemen: '', palette: [],
            gift_intro: content.gift_registry_enabled ? content.gift_registry_intro || '' : '',
        },
        date_parts: valid ? [String(date.getDate()), date.toLocaleDateString(content.primary_locale === 'ar' ? 'ar' : 'en-US', { month: 'long' }), String(date.getFullYear())] : ['—', '—', '—'],
    };
}
