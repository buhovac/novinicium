/**
 * Renders <BlogContent> to HTML with Vue's server renderer, so Pest tests
 * can assert on real markup. Reads the node tree as JSON on stdin and
 * prints the HTML. Uses only installed packages (vite, @vitejs/plugin-vue, vue).
 *
 * Usage: echo '[...nodes]' | node tests/js/render-blog-content.mjs
 *
 * To render a single blog component instead (e.g. a view only reachable after
 * interaction), pass {"component": "ImageComparisonSlider", "props": {...}}.
 */
import { fileURLToPath } from 'node:url';
import vue from '@vitejs/plugin-vue';
import { createServer } from 'vite';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';

const root = fileURLToPath(new URL('../..', import.meta.url));

let input = '';

for await (const chunk of process.stdin) {
    input += chunk;
}

const server = await createServer({
    root,
    configFile: false,
    logLevel: 'error',
    plugins: [vue()],
    resolve: { alias: { '@': `${root}/resources/js` } },
    server: { middlewareMode: true, hmr: false, ws: false },
    appType: 'custom',
    optimizeDeps: { noDiscovery: true },
});

try {
    const payload = JSON.parse(input);
    const isSingleComponent = !Array.isArray(payload);
    const name = isSingleComponent ? payload.component : 'BlogContent';

    if (!/^[A-Za-z]+$/.test(name)) {
        throw new Error(`Invalid component name: ${name}`);
    }

    const { default: component } = await server.ssrLoadModule(
        `/resources/js/components/blog/${name}.vue`,
    );
    const props = isSingleComponent ? payload.props : { nodes: payload };
    const html = await renderToString(
        createSSRApp({ render: () => h(component, props) }),
    );
    process.stdout.write(html);
} finally {
    await server.close();
}
