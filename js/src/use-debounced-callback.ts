import { useCallback, useRef } from 'react';

/**
 * Returns a debounced version of the given callback. Repeated calls within
 * the delay coalesce into a single invocation of the latest arguments.
 */
export function useDebouncedCallback<Args extends unknown[]>(
    callback: (...args: Args) => void,
    delay: number,
): (...args: Args) => void {
    const callbackRef = useRef(callback);
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    callbackRef.current = callback;

    return useCallback(
        (...args: Args) => {
            if (timerRef.current) {
                clearTimeout(timerRef.current);
            }

            timerRef.current = setTimeout(() => {
                callbackRef.current(...args);
            }, delay);
        },
        [delay],
    );
}