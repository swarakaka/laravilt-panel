import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, watch } from 'vue';

interface FontData {
    provider: string;
    family: string;
    url: string;
    weights: number[];
    subsets?: string[];
    display?: string;
}

interface PanelProps {
    font?: FontData | null;
}

/**
 * Load a font stylesheet dynamically.
 */
function loadFontStylesheet(url: string, fontFamily: string): void {
    if (typeof document === 'undefined') {
        return;
    }

    const linkId = `panel-font-${fontFamily.replace(/\s+/g, '-').toLowerCase()}`;

    // Check if the link already exists
    if (document.getElementById(linkId)) {
        return;
    }

    const link = document.createElement('link');
    link.id = linkId;
    link.rel = 'stylesheet';
    link.href = url;
    document.head.appendChild(link);
}

/**
 * Apply the font family to the document body.
 */
function applyFontFamily(fontFamily: string): void {
    if (typeof document === 'undefined') {
        return;
    }

    const fontValue = `"${fontFamily}", ui-sans-serif, system-ui, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'`;

    // Set CSS custom property for the font family on :root
    document.documentElement.style.setProperty('--font-sans', fontValue);

    // Also set it directly on body and html to override Tailwind's @theme inline
    document.body.style.fontFamily = `var(--font-sans)`;
    document.documentElement.style.fontFamily = `var(--font-sans)`;
}

/**
 * Initialize the panel font from page props.
 */
export function initializePanelFont(): void {
    if (typeof window === 'undefined') {
        return;
    }

    // Try to get font data from the Inertia page data
    const pageData = document.getElementById('app')?.dataset?.page;
    if (pageData) {
        try {
            const props = JSON.parse(pageData);
            const font = props.props?.panel?.font as FontData | null;
            if (font?.url && font?.family) {
                loadFontStylesheet(font.url, font.family);
                applyFontFamily(font.family);
            }
        } catch (e) {
            // Ignore parsing errors
        }
    }
}

/**
 * Composable for managing panel fonts.
 */
export function usePanelFont() {
    const page = usePage<{ panel?: PanelProps }>();

    const font = computed(() => page.props.panel?.font || null);
    const fontFamily = computed(() => font.value?.family || null);
    const fontUrl = computed(() => font.value?.url || null);

    const loadFont = () => {
        if (fontUrl.value && fontFamily.value) {
            loadFontStylesheet(fontUrl.value, fontFamily.value);
            applyFontFamily(fontFamily.value);
        }
    };

    onMounted(() => {
        loadFont();
    });

    // Watch for font changes (in case of SPA navigation between panels)
    watch(font, () => {
        loadFont();
    });

    return {
        font,
        fontFamily,
        fontUrl,
        loadFont,
    };
}
