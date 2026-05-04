<div
    x-data="{
        open: false,
        tab: 'login',
        email: '',
        password: '',
        name: '',
        error: '',
        loading: false,
        async submit() {
            this.error = '';
            this.loading = true;
            try {
                const res = await fetch(this.tab === 'login' ? '{{ route('login') }}' : '{{ route('register') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(
                        this.tab === 'login'
                            ? { email: this.email, password: this.password }
                            : { name: this.name, email: this.email, password: this.password }
                    ),
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    this.error = data.message ?? 'Something went wrong.';
                }
            } finally {
                this.loading = false;
            }
        }
    }"
    @open-login.window="open = true"
>
    {{-- Trigger slot (the sidebar button passes itself in) --}}
    <div @click="open = true">{{ $slot }}</div>

    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 bg-black/60 z-40"
        style="display:none"
    ></div>

    {{-- Modal --}}
    <div
        x-show="open"
        x-transition:enter="transition duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-center justify-center pointer-events-none"
        style="display:none"
    >
        <div class="bg-[#2C2C2A] rounded-2xl p-6 w-full max-w-sm pointer-events-auto shadow-2xl">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-white text-lg font-semibold">Welcome to FaultLine</h2>
                <button @click="open = false" class="text-[#666] hover:text-white transition-colors cursor-pointer text-lg leading-none">✕</button>
            </div>

            {{-- Tabs --}}
            <div class="flex gap-1 bg-[#1c1c1a] rounded-lg p-1 mb-5">
                <button
                    @click="tab = 'login'; error = ''"
                    class="flex-1 text-sm py-1.5 rounded-md transition-colors cursor-pointer"
                    :class="tab === 'login' ? 'bg-[#3A3A38] text-white' : 'text-[#888] hover:text-white'"
                >Sign in</button>
                <button
                    @click="tab = 'register'; error = ''"
                    class="flex-1 text-sm py-1.5 rounded-md transition-colors cursor-pointer"
                    :class="tab === 'register' ? 'bg-[#3A3A38] text-white' : 'text-[#888] hover:text-white'"
                >Register</button>
            </div>

            {{-- Error --}}
            <p x-show="error" x-text="error" class="text-red-400 text-xs mb-4"></p>

            {{-- Form --}}
            <form @submit.prevent="submit" class="flex flex-col gap-3">
                <template x-if="tab === 'register'">
                    <input
                        x-model="name"
                        type="text"
                        placeholder="Name"
                        class="bg-[#1c1c1a] text-white text-sm rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] placeholder-[#555]"
                    />
                </template>
                <input
                    x-model="email"
                    type="email"
                    placeholder="Email"
                    class="bg-[#1c1c1a] text-white text-sm rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] placeholder-[#555]"
                />
                <input
                    x-model="password"
                    type="password"
                    placeholder="Password"
                    class="bg-[#1c1c1a] text-white text-sm rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] placeholder-[#555]"
                />
                <button
                    type="submit"
                    :disabled="loading"
                    class="bg-[#3A3A38] hover:bg-[#4a4a48] text-white text-sm py-2 rounded-lg transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    x-text="loading ? 'Please wait…' : (tab === 'login' ? 'Sign in' : 'Create account')"
                ></button>
            </form>

            {{-- Divider --}}
            <div class="flex items-center gap-3 my-4">
                <div class="flex-1 h-px bg-[#3a3a38]"></div>
                <span class="text-[#555] text-xs">or continue with</span>
                <div class="flex-1 h-px bg-[#3a3a38]"></div>
            </div>

            {{-- Slack OAuth --}}
            <a
                href="{{ route('auth.slack') }}"
                class="flex items-center justify-center gap-2 w-full bg-[#1c1c1a] hover:bg-[#3A3A38] text-white text-sm py-2 rounded-lg transition-colors cursor-pointer"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/>
                </svg>
                Continue with Slack
            </a>
        </div>
    </div>
</div>
