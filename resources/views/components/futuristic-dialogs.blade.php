<style>
    :root {
        --wf-dialog-navy: #18375d;
        --wf-dialog-blue: #68a7ee;
        --wf-dialog-ink: #15314f;
        --wf-dialog-muted: #68809a;
    }

    .wf-dialog-backdrop[hidden] { display: none !important; }
    .wf-dialog-backdrop {
        position: fixed;
        inset: 0;
        z-index: 2147483600;
        display: grid;
        place-items: center;
        padding: 18px;
        background: rgba(7, 22, 39, .64);
        -webkit-backdrop-filter: blur(9px);
        backdrop-filter: blur(9px);
        animation: wf-dialog-fade .18s ease-out;
    }
    .wf-dialog-panel {
        position: relative;
        width: min(100%, 470px);
        overflow: hidden;
        border: 1px solid rgba(104, 167, 238, .48);
        border-radius: 24px;
        color: var(--wf-dialog-ink);
        background:
            radial-gradient(circle at 92% 5%, rgba(104, 167, 238, .22), transparent 34%),
            linear-gradient(155deg, #fff 0%, #f4f9ff 100%);
        box-shadow:
            0 28px 90px rgba(4, 20, 37, .38),
            0 0 0 1px rgba(255, 255, 255, .7) inset;
        animation: wf-dialog-rise .22s cubic-bezier(.2, .8, .2, 1);
    }
    .wf-dialog-panel::before {
        content: "";
        position: absolute;
        inset: 0 0 auto;
        height: 4px;
        background: linear-gradient(90deg, var(--wf-dialog-navy), var(--wf-dialog-blue), #8fd3ff);
    }
    .wf-dialog-body { padding: 25px 25px 19px; }
    .wf-dialog-heading {
        display: grid;
        grid-template-columns: 50px minmax(0, 1fr);
        gap: 14px;
        align-items: center;
    }
    .wf-dialog-icon {
        width: 50px;
        height: 50px;
        display: grid;
        place-items: center;
        border: 1px solid rgba(104, 167, 238, .45);
        border-radius: 16px;
        color: #fff;
        background: linear-gradient(145deg, var(--wf-dialog-navy), var(--wf-dialog-blue));
        box-shadow: 0 10px 25px rgba(24, 55, 93, .22);
        font-size: 23px;
        font-weight: 900;
    }
    .wf-dialog-kicker {
        margin-bottom: 3px;
        color: #4f86c3;
        font-size: 10px;
        font-weight: 900;
        letter-spacing: .13em;
        text-transform: uppercase;
    }
    .wf-dialog-title {
        margin: 0;
        color: var(--wf-dialog-navy);
        font-size: clamp(19px, 4vw, 24px);
        font-weight: 850;
        line-height: 1.18;
    }
    .wf-dialog-message {
        margin: 17px 0 0;
        color: #4c6782;
        font-size: 14px;
        line-height: 1.58;
        white-space: pre-line;
    }
    .wf-dialog-copy-field {
        width: 100%;
        margin-top: 16px;
        border: 1px solid #bdd7f1;
        border-radius: 13px;
        padding: 12px 13px;
        color: var(--wf-dialog-navy);
        background: #fff;
        box-shadow: 0 7px 20px rgba(24, 55, 93, .07) inset;
        font: 700 13px/1.4 ui-monospace, SFMono-Regular, Consolas, monospace;
    }
    .wf-dialog-copy-field:focus {
        border-color: var(--wf-dialog-blue);
        outline: 3px solid rgba(104, 167, 238, .22);
    }
    .wf-dialog-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        padding: 16px 25px 22px;
        border-top: 1px solid rgba(104, 167, 238, .18);
        background: rgba(235, 245, 255, .62);
    }
    .wf-dialog-btn {
        min-width: 104px;
        min-height: 43px;
        border: 1px solid transparent;
        border-radius: 13px;
        padding: 9px 17px;
        font-size: 13px;
        font-weight: 850;
        cursor: pointer;
        transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
    }
    .wf-dialog-btn:hover { transform: translateY(-1px); }
    .wf-dialog-btn:focus-visible { outline: 3px solid rgba(104, 167, 238, .32); outline-offset: 2px; }
    .wf-dialog-btn.secondary {
        border-color: #c4d8ec;
        color: var(--wf-dialog-navy);
        background: #fff;
    }
    .wf-dialog-btn.primary {
        color: #fff;
        background: linear-gradient(135deg, var(--wf-dialog-navy), #326ba7 56%, var(--wf-dialog-blue));
        box-shadow: 0 10px 22px rgba(24, 55, 93, .19);
    }
    .wf-dialog-btn.danger {
        color: #fff;
        background: linear-gradient(135deg, #8f2940, #d94c60);
        box-shadow: 0 10px 22px rgba(143, 41, 64, .18);
    }

    .alert.alert-success,
    .alert.alert-danger,
    .alert.alert-warning,
    .alert.alert-info {
        position: relative;
        border: 1px solid rgba(104, 167, 238, .34) !important;
        border-left: 4px solid var(--wf-dialog-blue) !important;
        border-radius: 15px !important;
        color: var(--wf-dialog-navy) !important;
        background: linear-gradient(135deg, #fff, #eef7ff) !important;
        box-shadow: 0 12px 28px rgba(24, 55, 93, .11) !important;
        font-weight: 700;
    }
    .alert.alert-success { border-left-color: #20a56b !important; }
    .alert.alert-danger { border-left-color: #d94c60 !important; }
    .alert.alert-warning { border-left-color: #e8a526 !important; }

    @keyframes wf-dialog-fade { from { opacity: 0; } }
    @keyframes wf-dialog-rise {
        from { opacity: 0; transform: translateY(14px) scale(.975); }
    }
    @media (max-width: 520px) {
        .wf-dialog-backdrop { align-items: end; padding: 10px; }
        .wf-dialog-panel { border-radius: 21px; }
        .wf-dialog-body { padding: 22px 19px 17px; }
        .wf-dialog-actions { padding: 14px 19px 19px; }
        .wf-dialog-btn { flex: 1; min-width: 0; }
    }
    @media (prefers-reduced-motion: reduce) {
        .wf-dialog-backdrop,
        .wf-dialog-panel { animation: none; }
        .wf-dialog-btn { transition: none; }
    }
</style>

<div class="wf-dialog-backdrop" id="wfDialogBackdrop" hidden>
    <section class="wf-dialog-panel" role="dialog" aria-modal="true" aria-labelledby="wfDialogTitle" aria-describedby="wfDialogMessage">
        <div class="wf-dialog-body">
            <div class="wf-dialog-heading">
                <div class="wf-dialog-icon" id="wfDialogIcon" aria-hidden="true">i</div>
                <div>
                    <div class="wf-dialog-kicker" id="wfDialogKicker">System Notice</div>
                    <h2 class="wf-dialog-title" id="wfDialogTitle">Notice</h2>
                </div>
            </div>
            <p class="wf-dialog-message" id="wfDialogMessage"></p>
            <input class="wf-dialog-copy-field" id="wfDialogCopyField" type="text" readonly hidden>
        </div>
        <div class="wf-dialog-actions">
            <button class="wf-dialog-btn secondary" id="wfDialogCancel" type="button">Cancel</button>
            <button class="wf-dialog-btn primary" id="wfDialogConfirm" type="button">Okay</button>
        </div>
    </section>
</div>

<script>
    (() => {
        if (window.FuturisticDialog) return;

        const backdrop = document.getElementById('wfDialogBackdrop');
        const panel = backdrop.querySelector('.wf-dialog-panel');
        const icon = document.getElementById('wfDialogIcon');
        const kicker = document.getElementById('wfDialogKicker');
        const title = document.getElementById('wfDialogTitle');
        const message = document.getElementById('wfDialogMessage');
        const copyField = document.getElementById('wfDialogCopyField');
        const cancelButton = document.getElementById('wfDialogCancel');
        const confirmButton = document.getElementById('wfDialogConfirm');
        let resolveDialog = null;
        let previousFocus = null;

        const close = result => {
            if (backdrop.hidden) return;
            backdrop.hidden = true;
            document.documentElement.style.removeProperty('overflow');
            const resolver = resolveDialog;
            resolveDialog = null;
            resolver?.(result);
            previousFocus?.focus?.();
        };

        const open = options => {
            if (resolveDialog) close(false);

            previousFocus = document.activeElement;
            icon.textContent = options.icon || 'i';
            kicker.textContent = options.kicker || 'Wayfinding System';
            title.textContent = options.title || 'System Notice';
            message.textContent = String(options.message || '');
            confirmButton.textContent = options.confirmText || 'Okay';
            confirmButton.className = `wf-dialog-btn ${options.danger ? 'danger' : 'primary'}`;
            cancelButton.textContent = options.cancelText || 'Cancel';
            cancelButton.hidden = !options.showCancel;
            copyField.hidden = !options.copyValue;
            copyField.value = options.copyValue || '';
            backdrop.hidden = false;
            document.documentElement.style.overflow = 'hidden';

            requestAnimationFrame(() => {
                if (!copyField.hidden) {
                    copyField.focus();
                    copyField.select();
                } else {
                    confirmButton.focus();
                }
            });

            return new Promise(resolve => {
                resolveDialog = resolve;
            });
        };

        confirmButton.addEventListener('click', async () => {
            if (!copyField.hidden) {
                copyField.select();
                try {
                    await navigator.clipboard.writeText(copyField.value);
                    confirmButton.textContent = 'Copied!';
                    setTimeout(() => close(true), 500);
                    return;
                } catch {
                    document.execCommand?.('copy');
                }
            }
            close(true);
        });
        cancelButton.addEventListener('click', () => close(false));
        backdrop.addEventListener('click', event => {
            if (event.target === backdrop) close(false);
        });
        panel.addEventListener('keydown', event => {
            if (event.key !== 'Tab') return;
            const buttons = [...panel.querySelectorAll('button:not([hidden]), input:not([hidden])')];
            const first = buttons[0];
            const last = buttons[buttons.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !backdrop.hidden) close(false);
        });

        window.FuturisticDialog = {
            alert(messageText, options = {}) {
                return open({
                    icon: options.icon || 'i',
                    title: options.title || 'System Notice',
                    message: messageText,
                    confirmText: options.confirmText || 'Okay',
                });
            },
            confirm(messageText, options = {}) {
                return open({
                    icon: options.icon || '!',
                    kicker: options.kicker || 'Confirmation Required',
                    title: options.title || 'Please Confirm',
                    message: messageText,
                    confirmText: options.confirmText || 'Continue',
                    cancelText: options.cancelText || 'Cancel',
                    danger: options.danger ?? true,
                    showCancel: true,
                });
            },
            copy(label, value) {
                return open({
                    icon: '↗',
                    kicker: 'Share Destination',
                    title: 'Copy Route Link',
                    message: label || 'Copy this route link and share it with your visitors.',
                    copyValue: value,
                    confirmText: 'Copy Link',
                    cancelText: 'Close',
                    showCancel: true,
                });
            },
        };

        window.alert = messageText => {
            window.FuturisticDialog.alert(messageText);
        };

        document.addEventListener('submit', async event => {
            const form = event.target;
            const inlineHandler = form.getAttribute?.('onsubmit') || '';
            const match = inlineHandler.match(/confirm\((['"])(.*?)\1\)/i);
            if (!match || form.dataset.wfDialogConfirmed === 'true') return;

            event.preventDefault();
            event.stopImmediatePropagation();
            const confirmed = await window.FuturisticDialog.confirm(match[2], {
                title: 'Confirm This Action',
                confirmText: /delete|remove|restore|reset/i.test(match[2]) ? 'Yes, Continue' : 'Continue',
            });

            if (!confirmed) return;

            form.dataset.wfDialogConfirmed = 'true';
            const originalHandler = form.getAttribute('onsubmit');
            form.removeAttribute('onsubmit');
            form.requestSubmit(event.submitter || undefined);
            setTimeout(() => {
                form.dataset.wfDialogConfirmed = 'false';
                if (originalHandler) form.setAttribute('onsubmit', originalHandler);
            }, 0);
        }, true);
    })();
</script>
