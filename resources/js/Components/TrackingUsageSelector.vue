<script setup>

const props = defineProps({
    modelValue: Number,
    trackingUsages: Object,
    stacked: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

let bits = props.modelValue;

const selectUsageType = (bit) => {
    bits ^= bit;
    emit('update:modelValue', bits);
}

</script>

<template>
    <div class="grid gap-6 text-left" :class="(stacked) ? 'grid-cols-1' : 'grid-cols-1 md:grid-cols-2 xl:grid-cols-3'">
        <div v-for="(track, type) in trackingUsages"
             @click="selectUsageType(track.bit)"
             class="cursor-pointer select-none rounded-xl border-2 hover:border-indigo-200 dark:hover:border-gray-400 transition"
             :class="[(track.bit & bits) ? '!border-indigo-400' : '', (stacked) ? 'p-2' : 'p-4']"
        >
            <div class="flex items-center" :class="(stacked) ? 'gap-x-2' : 'gap-x-5'">
                <div>
                    <div v-if="type === 'visitors'">
                        <svg :class="(stacked) ? 'size-6' : 'size-10'" class="text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="m4.736 1.968-.892 3.269-.014.058C2.113 5.568 1 6.006 1 6.5 1 7.328 4.134 8 8 8s7-.672 7-1.5c0-.494-1.113-.932-2.83-1.205l-.014-.058-.892-3.27c-.146-.533-.698-.849-1.239-.734C9.411 1.363 8.62 1.5 8 1.5s-1.411-.136-2.025-.267c-.541-.115-1.093.2-1.239.735m.015 3.867a.25.25 0 0 1 .274-.224c.9.092 1.91.143 2.975.143a30 30 0 0 0 2.975-.143.25.25 0 0 1 .05.498c-.918.093-1.944.145-3.025.145s-2.107-.052-3.025-.145a.25.25 0 0 1-.224-.274M3.5 10h2a.5.5 0 0 1 .5.5v1a1.5 1.5 0 0 1-3 0v-1a.5.5 0 0 1 .5-.5m-1.5.5q.001-.264.085-.5H2a.5.5 0 0 1 0-1h3.5a1.5 1.5 0 0 1 1.488 1.312 3.5 3.5 0 0 1 2.024 0A1.5 1.5 0 0 1 10.5 9H14a.5.5 0 0 1 0 1h-.085q.084.236.085.5v1a2.5 2.5 0 0 1-5 0v-.14l-.21-.07a2.5 2.5 0 0 0-1.58 0l-.21.07v.14a2.5 2.5 0 0 1-5 0zm8.5-.5h2a.5.5 0 0 1 .5.5v1a1.5 1.5 0 0 1-3 0v-1a.5.5 0 0 1 .5-.5"/>
                        </svg>
                    </div>
                    <div v-else-if="type === 'users'">
                        <svg :class="(stacked) ? 'size-6' : 'size-10'" class="text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
                        </svg>
                    </div>
                    <div v-if="type === 'payments'">
                        <svg :class="(stacked) ? 'size-6' : 'size-10'" class="text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M5.5 9.511c.076.954.83 1.697 2.182 1.785V12h.6v-.709c1.4-.098 2.218-.846 2.218-1.932 0-.987-.626-1.496-1.745-1.76l-.473-.112V5.57c.6.068.982.396 1.074.85h1.052c-.076-.919-.864-1.638-2.126-1.716V4h-.6v.719c-1.195.117-2.01.836-2.01 1.853 0 .9.606 1.472 1.613 1.707l.397.098v2.034c-.615-.093-1.022-.43-1.114-.9zm2.177-2.166c-.59-.137-.91-.416-.91-.836 0-.47.345-.822.915-.925v1.76h-.005zm.692 1.193c.717.166 1.048.435 1.048.91 0 .542-.412.914-1.135.982V8.518z"/>
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                            <path d="M8 13.5a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11m0 .5A6 6 0 1 0 8 2a6 6 0 0 0 0 12"/>
                        </svg>
                    </div>
                </div>
                <div class="flex justify-between w-full">
                    <div :class="(stacked) ? 'text-base' : 'text-lg font-medium'">
                        {{ track.name }}
                    </div>
                    <div>
                        <input
                            type="radio"
                            :checked="track.bit & bits"
                            class="text-indigo-500 bg-gray-100 border-gray-300 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:checked:bg-indigo-500 focus:ring-2 dark:focus:ring-indigo-500 dark:ring-offset-gray-800"
                        />
                    </div>
                </div>
            </div>
            <div v-if=" !stacked" class="flex items-center mt-5">
                <div>
                    {{ track.descr }}
                </div>
            </div>
        </div>
    </div>
</template>
