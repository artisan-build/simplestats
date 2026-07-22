<x-filament-panels::page>
    {{ $this->content }}

    {{-- Merge the history table's heading row and search toolbar into one line --}}
    <style>
        @media (min-width: 640px) {
            .fi-ta-header-ctn {
                display: flex;
                align-items: stretch;
            }

            .fi-ta-header-ctn .fi-ta-header {
                flex: 1 1 auto;
            }

            .fi-ta-header-ctn .fi-ta-header-toolbar {
                flex-shrink: 0;
                padding-inline-start: 0;
            }
        }
    </style>
</x-filament-panels::page>
