/**
 * CreatorAI - Utility Functions
 */

const Utils = {
    /**
     * Show toast notification
     */
    toast(message, type = 'info', duration = 4000) {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ',
        };

        toast.innerHTML = `
            <span style="font-size: 1.2em; flex-shrink: 0;">${icons[type] || icons.info}</span>
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    /**
     * Show loading state on button
     */
    btnLoading(btn, loading = true) {
        if (loading) {
            btn.dataset.originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Loading...';
        } else {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
        }
    },

    /**
     * Debounce function
     */
    debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    },

    /**
     * Format number with commas
     */
    formatNumber(num) {
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
        return num?.toString() || '0';
    },

    /**
     * Format date
     */
    formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    },

    /**
     * Format time ago
     */
    timeAgo(dateStr) {
        const now = new Date();
        const date = new Date(dateStr);
        const diff = Math.floor((now - date) / 1000);

        if (diff < 60) return 'Just now';
        if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
        if (diff < 2592000) return `${Math.floor(diff / 86400)}d ago`;
        return this.formatDate(dateStr);
    },

    /**
     * Copy text to clipboard
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.toast('Copied to clipboard!', 'success', 2000);
            return true;
        } catch {
            // Fallback
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            this.toast('Copied to clipboard!', 'success', 2000);
            return true;
        }
    },

    /**
     * Truncate text
     */
    truncate(text, length = 100) {
        if (!text || text.length <= length) return text || '';
        return text.substring(0, length) + '...';
    },

    /**
     * Escape HTML
     */
    escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str || ''));
        return div.innerHTML;
    },

    /**
     * Simple markdown to HTML (for AI responses)
     */
    markdownToHtml(text) {
        if (!text) return '';
        // Use marked.js if available, otherwise basic conversion
        if (typeof marked !== 'undefined') {
            return marked.parse(text);
        }
        // Basic fallback
        return text
            .replace(/### (.+)/g, '<h4>$1</h4>')
            .replace(/## (.+)/g, '<h3>$1</h3>')
            .replace(/# (.+)/g, '<h2>$1</h2>')
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            .replace(/\n- (.+)/g, '\n<li>$1</li>')
            .replace(/(<li>.+<\/li>)/gs, '<ul>$1</ul>')
            .replace(/\n\d+\. (.+)/g, '\n<li>$1</li>')
            .replace(/\n/g, '<br>');
    },

    /**
     * Show/hide element
     */
    show(el) {
        if (typeof el === 'string') el = document.querySelector(el);
        if (el) el.classList.remove('d-none');
    },

    hide(el) {
        if (typeof el === 'string') el = document.querySelector(el);
        if (el) el.classList.add('d-none');
    },

    toggle(el) {
        if (typeof el === 'string') el = document.querySelector(el);
        if (el) el.classList.toggle('d-none');
    },

    /**
     * Open modal
     */
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        const backdrop = document.getElementById(modalId + '-backdrop') || 
                         document.querySelector('.modal-backdrop');
        if (modal) modal.classList.add('active');
        if (backdrop) backdrop.classList.add('active');
        document.body.style.overflow = 'hidden';
    },

    /**
     * Close modal
     */
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        const backdrop = document.getElementById(modalId + '-backdrop') || 
                         document.querySelector('.modal-backdrop');
        if (modal) modal.classList.remove('active');
        if (backdrop) backdrop.classList.remove('active');
        document.body.style.overflow = '';
    },

    /**
     * Confirm dialog
     */
    async confirm(message, title = 'Confirm') {
        return new Promise((resolve) => {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop active';

            const modal = document.createElement('div');
            modal.className = 'modal active';
            modal.style.maxWidth = '420px';
            modal.innerHTML = `
                <div class="modal-header">
                    <h3 class="modal-title">${title}</h3>
                </div>
                <p style="color: var(--text-secondary); margin-bottom: var(--space-6);">${message}</p>
                <div style="display: flex; gap: var(--space-3); justify-content: flex-end;">
                    <button class="btn btn-secondary" id="confirm-cancel">Cancel</button>
                    <button class="btn btn-primary" id="confirm-ok">Confirm</button>
                </div>
            `;

            document.body.appendChild(backdrop);
            document.body.appendChild(modal);

            const cleanup = () => {
                backdrop.remove();
                modal.remove();
            };

            modal.querySelector('#confirm-cancel').onclick = () => { cleanup(); resolve(false); };
            modal.querySelector('#confirm-ok').onclick = () => { cleanup(); resolve(true); };
            backdrop.onclick = () => { cleanup(); resolve(false); };
        });
    },

    /**
     * Get initials from name
     */
    getInitials(name) {
        if (!name) return '?';
        return name.split(' ').map(w => w[0]).join('').toUpperCase().substring(0, 2);
    },

    /**
     * Smooth scroll to element
     */
    scrollTo(selector) {
        const el = document.querySelector(selector);
        if (el) el.scrollIntoView({ behavior: 'smooth' });
    },

    /**
     * Get query parameter
     */
    getParam(name) {
        return new URLSearchParams(window.location.search).get(name);
    },

    /**
     * Download text as file
     */
    downloadText(text, filename = 'content.txt') {
        const blob = new Blob([text], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    },

    /**
     * Animate elements on scroll
     */
    initScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.animate-in').forEach(el => observer.observe(el));
    },
};

// Add slideOutRight animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutRight {
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
