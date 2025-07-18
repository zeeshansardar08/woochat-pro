// WooChat Pro – Onboarding Wizard

document.addEventListener('DOMContentLoaded', function () {
    const wizard = document.getElementById('wcwp-onboarding-modal');
    if (!wizard) return;
    let step = 0;
    const steps = wizard.querySelectorAll('.wcwp-onboarding-step');
    const progress = wizard.querySelector('.wcwp-onboarding-progress-inner');
    const nextBtn = wizard.querySelector('.wcwp-onboarding-next');
    const prevBtn = wizard.querySelector('.wcwp-onboarding-prev');
    const skipBtn = wizard.querySelector('.wcwp-onboarding-skip');
    const finishBtn = wizard.querySelector('.wcwp-onboarding-finish');

    function showStep(idx) {
        steps.forEach((s, i) => s.style.display = i === idx ? 'block' : 'none');
        progress.style.width = ((idx + 1) / steps.length * 100) + '%';
        prevBtn.style.display = idx === 0 ? 'none' : 'inline-block';
        nextBtn.style.display = idx === steps.length - 1 ? 'none' : 'inline-block';
        finishBtn.style.display = idx === steps.length - 1 ? 'inline-block' : 'none';
    }
    showStep(step);

    nextBtn.addEventListener('click', function () {
        if (step < steps.length - 1) {
            step++;
            showStep(step);
        }
    });
    prevBtn.addEventListener('click', function () {
        if (step > 0) {
            step--;
            showStep(step);
        }
    });
    skipBtn.addEventListener('click', function () {
        wizard.style.display = 'none';
    });
    finishBtn.addEventListener('click', function () {
        wizard.style.display = 'none';
    });
}); 