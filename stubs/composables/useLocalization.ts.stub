import { computed, watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface Localization {
    locale: string;
    timezone: string;
    direction: 'ltr' | 'rtl';
    isRtl: boolean;
}

interface Translations {
    [key: string]: string | Translations;
}

// Global translations cache
let translationsCache: Record<string, Translations> = {};

/**
 * Get value from object - supports both flat keys and nested paths
 * The middleware flattens translations to dot notation keys like "demo.form.title"
 * So we first check for a direct match, then try nested traversal
 */
function getNestedValue(obj: Translations, path: string): string | undefined {
    // First, try direct key lookup (for flattened translations from middleware)
    if (path in obj && typeof obj[path] === 'string') {
        return obj[path] as string;
    }

    // Fallback: try nested traversal for backward compatibility
    const keys = path.split('.');
    let current: string | Translations = obj;

    for (const key of keys) {
        if (typeof current !== 'object' || current === null) {
            return undefined;
        }
        current = (current as Translations)[key];
    }

    return typeof current === 'string' ? current : undefined;
}

/**
 * Replace placeholders in translation string
 */
function replacePlaceholders(str: string, replacements: Record<string, string | number>): string {
    return str.replace(/:(\w+)/g, (_, key) => {
        return String(replacements[key] ?? `:${key}`);
    });
}

/**
 * Composable for localization
 */
export function useLocalization() {
    const page = usePage();

    const localization = computed<Localization>(() => {
        return (page.props as any).localization ?? {
            locale: 'en',
            timezone: 'UTC',
            direction: 'ltr',
            isRtl: false,
        };
    });

    const locale = computed(() => localization.value.locale);
    const timezone = computed(() => localization.value.timezone);
    const direction = computed(() => localization.value.direction);
    const isRtl = computed(() => localization.value.isRtl);

    // Update HTML attributes when localization changes
    watchEffect(() => {
        if (typeof document !== 'undefined') {
            document.documentElement.lang = locale.value;
            document.documentElement.dir = direction.value;
        }
    });

    /**
     * Get translations for the current locale
     */
    const translations = computed<Translations>(() => {
        return (page.props as any).translations ?? translationsCache[locale.value] ?? {};
    });

    /**
     * Translate a key with optional replacements
     * Similar to Laravel's __() / trans() function
     */
    function trans(key: string, replacements: Record<string, string | number> = {}): string {
        const value = getNestedValue(translations.value, key);

        if (value === undefined) {
            // Return the key if no translation found (like Laravel does)
            return replacePlaceholders(key, replacements);
        }

        return replacePlaceholders(value, replacements);
    }

    /**
     * Alias for trans()
     */
    function __(key: string, replacements: Record<string, string | number> = {}): string {
        return trans(key, replacements);
    }

    /**
     * Check if a translation exists
     */
    function hasTranslation(key: string): boolean {
        return getNestedValue(translations.value, key) !== undefined;
    }

    /**
     * Get plural translation
     * Similar to Laravel's trans_choice()
     */
    function transChoice(key: string, count: number, replacements: Record<string, string | number> = {}): string {
        const value = getNestedValue(translations.value, key);

        if (value === undefined) {
            return replacePlaceholders(key, { ...replacements, count });
        }

        // Simple plural handling: "one|many" format
        const parts = value.split('|');
        const text = count === 1 ? parts[0] : (parts[1] ?? parts[0]);

        return replacePlaceholders(text, { ...replacements, count });
    }

    return {
        locale,
        timezone,
        direction,
        isRtl,
        localization,
        translations,
        trans,
        __,
        hasTranslation,
        transChoice,
    };
}

/**
 * Set translations cache (called from server)
 */
export function setTranslations(locale: string, translations: Translations): void {
    translationsCache[locale] = translations;
}

/**
 * Global trans function for use outside components
 */
export function trans(key: string, replacements: Record<string, string | number> = {}): string {
    // Try to get from page props first
    if (typeof window !== 'undefined' && (window as any).__inertia_page) {
        const pageData = (window as any).__inertia_page;
        const translations = pageData.props?.translations ?? {};
        const value = getNestedValue(translations, key);

        if (value !== undefined) {
            return replacePlaceholders(value, replacements);
        }
    }

    return replacePlaceholders(key, replacements);
}

// Export __ as alias
export const __ = trans;
