import { useCallback, useRef } from 'react';

export type DebouncedCallback<Args extends unknown[]> = ((
    ...args: Args
) => void) & {
    /** Discard a pending invocation, if any. */
    cancel: () => void;
};

/**
 * Returns a debounced version of the given callback. Repeated calls within
 * the delay coalesce into a single invocation of the latest arguments.
 */
export function useDebouncedCallback<Args extends unknown[]>(
    callback: (...args: Args) => void,
    delay: number,
): DebouncedCallback<Args> {
    const callbackRef = useRef(callback);
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    callbackRef.current = callback;

    const debounced = useCallback(
        (...args: Args) => {
            if (timerRef.current) {
                clearTimeout(timerRef.current);
            }

            timerRef.current = setTimeout(() => {
                timerRef.current = null;
                callbackRef.current(...args);
            }, delay);
        },
        [delay],
    ) as DebouncedCallback<Args>;

    debounced.cancel = useCallback(() => {
        if (timerRef.current) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }
    }, []);

    return debounced;
}