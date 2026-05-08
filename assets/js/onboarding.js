// WooChat Pro – Onboarding Wizard

document.addEventListener('DOMContentLoaded', function () {
    const wizard = document.getElementById('wcwp-onboarding-modal');
    if (!wizard) return;

    const steps     = Array.from(wizard.querySelectorAll('.wcwp-onboarding-step'));
    const progress  = wizard.querySelector('.wcwp-onboarding-progress-inner');
    const nextBtn   = wizard.querySelector('.wcwp-onboarding-next');
    const prevBtn   = wizard.querySelector('.wcwp-onboarding-prev');
    const skipBtn   = wizard.querySelector('.wcwp-onboarding-skip');
    const finishBtn = wizard.querySelector('.wcwp-onboarding-finish');

    const config = (typeof wcwpOnboarding !== 'undefined') ? wcwpOnboarding : {};
    const i18n = config.i18n || {};
    const defaultNextLabel = nextBtn ? nextBtn.textContent : 'Next';

    let step = 0;
    let saving = false;

    function showStep(idx) {
        steps.forEach((s, i) => s.style.display = i === idx ? 'block' : 'none');
        if (progress) progress.style.width = ((idx + 1) / steps.length * 100) + '%';
        prevBtn.style.display   = idx === 0 ? 'none' : 'inline-block';
        nextBtn.style.display   = idx === steps.length - 1 ? 'none' : 'inline-block';
        finishBtn.style.display = idx === steps.length - 1 ? 'inline-block' : 'none';

        if (currentStepName() === 'credentials') {
            applyProviderVisibility();
        }
    }

    function currentStepName() {
        const el = steps[step];
        return el ? el.getAttribute('data-step') : '';
    }

    function selectedProvider() {
        const checked = wizard.querySelector('input[name="wcwp_ob_provider"]:checked');
        return checked ? checked.value : 'twilio';
    }

    function applyProviderVisibility() {
        const provider = selectedProvider();
        wizard.querySelectorAll('[data-provider-fields]').forEach(function (block) {
            block.style.display = block.getAttribute('data-provider-fields') === provider ? 'block' : 'none';
        });
        wizard.querySelectorAll('[data-provider-hint]').forEach(function (hint) {
            hint.style.display = hint.getAttribute('data-provider-hint') === provider ? 'block' : 'none';
        });
    }

    wizard.querySelectorAll('input[name="wcwp_ob_provider"]').forEach(function (radio) {
        radio.addEventListener('change', applyProviderVisibility);
    });

    function clearFieldErrors() {
        wizard.querySelectorAll('.wcwp-onboarding-field-error').forEach(function (el) { el.textContent = ''; });
        wizard.querySelectorAll('.wcwp-onboarding-field.has-error').forEach(function (el) { el.classList.remove('has-error'); });
        const formErr = wizard.querySelector('.wcwp-onboarding-form-error');
        if (formErr) formErr.textContent = '';
    }

    function showFieldErrors(fields) {
        Object.keys(fields).forEach(function (key) {
            const errEl = wizard.querySelector('[data-error-for="' + key + '"]');
            if (errEl) {
                errEl.textContent = fields[key];
                const wrap = errEl.closest('.wcwp-onboarding-field');
                if (wrap) wrap.classList.add('has-error');
            }
        });
        const formErr = wizard.querySelector('.wcwp-onboarding-form-error');
        if (formErr) formErr.textContent = i18n.saveError || 'Could not save. Please check the highlighted fields.';
    }

    function showFormError(message) {
        const formErr = wizard.querySelector('.wcwp-onboarding-form-error');
        if (formErr) formErr.textContent = message;
    }

    function setSaving(isSaving) {
        saving = isSaving;
        nextBtn.disabled = isSaving;
        prevBtn.disabled = isSaving;
        nextBtn.textContent = isSaving ? (i18n.saving || 'Saving…') : (i18n.next || defaultNextLabel);
    }

    function saveCredentials() {
        if (!config.ajaxUrl || !config.saveNonce) {
            // No AJAX wiring (e.g. localized data missing) — fail open so the
            // wizard doesn't trap the admin. They can still configure via the tab.
            return Promise.resolve();
        }

        const provider = selectedProvider();
        const body = new URLSearchParams();
        body.append('action', 'wcwp_save_onboarding_credentials');
        body.append('nonce', config.saveNonce);
        body.append('provider', provider);

        const fieldNames = provider === 'twilio'
            ? ['twilio_sid', 'twilio_token', 'twilio_from']
            : ['cloud_token', 'cloud_phone_id', 'cloud_from'];

        fieldNames.forEach(function (name) {
            const input = wizard.querySelector('[data-provider-fields="' + provider + '"] [name="' + name + '"]');
            body.append(name, input ? input.value : '');
        });

        return fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: body
        }).then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data: data }; });
        });
    }

    nextBtn.addEventListener('click', function () {
        if (saving) return;

        if (currentStepName() === 'credentials') {
            clearFieldErrors();
            setSaving(true);
            saveCredentials().then(function (result) {
                setSaving(false);
                if (!result) { advance(); return; }
                if (result.ok && result.data && result.data.success) {
                    advance();
                    return;
                }
                const payload = (result.data && result.data.data) || {};
                if (payload.fields) {
                    showFieldErrors(payload.fields);
                } else {
                    showFormError(payload.message || i18n.saveError || 'Could not save.');
                }
            }).catch(function () {
                setSaving(false);
                showFormError(i18n.networkError || 'Network error. Please try again.');
            });
            return;
        }

        advance();
    });

    function advance() {
        if (step < steps.length - 1) {
            step++;
            showStep(step);
        }
    }

    prevBtn.addEventListener('click', function () {
        if (saving) return;
        if (step > 0) {
            step--;
            showStep(step);
        }
    });

    function persistDismissal() {
        if (!config.ajaxUrl || !config.dismissNonce) return;
        const body = new URLSearchParams();
        body.append('action', 'wcwp_dismiss_onboarding');
        body.append('nonce', config.dismissNonce);
        fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: body
        }).catch(function () { /* best-effort; UI is already dismissed */ });
    }

    skipBtn.addEventListener('click', function () {
        if (saving) return;
        wizard.style.display = 'none';
        persistDismissal();
    });
    finishBtn.addEventListener('click', function () {
        if (saving) return;
        wizard.style.display = 'none';
        persistDismissal();
    });

    showStep(step);
});
