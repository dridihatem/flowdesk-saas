/**
 * Shared Nova agent HTTP client: primary orchestrator path with legacy chat fallback.
 */

/**
 * @param {object} opts
 * @param {string} opts.message
 * @param {string|null} [opts.conversationId]
 * @param {string|null} [opts.agentUrl]
 * @param {string|null} [opts.legacyChatUrl]
 * @param {boolean} [opts.useAgent]
 * @param {string|null} [opts.currentPage]
 * @param {object|null} [opts.currentEntity]
 * @param {object|null} [opts.confirmation]
 * @param {string} opts.csrf
 * @param {AbortSignal} [opts.signal]
 * @returns {Promise<{ok: boolean, status: number, data: object, via: 'agent'|'legacy'}>}
 */
export async function flowdeskNovaSendMessage(opts = {}) {
    const message = String(opts.message || '').trim();
    const csrf = opts.csrf || '';
    const useAgent = opts.useAgent !== false && Boolean(opts.agentUrl);
    const agentUrl = opts.agentUrl || null;
    const legacyChatUrl = opts.legacyChatUrl || opts.chatUrl || null;

    if (!message) {
        throw new Error('Empty message');
    }

    if (useAgent && agentUrl) {
        try {
            const agentBody = {
                message,
                conversation_id: opts.conversationId || null,
                current_page: opts.currentPage || null,
                current_entity: opts.currentEntity || null,
            };
            if (opts.confirmation) {
                agentBody.confirmation = opts.confirmation;
            }

            const agentRes = await fetch(agentUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(agentBody),
                signal: opts.signal,
            });

            const agentData = await agentRes.json().catch(() => ({}));

            // Soft-fallback only on transport / server failures — keep 4xx visible.
            if (agentRes.ok || (agentRes.status >= 400 && agentRes.status < 500)) {
                return {
                    ok: agentRes.ok,
                    status: agentRes.status,
                    data: normalizeAgentPayload(agentData),
                    via: 'agent',
                };
            }
        } catch (err) {
            if (err?.name === 'AbortError') {
                throw err;
            }
            // fall through to legacy
        }
    }

    if (!legacyChatUrl) {
        throw new Error('Nova chat endpoint unavailable');
    }

    const legacyRes = await fetch(legacyChatUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrf,
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            message,
            conversation_id: opts.conversationId || null,
        }),
        signal: opts.signal,
    });

    const legacyData = await legacyRes.json().catch(() => ({}));

    return {
        ok: legacyRes.ok,
        status: legacyRes.status,
        data: normalizeLegacyPayload(legacyData),
        via: 'legacy',
    };
}

function normalizeAgentPayload(data) {
    const reply = String(data.message || data.reply || '').trim();

    return {
        ...data,
        reply,
        conversation_id: data.conversation_id ?? null,
        run_id: data.run_id ?? null,
        activities: Array.isArray(data.activities) ? data.activities : [],
        clarification_options: data.clarification_options ?? null,
        confirmation: data.confirmation ?? null,
        project_preview: data.project_preview ?? null,
        assignment_preview: data.assignment_preview ?? null,
        agent_status: data.status ?? null,
    };
}

function normalizeLegacyPayload(data) {
    return {
        ...data,
        reply: String(data.reply || '').trim(),
        conversation_id: data.conversation_id ?? null,
        run_id: null,
        activities: [],
        clarification_options: null,
        confirmation: null,
        project_preview: null,
        assignment_preview: null,
        agent_status: null,
    };
}

/**
 * Resolve page context from Alpine cfg or shared window payload.
 */
export function flowdeskNovaResolvePageContext(cfg = {}) {
    const shared = (typeof window !== 'undefined' && window.flowdeskNovaPageContext) || {};
    const page = cfg.currentPage || cfg.current_page || shared.current_page || null;
    let entity = cfg.currentEntity || cfg.current_entity || shared.current_entity || null;

    if (entity && typeof entity === 'object' && entity.type && entity.id) {
        entity = { type: String(entity.type), id: String(entity.id) };
    } else {
        entity = null;
    }

    return { currentPage: page, currentEntity: entity };
}
