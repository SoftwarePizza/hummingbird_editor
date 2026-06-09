(function () {
    'use strict';

    function initExpandText() {
        var blocks = document.querySelectorAll('.ps-customtext');
        blocks.forEach(function (block) {
            if (block.dataset.hbeExpandInit) { return; }
            block.dataset.hbeExpandInit = '1';

            /* Keep ps-customtext content inside a Bootstrap-like container */
            var container = null;
            if (block.firstElementChild && block.firstElementChild.classList.contains('container')) {
                container = block.firstElementChild;
            } else {
                container = document.createElement('div');
                container.className = 'container';
                while (block.firstChild) {
                    container.appendChild(block.firstChild);
                }
                block.appendChild(container);
            }

            /* Wrap all existing children in a clippable div.
               Full text stays in DOM – bots / screen readers see everything. */
            var inner = document.createElement('div');
            inner.className = 'hbe-et-content';
            /* move all children */
            while (container.firstChild) {
                inner.appendChild(container.firstChild);
            }
            container.appendChild(inner);

            /* Measure line height to calculate 5-line threshold */
            var cs = window.getComputedStyle(inner);
            var lh = parseFloat(cs.lineHeight);
            if (!lh || lh <= 0) {
                lh = parseFloat(cs.fontSize) * 1.5;
            }
            var maxH = Math.round(lh * 5);

            /* Only activate if content actually overflows 5 lines */
            if (inner.scrollHeight <= maxH + Math.round(lh * 0.6)) {
                /* content fits — unwrap and leave as-is */
                while (inner.firstChild) { container.appendChild(inner.firstChild); }
                container.removeChild(inner);
                return;
            }

            /* Apply collapse */
            inner.style.maxHeight = maxH + 'px';
            inner.style.overflow  = 'hidden';
            inner.style.transition = 'max-height 0.35s ease';

            /* Build toggle link */
            var btn = document.createElement('a');
            btn.href = '#';
            btn.className = 'hbe-et-toggle';
            btn.setAttribute('aria-expanded', 'false');
            btn.setAttribute('aria-controls', '');
            btn.textContent = 'Rozwiń';
            container.appendChild(btn);

            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var isExpanded = btn.getAttribute('aria-expanded') === 'true';
                if (isExpanded) {
                    inner.style.maxHeight = maxH + 'px';
                    btn.textContent = 'Rozwiń';
                    btn.setAttribute('aria-expanded', 'false');
                    /* Scroll back to top of block if it moved out of view */
                    var rect = block.getBoundingClientRect();
                    if (rect.top < 0) {
                        block.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } else {
                    inner.style.maxHeight = inner.scrollHeight + 'px';
                    btn.textContent = 'Zwiń';
                    btn.setAttribute('aria-expanded', 'true');
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initExpandText);
    } else {
        initExpandText();
    }
})();
