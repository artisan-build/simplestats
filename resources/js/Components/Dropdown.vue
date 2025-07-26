<script setup>
import {computed, onMounted, onUnmounted, ref} from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '48',
    },
    direction: {
        type: String,
        default: 'down',
    },
    contentClasses: {
        type: Array,
        default: () => ['py-1', 'bg-white dark:bg-dark-700'],
    },
});

let open = ref(false);

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

const closeOnClickOutside = (e) => {
    if (!e.target.closest('.relative')) {
        open.value = false;
    }
};

onMounted(() => {
    document.addEventListener('keydown', closeOnEscape);
    document.addEventListener('click', closeOnClickOutside);
});
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.removeEventListener('click', closeOnClickOutside);
});

const widthClass = computed(() => {
    return {
        '48': 'w-48',
    }[props.width.toString()];
});

const alignmentClasses = computed(() => {
    const origin = props.direction === 'up' ? 'bottom' : 'top';
    if (props.align === 'left') {
        return `ltr:origin-${origin}-left rtl:origin-${origin}-right start-0`;
    }

    if (props.align === 'right') {
        return `ltr:origin-${origin}-right rtl:origin-${origin}-left end-0`;
    }

    return `origin-${origin}`;
});
</script>

<template>
    <div class="relative text-left">
        <div @click="open = ! open">
            <slot name="trigger" />
        </div>

        <transition
            enter-active-class="transition ease-out duration-200"
            :enter-from-class="'transform opacity-0 ' + (props.direction === 'up' ? 'scale-95 -translate-y-1' : 'scale-95')"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            :leave-to-class="'transform opacity-0 ' + (props.direction === 'up' ? 'scale-95 -translate-y-1' : 'scale-95')"
        >
            <div
                v-show="open"
                class="absolute z-50 rounded-md shadow-lg font-normal"
                :class="[
                    widthClass,
                    alignmentClasses,
                    props.direction === 'up' ? 'mb-2 bottom-full' : 'mt-2'
                ]"
                style="display: none;"
                @click="open = false"
            >
                <div class="rounded-md ring-1 ring-black ring-opacity-5" :class="contentClasses">
                    <slot name="content" />
                </div>
            </div>
        </transition>
    </div>
</template>
