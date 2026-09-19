/**
 * Lightweight canvas neural-network / data-flow background for Nova assistant cards.
 * Prefer cyan / teal data-flow hues; when fullscreen, denser field for Jarvis-style immersion.
 */

function createNodes(count, width, height) {
    const nodes = [];
    for (let i = 0; i < count; i++) {
        nodes.push({
            x: Math.random() * width,
            y: Math.random() * height,
            vx: (Math.random() - 0.5) * 0.35,
            vy: (Math.random() - 0.5) * 0.35,
            pulse: Math.random() * Math.PI * 2,
            radius: 1.2 + Math.random() * 1.4,
        });
    }

    return nodes;
}

function readAccentRgb() {
    try {
        const raw = getComputedStyle(document.documentElement).getPropertyValue('--flow-primary').trim();
        if (!raw) {
            return { r: 14, g: 165, b: 233 };
        }
        const hex = raw.replace('#', '');
        if (/^[0-9a-fA-F]{6}$/.test(hex)) {
            return {
                r: parseInt(hex.slice(0, 2), 16),
                g: parseInt(hex.slice(2, 4), 16),
                b: parseInt(hex.slice(4, 6), 16),
            };
        }
        const rgb = raw.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
        if (rgb) {
            return { r: Number(rgb[1]), g: Number(rgb[2]), b: Number(rgb[3]) };
        }
    } catch {
        // fall through
    }

    return { r: 14, g: 165, b: 233 };
}

export function initNovaNeuralBackground(canvas, options = {}) {
    const compact = Boolean(options.compact);
    const fullscreen = Boolean(options.fullscreen);
    const nodeCount = compact ? 16 : (fullscreen ? 48 : 26);
    const linkDistance = compact ? 88 : (fullscreen ? 140 : 118);
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return { destroy() {} };
    }

    let width = 0;
    let height = 0;
    let nodes = [];
    let frameId = null;
    let running = true;
    let energy = 1;
    const accent = readAccentRgb();
    // Soft cyan companion so company primary tints without purple AI slop
    const companion = { r: 56, g: 189, b: 248 };
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const resize = () => {
        const rect = canvas.getBoundingClientRect();
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        width = Math.max(1, rect.width);
        height = Math.max(1, rect.height);
        canvas.width = Math.floor(width * dpr);
        canvas.height = Math.floor(height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        if (nodes.length === 0 || fullscreen) {
            nodes = createNodes(nodeCount, width, height);
        }
    };

    const resizeObserver = typeof ResizeObserver !== 'undefined'
        ? new ResizeObserver(resize)
        : null;

    resize();
    resizeObserver?.observe(canvas.parentElement || canvas);

    const draw = (time) => {
        if (!running) {
            return;
        }

        const t = time * 0.001;
        const speed = reducedMotion ? 0.15 : 0.35 + energy * 0.25;

        ctx.clearRect(0, 0, width, height);

        // Soft vignette
        const vignette = ctx.createRadialGradient(
            width * 0.5,
            height * 0.45,
            width * 0.05,
            width * 0.5,
            height * 0.5,
            Math.max(width, height) * 0.75,
        );
        vignette.addColorStop(0, 'rgba(15, 23, 42, 0)');
        vignette.addColorStop(1, fullscreen ? 'rgba(2, 6, 23, 0.72)' : 'rgba(2, 6, 23, 0.55)');
        ctx.fillStyle = vignette;
        ctx.fillRect(0, 0, width, height);

        for (const node of nodes) {
            if (!reducedMotion) {
                node.x += node.vx * speed;
                node.y += node.vy * speed;
                if (node.x < 0 || node.x > width) {
                    node.vx *= -1;
                    node.x = Math.max(0, Math.min(width, node.x));
                }
                if (node.y < 0 || node.y > height) {
                    node.vy *= -1;
                    node.y = Math.max(0, Math.min(height, node.y));
                }
            }
            node.pulse += 0.02 * speed;
        }

        // Connections + flowing data pulses
        for (let i = 0; i < nodes.length; i++) {
            for (let j = i + 1; j < nodes.length; j++) {
                const a = nodes[i];
                const b = nodes[j];
                const dx = a.x - b.x;
                const dy = a.y - b.y;
                const dist = Math.hypot(dx, dy);
                if (dist > linkDistance) {
                    continue;
                }

                const alpha = (1 - dist / linkDistance) * (0.18 + energy * 0.14);
                ctx.strokeStyle = `rgba(${companion.r}, ${companion.g}, ${companion.b}, ${alpha})`;
                ctx.lineWidth = fullscreen ? 1 : 0.8;
                ctx.beginPath();
                ctx.moveTo(a.x, a.y);
                ctx.lineTo(b.x, b.y);
                ctx.stroke();

                if (!reducedMotion) {
                    const flow = (t * (0.8 + energy) + i * 0.3 + j * 0.17) % 1;
                    const px = a.x + (b.x - a.x) * flow;
                    const py = a.y + (b.y - a.y) * flow;
                    ctx.fillStyle = `rgba(${accent.r}, ${accent.g}, ${accent.b}, ${alpha + 0.28})`;
                    ctx.beginPath();
                    ctx.arc(px, py, fullscreen ? 1.35 : 1.1, 0, Math.PI * 2);
                    ctx.fill();
                }
            }
        }

        // Nodes
        for (const node of nodes) {
            const glow = 0.45 + Math.sin(node.pulse) * 0.25;
            const r = node.radius + glow * 0.6;

            const gradient = ctx.createRadialGradient(node.x, node.y, 0, node.x, node.y, r * 3);
            gradient.addColorStop(0, `rgba(${companion.r}, ${companion.g}, ${companion.b}, ${0.32 + glow * 0.25})`);
            gradient.addColorStop(1, `rgba(${companion.r}, ${companion.g}, ${companion.b}, 0)`);
            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc(node.x, node.y, r * 3, 0, Math.PI * 2);
            ctx.fill();

            ctx.fillStyle = `rgba(186, 230, 253, ${0.65 + glow * 0.3})`;
            ctx.beginPath();
            ctx.arc(node.x, node.y, r, 0, Math.PI * 2);
            ctx.fill();
        }

        frameId = requestAnimationFrame(draw);
    };

    frameId = requestAnimationFrame(draw);

    return {
        setEnergy(value) {
            energy = Math.max(0.6, Math.min(2, value));
        },
        destroy() {
            running = false;
            if (frameId) {
                cancelAnimationFrame(frameId);
            }
            resizeObserver?.disconnect();
        },
    };
}
