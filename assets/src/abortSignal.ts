/**
 * Combine 1..N AbortSignals into a single signal that aborts as soon as any input aborts.
 *
 * Prefers the native `AbortSignal.any()` (Chrome 116+, FF 124+, Safari 17.4+, Node 20+)
 * and falls back to a manual implementation in older runtimes.
 *
 * `undefined` / `null` entries are ignored so callers can pass optional sources without
 * pre-filtering.
 */
export const combineSignals = (
    ...signals: ReadonlyArray<AbortSignal | undefined | null>
): AbortSignal | undefined => {
    const real = signals.filter((s): s is AbortSignal => Boolean(s));
    if (real.length === 0) {
        return undefined;
    }
    if (real.length === 1) {
        return real[0];
    }

    const nativeAny = (AbortSignal as unknown as { any?: (s: AbortSignal[]) => AbortSignal }).any;
    if (typeof nativeAny === 'function') {
        return nativeAny.call(AbortSignal, real);
    }

    const controller = new AbortController();
    const abort = (signal: AbortSignal): void => {
        if (!controller.signal.aborted) {
            controller.abort((signal as AbortSignal & { reason?: unknown }).reason);
        }
    };
    for (const signal of real) {
        if (signal.aborted) {
            abort(signal);
            break;
        }
        signal.addEventListener('abort', () => abort(signal), { once: true });
    }
    return controller.signal;
};

/**
 * Build a signal that aborts after `ms` milliseconds. Prefers the native
 * `AbortSignal.timeout()` (Chrome 103+, FF 100+, Safari 16+, Node 17.3+).
 */
export const timeoutSignal = (ms: number): AbortSignal => {
    const nativeTimeout = (AbortSignal as unknown as { timeout?: (ms: number) => AbortSignal })
        .timeout;
    if (typeof nativeTimeout === 'function') {
        return nativeTimeout.call(AbortSignal, ms);
    }

    const controller = new AbortController();
    setTimeout(() => {
        if (!controller.signal.aborted) {
            controller.abort(
                Object.assign(new Error(`Request timed out after ${ms}ms`), { name: 'TimeoutError' }),
            );
        }
    }, ms);
    return controller.signal;
};
