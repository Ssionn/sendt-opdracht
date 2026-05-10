<div
    x-data="{
        open: false,
        notifyEmail: {{ auth()->user()->notify_email ? 'true' : 'false' }},
        notifySlack: {{ auth()->user()->notify_slack ? 'true' : 'false' }},
        notifySentry: {{ auth()->user()->notify_sentry ? 'true' : 'false' }},
        sentryDsn: '{{ auth()->user()->sentry_dsn ?? '' }}',
        sentryAuthToken: '',
        sentryOrgSlug: '{{ auth()->user()->sentry_org_slug ?? '' }}',
        sentryProjectSlug: '{{ auth()->user()->sentry_project_slug ?? '' }}',
        sentryRegion: '{{ auth()->user()->sentry_region ?? 'us' }}',
        saving: false,
        saved: false,
        async save() {
            this.saving = true;
            this.saved = false;
            const body = {
                notify_email: this.notifyEmail,
                notify_slack: this.notifySlack,
                notify_sentry: this.notifySentry,
                sentry_dsn: this.sentryDsn || null,
                sentry_org_slug: this.sentryOrgSlug || null,
                sentry_project_slug: this.sentryProjectSlug || null,
                sentry_region: this.sentryRegion || null,
            };
            if (this.sentryAuthToken) body.sentry_auth_token = this.sentryAuthToken;
            await fetch('{{ route('settings.update') }}', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(body),
            });
            this.saving = false;
            this.saved = true;
            if (this.sentryAuthToken) this.sentryAuthToken = '';
            setTimeout(() => this.saved = false, 2000);
        }
    }"
>
    {{-- Cog trigger --}}
    <button
        @click="open = true"
        class="text-[#666] hover:text-white transition-colors cursor-pointer shrink-0"
        title="Settings"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    </button>

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
        <div class="bg-[#2C2C2A] rounded-2xl p-6 w-full max-w-sm pointer-events-auto shadow-2xl flex flex-col gap-5">

            {{-- Header --}}
            <div class="flex items-center justify-between">
                <h2 class="text-white text-lg font-semibold">Settings</h2>
                <button @click="open = false" class="text-[#666] hover:text-white transition-colors cursor-pointer text-lg leading-none">✕</button>
            </div>

            {{-- Notifications section --}}
            <div class="flex flex-col gap-3">
                <span class="text-[#666] text-xs uppercase tracking-widest">Notifications</span>

                {{-- Email toggle --}}
                <label class="flex items-center justify-between cursor-pointer">
                    <div class="flex flex-col">
                        <span class="text-white text-sm">Email</span>
                        <span class="text-[#666] text-xs">{{ auth()->user()->email }}</span>
                    </div>
                    <button
                        type="button"
                        @click="notifyEmail = !notifyEmail"
                        class="relative w-10 h-5 rounded-full transition-colors cursor-pointer"
                        :class="notifyEmail ? 'bg-white' : 'bg-[#1c1c1a]'"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full transition-all"
                            :class="notifyEmail ? 'translate-x-5 bg-[#2C2C2A]' : 'translate-x-0 bg-[#555]'"
                        ></span>
                    </button>
                </label>

                {{-- Slack section --}}
                <div
                    class="flex flex-col gap-2"
                    x-data="{
                        botToken: '',
                        channel: '{{ auth()->user()->slack_channel ?? '' }}',
                        tokenError: '',
                        tokenSuccess: '',
                        testingToken: false,
                        async testToken() {
                            this.tokenError = '';
                            this.tokenSuccess = '';
                            this.testingToken = true;
                            try {
                                const res = await fetch('{{ route('settings.slack-token') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Accept': 'application/json',
                                    },
                                    body: JSON.stringify({ token: this.botToken, channel: this.channel || null }),
                                });
                                const data = await res.json();
                                if (data.success) {
                                    this.tokenSuccess = 'Connected to ' + data.team + '!';
                                    this.botToken = '';
                                } else {
                                    this.tokenError = data.message ?? 'Invalid token.';
                                }
                            } finally {
                                this.testingToken = false;
                            }
                        }
                    }"
                >
                    <label class="flex items-center justify-between cursor-pointer">
                        <div class="flex flex-col">
                            <span class="text-white text-sm">Slack</span>
                            <span class="text-[#666] text-xs">
                                @if(auth()->user()->provider === 'slack' && auth()->user()->slack_bot_token)
                                    Connected · <span class="text-[#666]">Bot token connected ✅</span>
                                @elseif(auth()->user()->provider === 'slack')
                                    Connected · <span class="text-[#666]">Bot token not connected ❌</span>
                                @else
                                    Not connected ❌
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(auth()->user()->provider !== 'slack')
                            <a
                                href="{{ route('auth.slack.connect') }}"
                                class="text-xs text-[#888] hover:text-white transition-colors underline"
                            >Connect</a>
                            @endif
                            <button
                                type="button"
                                @click="{{ auth()->user()->provider === 'slack' ? 'notifySlack = !notifySlack' : '' }}"
                                class="relative w-10 h-5 rounded-full transition-colors {{ auth()->user()->provider === 'slack' ? 'cursor-pointer' : 'cursor-not-allowed opacity-40' }}"
                                :class="notifySlack ? 'bg-white' : 'bg-[#1c1c1a]'"
                                {{ auth()->user()->provider === 'slack' ? '' : 'disabled' }}
                            >
                                <span
                                    class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full transition-all"
                                    :class="notifySlack ? 'translate-x-5 bg-[#2C2C2A]' : 'translate-x-0 bg-[#555]'"
                                ></span>
                            </button>
                        </div>
                    </label>

                    @if(auth()->user()->provider === 'slack')
                    <p x-show="tokenError" x-text="tokenError" class="text-red-400 text-xs"></p>
                    <p x-show="tokenSuccess" x-text="tokenSuccess" class="text-green-400 text-xs"></p>
                    <input
                        x-model="botToken"
                        type="password"
                        placeholder="xoxb- bot token"
                        class="bg-[#1c1c1a] text-white text-xs rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] placeholder-[#555] w-full"
                    />
                    <div class="flex gap-2">
                        <input
                            x-model="channel"
                            type="text"
                            placeholder="#channel-name"
                            class="flex-1 bg-[#1c1c1a] text-white text-xs rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] placeholder-[#555]"
                        />
                        <button
                            @click="testToken()"
                            :disabled="testingToken || !botToken"
                            class="text-xs px-3 py-2 rounded-lg bg-[#3A3A38] hover:bg-[#4a4a48] text-white transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed shrink-0"
                            x-text="testingToken ? 'Testing…' : 'Test & save'"
                        ></button>
                    </div>
                    @endif
                </div>

                <div class="flex flex-col gap-2">
                    <label class="flex items-center justify-between cursor-pointer">
                        <div class="flex flex-col">
                            <span class="text-white text-sm">Sentry</span>
                            <span class="text-[#666] text-xs">Report exceptions to Sentry</span>
                            @if (auth()->user()->sentry_dsn && !empty(auth()->user()->sentry_auth_token))
                                <span class="text-[#666] text-xs">Sentry connected ✅</span>
                            @else
                                <span class="text-[#666] text-xs">Sentry not connected ❌</span>
                            @endif
                        </div>
                        <button
                            type="button"
                            @click="notifySentry = !notifySentry"
                            class="relative w-10 h-5 rounded-full transition-colors cursor-pointer"
                            :class="notifySentry ? 'bg-white' : 'bg-[#1c1c1a]'"
                        >
                            <span
                                class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full transition-all"
                                :class="notifySentry ? 'translate-x-5 bg-[#2C2C2A]' : 'translate-x-0 bg-[#555]'"
                            ></span>
                        </button>
                    </label>
                    <p class="text-[#666] text-xs">To resolve exceptions via the Sentry API, provide your credentials below.</p>
                    <input
                        x-model="sentryDsn"
                        type="text"
                        placeholder="DSN (https://…@sentry.io/…)"
                        class="bg-[#1c1c1a] text-white text-xs rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] placeholder-[#555] w-full"
                    />
                    <input
                        x-model="sentryAuthToken"
                        type="password"
                        placeholder="Auth token (sntrys_…)"
                        class="bg-[#1c1c1a] text-white text-xs rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] placeholder-[#555] w-full"
                    />
                    <input
                        x-model="sentryOrgSlug"
                        type="text"
                        placeholder="Organisation slug"
                        class="bg-[#1c1c1a] text-white text-xs rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] placeholder-[#555] w-full"
                    />
                    <input
                        x-model="sentryProjectSlug"
                        type="text"
                        placeholder="Project slug"
                        class="bg-[#1c1c1a] text-white text-xs rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] placeholder-[#555] w-full"
                    />
                    <select
                        x-model="sentryRegion"
                        class="bg-[#1c1c1a] text-white text-xs rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] w-full"
                    >
                        <option value="us">Region: US (sentry.io)</option>
                        <option value="de">Region: EU (de.sentry.io)</option>
                    </select>
                </div>
            </div>

            @if(auth()->user()->provider === 'slack' && ! auth()->user()->password)
            <div
                x-data="{
                    password: '',
                    password_confirmation: '',
                    passwordError: '',
                    passwordSaved: false,
                    savingPassword: false,
                    async setPassword() {
                        this.passwordError = '';
                        this.savingPassword = true;
                        try {
                            const res = await fetch('{{ route('settings.password') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ password: this.password, password_confirmation: this.password_confirmation }),
                            });
                            const data = await res.json();
                            if (data.success) {
                                this.passwordSaved = true;
                                this.password = '';
                                this.password_confirmation = '';
                            } else {
                                this.passwordError = data.message ?? 'Something went wrong.';
                            }
                        } finally {
                            this.savingPassword = false;
                        }
                    }
                }"
            >
                <div class="flex flex-col gap-3">
                    <span class="text-[#666] text-xs uppercase tracking-widest">Set a password</span>
                    <p class="text-[#888] text-xs">You signed in with Slack. Set a password to also log in with email.</p>
                    <p x-show="passwordError" x-text="passwordError" class="text-red-400 text-xs"></p>
                    <p x-show="passwordSaved" class="text-green-400 text-xs">Password set successfully!</p>
                    <input x-model="password" type="password" placeholder="New password" class="bg-[#1c1c1a] text-white text-sm rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] placeholder-[#555]" />
                    <input x-model="password_confirmation" type="password" placeholder="Confirm password" class="bg-[#1c1c1a] text-white text-sm rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] placeholder-[#555]" />
                    <button @click="setPassword()" :disabled="savingPassword" class="bg-[#3A3A38] hover:bg-[#4a4a48] text-white text-sm py-2 rounded-lg transition-colors cursor-pointer disabled:opacity-50" x-text="savingPassword ? 'Saving…' : 'Set password'"></button>
                </div>
            </div>
            @endif

            {{-- Save button --}}
            <button
                @click="save()"
                :disabled="saving"
                class="bg-[#3A3A38] hover:bg-[#4a4a48] text-white text-sm py-2 rounded-lg transition-colors cursor-pointer disabled:opacity-50"
                x-text="saved ? 'Saved!' : (saving ? 'Saving…' : 'Save changes')"
            ></button>
        </div>
    </div>
</div>
