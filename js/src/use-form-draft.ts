import { useRef } from 'react';
import { useHttp } from '@inertiajs/react';
import { useDebouncedCallback } from './use-debounced-callback';

export type DraftData = Record<string, string | number | boolean | null | undefined>;

export interface UseFormDraftOptions {
    /**
     * Base URL segment the draft routes live under. Defaults to
     * `/dashboard`, matching forms registered inside a
     * `Route::prefix('dashboard')` group.
     */
    baseUrl?: string;

    /** Debounce delay in milliseconds. Defaults to 500. */
    delay?: number;
}

/**
 * Draft persistence for a form registered via `Form::add()` /
 * `Route::form()` on the server. Edits are debounced and merged into
 * the server's stored draft; `clear` removes it.
 *
 * The form key is the form's draft key (the kebab-cased class
 * basename on the PHP side, e.g. `CheckoutForm` => `checkout`).
 *
 * ```tsx
 * const { track, set, clear } = useFormDraft('checkout', billing);
 *
 * <Input type="text" name="company" {...track('company')} />
 * <SimpleCombobox defaultValue={billing.country}
 *     onValueChange={(country) => set('country', country)} />
 * ```
 */
export function useFormDraft<T extends DraftData>(
    key: string,
    data: T,
    options: UseFormDraftOptions | number = {},
) {
    const { baseUrl = '/dashboard', delay = 500 } =
        typeof options === 'number' ? { delay: options } : options;

    // The value type satisfies Inertia's FormDataConvertible shape;
    // the casts work around useHttp's self-referential generic
    // constraint, which cannot be satisfied by a caller-side generic.
    const http = useHttp<DraftData>(data);
    const pending = useRef<Partial<T>>({});

    const flush = useDebouncedCallback(() => {
        const partial = pending.current;

        if (Object.keys(partial).length === 0) {
            return;
        }

        pending.current = {};

        // Only the edited fields are sent; the server merges the
        // partial into the stored draft.
        http.setData(partial);
        void http.patch(draftUrl(key, baseUrl));
    }, delay);

    /**
     * Persist a single field's value (for inputs outside the normal
     * change-event flow, e.g. comboboxes).
     */
    const set = <K extends keyof T>(field: K, value: T[K]) => {
        pending.current[field] = value;

        flush();
    };

    /**
     * Spread onto an uncontrolled input to wire its default value and
     * debounced draft persistence.
     */
    const track = <K extends keyof T>(field: K) => ({
        defaultValue: data[field],
        onChange: (event: React.ChangeEvent<HTMLInputElement>) =>
            set(field, event.target.value as T[K]),
    });

    /**
     * Remove the stored draft.
     */
    const clear = () => {
        pending.current = {};

        void http.delete(draftUrl(key, baseUrl));
    };

    return { track, set, clear };
}

/**
 * Draft routes follow the `forms/{key}/draft` convention registered
 * by `Form::add()`.
 */
const draftUrl = (key: string, baseUrl: string): string =>
    `${baseUrl.replace(/\/$/, '')}/forms/${key}/draft`;