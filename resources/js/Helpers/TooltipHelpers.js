// Shared markup for explanatory hover tooltips: width-capped body with an optional
// green title line. Components/InfoTooltip.vue renders it behind an info icon; for
// tooltips attached to an existing element bind it directly via
// v-tooltip="{content: infoTooltipContent({title, text}), html: true}".
export function infoTooltipContent({title = '', text = '', html = ''}) {
    const inner = html
        ? html
        : `<div>${title ? `<div class="font-bold text-green-300">${title}</div>` : ''}<div>${text}</div></div>`;

    return `<div class="flex flex-col space-y-4 max-w-64 p-2 text-sm">${inner}</div>`;
}
