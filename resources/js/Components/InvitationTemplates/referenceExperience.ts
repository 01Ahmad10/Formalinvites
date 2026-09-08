import { computed, onBeforeUnmount, onMounted, ref, watch, type Ref } from 'vue';
import './referenceFonts.css';

export function eventInstant(day: string | null, time: string | null, timezone: string): string | null {
    if (!day || !time) return null;
    const target = Date.parse(`${day}T${time.slice(0,5)}:00Z`);
    if (!Number.isFinite(target)) return null;
    try {
        const formatter = new Intl.DateTimeFormat('sv-SE', { timeZone: timezone, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23' });
        let instant = target;
        for (let i = 0; i < 3; i++) instant += target - Date.parse(formatter.format(new Date(instant)).replace(' ', 'T') + 'Z');
        return new Date(instant).toISOString();
    } catch { return null; }
}

// Presentation helpers only. RSVP validation and persistence remain in Laravel.
export function useReferenceExperience(invitation: () => any, root: Ref<HTMLElement | null>, options: { revealOffset?: number } = {}) {
    const now = ref(Date.now());
    let timer: ReturnType<typeof setInterval>;
    let observer: IntersectionObserver | undefined;
    let revealFrame = 0;
    let revealElements: HTMLElement[] = [];
    const date = computed(() => new Date(invitation().event.date_iso ? `${invitation().event.date_iso}T00:00:00` : invitation().event.main_date || ''));
    const validDate = computed(() => Number.isFinite(date.value.getTime()));
    const names = computed(() => [invitation().event.host_name, invitation().event.second_host_name].filter(Boolean).join(' & '));
    const dateLabel = computed(() => validDate.value ? [date.value.getDate(), date.value.getMonth() + 1, date.value.getFullYear()].map(n => String(n).padStart(2, '0')).join(' . ') : '');
    const month = computed(() => validDate.value ? date.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : '');
    const days = computed(() => validDate.value ? [...Array(new Date(date.value.getFullYear(), date.value.getMonth(), 1).getDay()).fill(null), ...Array.from({ length: new Date(date.value.getFullYear(), date.value.getMonth() + 1, 0).getDate() }, (_, i) => i + 1)] : []);
    const countdown = computed(() => {
        const target = new Date(date.value);
        const time = invitation().event.start_time?.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
        if (time) target.setHours(Number(time[1]) % 12 + (time[3].toUpperCase() === 'PM' ? 12 : 0), Number(time[2]), 0, 0);
        // Reference demo clocks have explicit instants; real Event handling stays unchanged.
        const demoTarget = Date.parse(invitation().reference_demo?.countdown_at ?? invitation().event.countdown_at ?? '');
        const seconds = Math.max(0, Math.floor(((Number.isFinite(demoTarget) ? demoTarget : target.getTime()) - now.value) / 1000)) || 0;
        return [Math.floor(seconds / 86400), Math.floor(seconds / 3600) % 24, Math.floor(seconds / 60) % 60, seconds % 60].map(n => String(n).padStart(2, '0'));
    });
    function enter() { root.value?.querySelector('[data-invitation-content]')?.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'start' }); }
    function updateReveals() {
        if (revealFrame) return;
        revealFrame = requestAnimationFrame(() => {
            revealFrame = 0;
            for (const el of revealElements) {
                const transform = getComputedStyle(el).transform;
                const translateY = transform === 'none' ? 0 : new DOMMatrixReadOnly(transform).m42;
                const layoutTop = el.getBoundingClientRect().top - translateY;
                el.classList.toggle('arrived', layoutTop <= innerHeight - (options.revealOffset ?? 0));
            }
        });
    }
    onMounted(() => {
        timer = setInterval(() => { now.value = Date.now(); }, 1000);
        if (options.revealOffset !== undefined) {
            revealElements = [...root.value?.querySelectorAll<HTMLElement>('[data-reveal]') ?? []];
            window.addEventListener('scroll', updateReveals, { passive: true, capture: true });
            window.addEventListener('resize', updateReveals);
            updateReveals();
            return;
        }
        observer = new IntersectionObserver(entries => entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add('arrived'); observer?.unobserve(entry.target); } }), { threshold: .08 });
        root.value?.querySelectorAll('[data-reveal]').forEach(el => observer!.observe(el));
    });
    watch(() => [invitation().content?.story_enabled, invitation().content?.gift_registry_enabled, invitation().content?.ending_enabled], () => {
        if (options.revealOffset !== undefined) { revealElements = [...root.value?.querySelectorAll<HTMLElement>('[data-reveal]') ?? []]; updateReveals(); }
        else root.value?.querySelectorAll('[data-reveal]:not(.arrived)').forEach(el => observer?.observe(el));
    }, { flush: 'post' });
    onBeforeUnmount(() => { clearInterval(timer); observer?.disconnect(); cancelAnimationFrame(revealFrame); window.removeEventListener('scroll', updateReveals, true); window.removeEventListener('resize', updateReveals); });
    return { names, date, validDate, dateLabel, month, days, countdown, enter };
}
