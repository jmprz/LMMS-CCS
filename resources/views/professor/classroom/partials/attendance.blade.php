<section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5"
    x-data="attendanceControl()" x-init="load(); timer = setInterval(() => load(), 10000)" @destroy.window="clearInterval(timer)">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-bold text-gray-900">Student Attendance</h2>
            <p class="text-sm text-gray-500" x-text="session ? (session.status === 'active' ? 'Active Attendance' : 'Attendance ' + session.status) : 'No attendance session started'"></p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <label x-show="!session || session.status !== 'active'" class="text-sm">Late after
                <input type="number" min="0" max="120" x-model="grace" class="w-20 rounded-lg border-gray-300" aria-label="Lateness grace period in minutes"> minutes
            </label>
            <button type="button" x-show="!session || session.status !== 'active'" :disabled="busy" @click="act('start')"
                class="rounded-lg bg-green-700 px-4 py-2 text-sm font-bold text-white disabled:opacity-50">Start Attendance Session</button>
            <button type="button" x-show="session && session.status === 'active'" x-cloak :disabled="busy" @click="act('finalize')"
                class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-bold text-white disabled:opacity-50">Finalize Attendance</button>
            <button type="button" x-show="session && session.status === 'active'" x-cloak :disabled="busy" @click="act('cancel')"
                class="rounded-lg border border-gray-300 px-4 py-2 text-sm disabled:opacity-50">Cancel Attendance</button>
        </div>
    </div>
    <p class="mt-2 text-sm text-gray-500">Start for a real class meeting. Review collection before finalizing; pending students will be marked absent. Stopping the lab does not finalize attendance.</p>
    <p role="alert" class="mt-2 text-sm text-red-700" x-show="error" x-text="error"></p>
    <div class="mt-4 flex flex-wrap gap-4" x-show="counts">
        <template x-for="key in ['expected', 'present', 'late', 'pending', 'absent', 'excused']" :key="key">
            <div class="rounded-lg bg-gray-50 px-3 py-2 text-sm"><span class="capitalize" x-text="key"></span>: <strong x-text="counts ? counts[key] : 0"></strong></div>
        </template>
    </div>
    <details class="mt-3" x-show="roster.length">
        <summary class="cursor-pointer text-sm font-semibold">Review attendance roster</summary>
        <div class="mt-2 max-h-64 overflow-y-auto">
            <template x-for="student in roster" :key="student.user_id">
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 py-2 text-sm">
                    <span x-text="student.name"></span>
                    <div class="flex items-center gap-3"><span class="capitalize" x-text="student.status"></span>
                        <button type="button" x-show="session && session.status === 'active' && ['pending', 'excused'].includes(student.status)"
                            :disabled="busy" @click="excuse(student)" class="text-blue-700 underline"
                            x-text="student.status === 'excused' ? 'Remove excuse' : 'Excuse'"></button>
                    </div>
                </div>
            </template>
        </div>
    </details>
</section>
<script>
function attendanceControl() {
    return {
        session: null, counts: null, roster: [], error: '', busy: false, grace: 10, timer: null,
        base: @js(route('professor.attendance.show', $class->id)),
        actions: @js(url('/professor/attendance')),
        async request(url, method = 'GET', body = null) {
            const response = await fetch(url, { method, headers: {
                'Accept': 'application/json', 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }, ...(body ? { body: JSON.stringify(body) } : {}) });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Attendance request failed.');
            this.session = data.session; this.counts = data.counts; this.roster = data.roster;
        },
        async load() {
            if (this.busy) return;
            try { await this.request(this.base); this.error = ''; } catch (error) { this.error = error.message; }
        },
        async act(action) {
            if (action === 'finalize' && !confirm('Have you verified attendance collection? All pending roster entries will become absent.')) return;
            if (action === 'cancel' && !confirm('Cancel this attendance session? It will remain excluded from verified attendance.')) return;
            this.busy = true;
            try {
                await this.request(action === 'start' ? this.base : `${this.actions}/${this.session.id}/${action}`, 'POST',
                    action === 'start' ? { late_after_minutes: Number(this.grace) } : null);
                this.error = '';
            } catch (error) { this.error = error.message; } finally { this.busy = false; }
        },
        async excuse(student) {
            this.busy = true;
            try { await this.request(`${this.actions}/${this.session.id}/students/${student.user_id}/excuse`, 'PATCH', { excused: student.status !== 'excused' }); this.error = ''; }
            catch (error) { this.error = error.message; } finally { this.busy = false; }
        },
        destroy() { clearInterval(this.timer); }
    };
}
</script>
