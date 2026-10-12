(function (root) {
    'use strict';
    function eligible(documentObject) {
        return documentObject.visibilityState === 'visible' && documentObject.hasFocus()
            && !Array.from(documentObject.querySelectorAll('[data-engagement-exclusive]'))
                .some(element => element.getClientRects().length > 0);
    }

    function track(classId, token) {
        const api = root.LMMSMaterialEngagement;
        const base = `/student/classroom/${classId}/activity-`;
        let session = null, key = api.sessionKey(), clock = null, sequence = 0, starting = false, leaving = false;
        let queue = Promise.resolve();
        const headers = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' };
        const sample = () => clock?.tick(eligible(document));
        const payload = () => { sample(); return { session_id: session.session_id, sequence: ++sequence, active_seconds: clock.seconds }; };
        async function start() {
            if (starting || session || leaving || !eligible(document)) return;
            starting = true;
            try {
                const response = await fetch(base + 'start', { method: 'POST', headers, body: JSON.stringify({ client_session_key: key }) });
                if (!response.ok) return;
                session = await response.json();
                if (session.closed) { session = null; key = api.sessionKey(); return; }
                clock = new api.ActiveMaterialClock(); sequence = 0;
                if (leaving) end();
            } catch (_) { /* A retry retains the same open key. */ }
            finally { starting = false; }
        }
        function heartbeat() {
            if (!session || leaving) return;
            const body = payload();
            queue = queue.catch(() => {}).then(async () => {
                const response = await fetch(base + 'heartbeat', { method: 'POST', headers, body: JSON.stringify(body) });
                if (response.ok && (await response.json()).closed) {
                    session = null; clock = null; key = api.sessionKey();
                }
            }).catch(() => {});
        }
        function end() {
            if (!session) return;
            api.flushSession(base + 'end', payload(), token);
            session = null; clock = null; key = api.sessionKey();
        }
        ['pointerdown', 'pointermove', 'keydown', 'wheel', 'touchstart'].forEach(event =>
            window.addEventListener(event, () => clock?.activity(), { passive: true }));
        const transition = () => { clock?.tick(false); if (session) heartbeat(); else start(); };
        document.addEventListener('visibilitychange', transition);
        window.addEventListener('focus', () => { clock?.activity(); transition(); });
        window.addEventListener('blur', transition);
        window.addEventListener('pagehide', () => { leaving = true; end(); });
        window.addEventListener('pageshow', () => { leaving = false; start(); });
        setInterval(() => { sample(); if (!session) start(); }, 1000);
        setInterval(heartbeat, 10000);
        start();
    }
    root.LMMSActivityEngagement = { eligible, track };
})(typeof window !== 'undefined' ? window : globalThis);
