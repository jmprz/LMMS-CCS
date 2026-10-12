(function (root) {
    'use strict';
    class ActiveMaterialClock {
        constructor(now = () => performance.now()) {
            this.now = now;
            this.lastAt = now();
            this.lastActivity = this.lastAt;
            this.milliseconds = 0;
        }
        activity() { this.lastActivity = this.now(); }
        tick(eligible) {
            const current = this.now();
            const delta = Math.max(0, current - this.lastAt);
            // Discard suspended/throttled timer gaps rather than inventing viewing time.
            if (eligible && delta <= 1500 && current - this.lastActivity <= 60000) {
                this.milliseconds += delta;
            }
            this.lastAt = current;
        }
        get seconds() { return Math.floor(this.milliseconds / 1000); }
    }

    function flushSession(url, payload, token, navigatorObject = navigator, fetcher = fetch) {
        const form = new FormData();
        form.append('_token', token);
        Object.entries(payload).forEach(([key, value]) => form.append(key, value));
        try {
            if (navigatorObject.sendBeacon && navigatorObject.sendBeacon(url, form)) return;
        } catch (_) { /* Fall back when the browser cannot queue a beacon. */ }
        return fetcher(url, { method: 'POST', body: form, keepalive: true }).catch(() => {});
    }

    function sessionKey(cryptoObject = crypto) {
        if (cryptoObject.randomUUID) return cryptoObject.randomUUID();
        const bytes = cryptoObject.getRandomValues(new Uint8Array(16));
        bytes[6] = (bytes[6] & 15) | 64;
        bytes[8] = (bytes[8] & 63) | 128;
        const hex = Array.from(bytes, value => value.toString(16).padStart(2, '0')).join('');
        return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
    }

    function createViewer(token) {
        return {
            showViewer: false, viewerReady: false, opening: false, session: null, pendingOpen: null,
            currentMaterial: { title: '', type: '', url: '', id: null },
            clock: null, sequence: 0, timer: null, heartbeatTimer: null, queue: Promise.resolve(), leaving: false,
            init() {
                this.onActivity = () => this.clock?.activity();
                this.onVisibility = () => { this.sample(); this.sendHeartbeat(); };
                this.onPageHide = () => { this.leaving = true; this.flush(); this.stopTimers(); this.showViewer = false; };
                ['pointerdown', 'pointermove', 'keydown', 'wheel', 'touchstart', 'focus'].forEach(event => window.addEventListener(event, this.onActivity, { passive: true }));
                document.addEventListener('visibilitychange', this.onVisibility);
                window.addEventListener('blur', this.onVisibility);
                window.addEventListener('pagehide', this.onPageHide);
            },
            destroy() {
                this.flush(); this.stopTimers();
                ['pointerdown', 'pointermove', 'keydown', 'wheel', 'touchstart', 'focus'].forEach(event => window.removeEventListener(event, this.onActivity));
                document.removeEventListener('visibilitychange', this.onVisibility);
                window.removeEventListener('blur', this.onVisibility);
                window.removeEventListener('pagehide', this.onPageHide);
            },
            async openMaterial(material) {
                if (this.opening || (this.showViewer && this.currentMaterial.id === material.id)) return;
                if (this.session) await this.closeMaterial();
                this.opening = true; this.leaving = false;
                this.currentMaterial = material; this.viewerReady = false;
                try {
                    if (!this.pendingOpen || this.pendingOpen.materialId !== material.id) {
                        this.pendingOpen = { materialId: material.id, key: sessionKey() };
                    }
                    const response = await fetch(`/student/materials/${material.id}/log-start`, {
                        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                        body: JSON.stringify({ client_session_key: this.pendingOpen.key })
                    });
                    if (!response.ok) throw new Error('Unable to record the material open.');
                    this.session = await response.json();
                    this.pendingOpen = null;
                    if (this.session.closed) throw new Error('The material session is already closed.');
                    this.clock = new ActiveMaterialClock(); this.sequence = 0; this.queue = Promise.resolve();
                    this.showViewer = true;
                    if (this.leaving) { this.flush(); this.showViewer = false; return; }
                    this.timer = setInterval(() => this.sample(), 1000);
                    this.heartbeatTimer = setInterval(() => this.sendHeartbeat(), 10000);
                } catch (error) { console.warn(error.message); this.showViewer = false; }
                finally { this.opening = false; }
            },
            sample() {
                this.clock?.tick(this.showViewer && this.viewerReady && this.session?.measurement_mode === 'internal_visible'
                    && document.visibilityState === 'visible' && document.hasFocus());
            },
            payload() {
                this.sample();
                return { session_id: this.session.session_id, sequence: ++this.sequence, active_seconds: this.clock?.seconds || 0 };
            },
            sendHeartbeat() {
                if (!this.session || !this.showViewer) return;
                const url = `/student/materials/${this.currentMaterial.id}/log-heartbeat`;
                const body = this.payload();
                this.queue = this.queue.catch(() => {}).then(() => fetch(url, {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: JSON.stringify(body)
                })).catch(() => {});
            },
            stopTimers() { clearInterval(this.timer); clearInterval(this.heartbeatTimer); this.timer = null; this.heartbeatTimer = null; },
            flush() {
                if (!this.session) return;
                return flushSession(`/student/materials/${this.currentMaterial.id}/log-end`, this.payload(), token);
            },
            async closeMaterial() {
                if (!this.session) return;
                const url = `/student/materials/${this.currentMaterial.id}/log-end`;
                const body = this.payload();
                this.stopTimers(); this.showViewer = false;
                // Closing never makes the UI depend on delivery. Missed finalization stays auditable.
                try {
                    const response = await fetch(url, { method: 'POST', keepalive: true,
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }, body: JSON.stringify(body) });
                    if (!response.ok) flushSession(url, body, token);
                } catch (_) { flushSession(url, body, token); }
                this.session = null; this.clock = null;
            }
        };
    }
    const api = { ActiveMaterialClock, flushSession, createViewer, sessionKey };
    root.LMMSMaterialEngagement = api;
    if (typeof module !== 'undefined' && module.exports) module.exports = api;
})(typeof window !== 'undefined' ? window : globalThis);
