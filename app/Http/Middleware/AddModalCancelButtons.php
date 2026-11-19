<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddModalCancelButtons
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }

        $script = <<<'HTML'
<script>
(() => {
    const closeModal = (modal) => {
        modal.classList.remove('open');
        modal.style.display = 'none';
        modal.dispatchEvent(new CustomEvent('close', { bubbles: true }));
    };

    const addCancelButton = (form, modal) => {
        if (form.querySelector('[data-global-modal-cancel], [data-modal-cancel]')) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.globalModalCancel = 'true';
        button.textContent = 'Ghairi';
        button.style.cssText = 'margin-left:8px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;color:#334155;padding:10px 13px;font:inherit;font-weight:700;cursor:pointer';
        button.addEventListener('click', () => closeModal(modal));
        form.append(button);
    };

    const addCloseButton = (modal) => {
        const existing = modal.querySelector('.modal-close, .close, [data-global-modal-close]');
        if (existing) {
            // Some older modals use an icon font only. Keep a visible × fallback.
            if (!existing.textContent.trim()) existing.textContent = '×';
            existing.setAttribute('aria-label', 'Funga');
            existing.setAttribute('title', 'Funga');
            return;
        }

        const box = modal.querySelector('.modal-box, .box');
        if (!box) return;

        if (getComputedStyle(box).position === 'static') box.style.position = 'relative';
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.globalModalClose = 'true';
        button.textContent = '×';
        button.setAttribute('aria-label', 'Funga');
        button.setAttribute('title', 'Funga');
        button.style.cssText = 'position:absolute;top:12px;right:14px;border:0;background:transparent;color:#64748b;font-size:26px;line-height:1;cursor:pointer;z-index:2';
        button.addEventListener('click', () => closeModal(modal));
        box.prepend(button);
    };

    document.querySelectorAll('.modal-overlay, .modal, [x-on\:close\.stop]').forEach((modal) => {
        addCloseButton(modal);
        modal.querySelectorAll('form').forEach((form) => addCancelButton(form, modal));
    });
})();
</script>
HTML;

        $response->setContent(str_replace('</body>', $script.'</body>', $response->getContent()));

        return $response;
    }
}
