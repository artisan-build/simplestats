import { readFileSync } from 'fs';

export default function shikiTransformPlugin() {
    let shiki;

    return {
        name: 'vite-plugin-shiki-transform',
        async transform(code, id) {
            if (!id.endsWith('?shiki')) return;

            if (!shiki) {
                shiki = await import('shiki');
            }

            const filePath = id.replace('?shiki', '');
            const source = readFileSync(filePath, 'utf-8');

            const html = await shiki.codeToHtml(source.trim(), {
                lang: 'php',
                theme: 'github-dark',
            });

            return {
                code: `export default ${JSON.stringify(html)};`,
                map: null,
            };
        },
    };
}
