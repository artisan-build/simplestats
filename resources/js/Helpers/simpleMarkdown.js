// Minimal escape-first markdown renderer for AI chat messages. Parses line by
// line so mixed blocks (intro sentence directly followed by list items, as
// LLMs often emit without a blank line in between) still render correctly.
// The agent's instructions constrain output to simple markdown, so a full
// parser (marked + dompurify) is not needed yet.
const escapeHtml = (text) => text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

const renderLink = (match, label, url) => {
    // Injection hardening: tool results contain user-controlled strings
    // (referrer/campaign names, ...), so only same-origin URLs become
    // clickable links (SPA-navigated by AiChatMessage). Everything else is
    // rendered as plain text.
    const isInternal = typeof window !== 'undefined' && url.startsWith(`${window.location.origin}/`);

    if (!isInternal) {
        return label === url ? url : `${label} (${url})`;
    }

    return `<a href="${url}" class="text-indigo-600 dark:text-indigo-400 underline">${label}</a>`;
};

const renderInline = (text) => text
    .replace(/`([^`]+)`/g, '<code class="px-1 py-0.5 rounded bg-gray-100 dark:bg-dark-700 text-[0.85em]">$1</code>')
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    .replace(/\*([^*]+)\*/g, '<em>$1</em>')
    .replace(/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/g, renderLink);

const UL_CLASS = 'list-disc ps-5 space-y-1';
const OL_CLASS = 'list-decimal ps-5 space-y-1';
const TH_CLASS = 'px-2 py-1 font-semibold text-left border-b dark:border-dark-600 whitespace-nowrap';
const TD_CLASS = 'px-2 py-1 border-b border-gray-100 dark:border-dark-700 align-top';

const splitTableRow = (line) => line
    .replace(/^\|/, '')
    .replace(/\|$/, '')
    .split('|')
    .map((cell) => cell.trim());

const isSeparatorRow = (cells) => cells.every((cell) => /^:?-{2,}:?$/.test(cell));

const renderTable = (tableLines) => {
    const rows = tableLines.map(splitTableRow);
    const hasHeader = rows.length > 1 && isSeparatorRow(rows[1]);
    const header = hasHeader ? rows[0] : null;
    const body = hasHeader ? rows.slice(2) : rows;

    const head = header
        ? `<thead><tr>${header.map((cell) => `<th class="${TH_CLASS}">${renderInline(cell)}</th>`).join('')}</tr></thead>`
        : '';

    const bodyHtml = body
        .map((cells) => `<tr>${cells.map((cell) => `<td class="${TD_CLASS}">${renderInline(cell)}</td>`).join('')}</tr>`)
        .join('');

    return `<div class="overflow-x-auto"><table class="w-full border-collapse text-[13px]">${head}<tbody>${bodyHtml}</tbody></table></div>`;
};

export function renderSimpleMarkdown(markdown) {
    const lines = escapeHtml(markdown ?? '').trim().split('\n');
    const html = [];
    let listItems = [];
    let listTag = null;
    let paragraph = [];
    let tableLines = [];

    const flushParagraph = () => {
        if (paragraph.length) {
            html.push(`<p>${paragraph.map(renderInline).join('<br>')}</p>`);
            paragraph = [];
        }
    };

    const flushList = () => {
        if (listItems.length) {
            const cssClass = listTag === 'ol' ? OL_CLASS : UL_CLASS;
            html.push(`<${listTag} class="${cssClass}">${listItems.join('')}</${listTag}>`);
            listItems = [];
            listTag = null;
        }
    };

    const flushTable = () => {
        if (tableLines.length) {
            html.push(renderTable(tableLines));
            tableLines = [];
        }
    };

    for (const rawLine of lines) {
        const line = rawLine.trim();
        const unordered = line.match(/^[-*]\s+(.*)/);
        const ordered = line.match(/^\d+[.)]\s+(.*)/);
        const heading = line.match(/^#{1,6}\s+(.*)/);
        const tableRow = line.startsWith('|') && line.includes('|', 1);

        if (!line) {
            flushParagraph();
            flushList();
            flushTable();
            continue;
        }

        if (tableRow) {
            flushParagraph();
            flushList();
            tableLines.push(line);
            continue;
        }

        flushTable();

        if (unordered || ordered) {
            flushParagraph();

            const tag = unordered ? 'ul' : 'ol';

            if (listTag && listTag !== tag) {
                flushList();
            }

            listTag = tag;
            listItems.push(`<li>${renderInline((unordered ?? ordered)[1])}</li>`);
            continue;
        }

        flushList();

        if (heading) {
            flushParagraph();
            html.push(`<p class="font-semibold">${renderInline(heading[1])}</p>`);
            continue;
        }

        paragraph.push(line);
    }

    flushParagraph();
    flushList();
    flushTable();

    return html.join('');
}
