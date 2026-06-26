<script setup>
import {computed, ref, watch} from 'vue'
import {Listbox, ListboxButton, ListboxOption, ListboxOptions} from '@headlessui/vue'
import ChevronUpDownIcon from 'bootstrap-icons/icons/chevron-expand.svg?component'
import CheckIcon from 'bootstrap-icons/icons/check2.svg?component'

const props = defineProps({
    modelValue: [Array, Object, String, Number, null],
    options: { type: [Array, Object], required: true },
    multiple: { type: Boolean, default: false },
    trackBy: { type: String, default: 'id' },
    labelBy: { type: String, default: 'name' },
    class: { type: String, default: '' },
    name: { type: String, default: '' },
    id: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'change'])

const open = ref(false)
const internalSelectedValues = ref([])
const initialSelectedValues = ref([])

const normalizedOptions = computed(() => {
    if (Array.isArray(props.options)) return props.options
    if (typeof props.options === 'object' && props.options !== null) {
        return Object.values(props.options)
    }
    return []
})

const selected = computed({
    get() {
        if (props.multiple) {
            if (!Array.isArray(props.modelValue)) return []
            return normalizedOptions.value.filter(option => props.modelValue.includes(option[props.trackBy]))
        } else {
            return (normalizedOptions.value.find(option => option[props.trackBy] == props.modelValue) ?? null)
        }
    },
    set(val) {
        if (props.multiple) {
            const ids = val.map(v => v[props.trackBy])
            internalSelectedValues.value = ids
            emit('update:modelValue', ids)
        } else {
            const id = val?.[props.trackBy] ?? null
            emit('update:modelValue', id)
            emit('change', { name: props.name ?? props.id, value: id })
        }
    }
})

watch(() => props.modelValue, val => {
    if (props.multiple && Array.isArray(val)) {
        internalSelectedValues.value = [...val]
    }
})

watch(open, (val) => {
    if (val && props.multiple) {
        initialSelectedValues.value = [...internalSelectedValues.value]
    }

    if (!val && props.multiple) {
        const hasChanged = JSON.stringify(initialSelectedValues.value) !== JSON.stringify(internalSelectedValues.value)

        if (hasChanged) {
            emit('change', { name: props.name ?? props.id, value: internalSelectedValues.value })
        }
    }
})
</script>

<template>
    <Listbox
        :modelValue="selected"
        @update:modelValue="selected = $event"
        :multiple="multiple"
        as="div"
        v-slot="{ open: isOpen }"
    >
        <div class="relative">
            <ListboxButton :class="['relative cursor-default rounded-md border bg-white dark:bg-dark-900 py-2 pl-3 pr-10 text-left shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 border-gray-300 dark:border-dark-700 text-gray-900 dark:text-dark-200', props.class]">
                <span class="block truncate">
                  <template v-if="multiple">
                    {{ selected.length ? selected.map(o => o[labelBy]).join(', ') : 'Select...' }}
                  </template>
                  <template v-else>
                    <slot name="selected" :option="selected">
                      {{ selected?.[labelBy] ?? 'Select...' }}
                    </slot>
                  </template>
                </span>
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                  <ChevronUpDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                </span>
            </ListboxButton>

            <ListboxOptions :class="(normalizedOptions.length > 20 ? 'max-h-60' : '')" class="absolute z-10 mt-1 w-full overflow-auto rounded-md bg-white dark:bg-dark-800 py-1 shadow-lg ring-1 ring-black/5 focus:outline-none">
                <ListboxOption
                    v-for="option in normalizedOptions"
                    :key="option[trackBy]"
                    :value="option"
                    v-slot="{ active, selected: isSelected }"
                    as="template"
                >
                    <li :class="[active ? 'bg-indigo-500 text-white' : 'text-gray-900 dark:text-dark-200', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                        <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate']">
                          <slot name="option" :option="option">
                            {{ option[labelBy] }}
                          </slot>
                        </span>
                        <span v-if="isSelected && multiple" :class="[active ? 'text-white' : 'text-indigo-500', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                          <CheckIcon class="h-5 w-5" aria-hidden="true" />
                        </span>
                    </li>
                </ListboxOption>
            </ListboxOptions>
        </div>

        <!-- track open state for dropdown close detection -->
        <template v-if="isOpen">
            <span v-show="false">{{ open = true }}</span>
        </template>
        <template v-else>
            <span v-show="false">{{ open = false }}</span>
        </template>
    </Listbox>
</template>

