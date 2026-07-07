import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';

const DEFAULT_LABELS = {
    elements: 'Elements',
    inspector: 'Inspector',
    inspectorHint: 'Select an element on the canvas to edit styles and settings.',
    tabStyle: 'Style',
    tabSettings: 'Settings',
    tabLayers: 'Layers',
    catSections: 'Sections',
    catBasic: 'Basic',
    catMedia: 'Media',
};

const CANVAS_HEIGHT = 620;

function extractLandingParts(html) {
    const raw = String(html || '').trim();
    if (!raw) {
        return { body: '', css: '' };
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(raw, 'text/html');
    const css = [...doc.querySelectorAll('style')]
        .map((node) => node.textContent || '')
        .join('\n')
        .trim();
    const body = doc.body?.innerHTML?.trim() || raw;

    return { body, css };
}

function wrapLandingExport(body, css) {
    const content = String(body || '').trim();
    const styles = String(css || '').trim();
    if (!content && !styles) {
        return '';
    }

    return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Landing page</title>
${styles ? `<style>\n${styles}\n</style>` : ''}
</head>
<body>
${content}
</body>
</html>`;
}

function buildStudioLayout(container, labels) {
    const L = { ...DEFAULT_LABELS, ...labels };

    container.classList.add('flow-landing-studio');
    container.innerHTML = `
<div class="flow-landing-studio__toolbar" data-lp-toolbar></div>
<div class="flow-landing-studio__workspace">
  <aside class="flow-landing-studio__sidebar flow-landing-studio__sidebar--left">
    <header class="flow-landing-studio__panel-head">
      <i class="fa-solid fa-shapes" aria-hidden="true"></i>
      <span>${L.elements}</span>
    </header>
    <div class="flow-landing-studio__panel-body" data-lp-blocks></div>
  </aside>
  <div class="flow-landing-studio__canvas-wrap" data-lp-canvas></div>
  <aside class="flow-landing-studio__sidebar flow-landing-studio__sidebar--right">
    <header class="flow-landing-studio__panel-head flow-landing-studio__panel-head--inspector">
      <span class="flow-landing-studio__inspector-title">
        <i class="fa-solid fa-sliders" aria-hidden="true"></i>
        <span>${L.inspector}</span>
      </span>
      <span class="flow-landing-studio__selection-label" data-lp-selection hidden></span>
    </header>
    <div class="flow-landing-studio__inspector-body">
      <div class="flow-landing-studio__inspector-empty" data-lp-inspector-empty>
        <div class="flow-landing-studio__inspector-empty-icon">
          <i class="fa-regular fa-hand-pointer" aria-hidden="true"></i>
        </div>
        <p>${L.inspectorHint}</p>
      </div>
      <div class="flow-landing-studio__inspector-content" data-lp-inspector-content>
        <nav class="flow-landing-studio__inspector-tabs" role="tablist">
          <button type="button" class="is-active" data-lp-inspector-tab="styles" role="tab">${L.tabStyle}</button>
          <button type="button" data-lp-inspector-tab="traits" role="tab">${L.tabSettings}</button>
          <button type="button" data-lp-inspector-tab="layers" role="tab">${L.tabLayers}</button>
        </nav>
        <div class="flow-landing-studio__inspector-pane is-active" data-lp-inspector-pane="styles" data-lp-styles></div>
        <div class="flow-landing-studio__inspector-pane" data-lp-inspector-pane="traits" data-lp-traits></div>
        <div class="flow-landing-studio__inspector-pane" data-lp-inspector-pane="layers" data-lp-layers></div>
      </div>
    </div>
  </aside>
</div>`;

    const refs = {
        canvas: container.querySelector('[data-lp-canvas]'),
        toolbar: container.querySelector('[data-lp-toolbar]'),
        blocks: container.querySelector('[data-lp-blocks]'),
        styles: container.querySelector('[data-lp-styles]'),
        traits: container.querySelector('[data-lp-traits]'),
        layers: container.querySelector('[data-lp-layers]'),
        inspectorEmpty: container.querySelector('[data-lp-inspector-empty]'),
        inspectorContent: container.querySelector('[data-lp-inspector-content]'),
        selectionLabel: container.querySelector('[data-lp-selection]'),
        labels: L,
    };

    container.querySelectorAll('[data-lp-inspector-tab]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const tab = btn.getAttribute('data-lp-inspector-tab');
            container.querySelectorAll('[data-lp-inspector-tab]').forEach((b) => {
                b.classList.toggle('is-active', b === btn);
            });
            container.querySelectorAll('[data-lp-inspector-pane]').forEach((pane) => {
                pane.classList.toggle('is-active', pane.getAttribute('data-lp-inspector-pane') === tab);
            });
        });
    });

    return refs;
}

function registerComponentTypes(editor) {
    const dc = editor.DomComponents;

    dc.addType('lp-link', {
        extend: 'link',
        model: {
            defaults: {
                traits: [
                    { type: 'text', name: 'href', label: 'URL' },
                    {
                        type: 'select',
                        name: 'target',
                        label: 'Target',
                        options: [
                            { id: '', name: 'Same window' },
                            { id: '_blank', name: 'New tab' },
                        ],
                    },
                    { type: 'text', name: 'title', label: 'Title' },
                ],
            },
        },
    });

    dc.addType('lp-image', {
        extend: 'image',
        model: {
            defaults: {
                traits: [
                    { type: 'text', name: 'src', label: 'Image URL' },
                    { type: 'text', name: 'alt', label: 'Alt text' },
                    { type: 'text', name: 'title', label: 'Title' },
                ],
            },
        },
    });
}

function blockIcon(svg) {
    return `<div class="flow-landing-block-icon">${svg}</div>`;
}

const ICONS = {
    text: blockIcon('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h10M4 18h14"/></svg>'),
    heading: blockIcon('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 4v16M18 4v16M6 12h12"/></svg>'),
    button: blockIcon('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="8" rx="2"/></svg>'),
    image: blockIcon('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10" r="1.5"/><path d="M21 16l-5-5-4 4-2-2-5 5"/></svg>'),
    video: blockIcon('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M10 9l6 3-6 3V9z"/></svg>'),
    html: blockIcon('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 4L4 8l4 4M16 20l4-4-4-4M14 4l-6 16"/></svg>'),
    divider: blockIcon('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/></svg>'),
    spacer: blockIcon('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v16M8 8h8M8 16h8"/></svg>'),
    section: blockIcon('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>'),
};

function registerLandingBlocks(editor, labels) {
    const bm = editor.BlockManager;
    const catBasic = labels.catBasic;
    const catMedia = labels.catMedia;
    const catSections = labels.catSections;

    bm.add('basic-h1', {
        label: 'Heading',
        category: catBasic,
        media: ICONS.heading,
        content: '<h2 style="margin:0 0 12px;font-size:2rem;font-weight:700;color:#0f172a">Heading</h2>',
    });
    bm.add('basic-h3', {
        label: 'Subheading',
        category: catBasic,
        media: ICONS.heading,
        content: '<h3 style="margin:0 0 8px;font-size:1.25rem;font-weight:600;color:#334155">Subheading</h3>',
    });
    bm.add('basic-text', {
        label: 'Text',
        category: catBasic,
        media: ICONS.text,
        content: '<p style="margin:0 0 16px;font-size:1rem;line-height:1.6;color:#475569">Write your paragraph here. Click to edit text directly on the canvas.</p>',
    });
    bm.add('basic-button', {
        label: 'Button',
        category: catBasic,
        media: ICONS.button,
        content: '<a data-gjs-type="lp-link" href="#cta" style="display:inline-block;background:#4f46e5;color:#fff;padding:12px 24px;border-radius:8px;font-weight:600;text-decoration:none;font-size:0.95rem">Call to action</a>',
    });
    bm.add('basic-link', {
        label: 'Text link',
        category: catBasic,
        media: ICONS.text,
        content: '<a data-gjs-type="lp-link" href="#" style="color:#4f46e5;font-weight:600;text-decoration:underline">Learn more</a>',
    });
    bm.add('basic-list', {
        label: 'Bullet list',
        category: catBasic,
        media: ICONS.text,
        content: `<ul style="margin:0 0 16px;padding-left:1.25rem;color:#475569;line-height:1.7">
<li>First item</li><li>Second item</li><li>Third item</li></ul>`,
    });
    bm.add('basic-divider', {
        label: 'Divider',
        category: catBasic,
        media: ICONS.divider,
        content: '<hr style="border:none;border-top:1px solid #e2e8f0;margin:32px 0"/>',
    });
    bm.add('basic-spacer', {
        label: 'Spacer',
        category: catBasic,
        media: ICONS.spacer,
        content: '<div style="height:48px" aria-hidden="true"></div>',
    });
    bm.add('basic-html', {
        label: 'HTML',
        category: catBasic,
        media: ICONS.html,
        content: '<div style="padding:16px;background:#f8fafc;border-radius:8px;border:1px dashed #cbd5e1"><strong>Custom HTML</strong><p style="margin:8px 0 0;color:#64748b">Double-click to edit this block.</p></div>',
    });

    bm.add('media-image', {
        label: 'Image',
        category: catMedia,
        media: ICONS.image,
        content: '<img data-gjs-type="lp-image" src="https://placehold.co/800x450/e2e8f0/64748b?text=Image" alt="Description" style="width:100%;max-width:100%;border-radius:12px;display:block"/>',
    });
    bm.add('media-video', {
        label: 'Video',
        category: catMedia,
        media: ICONS.video,
        content: `<div style="position:relative;width:100%;padding-bottom:56.25%;border-radius:12px;overflow:hidden;background:#0f172a">
<a data-gjs-type="lp-link" href="https://www.youtube.com/watch?v=dQw4w9WgXcQ" target="_blank" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;text-decoration:none;font-weight:600">▶ Watch video</a>
</div>`,
    });
    bm.add('media-figure', {
        label: 'Image + caption',
        category: catMedia,
        media: ICONS.image,
        content: `<figure style="margin:0">
<img data-gjs-type="lp-image" src="https://placehold.co/800x450/e2e8f0/64748b?text=Image" alt="Caption" style="width:100%;border-radius:12px;display:block"/>
<figcaption style="margin-top:8px;font-size:0.875rem;color:#64748b;text-align:center">Image caption</figcaption>
</figure>`,
    });

    bm.add('lp-hero', {
        label: 'Hero',
        category: catSections,
        media: ICONS.section,
        content: `<section style="padding:72px 24px;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;text-align:center">
  <div style="max-width:960px;margin:0 auto">
    <h1 style="font-size:2.5rem;margin:0 0 16px">Your headline</h1>
    <p style="font-size:1.125rem;opacity:.9;margin:0 0 24px">A short value proposition for your visitors.</p>
    <a data-gjs-type="lp-link" href="#cta" style="display:inline-block;background:#fff;color:#4f46e5;padding:12px 28px;border-radius:8px;font-weight:600;text-decoration:none">Get started</a>
  </div>
</section>`,
    });
    bm.add('lp-features', {
        label: 'Features',
        category: catSections,
        media: ICONS.section,
        content: `<section style="padding:64px 24px;background:#f8fafc">
  <div style="max-width:960px;margin:0 auto">
    <h2 style="text-align:center;margin:0 0 32px">Features</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px">
      <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.08)">
        <h3 style="margin:0 0 8px">Feature one</h3>
        <p style="margin:0;color:#64748b">Describe a key benefit here.</p>
      </div>
      <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.08)">
        <h3 style="margin:0 0 8px">Feature two</h3>
        <p style="margin:0;color:#64748b">Describe a key benefit here.</p>
      </div>
      <div style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.08)">
        <h3 style="margin:0 0 8px">Feature three</h3>
        <p style="margin:0;color:#64748b">Describe a key benefit here.</p>
      </div>
    </div>
  </div>
</section>`,
    });
    bm.add('lp-cta', {
        label: 'CTA',
        category: catSections,
        media: ICONS.section,
        content: `<section id="cta" style="padding:56px 24px;background:#0f172a;color:#fff;text-align:center">
  <div style="max-width:720px;margin:0 auto">
    <h2 style="margin:0 0 12px">Ready to start?</h2>
    <p style="margin:0 0 24px;opacity:.85">Join today and grow your business.</p>
    <a data-gjs-type="lp-link" href="#" style="display:inline-block;background:#6366f1;color:#fff;padding:12px 28px;border-radius:8px;font-weight:600;text-decoration:none">Contact us</a>
  </div>
</section>`,
    });
    bm.add('lp-table', {
        label: 'Pricing table',
        category: catSections,
        media: ICONS.section,
        content: `<section style="padding:48px 24px">
  <div style="max-width:960px;margin:0 auto;overflow-x:auto">
    <table style="width:100%;border-collapse:collapse">
      <thead>
        <tr>
          <th style="border:1px solid #cbd5e1;padding:12px;background:#f1f5f9;text-align:left">Plan</th>
          <th style="border:1px solid #cbd5e1;padding:12px;background:#f1f5f9;text-align:left">Price</th>
        </tr>
      </thead>
      <tbody>
        <tr><td style="border:1px solid #cbd5e1;padding:12px">Starter</td><td style="border:1px solid #cbd5e1;padding:12px">$29</td></tr>
        <tr><td style="border:1px solid #cbd5e1;padding:12px">Pro</td><td style="border:1px solid #cbd5e1;padding:12px">$79</td></tr>
      </tbody>
    </table>
  </div>
</section>`,
    });
    bm.add('lp-footer', {
        label: 'Footer',
        category: catSections,
        media: ICONS.section,
        content: `<footer style="padding:32px 24px;background:#1e293b;color:#cbd5e1;text-align:center">
  <p style="margin:0">© 2026 Your Company. All rights reserved.</p>
</footer>`,
    });
    bm.add('lp-section', {
        label: 'Blank section',
        category: catSections,
        media: ICONS.section,
        content: `<section style="padding:48px 24px;background:#ffffff">
  <div style="max-width:960px;margin:0 auto">
    <h2 style="margin:0 0 12px">Section title</h2>
    <p style="margin:0;color:#475569">Drag elements from the left panel into this section.</p>
  </div>
</section>`,
    });
    bm.add('lp-two-cols', {
        label: '2 columns',
        category: catSections,
        media: ICONS.section,
        content: `<section style="padding:48px 24px">
  <div style="max-width:960px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:center">
    <div>
      <h2 style="margin:0 0 12px">Left column</h2>
      <p style="margin:0;color:#475569">Text content or media on the right.</p>
    </div>
    <div>
      <img data-gjs-type="lp-image" src="https://placehold.co/480x320/e2e8f0/475569?text=Image" alt="Placeholder" style="width:100%;border-radius:12px" />
    </div>
  </div>
</section>`,
    });
}

function componentLabel(component) {
    if (!component) {
        return '';
    }
    const name = component.getName?.() || component.get('type') || 'Element';
    const tag = component.get('tagName') || '';
    return tag ? `${name} · &lt;${tag}&gt;` : name;
}

function wireInspector(editor, studio) {
    const { inspectorEmpty, inspectorContent, selectionLabel } = studio;

    const showInspector = (visible, component = null) => {
        inspectorEmpty.classList.toggle('is-hidden', visible);
        inspectorContent.classList.toggle('is-visible', visible);
        if (selectionLabel) {
            if (visible && component) {
                selectionLabel.innerHTML = componentLabel(component);
                selectionLabel.hidden = false;
            } else {
                selectionLabel.textContent = '';
                selectionLabel.hidden = true;
            }
        }
    };

    editor.on('component:selected', (component) => showInspector(true, component));
    editor.on('component:deselected', () => {
        if (!editor.getSelected()) {
            showInspector(false);
        }
    });
    editor.on('load', () => showInspector(false));
}

function registerDeviceCommands(editor) {
    ['Desktop', 'Tablet', 'Mobile'].forEach((device) => {
        const slug = device.toLowerCase();
        editor.Commands.add(`set-device-${slug}`, {
            run: (ed) => ed.setDevice(device),
        });
    });
}

function setupLandingEditor(editor, studio) {
    registerComponentTypes(editor);
    registerLandingBlocks(editor, studio.labels);
    registerDeviceCommands(editor);
    wireInspector(editor, studio);
}

/**
 * @param {HTMLElement} container
 * @param {{ onUpdate?: () => void, labels?: Record<string, string> }} options
 */
export function createLandingBuilder(container, { onUpdate, labels } = {}) {
    const studio = buildStudioLayout(container, labels);

    const editor = grapesjs.init({
        container: studio.canvas,
        height: `${CANVAS_HEIGHT}px`,
        width: 'auto',
        storageManager: false,
        noticeOnUnload: false,
        fromElement: false,
        showOffsets: false,
        showDevices: false,
        avoidInlineStyle: false,
        assetManager: {
            embedAsBase64: false,
            upload: false,
        },
        blockManager: {
            appendTo: studio.blocks,
        },
        layerManager: {
            appendTo: studio.layers,
        },
        traitManager: {
            appendTo: studio.traits,
        },
        styleManager: {
            appendTo: studio.styles,
        },
        deviceManager: {
            devices: [
                { id: 'desktop', name: 'Desktop', width: '' },
                { id: 'tablet', name: 'Tablet', width: '768px', widthMedia: '992px' },
                { id: 'mobile', name: 'Mobile', width: '375px', widthMedia: '480px' },
            ],
        },
        canvas: {
            styles: [],
            scripts: [],
        },
        panels: {
            defaults: [
                {
                    id: 'panel-devices',
                    el: studio.toolbar,
                    buttons: [
                        { id: 'device-desktop', command: 'set-device-desktop', label: '<i class="fa-solid fa-desktop"></i>', attributes: { title: 'Desktop' }, active: true },
                        { id: 'device-tablet', command: 'set-device-tablet', label: '<i class="fa-solid fa-tablet-screen-button"></i>', attributes: { title: 'Tablet' } },
                        { id: 'device-mobile', command: 'set-device-mobile', label: '<i class="fa-solid fa-mobile-screen"></i>', attributes: { title: 'Mobile' } },
                        { id: 'visibility', command: 'sw-visibility', label: '<i class="fa-regular fa-square"></i>', attributes: { title: 'Outlines' } },
                        { id: 'preview', command: 'preview', label: '<i class="fa-regular fa-eye"></i>', attributes: { title: 'Preview' } },
                        { id: 'fullscreen', command: 'fullscreen', label: '<i class="fa-solid fa-expand"></i>', attributes: { title: 'Fullscreen' } },
                        { id: 'undo', command: 'undo', label: '<i class="fa-solid fa-rotate-left"></i>', attributes: { title: 'Undo' } },
                        { id: 'redo', command: 'redo', label: '<i class="fa-solid fa-rotate-right"></i>', attributes: { title: 'Redo' } },
                    ],
                },
            ],
        },
        plugins: [(ed) => setupLandingEditor(ed, studio)],
    });

    editor.on('update', () => onUpdate?.());

    return editor;
}

export function loadLandingHtml(editor, html) {
    const { body, css } = extractLandingParts(html);
    const defaultBody = '<section style="padding:48px 24px"><div style="max-width:960px;margin:0 auto"><h2 style="margin:0 0 12px">Your landing page</h2><p style="margin:0;color:#475569">Drag elements from the left panel to build your page.</p></div></section>';

    const apply = () => {
        editor.setComponents(body || defaultBody);
        if (css) {
            editor.setStyle(css);
        }
    };

    if (editor.Canvas?.getFrameEl?.()) {
        apply();
        return;
    }

    editor.once('load', apply);
}

export function exportLandingHtml(editor) {
    return wrapLandingExport(editor.getHtml(), editor.getCss());
}

export function destroyLandingBuilder(editor) {
    if (editor && typeof editor.destroy === 'function') {
        editor.destroy();
    }
}

export { wrapLandingExport as wrapLandingDocument, extractLandingParts };
