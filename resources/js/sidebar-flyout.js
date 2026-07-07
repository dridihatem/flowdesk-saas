/**
 * Floating flyout panels for the workspace sidebar (hover + click, RTL-aware).
 * Panel is teleported to <body> so backdrop-filter on the sidebar does not trap fixed positioning.
 */
export function registerSidebarFlyout(Alpine) {
    Alpine.data('flowdeskSidebarFlyout', () => ({
        open: false,
        panelStyle: '',
        closeTimer: null,
        _activeTrigger: null,

        init() {
            this._onViewportChange = () => {
                if (this.open && this._activeTrigger) {
                    this.positionPanel(this._activeTrigger);
                }
            };
            window.addEventListener('resize', this._onViewportChange, { passive: true });
            window.addEventListener('scroll', this._onViewportChange, { passive: true, capture: true });
        },

        destroy() {
            window.removeEventListener('resize', this._onViewportChange);
            window.removeEventListener('scroll', this._onViewportChange, true);
        },

        openFlyout(el) {
            clearTimeout(this.closeTimer);
            this._activeTrigger = el;
            this.positionPanel(el);
            this.open = true;
        },

        toggle(el) {
            if (this.open) {
                this.close();
            } else {
                this.openFlyout(el);
            }
        },

        positionPanel(el) {
            const rect = el.getBoundingClientRect();
            const gap = 10;
            const panelWidth = 248;
            const rtl = document.documentElement.dir === 'rtl';
            let left = rtl ? rect.left - panelWidth - gap : rect.right + gap;
            left = Math.max(8, Math.min(left, window.innerWidth - panelWidth - 8));

            const estimatedHeight = 280;
            let top = rect.top;
            top = Math.max(8, Math.min(top, window.innerHeight - estimatedHeight));

            this.panelStyle = `top:${top}px;left:${left}px;width:${panelWidth}px`;
        },

        scheduleClose() {
            this.closeTimer = setTimeout(() => {
                this.open = false;
                this._activeTrigger = null;
            }, 140);
        },

        cancelClose() {
            clearTimeout(this.closeTimer);
        },

        close() {
            clearTimeout(this.closeTimer);
            this.open = false;
            this._activeTrigger = null;
        },
    }));
}
