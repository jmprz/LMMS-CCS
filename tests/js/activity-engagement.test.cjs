const test = require('node:test');
const assert = require('node:assert/strict');
require('../../public/js/material-engagement.js');
require('../../public/js/activity-engagement.js');
const { eligible } = globalThis.LMMSActivityEngagement;
test('UI activity requires focus and visibility and excludes material/quiz viewers', () => {
    const doc = { visibilityState: 'visible', hasFocus: () => true, querySelectorAll: () => [] };
    assert.equal(eligible(doc), true);
    doc.visibilityState = 'hidden'; assert.equal(eligible(doc), false);
    doc.visibilityState = 'visible'; doc.hasFocus = () => false; assert.equal(eligible(doc), false);
    doc.hasFocus = () => true;
    doc.querySelectorAll = () => [{ getClientRects: () => [{}] }]; assert.equal(eligible(doc), false);
    doc.querySelectorAll = () => [{ getClientRects: () => [] }]; assert.equal(eligible(doc), true);
});
test('generic clock excludes idle periods and suspended timers', () => {
    let time = 0;
    const clock = new globalThis.LMMSMaterialEngagement.ActiveMaterialClock(() => time);
    time = 1000; clock.tick(true); assert.equal(clock.seconds, 1);
    time = 2000; clock.tick(false); assert.equal(clock.seconds, 1);
    time = 61000; clock.tick(true); assert.equal(clock.seconds, 1);
    time = 62000; clock.tick(true); assert.equal(clock.seconds, 1);
    clock.activity(); time = 63000; clock.tick(true); assert.equal(clock.seconds, 2);
});
test('classroom tracker flushes cumulative seconds on pagehide and restarts after pageshow', async () => {
    const fs = require('node:fs');
    const vm = require('node:vm');
    let time = 0, nextId = 0;
    const listeners = {}, documentListeners = {}, intervals = [], flushes = [];
    const doc = { visibilityState: 'visible', hasFocus: () => true, querySelectorAll: () => [],
        addEventListener: (event, handler) => { documentListeners[event] = handler; } };
    const materialApi = globalThis.LMMSMaterialEngagement;
    const win = { addEventListener: (event, handler) => { listeners[event] = handler; },
        LMMSMaterialEngagement: {
            ActiveMaterialClock: class extends materialApi.ActiveMaterialClock { constructor() { super(() => time); } },
            sessionKey: () => `test-key-${nextId}`,
            flushSession: (url, payload, token) => flushes.push({ url, payload, token })
        } };
    const context = { window: win, document: doc, setInterval: (handler, ms) => intervals.push({ handler, ms }),
        fetch: async url => ({ ok: true, json: async () => url.endsWith('start') ? { session_id: ++nextId, closed: false } : { closed: false } }) };
    vm.runInNewContext(fs.readFileSync(require.resolve('../../public/js/activity-engagement.js'), 'utf8'), context);
    win.LMMSActivityEngagement.track(1, 'test-token');
    await new Promise(resolve => setImmediate(resolve));
    time = 1000; intervals.find(timer => timer.ms === 1000).handler();
    doc.visibilityState = 'hidden'; time = 2000; documentListeners.visibilitychange();
    time = 3000; listeners.pagehide();
    assert.equal(flushes.length, 1);
    assert.equal(flushes[0].url, '/student/classroom/1/activity-end');
    assert.equal(flushes[0].payload.active_seconds, 1);
    assert.equal(flushes[0].token, 'test-token');
    doc.visibilityState = 'visible'; listeners.pageshow();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(nextId, 2);
});
