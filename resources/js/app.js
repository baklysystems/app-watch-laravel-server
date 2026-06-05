import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

// ============================================
// Appswatch UI Pro Max — JavaScript System
// ============================================

/**
 * Toast Notification System
 * Usage: Appswatch.toast('Saved successfully!', 'success')
 *        Appswatch.toast('Something went wrong', 'error')
 *        Appswatch.toast('Warning message', 'warning')
 *        Appswatch.toast('Heads up!', 'info')
 */
window.Appswatch = window.Appswatch || {};

const ToastSystem = {
    container: null,
    maxToasts: 5,
    duration: 4000,

    ensureContainer() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
        return this.container;
    },

    icons: {
        success: `<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        error: `<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        warning: `<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>`,
        info: `<svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
    },

    show(message, type = 'info', options = {}) {
        const duration = options.duration || this.duration;
        const container = this.ensureContainer();

        // Limit number of toasts
        const existingToasts = container.querySelectorAll('.toast');
        while (existingToasts.length >= this.maxToasts) {
            existingToasts[0].classList.add('toast-exit');
            setTimeout(() => existingToasts[0].remove(), 300);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            ${this.icons[type] || this.icons.info}
            <span class="text-sm font-medium flex-1">${message}</span>
            <button class="shrink-0 opacity-60 hover:opacity-100 transition-opacity" onclick="this.closest('.toast').remove()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        `;

        container.appendChild(toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.style.animation = 'none';
            toast.offsetHeight; // trigger reflow
            toast.style.animation = '';
        });

        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.classList.add('toast-exit');
                    setTimeout(() => {
                        if (toast.parentNode) toast.remove();
                    }, 300);
                }
            }, duration);
        }

        return toast;
    },
};

window.Appswatch.toast = (message, type, options) => ToastSystem.show(message, type, options);
window.Appswatch.toast.success = (message, options) => ToastSystem.show(message, 'success', options);
window.Appswatch.toast.error = (message, options) => ToastSystem.show(message, 'error', options);
window.Appswatch.toast.warning = (message, options) => ToastSystem.show(message, 'warning', options);
window.Appswatch.toast.info = (message, options) => ToastSystem.show(message, 'info', options);

/**
 * Dark mode persistence — saves preference to localStorage
 */
/**
 * Dark mode is managed by Alpine.js in navigation.blade.php.
 * This ensures consistent persistence with localStorage key 'darkMode'.
 * The system preference is honored on first visit via Alpine's x-init.
 */

/**
 * Intersection Observer — Animate elements as they scroll into view
 */
const animateOnScroll = () => {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.1, rootMargin: '0px 0px -20px 0px' }
    );

    document.querySelectorAll('[data-animate]').forEach((el) => {
        observer.observe(el);
    });
};

/**
 * Enhanced Chart.js — dark mode aware, responsive
 */
const enhancedCharts = (canvas, config) => {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.6)';

    const defaults = {
        type: config.type || 'line',
        data: {
            labels: config.labels || [],
            datasets: [{
                label: config.label || '',
                data: config.data || [],
                borderColor: config.color || '#6366f1',
                backgroundColor: `${config.color || '#6366f1'}20`,
                fill: config.fill !== false,
                tension: 0.35,
                pointRadius: config.pointRadius ?? 3,
                pointHoverRadius: 6,
                pointBackgroundColor: config.color || '#6366f1',
                borderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: { display: !!config.label },
                tooltip: {
                    backgroundColor: isDark ? '#1e293b' : '#ffffff',
                    titleColor: isDark ? '#f1f5f9' : '#0f172a',
                    bodyColor: isDark ? '#cbd5e1' : '#475569',
                    borderColor: isDark ? '#334155' : '#e2e8f0',
                    borderWidth: 1,
                    cornerRadius: 12,
                    padding: 10,
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: textColor, padding: 8, font: { size: 11 } },
                    grid: { color: gridColor, drawBorder: false },
                    border: { display: false },
                },
                x: {
                    ticks: { color: textColor, padding: 8, font: { size: 11 } },
                    grid: { display: false },
                    border: { display: false },
                },
            },
        },
    };

    return new Chart(canvas, defaults);
};

/**
 * Initialize auto charts from data-chart attributes
 */
const initAutoCharts = () => {
    document.querySelectorAll('[data-chart]').forEach((canvas) => {
        try {
            const config = JSON.parse(canvas.dataset.chart);
            enhancedCharts(canvas, config);
        } catch (e) {
            // Silently skip invalid configs
        }
    });
};

/**
 * Ripple effect on buttons with [data-ripple]
 */
const initRippleEffect = () => {
    document.querySelectorAll('[data-ripple]').forEach((btn) => {
        btn.addEventListener('click', function (e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.3);
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
            `;

            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);

            ripple.addEventListener('animationend', () => ripple.remove());
        });
    });
};

// Inject ripple keyframes
const rippleStyle = document.createElement('style');
rippleStyle.textContent = `
    @keyframes ripple {
        to { transform: scale(4); opacity: 0; }
    }
`;
document.head.appendChild(rippleStyle);

/**
 * Copy to clipboard utility
 */
window.Appswatch.copy = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        Appswatch.toast.success('Copied to clipboard!');
        return true;
    } catch {
        Appswatch.toast.error('Failed to copy');
        return false;
    }
};

/**
 * Format date relative or absolute
 */
window.Appswatch.formatDate = (dateString, format = 'relative') => {
    const date = new Date(dateString);
    if (format === 'relative') {
        const now = new Date();
        const diff = now - date;
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        if (seconds < 60) return 'just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;
        return date.toLocaleDateString();
    }
    return date.toLocaleString();
};

/**
 * Dark mode observer — reinitialize charts on theme change
 */
const observeDarkMode = () => {
    const observer = new MutationObserver(() => {
        // Reinitialize all Chart instances that have canvas refs
        document.querySelectorAll('[data-chart]').forEach((canvas) => {
            const instance = Chart.getChart(canvas);
            if (instance) {
                const isDark = document.documentElement.classList.contains('dark');
                const textColor = isDark ? '#94a3b8' : '#64748b';
                const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.6)';

                instance.options.scales.y.ticks.color = textColor;
                instance.options.scales.y.grid.color = gridColor;
                instance.options.scales.x.ticks.color = textColor;
                if (instance.options.plugins.tooltip) {
                    instance.options.plugins.tooltip.backgroundColor = isDark ? '#1e293b' : '#ffffff';
                    instance.options.plugins.tooltip.titleColor = isDark ? '#f1f5f9' : '#0f172a';
                    instance.options.plugins.tooltip.bodyColor = isDark ? '#cbd5e1' : '#475569';
                    instance.options.plugins.tooltip.borderColor = isDark ? '#334155' : '#e2e8f0';
                }
                instance.update();
            }
        });
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class'],
    });
};

// ============================================
// Init on DOM Ready
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    initAutoCharts();
    animateOnScroll();
    initRippleEffect();
    observeDarkMode();

    // Auto-dismiss flash messages
    document.querySelectorAll('[data-flash]')?.forEach((el) => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-10px)';
            setTimeout(() => el.remove(), 300);
        }, 5000);
    });

    // Tab system for any [data-tabs] elements
    document.querySelectorAll('[data-tabs]').forEach((tabsContainer) => {
        const tabs = tabsContainer.querySelectorAll('[data-tab]');
        const panels = tabsContainer.querySelectorAll('[data-panel]');

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;

                tabs.forEach((t) => t.classList.remove('border-brand-500', 'text-brand-600', 'dark:text-brand-400'));
                tab.classList.add('border-brand-500', 'text-brand-600', 'dark:text-brand-400');

                panels.forEach((p) => {
                    if (p.dataset.panel === target) {
                        p.classList.remove('hidden');
                        p.classList.add('animate-fade-in');
                    } else {
                        p.classList.add('hidden');
                        p.classList.remove('animate-fade-in');
                    }
                });
            });
        });
    });
});

// Service Vitals Test
window.testServiceVitals = async (projectId) => {
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const resp = await fetch(`/settings/project/${projectId}/test-service`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        });
        const data = await resp.json();

        let msg = '';
        for (const [name, status] of Object.entries(data.services)) {
            msg += `${status.ok ? '✅' : '❌'} ${name}: ${status.details.status}\n`;
        }
        alert(`Service Vitals — ${data.project}\n\n${msg}`);
    } catch (e) {
        Appswatch.toast.error('Failed to test services');
    }
};

// ============================================
// Dark Mode Persistence (W1-005)
// ============================================
(function () {
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('appswatch-theme');
    const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme === 'dark' || (!savedTheme && systemDark)) {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }

    // Attach toggle listener after DOM loads
    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.getElementById('dark-mode-toggle');
        if (toggle) {
            toggle.addEventListener('click', () => {
                if (html.classList.contains('dark')) {
                    html.classList.remove('dark');
                    localStorage.setItem('appswatch-theme', 'light');
                } else {
                    html.classList.add('dark');
                    localStorage.setItem('appswatch-theme', 'dark');
                }
            });
        }

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (!localStorage.getItem('appswatch-theme')) {
                if (e.matches) {
                    html.classList.add('dark');
                } else {
                    html.classList.remove('dark');
                }
            }
        });
    });
})();

// Sync Test — called from Settings page "Test Sync Now" button
window.testSync = async () => {
    const projectId = document.querySelector('input[name="project_id"]')?.value
        || new URLSearchParams(window.location.search).get('project_id')
        || (() => { const match = window.location.href.match(/project\/([a-f0-9-]+)/); return match ? match[1] : null; })();

    if (!projectId) {
        Appswatch.toast.error('Cannot determine project ID');
        return;
    }

    const btn = document.getElementById('test-sync-btn');
    const text = document.getElementById('test-sync-text');
    const spinner = document.getElementById('test-sync-spinner');
    const resultDiv = document.getElementById('sync-result');
    const errorDiv = document.getElementById('sync-error');

    // Show loading state
    if (btn) btn.disabled = true;
    if (text) text.classList.add('hidden');
    if (spinner) spinner.classList.remove('hidden');
    if (resultDiv) resultDiv.classList.add('hidden');
    if (errorDiv) errorDiv.classList.add('hidden');

    try {
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const resp = await fetch(`/settings/project/${projectId}/test-sync`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        });

        if (!resp.ok) {
            const err = await resp.json();
            throw new Error(err.message || `HTTP ${resp.status}`);
        }

        const data = await resp.json();
        renderSyncResult(data);
        Appswatch.toast.success('Sync test completed');
    } catch (e) {
        if (errorDiv) {
            errorDiv.textContent = 'Error: ' + e.message;
            errorDiv.classList.remove('hidden');
        }
        Appswatch.toast.error('Sync test failed: ' + e.message);
    } finally {
        if (btn) btn.disabled = false;
        if (text) text.classList.remove('hidden');
        if (spinner) spinner.classList.add('hidden');
    }
};

function renderSyncResult(data) {
    const resultDiv = document.getElementById('sync-result');
    if (!resultDiv) return;
    resultDiv.classList.remove('hidden');

    // Badge
    const badge = document.getElementById('sync-badge');
    const message = document.getElementById('sync-message');
    if (data.status === 'connected') {
        badge.textContent = '● CONNECTED';
        badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300';
        message.textContent = data.message || '';
    } else {
        badge.textContent = '● DISCONNECTED';
        badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-300';
        message.textContent = data.message || '';
    }

    // Project info
    const proj = data.project || {};
    document.getElementById('sync-project-name').textContent = proj.name || '—';
    document.getElementById('sync-last-seen').textContent = proj.last_seen_at
        ? new Date(proj.last_seen_at).toLocaleString()
        : 'Never';
    document.getElementById('sync-environment').textContent = proj.environment || '—';

    // Auth info
    const auth = data.auth || {};
    document.getElementById('sync-api-key').textContent = (auth.api_key_prefix || 'unknown') + '•••••••';
    document.getElementById('sync-rate-limit').textContent = auth.rate_limit + ' req/min';

    // Sync check
    const sync = data.sync_check || {};
    document.getElementById('sync-server-time').textContent = sync.server_time
        ? new Date(sync.server_time).toLocaleString()
        : '—';

    // Data Freshness
    const freshnessDiv = document.getElementById('sync-freshness');
    if (freshnessDiv) {
        const freshness = data.dalat_freshness || {};
        freshnessDiv.innerHTML = Object.entries(freshness).map(([key, val]) => {
            const label = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            const time = val ? new Date(val).toLocaleString() : 'No data';
            const color = val ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500';
            return `<div class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800/50">
                <span class="text-xs text-gray-500 dark:text-gray-400">${label}</span>
                <p class="text-xs font-mono ${color}">${time}</p>
            </div>`;
        }).join('');
    }

    // Data Volumes
    const volumesDiv = document.getElementById('sync-volumes');
    if (volumesDiv) {
        const volumes = data.data_volumes || {};
        volumesDiv.innerHTML = Object.entries(volumes).map(([key, val]) => {
            const label = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            return `<div class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800/50">
                <span class="text-xs text-gray-500 dark:text-gray-400">${label}</span>
                <p class="text-xs font-bold font-mono text-gray-900 dark:text-gray-100">${val.toLocaleString()}</p>
            </div>`;
        }).join('');
    }

    // Recommendation
    const recDiv = document.getElementById('sync-recommendation');
    if (recDiv && sync.recommendation) {
        const isGood = sync.recommendation.includes('good') || sync.recommendation.includes('Everything');
        recDiv.textContent = sync.recommendation;
        recDiv.className = 'p-4 rounded-xl border ' + (isGood
            ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-300'
            : 'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800/50 text-amber-700 dark:text-amber-300');
    }
}

Alpine.start();
