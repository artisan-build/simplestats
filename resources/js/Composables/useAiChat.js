import {reactive, ref} from 'vue';

// Module-scoped singleton so the StatsFilter toggle button and the AppLayout
// panel share the same state across Inertia navigations.
const isOpen = ref(false);
const conversationId = ref(null);
const conversations = ref([]);
const messages = ref([]);
const isStreaming = ref(false);
const toolActivity = ref(null);
const error = ref(null);
const contextProjectId = ref(null);
const panelWidth = ref(384);

let abortController = null;
let initialized = false;
let hydrated = false;
let conversationsLoaded = false;

const OPEN_KEY = 'ai-chat-open';
const CONVERSATION_KEY = 'ai-chat-conversation-id';
const WIDTH_KEY = 'ai-chat-width';

const PANEL_WIDTH_DEFAULT = 384;
const PANEL_WIDTH_MIN = 320;
const PANEL_WIDTH_MAX = 640;

const toolLabels = {
    'list-stats-capabilities': 'Looking up your projects…',
    'get-kpi-stats': 'Crunching your KPIs…',
    'get-grouped-breakdown': 'Breaking down your stats…',
    'get-recurring-revenue-stats': 'Calculating recurring revenue…',
    'get-retention-cohorts': 'Building retention cohorts…',
    'search-docs': 'Searching the docs…',
};

// localStorage is only touched lazily from onMounted hooks, never at module
// scope, because the app is also rendered via SSR.
const initFromStorage = () => {
    if (initialized || typeof window === 'undefined') {
        return;
    }

    initialized = true;
    isOpen.value = localStorage.getItem(OPEN_KEY) === '1';
    conversationId.value = localStorage.getItem(CONVERSATION_KEY) || null;
    panelWidth.value = clampPanelWidth(parseInt(localStorage.getItem(WIDTH_KEY), 10) || PANEL_WIDTH_DEFAULT);

    if (isOpen.value) {
        hydrateConversation();
    }
};

const persistConversationId = (id) => {
    conversationId.value = id;

    if (typeof window !== 'undefined') {
        id ? localStorage.setItem(CONVERSATION_KEY, id) : localStorage.removeItem(CONVERSATION_KEY);
    }
};

const setOpen = (open) => {
    isOpen.value = open;

    if (typeof window !== 'undefined') {
        localStorage.setItem(OPEN_KEY, open ? '1' : '0');
    }

    if (open) {
        hydrateConversation();
    }
};

const toggle = () => setOpen(!isOpen.value);

const clampPanelWidth = (width) => Math.min(PANEL_WIDTH_MAX, Math.max(PANEL_WIDTH_MIN, width));

const setPanelWidth = (width) => {
    panelWidth.value = clampPanelWidth(Math.round(width));

    if (typeof window !== 'undefined') {
        localStorage.setItem(WIDTH_KEY, panelWidth.value);
    }
};

const hydrateConversation = async () => {
    if (hydrated || !conversationId.value) {
        hydrated = true;

        return;
    }

    hydrated = true;

    try {
        const {data} = await axios.get(route('ai.conversations.show', conversationId.value));
        messages.value = data.messages.map(({role, content}) => ({role, content}));
    } catch {
        // Stale or foreign conversation id: start fresh.
        persistConversationId(null);
        messages.value = [];
    }
};

const newConversation = () => {
    abort();
    persistConversationId(null);
    messages.value = [];
    error.value = null;
};

const loadConversations = async () => {
    if (conversationsLoaded) {
        return;
    }

    conversationsLoaded = true;

    try {
        const {data} = await axios.get(route('ai.conversations.index'));
        conversations.value = data;
    } catch {
        conversationsLoaded = false;
    }
};

const switchConversation = async (id) => {
    if (id === conversationId.value) {
        return;
    }

    abort();
    persistConversationId(id);
    messages.value = [];
    error.value = null;
    hydrated = false;

    await hydrateConversation();
};

const abort = () => {
    abortController?.abort();
    abortController = null;
    isStreaming.value = false;
    toolActivity.value = null;
};

const readCookie = (name) => decodeURIComponent(
    document.cookie.match(new RegExp(`${name}=([^;]+)`))?.[1] ?? ''
);

const sendMessage = async (text) => {
    const message = text.trim();

    if (!message || isStreaming.value) {
        return;
    }

    error.value = null;
    isStreaming.value = true;
    messages.value.push({role: 'user', content: message});

    // reactive() wrapper, because we keep mutating this object after pushing:
    // mutations on the raw object would bypass Vue's reactivity entirely.
    const assistantMessage = reactive({role: 'assistant', content: ''});
    messages.value.push(assistantMessage);

    abortController = new AbortController();
    let done = false;

    try {
        const response = await fetch(route('ai.chat.store'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': readCookie('XSRF-TOKEN'),
            },
            body: JSON.stringify({
                message,
                conversation_id: conversationId.value,
                project_id: contextProjectId.value || null,
            }),
            signal: abortController.signal,
        });

        if (!response.ok) {
            const body = await response.json().catch(() => ({}));
            throw new Error(body.message || `Request failed (${response.status})`);
        }

        const newConversationId = response.headers.get('X-Conversation-Id');

        if (newConversationId) {
            persistConversationId(newConversationId);
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (true) {
            const {done: readerDone, value} = await reader.read();

            if (readerDone) {
                break;
            }

            buffer += decoder.decode(value, {stream: true});

            const chunks = buffer.split('\n\n');
            buffer = chunks.pop();

            for (const chunk of chunks) {
                const data = chunk.replace(/^data: /, '').trim();

                if (!data) {
                    continue;
                }

                if (data === '[DONE]') {
                    done = true;
                    break;
                }

                let event;

                try {
                    event = JSON.parse(data);
                } catch {
                    // A server-side exception mid-stream dumps Laravel's HTML
                    // error page into the open SSE stream.
                    throw new Error('The assistant ran into an error. Please try again.');
                }

                handleEvent(event, assistantMessage);
            }

            if (done) {
                break;
            }
        }

        if (!done && assistantMessage.content === '') {
            throw new Error('The response stream ended unexpectedly.');
        }
    } catch (e) {
        if (e.name !== 'AbortError') {
            error.value = e.message || 'Something went wrong. Please try again.';

            if (assistantMessage.content === '') {
                messages.value.pop();
            }
        }
    } finally {
        isStreaming.value = false;
        toolActivity.value = null;
        abortController = null;
        // Title or updated_at changed, refetch on next dropdown open.
        conversationsLoaded = false;
    }
};

const handleEvent = (event, assistantMessage) => {
    switch (event.type) {
        case 'text_start':
            // Text segments before and after a tool call arrive as separate
            // text blocks; without a separator they would concatenate
            // mid-sentence.
            if (assistantMessage.content !== '') {
                assistantMessage.content += '\n\n';
            }
            break;
        case 'text_delta':
            assistantMessage.content += event.delta;
            toolActivity.value = null;
            break;
        case 'tool_call':
            toolActivity.value = toolLabels[event.tool_name] ?? 'Analyzing your stats…';
            break;
        case 'tool_result':
            toolActivity.value = null;
            break;
        case 'error':
            throw new Error(event.message || 'The assistant ran into an error.');
    }
};

const retry = () => {
    const lastUserMessage = [...messages.value].reverse().find((message) => message.role === 'user');

    if (!lastUserMessage) {
        return;
    }

    messages.value = messages.value.slice(0, messages.value.lastIndexOf(lastUserMessage));

    sendMessage(lastUserMessage.content);
};

export function useAiChat() {
    return {
        isOpen,
        conversationId,
        conversations,
        messages,
        isStreaming,
        toolActivity,
        error,
        contextProjectId,
        panelWidth,
        setPanelWidth,
        initFromStorage,
        toggle,
        setOpen,
        sendMessage,
        abort,
        retry,
        newConversation,
        loadConversations,
        switchConversation,
        setContextProjectId: (id) => contextProjectId.value = id,
    };
}
