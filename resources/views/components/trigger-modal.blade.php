<div x-data="{
    open: false,
    type: 'domain',
    message: '',
    error: '',
    loading: false,
    types: [
        { value: 'domain',           label: 'Domain Exception' },
        { value: 'runtime',          label: 'Runtime Exception' },
        { value: 'logic',            label: 'Logic Exception' },
        { value: 'invalid_argument', label: 'Invalid Argument' },
        { value: 'bad_method',       label: 'Bad Method Call' },
    ],
    async trigger() {
        this.error = '';
        this.loading = true;
        try {
            const res = await fetch('{{ route('exceptions.trigger') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ type: this.type, message: this.message || null }),
            });
            if (res.ok) {
                this.open = false;
                this.message = '';
                $dispatch('refresh-exceptions');
                $dispatch('poll-latest-exception');
            } else {
                const data = await res.json();
                this.error = data.message ?? 'Something went wrong.';
            }
        } catch (e) {
            this.error = 'Request failed.';
        } finally {
            this.loading = false;
        }
    }
}">
    <button
        @click="open = true"
        class="w-full text-white text-sm bg-[#2C2C2A] p-2 rounded-xl hover:bg-[#3A3A38] cursor-pointer text-left"
    >
        Trigger exception
    </button>

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
        <div class="bg-[#2C2C2A] rounded-2xl p-6 w-full max-w-sm pointer-events-auto shadow-2xl flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h2 class="text-white text-lg font-semibold">Trigger exception</h2>
                <button @click="open = false" class="text-[#666] hover:text-white transition-colors cursor-pointer text-lg leading-none">✕</button>
            </div>

            <p x-show="error" x-text="error" class="text-red-400 text-xs"></p>

            <div class="flex flex-col gap-1">
                <label class="text-[#888] text-xs">Exception type</label>
                <select
                    x-model="type"
                    class="bg-[#1c1c1a] text-white text-sm rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] cursor-pointer"
                >
                    <template x-for="t in types" :key="t.value">
                        <option :value="t.value" x-text="t.label"></option>
                    </template>
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[#888] text-xs">Message <span class="text-[#555]">(optional)</span></label>
                <input
                    x-model="message"
                    type="text"
                    placeholder="Leave empty for default message…"
                    class="bg-[#1c1c1a] text-white text-sm rounded-lg px-3 py-2 outline-none focus:ring-1 focus:ring-[#3A3A38] placeholder-[#555]"
                />
            </div>

            <button
                @click="trigger()"
                :disabled="loading"
                class="bg-[#3A3A38] hover:bg-[#4a4a48] text-white text-sm py-2 rounded-lg transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                x-text="loading ? 'Triggering…' : 'Trigger'"
            ></button>
        </div>
    </div>
</div>
