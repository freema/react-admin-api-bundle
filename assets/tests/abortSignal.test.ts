import { test } from 'node:test';
import { strict as assert } from 'node:assert';
import { combineSignals, timeoutSignal } from '../src/abortSignal';

test('combineSignals returns undefined when no signals are provided', () => {
    assert.equal(combineSignals(), undefined);
    assert.equal(combineSignals(undefined, null), undefined);
});

test('combineSignals returns the single signal verbatim', () => {
    const ctrl = new AbortController();
    assert.equal(combineSignals(ctrl.signal), ctrl.signal);
});

test('combineSignals aborts when any input signal aborts', () => {
    const a = new AbortController();
    const b = new AbortController();
    const combined = combineSignals(a.signal, b.signal);
    assert.ok(combined);
    assert.equal(combined!.aborted, false);

    b.abort('reason-b');
    assert.equal(combined!.aborted, true);
});

test('combineSignals is already aborted when an input was aborted up-front', () => {
    const a = new AbortController();
    a.abort('pre-aborted');
    const b = new AbortController();
    const combined = combineSignals(a.signal, b.signal);
    assert.ok(combined);
    assert.equal(combined!.aborted, true);
});

test('timeoutSignal aborts after the configured delay', async () => {
    const signal = timeoutSignal(20);
    assert.equal(signal.aborted, false);
    await new Promise((resolve) => setTimeout(resolve, 50));
    assert.equal(signal.aborted, true);
});
