<x-layouts.app>
    <div
        class="flex gap-4 py-12 h-screen overflow-hidden"
        x-data="{
            allExceptions: {{ Js::from($exceptions) }},
            perPage: 8,
            page: 1,
            selected: {{ $exceptions->first() ? Js::from($exceptions->first()) : 'null' }},
            get exceptions() {
                return this.allExceptions.slice((this.page - 1) * this.perPage, this.page * this.perPage);
            },
            get totalPages() {
                return Math.max(1, Math.ceil(this.allExceptions.length / this.perPage));
            },
            loading: false,
            async fetchExceptions() {
                this.loading = true;
                try {
                    const res = await fetch('{{ route('exceptions.index') }}', {
                        headers: { 'Accept': 'application/json' }
                    });
                    this.allExceptions = await res.json();
                    this.page = 1;
                    this.selected = this.allExceptions[0] ?? null;
                } finally {
                    this.loading = false;
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
    >
        {{-- Left sidebar --}}
        <div class="flex flex-col w-48 rounded-xl p-2 shrink-0">
            <a href="/" class="text-white text-xl hover:underline tracking-wider">FaultLine</a>

            @auth
            <div class="flex flex-col gap-2 mt-6">
                <button
                    @click="
                        fetch('{{ route('exceptions.trigger') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            },
                        }).finally(() => $dispatch('refresh-exceptions'))
                    "
                    class="text-white text-sm bg-[#2C2C2A] p-2 rounded-xl hover:bg-[#3A3A38] cursor-pointer"
                >
                    Trigger exception
                </button>
            </div>
            @endauth

            {{-- Auth footer --}}
            <div class="mt-auto">
                @auth
                <div class="flex items-center gap-2 p-2 rounded-xl">
                    <div class="flex flex-col min-w-0 flex-1">
                        <span class="text-white text-xs font-medium truncate">{{ auth()->user()->name }}</span>
                        <span class="text-[#666] text-xs truncate">{{ auth()->user()->email }}</span>
                    </div>
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

        {{-- Exception list panel --}}
        <div class="bg-[#2C2C2A] rounded-xl p-3 flex flex-col min-h-0 flex-1 min-w-0">
            <ul class="flex flex-col gap-2 overflow-y-auto flex-1 min-h-0 [scrollbar-gutter:stable]">
                <template x-for="exception in exceptions" :key="exception.id">
                    <li class="rounded-xl overflow-hidden shrink-0">
                        <button
                            @click="selected = (selected?.id === exception.id ? null : exception)"
                            class="w-full text-left flex items-center justify-between gap-4 px-4 py-3 transition-colors cursor-pointer"
                            :class="selected?.id === exception.id ? 'bg-[#3A3A38]' : 'bg-[#1c1c1a] hover:bg-[#3A3A38]'"
                        >
                            <div class="flex flex-col gap-0.5 min-w-0">
                                <span class="text-white text-sm font-semibold truncate" x-text="exception.message"></span>
                                <span class="text-[#888] text-xs truncate" x-text="exception.file + ':' + exception.line"></span>
                            </div>
                            <span class="text-[#888] text-xs shrink-0" x-text="new Date(exception.created_at).toLocaleString()"></span>
                        </button>
                    </li>
                </template>

                <template x-if="allExceptions.length === 0">
                    <li class="text-[#888] text-sm text-center py-16">
                        @auth
                        No exceptions recorded yet. Hit <span class="text-white">Trigger exception</span> to create one.
                        @else
                        <span
                            @click="$dispatch('open-login')"
                            class="cursor-pointer hover:text-white transition-colors"
                        >Sign in</span> to see your exceptions.
                        @endauth
                    </li>
                </template>
            </ul>

            {{-- Pagination --}}
            <div class="flex items-center justify-between pt-3 mt-3 border-t border-[#3a3a38] shrink-0">
                <span class="text-[#888] text-xs" x-text="'Page ' + page + ' of ' + totalPages + ' · ' + allExceptions.length + ' total'"></span>
                <div class="flex gap-2">
                    <button
                        @click="page = Math.max(1, page - 1)"
                        :disabled="page === 1"
                        class="text-xs px-3 py-1.5 rounded-lg bg-[#1c1c1a] text-[#aaa] hover:bg-[#3A3A38] disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition-colors"
                    >← Prev</button>
                    <button
                        @click="page = Math.min(totalPages, page + 1)"
                        :disabled="page === totalPages"
                        class="text-xs px-3 py-1.5 rounded-lg bg-[#1c1c1a] text-[#aaa] hover:bg-[#3A3A38] disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition-colors"
                    >Next →</button>
                </div>
            </div>
        </div>

        {{-- Stack trace drawer --}}
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
            {{-- Drawer header --}}
            <div class="flex items-start justify-between gap-4 mb-4 shrink-0">
                <div class="flex flex-col gap-1 min-w-0">
                    <span class="text-white text-sm font-semibold" x-text="selected?.message"></span>
                    <span class="text-[#888] text-xs" x-text="selected?.file + ':' + selected?.line"></span>
                </div>
                <button @click="selected = null" class="text-[#666] hover:text-white transition-colors shrink-0 cursor-pointer text-lg leading-none">✕</button>
            </div>

            {{-- Frames --}}
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
