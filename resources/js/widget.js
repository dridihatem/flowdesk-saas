/**
 * Flowdesk embeddable form widget (vanilla JS).
 * Mount: <div data-flowdesk-widget data-base-url="https://tenant.example.com" data-form-id="..." data-token="fd_live_..."></div>
 */
function embedPageContextHeaders() {
    try {
        const ctx = {
            page_url: window.location.href,
            path: window.location.pathname + window.location.search,
            referrer: document.referrer || null,
            title: (document.title || '').slice(0, 500) || null,
        };
        const json = JSON.stringify(ctx);
        const b64 = btoa(unescape(encodeURIComponent(json)));
        return { 'X-Flowdesk-Context': b64 };
    } catch (_) {
        return {};
    }
}

async function fetchSchema(baseUrl, formId, token) {
    const url = `${baseUrl.replace(/\/$/, '')}/api/v1/embed/forms/${formId}`;
    const res = await fetch(url, {
        headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
            ...embedPageContextHeaders(),
        },
    });
    if (!res.ok) {
        const err = await res.json().catch(() => ({}));
        throw new Error(err.message || `HTTP ${res.status}`);
    }
    return res.json();
}

async function submitForm(baseUrl, formId, token, payload) {
    const url = `${baseUrl.replace(/\/$/, '')}/api/v1/embed/forms/${formId}/submissions`;
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...embedPageContextHeaders(),
        },
        body: JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(data.message || `HTTP ${res.status}`);
    }
    return data;
}

function fieldInput(field, isDark) {
    const label = document.createElement('label');
    label.style.display = 'flex';
    label.style.flexDirection = 'column';
    label.style.gap = '0.35rem';
    label.style.fontSize = '0.875rem';

    const span = document.createElement('span');
    span.textContent = field.name + (field.required ? ' *' : '');
    span.style.color = isDark ? '#cbd5e1' : '#475569';
    label.appendChild(span);

    let input;
    if (field.type === 'textarea') {
        input = document.createElement('textarea');
        input.rows = 4;
    } else {
        input = document.createElement('input');
        input.type = field.type === 'number' ? 'number' : field.type === 'email' ? 'email' : 'text';
    }
    input.name = field.name;
    input.required = !!field.required;
    input.style.borderRadius = '0.5rem';
    input.style.border = `1px solid ${isDark ? 'rgba(148,163,184,0.35)' : 'rgba(148,163,184,0.8)'}`;
    input.style.padding = '0.5rem 0.65rem';
    input.style.background = isDark ? 'rgba(15,23,42,0.6)' : '#fff';
    input.style.color = isDark ? '#f8fafc' : '#0f172a';

    label.appendChild(input);
    return label;
}

function mount(root) {
    const baseUrl = root.dataset.baseUrl;
    const formId = root.dataset.formId;
    const token = root.dataset.token;
    if (!baseUrl || !formId || !token) {
        root.textContent = 'Flowdesk widget: missing data-base-url, data-form-id, or data-token.';
        return;
    }

    root.innerHTML = '';

    fetchSchema(baseUrl, formId, token)
        .then((schema) => {
            const widget = schema.meta?.widget || {};
            const primary = widget.primary || '#4f46e5';
            const theme = widget.theme || 'light';
            const isDark = theme === 'dark';
            const fields = schema.fields || [];
            const layout = schema.layout || 'simple';

            const title = document.createElement('h3');
            title.textContent = schema.form?.name || 'Form';
            title.style.margin = '0 0 0.75rem';
            title.style.fontSize = '1.125rem';
            title.style.fontWeight = '700';
            title.style.color = isDark ? '#f8fafc' : '#0f172a';
            root.appendChild(title);

            const card = document.createElement('div');
            card.style.borderRadius = '1rem';
            card.style.padding = '1.25rem';
            card.style.border = `1px solid ${isDark ? 'rgba(255,255,255,0.12)' : 'rgba(15,23,42,0.12)'}`;
            card.style.background = isDark ? 'rgba(15,23,42,0.92)' : '#ffffff';
            card.style.boxShadow = '0 10px 40px rgba(15,23,42,0.08)';

            const formEl = document.createElement('form');
            formEl.style.display = 'flex';
            formEl.style.flexDirection = 'column';
            formEl.style.gap = '0.75rem';

            if (layout === 'wizard') {
                const byStep = new Map();
                fields.forEach((f) => {
                    const s = f.step ?? 0;
                    if (!byStep.has(s)) {
                        byStep.set(s, []);
                    }
                    byStep.get(s).push(f);
                });
                const steps = [...byStep.keys()].sort((a, b) => a - b);
                steps.forEach((stepNum) => {
                    const fs = document.createElement('fieldset');
                    fs.style.border = `1px solid ${isDark ? 'rgba(148,163,184,0.25)' : 'rgba(148,163,184,0.5)'}`;
                    fs.style.borderRadius = '0.75rem';
                    fs.style.padding = '0.75rem';
                    const leg = document.createElement('legend');
                    leg.textContent = `${String.fromCharCode(65 + stepNum)} — Step ${stepNum + 1}`;
                    leg.style.padding = '0 0.25rem';
                    leg.style.color = isDark ? '#94a3b8' : '#64748b';
                    fs.appendChild(leg);
                    const inner = document.createElement('div');
                    inner.style.display = 'flex';
                    inner.style.flexDirection = 'column';
                    inner.style.gap = '0.75rem';
                    byStep.get(stepNum).forEach((f) => inner.appendChild(fieldInput(f, isDark)));
                    fs.appendChild(inner);
                    formEl.appendChild(fs);
                });
            } else {
                fields.forEach((f) => formEl.appendChild(fieldInput(f, isDark)));
            }

            const submit = document.createElement('button');
            submit.type = 'submit';
            submit.textContent = 'Submit';
            submit.style.padding = '0.6rem 1rem';
            submit.style.borderRadius = '0.5rem';
            submit.style.border = 'none';
            submit.style.background = primary;
            submit.style.color = '#fff';
            submit.style.fontWeight = '600';
            submit.style.cursor = 'pointer';

            card.appendChild(formEl);
            card.appendChild(submit);
            root.appendChild(card);

            formEl.addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(formEl);
                const payload = {};
                fields.forEach((f) => {
                    payload[f.name] = fd.get(f.name)?.toString() ?? '';
                });
                try {
                    await submitForm(baseUrl, formId, token, payload);
                    root.innerHTML = '';
                    const ok = document.createElement('p');
                    ok.textContent = 'Thank you — your response was recorded.';
                    ok.style.color = isDark ? '#bbf7d0' : '#166534';
                    root.appendChild(ok);
                } catch (err) {
                    const er = document.createElement('p');
                    er.textContent = err.message || 'Submission failed.';
                    er.style.color = '#b91c1c';
                    root.appendChild(er);
                }
            });
        })
        .catch((e) => {
            root.textContent = e.message || 'Could not load form.';
        });
}

document.querySelectorAll('[data-flowdesk-widget]').forEach(mount);
