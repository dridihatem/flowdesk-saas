/**
 * Lightweight canvas neural-network / data-flow background for Nova assistant cards.
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

export function initNovaNeuralBackground(canvas, options = {}) {
    const compact = Boolean(options.compact);
    const nodeCount = compact ? 16 : 26;
    const linkDistance = compact ? 88 : 118;
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
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const resize = () => {
        const rect = canvas.getBoundingClientRect();
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        width = Math.max(1, rect.width);
        height = Math.max(1, rect.height);
        canvas.width = Math.floor(width * dpr);
        canvas.height = Math.floor(height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        if (nodes.length === 0) {
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
        vignette.addColorStop(1, 'rgba(2, 6, 23, 0.55)');
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

                const alpha = (1 - dist / linkDistance) * (0.22 + energy * 0.12);
                ctx.strokeStyle = `rgba(56, 189, 248, ${alpha})`;
                ctx.lineWidth = 0.8;
                ctx.beginPath();
                ctx.moveTo(a.x, a.y);
                ctx.lineTo(b.x, b.y);
                ctx.stroke();

                if (!reducedMotion) {
                    const flow = (t * (0.8 + energy) + i * 0.3 + j * 0.17) % 1;
                    const px = a.x + (b.x - a.x) * flow;
                    const py = a.y + (b.y - a.y) * flow;
                    ctx.fillStyle = `rgba(129, 140, 248, ${alpha + 0.25})`;
                    ctx.beginPath();
                    ctx.arc(px, py, 1.1, 0, Math.PI * 2);
                    ctx.fill();
                }
            }
        }

        // Nodes
        for (const node of nodes) {
            const glow = 0.45 + Math.sin(node.pulse) * 0.25;
            const r = node.radius + glow * 0.6;

            const gradient = ctx.createRadialGradient(node.x, node.y, 0, node.x, node.y, r * 3);
            gradient.addColorStop(0, `rgba(125, 211, 252, ${0.35 + glow * 0.25})`);
            gradient.addColorStop(1, 'rgba(125, 211, 252, 0)');
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
