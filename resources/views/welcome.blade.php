<x-layouts.app>
    <div
        class="flex gap-4 py-12 h-screen overflow-hidden"
        x-data="{
            allExceptions: {{ Js::from($exceptions) }},
            perPage: 8,
            page: 1,
            filter: 'ongoing',
            selected: {{ Js::from($exceptions->firstWhere('id', (int) request('exception')) ?? $exceptions->first()) ?? 'null' }},
            get filtered() {
                if (this.filter === 'resolved') return this.allExceptions.filter(e => e.resolved_at);
                if (this.filter === 'ongoing') return this.allExceptions.filter(e => !e.resolved_at);
                return this.allExceptions;
            },
            get exceptions() {
                return this.filtered.slice((this.page - 1) * this.perPage, this.page * this.perPage);
            },
            get totalPages() {
                return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
            },
            loading: false,
            resolving: false,
            sentryError: '',
            async resolveException(resolveInSentry = false) {
                if (!this.selected || this.resolving) return;
                this.resolving = true;
                this.sentryError = '';
                try {
                    const res = await fetch('/exceptions/' + this.selected.id + '/resolve', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ resolve_in_sentry: resolveInSentry }),
                    });
                    if (res.ok) {
                        const payload = await res.clone().json().catch(() => null);
                        if (resolveInSentry && payload && payload.sentry_resolved === false && payload.sentry_error) {
                            this.sentryError = payload.sentry_error;
                            setTimeout(() => this.sentryError = '', 6000);
                        }
                        const now = new Date().toISOString();
                        const resolvedId = this.selected.id;
                        const currentIndex = this.filtered.findIndex(e => e.id === resolvedId);

                        this.allExceptions = this.allExceptions.map(e =>
                            e.id === resolvedId ? { ...e, resolved_at: now } : e
                        );

                        const nextFiltered = this.filtered;
                        if (nextFiltered.length > 0) {
                            const nextIndex = Math.min(currentIndex, nextFiltered.length - 1);
                            this.selected = nextFiltered[nextIndex];
                        } else {
                            this.selected = null;
                        }
                    }
                } finally {
                    this.resolving = false;
                }
            },
            async fetchExceptions() {
                this.loading = true;
                try {
                    const res = await fetch('{{ route('exceptions.index') }}', {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    this.allExceptions = data;
                    this.page = 1;
                    this.selected = this.filtered[0] ?? null;
                } finally {
                    this.loading = false;
                }
            },
            async pollException(id, attempts = 0) {
                if (attempts > 20) return;

                const delay = attempts < 5 ? 500 : 2000;

                await new Promise(r => setTimeout(r, delay));

                const res = await fetch('/exceptions/' + id, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!res.ok) return;

                const updated = await res.json();

                this.allExceptions = this.allExceptions.map(e => e.id === id ? updated : e);

                if (this.selected?.id === id) this.selected = updated;

                if (!updated.sent_to_mail && !updated.sent_to_slack && !updated.sent_to_sentry) {
                    this.pollException(id, attempts + 1);
                }
            },
            parseFrames(trace) {
                return trace.split('\n').filter(l => l.trim()).map(line => {
                    const m = line.match(/^(#\d+)\s+(.*)\((\d+)\):\s+(.+)$/);
                    if (!m) return { raw: line };
                    const fileParts = m[2].split('/');
                    const filename = fileParts.pop();
                    const dir = fileParts.join('/') + '/';
                    return { index: m[1], dir, filename, line: m[3], call: m[4] };
                });
            }
        }"
        @refresh-exceptions.window="fetchExceptions()"
        @poll-latest-exception.window="fetchExceptions().then(() => { if (allExceptions[0]) pollException(allExceptions[0].id) })"
    >
        <div class="flex flex-col w-48 rounded-xl shrink-0">
            <a href="/" class="text-white text-xl hover:underline tracking-wider">FaultLine</a>

            @auth
            <div class="flex flex-col gap-2 mt-6">
                <x-trigger-modal />
            </div>
            @endauth

            <div class="mt-auto">
                @auth
                <div class="flex items-center gap-2 rounded-xl">
                    @if(auth()->user()->avatar)
                    <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full shrink-0" />
                    @endif
                    <div class="flex flex-col min-w-0 flex-1">
                        <span class="text-white text-xs font-medium truncate">{{ auth()->user()->name }}</span>
                        <span class="text-[#666] text-xs truncate">{{ auth()->user()->email }}</span>
                    </div>
                    <x-settings-modal />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-[#666] hover:text-white transition-colors cursor-pointer" title="Sign out">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </button>
                    </form>
                </div>
                @else
                <x-auth.login-modal>
                    <button class="w-full text-[#888] hover:text-white text-sm p-2 rounded-xl hover:bg-[#2C2C2A] transition-colors cursor-pointer text-left">
                        Sign in / Register
                    </button>
                </x-auth.login-modal>
                @endauth
            </div>
        </div>

        <div class="bg-[#2C2C2A] rounded-xl p-3 flex flex-col min-h-0 flex-1 min-w-0">
            <div class="flex gap-1 bg-[#1c1c1a] rounded-lg p-1 mb-3 shrink-0">
                <button @click="filter = 'ongoing'; page = 1" class="flex-1 text-xs py-1.5 rounded-md transition-colors cursor-pointer" :class="filter === 'ongoing' ? 'bg-[#3A3A38] text-white' : 'text-[#888] hover:text-white'">Ongoing</button>
                <button @click="filter = 'resolved'; page = 1" class="flex-1 text-xs py-1.5 rounded-md transition-colors cursor-pointer" :class="filter === 'resolved' ? 'bg-[#3A3A38] text-white' : 'text-[#888] hover:text-white'">Resolved</button>
                <button @click="filter = 'all'; page = 1" class="flex-1 text-xs py-1.5 rounded-md transition-colors cursor-pointer" :class="filter === 'all' ? 'bg-[#3A3A38] text-white' : 'text-[#888] hover:text-white'">All</button>
            </div>

            <ul class="flex flex-col gap-2 overflow-y-auto flex-1 min-h-0 [scrollbar-gutter:stable]">
                <template x-for="exception in exceptions" :key="exception.id">
                    <li class="rounded-xl overflow-hidden shrink-0">
                        <button
                            @click="selected = (selected?.id === exception.id ? null : exception)"
                            class="w-full text-left flex items-center justify-between gap-4 px-4 py-3 transition-colors cursor-pointer"
                            :class="selected?.id === exception.id ? 'bg-[#3A3A38]' : 'bg-[#1c1c1a] hover:bg-[#3A3A38]'"
                        >
                            <div class="flex flex-col gap-0.5 min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="text-[10px] px-1.5 py-0.5 rounded font-mono shrink-0"
                                        :class="exception.type === 'domain' ? 'bg-[#f472b6]/10 text-[#f472b6]' : 'bg-[#7db8f7]/10 text-[#7db8f7]'"
                                        x-text="exception.type"
                                    ></span>
                                    <span class="text-white text-sm font-semibold truncate" x-text="exception.message"></span>
                                </div>
                                <span class="text-[#888] text-xs truncate" x-text="exception.file + ':' + exception.line"></span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <div class="flex items-center gap-1.5">
                                    <svg :class="exception.sent_to_mail ? 'text-[#7db8f7]' : 'opacity-20 text-[#888]'" title="Mail" class="w-3.5 h-3.5 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <svg :class="exception.sent_to_slack ? 'text-[#e8c97a]' : 'opacity-20 text-[#888]'" title="Slack" class="w-3.5 h-3.5 transition-opacity" viewBox="0 0 24 24" fill="currentColor"><path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/></svg>
                                    <svg :class="exception.sent_to_sentry ? 'text-[#f472b6]' : 'opacity-20 text-[#888]'" title="Sentry" class="w-3.5 h-3.5 transition-opacity" viewBox="0 0 24 24" fill="currentColor"><path d="M14.022.445a2.063 2.063 0 0 0-3.572 0L.18 18.997A2.063 2.063 0 0 0 1.966 22h4.457a12.14 12.14 0 0 1-.174-2 12.44 12.44 0 0 1 8.247-11.73l-1.18-2.044A10.12 10.12 0 0 0 9.05 20H6.57L14.014 7.13 18.63 15.1A10.13 10.13 0 0 0 15 15a10.2 10.2 0 0 0-2.04.21l1.04 1.8A8.14 8.14 0 0 1 22.08 22h1.954a2.063 2.063 0 0 0 1.786-3.003z"/></svg>
                                </div>
                                <span class="text-[#888] text-xs" x-text="new Date(exception.created_at).toLocaleString()"></span>
                            </div>
                        </button>
                    </li>
                </template>

                <template x-if="filtered.length === 0">
                    <li class="text-[#888] text-sm text-center py-16">
                        @auth
                        <span x-text="filter === 'resolved' ? 'No resolved exceptions yet.' : filter === 'ongoing' ? 'No ongoing exceptions. Hit Trigger exception to create one.' : 'No exceptions recorded yet. Hit Trigger exception to create one.'"></span>
                        @else
                        <span @click="$dispatch('open-login')" class="cursor-pointer hover:text-white transition-colors">Sign in</span> to see your exceptions.
                        @endauth
                    </li>
                </template>
            </ul>

            <div class="flex items-center justify-between pt-3 mt-3 border-t border-[#3a3a38] shrink-0">
                <span class="text-[#888] text-xs" x-text="'Page ' + page + ' of ' + totalPages + ' · ' + filtered.length + ' total'"></span>
                <div class="flex gap-2">
                    <button @click="page = Math.max(1, page - 1)" :disabled="page === 1" class="text-xs px-3 py-1.5 rounded-lg bg-[#1c1c1a] text-[#aaa] hover:bg-[#3A3A38] disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition-colors">← Prev</button>
                    <button @click="page = Math.min(totalPages, page + 1)" :disabled="page === totalPages" class="text-xs px-3 py-1.5 rounded-lg bg-[#1c1c1a] text-[#aaa] hover:bg-[#3A3A38] disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition-colors">Next →</button>
                </div>
            </div>
        </div>

        <div
            x-show="selected"
            x-transition:enter="transition[opacity,transform] duration-200"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition[opacity,transform] duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-8"
            class="bg-[#1c1c1a] rounded-xl p-4 flex flex-col min-h-0 w-3/5 shrink-0"
        >
            <div class="flex items-start justify-between gap-4 mb-4 shrink-0">
                <div class="flex flex-col gap-1 min-w-0">
                    <span class="text-white text-sm font-semibold" x-text="selected?.message"></span>
                    <span class="text-[#888] text-xs" x-text="selected?.file + ':' + selected?.line"></span>
                    <span x-show="sentryError" x-text="sentryError" class="text-[#f472b6] text-xs"></span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <!-- Resolve buttons — only shown when not yet resolved -->
                    <template x-if="selected && !selected.resolved_at">
                        <div class="flex items-center gap-2">
                            <!-- Resolve in Sentry button — only shown when sent_to_sentry is true -->
                            <template x-if="selected.sent_to_sentry">
                                <button
                                    @click="resolveException(true)"
                                    :disabled="resolving"
                                    class="text-xs px-3 py-1.5 rounded-lg bg-[#f472b6]/10 hover:bg-[#f472b6]/20 text-[#f472b6] transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                                    x-text="resolving ? 'Resolving…' : 'Resolve in Sentry'"
                                ></button>
                            </template>
                            <button
                                @click="resolveException(false)"
                                :disabled="resolving"
                                class="text-xs px-3 py-1.5 rounded-lg bg-[#2C2C2A] hover:bg-[#3A3A38] text-white transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
                                x-text="resolving ? 'Resolving…' : (selected.sent_to_sentry ? 'Resolve locally' : 'Resolve')"
                            ></button>
                        </div>
                    </template>
                    <!-- Resolved badge — shown when already resolved -->
                    <template x-if="selected && selected.resolved_at">
                        <span class="text-xs px-2 py-1 rounded-lg bg-green-500/10 text-green-400">Resolved</span>
                    </template>
                    <button @click="selected = null" class="text-[#666] hover:text-white transition-colors cursor-pointer text-lg leading-none">✕</button>
                </div>
            </div>

            <div class="overflow-y-auto flex-1 min-h-0 [scrollbar-gutter:stable]">
                <template x-if="selected">
                    <div>
                        <template x-for="(frame, i) in parseFrames(selected.stack_trace)" :key="i">
                            <div class="flex gap-3 py-2 border-b border-[#2a2a28] last:border-0 font-mono text-xs">
                                <template x-if="frame.raw">
                                    <span class="text-[#666]" x-text="frame.raw"></span>
                                </template>
                                <template x-if="!frame.raw">
                                    <div class="flex items-baseline gap-3 min-w-0 w-full">
                                        <span class="text-[#555] shrink-0 w-6 text-right" x-text="frame.index.replace('#', '')"></span>
                                        <div class="flex flex-col gap-0.5 min-w-0 flex-1">
                                            <div class="truncate">
                                                <span class="text-[#555]" x-text="frame.dir"></span><span class="text-[#aaa]" x-text="frame.filename"></span><span class="text-[#555]">:</span><span class="text-[#e8c97a]" x-text="frame.line"></span>
                                            </div>
                                            <span class="text-[#7db8f7] truncate" x-text="frame.call"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-layouts.app>
