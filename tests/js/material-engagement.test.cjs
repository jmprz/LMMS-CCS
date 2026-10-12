const test = require('node:test');
const assert = require('node:assert/strict');
require('../../public/js/material-engagement.js');
const { ActiveMaterialClock, flushSession, createViewer, sessionKey } = globalThis.LMMSMaterialEngagement;

test('visible activity accumulates; hidden, idle, and suspended gaps do not', () => {
    let now = 0;
    const clock = new ActiveMaterialClock(() => now);
    for (let i = 0; i < 10; i++) { now += 1000; clock.tick(true); }
    assert.equal(clock.seconds, 10);
    for (let i = 0; i < 20; i++) { now += 1000; clock.tick(false); }
    assert.equal(clock.seconds, 10);
    now += 60000; clock.tick(true); // No recovery of the hidden interval.
    now += 1000; clock.tick(true); // Visible but idle.
    assert.equal(clock.seconds, 10);
    clock.activity(); now += 1000; clock.tick(true);
    assert.equal(clock.seconds, 11);
    now += 30000; clock.tick(true); // Suspended timer.
    assert.equal(clock.seconds, 11);
});

test('pagehide flush sends the identified cumulative session with CSRF via beacon', () => {
    let seen;
    flushSession('/end', { session_id: 7, sequence: 2, active_seconds: 10 }, 'csrf', {
        sendBeacon(url, form) { seen = { url, id: form.get('session_id'), token: form.get('_token'), seconds: form.get('active_seconds') }; return true; }
    }, () => assert.fail('No fetch needed'));
    assert.deepEqual(seen, { url: '/end', id: '7', token: 'csrf', seconds: '10' });
});

test('beacon rejection falls back to keepalive fetch', async () => {
    let seen;
    await flushSession('/end', { session_id: 7, sequence: 3, active_seconds: 11 }, 'csrf', { sendBeacon: () => false },
        async (url, options) => { seen = options; });
    assert.equal(seen.keepalive, true);
    assert.equal(seen.body.get('active_seconds'), '11');
});

test('beacon exception falls back and UUID generation supports older browser APIs', async () => {
    let sent = false;
    await flushSession('/end', { session_id: 7, sequence: 4, active_seconds: 11 }, 'csrf', { sendBeacon() { throw new Error('Unavailable'); } },
        async () => { sent = true; });
    assert.equal(sent, true);
    const key = sessionKey({ getRandomValues(bytes) { return bytes.fill(255); } });
    assert.match(key, /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/);
});

test('viewer lifecycle pauses hidden and external resources and pagehide invokes finalization', () => {
    const events = {};
    global.window = { addEventListener(name, handler) { events[name] = handler; }, removeEventListener() {} };
    global.document = { visibilityState: 'hidden', hasFocus: () => true, addEventListener() {}, removeEventListener() {} };
    global.navigator = { sendBeacon: () => true };
    const viewer = createViewer('csrf');
    viewer.init();
    let eligible;
    viewer.showViewer = true; viewer.viewerReady = true;
    viewer.session = { session_id: 7, measurement_mode: 'internal_visible' };
    viewer.currentMaterial = { id: 1 };
    viewer.clock = { tick(value) { eligible = value; }, seconds: 4 };
    viewer.sample(); assert.equal(eligible, false);
    document.visibilityState = 'visible'; viewer.sample(); assert.equal(eligible, true);
    viewer.session.measurement_mode = 'external_open'; viewer.sample(); assert.equal(eligible, false);
    let flushed = 0; viewer.flush = () => flushed++;
    events.pagehide(); assert.equal(flushed, 1); assert.equal(viewer.showViewer, false);
    delete global.window; delete global.document; delete global.navigator;
});
