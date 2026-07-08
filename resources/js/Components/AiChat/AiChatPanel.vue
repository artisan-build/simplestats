<script setup>
import {nextTick, onMounted, ref, watch} from 'vue';
import {useAiChat} from '@/Composables/useAiChat.js';
import AiChatMessage from '@/Components/AiChat/AiChatMessage.vue';
import StarsIcon from 'bootstrap-icons/icons/stars.svg?component';
import PlusIcon from 'bootstrap-icons/icons/plus-lg.svg?component';
import CloseIcon from 'bootstrap-icons/icons/x-lg.svg?component';
import SendIcon from 'bootstrap-icons/icons/send.svg?component';
import StopIcon from 'bootstrap-icons/icons/stop-circle.svg?component';

const aiChat = useAiChat();

const input = ref('');
const messageList = ref(null);
const textarea = ref(null);

const exampleQuestions = [
    'How many registrations did I get last month?',
    'Which referrer brings the most revenue?',
    'How did my conversion rate develop this month?',
];

const scrollToBottom = () => {
    nextTick(() => {
        messageList.value?.scrollTo({top: messageList.value.scrollHeight});
    });
};

watch(() => aiChat.messages.value.map((message) => message.content.length).join(), scrollToBottom);
onMounted(scrollToBottom);

const resizeTextarea = () => {
    const el = textarea.value;

    if (el) {
        el.style.height = 'auto';
        el.style.height = `${Math.min(el.scrollHeight, 160)}px`;
    }
};

const send = (text = null) => {
    const message = text ?? input.value;

    if (!message.trim() || aiChat.isStreaming.value) {
        return;
    }

    input.value = '';
    nextTick(resizeTextarea);
    aiChat.sendMessage(message);
};

const onKeydown = (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        send();
    }
};
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between gap-2 border-b dark:border-dark-700 px-4 py-3">
            <div class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white">
                <StarsIcon class="size-5 text-indigo-500" />
                Ask AI
            </div>
            <div class="flex items-center gap-1">
                <button type="button" aria-label="New conversation" v-tooltip="'New conversation'" @click="aiChat.newConversation()" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded hover:bg-gray-100 dark:hover:bg-dark-700">
                    <PlusIcon class="size-5" />
                </button>
                <button type="button" aria-label="Close chat" @click="aiChat.setOpen(false)" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded hover:bg-gray-100 dark:hover:bg-dark-700">
                    <CloseIcon class="size-5" />
                </button>
            </div>
        </div>

        <div ref="messageList" class="flex-1 space-y-4 overflow-y-auto px-4 py-4">
            <div v-if="!aiChat.messages.value.length" class="flex h-full flex-col justify-center gap-3 text-center">
                <StarsIcon class="mx-auto size-8 text-indigo-400" />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Ask anything about your stats. For example:
                </p>
                <div class="flex flex-col gap-2">
                    <button
                        v-for="question in exampleQuestions"
                        :key="question"
                        type="button"
                        @click="send(question)"
                        class="rounded-lg border dark:border-dark-700 bg-gray-50 dark:bg-dark-900 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:border-indigo-300 hover:text-indigo-600 dark:hover:text-indigo-400"
                    >
                        {{ question }}
                    </button>
                </div>
            </div>

            <AiChatMessage v-for="(message, index) in aiChat.messages.value" :key="index" :message="message" />

            <div v-if="aiChat.toolActivity.value" class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500 animate-pulse">
                <StarsIcon class="size-4" />
                {{ aiChat.toolActivity.value }}
            </div>
            <div v-else-if="aiChat.isStreaming.value && !aiChat.messages.value.at(-1)?.content" class="flex items-center gap-2 text-sm text-gray-400 dark:text-gray-500 animate-pulse">
                <StarsIcon class="size-4" />
                Thinking…
            </div>

            <div v-if="aiChat.error.value" class="rounded-lg border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950 px-3 py-2 text-sm text-red-700 dark:text-red-300">
                {{ aiChat.error.value }}
                <button type="button" @click="aiChat.retry()" class="ml-1 font-semibold underline hover:no-underline">Retry</button>
            </div>
        </div>

        <div class="border-t dark:border-dark-700 p-3">
            <div class="flex items-center gap-2 rounded-lg border dark:border-dark-700 bg-gray-50 dark:bg-dark-900 px-3 py-2 focus-within:border-indigo-400 dark:focus-within:border-indigo-500">
                <textarea
                    ref="textarea"
                    v-model="input"
                    rows="1"
                    placeholder="Ask about your stats…"
                    class="max-h-40 flex-1 resize-none border-0 bg-transparent p-0 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0"
                    @input="resizeTextarea"
                    @keydown="onKeydown"
                ></textarea>
                <button
                    v-if="aiChat.isStreaming.value"
                    type="button"
                    aria-label="Stop response"
                    v-tooltip="'Stop'"
                    @click="aiChat.abort()"
                    class="p-1 text-gray-400 hover:text-red-500"
                >
                    <StopIcon class="size-5" />
                </button>
                <button
                    v-else
                    type="button"
                    aria-label="Send message"
                    :disabled="!input.trim()"
                    @click="send()"
                    class="p-1 text-indigo-500 hover:text-indigo-600 disabled:text-gray-300 dark:disabled:text-dark-600"
                >
                    <SendIcon class="size-5" />
                </button>
            </div>
        </div>
    </div>
</template>
