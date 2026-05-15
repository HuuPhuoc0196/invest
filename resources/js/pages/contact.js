import axios from 'axios';

document.addEventListener('DOMContentLoaded', function () {
    initFaq();
    initContactForm();
});

function initFaq() {
    document.querySelectorAll('.contact-faq-item__q').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const item = btn.closest('.contact-faq-item');
            const isOpen = item.classList.contains('is-open');
            document.querySelectorAll('.contact-faq-item.is-open').forEach(function (el) {
                el.classList.remove('is-open');
            });
            if (!isOpen) {
                item.classList.add('is-open');
            }
        });
    });
}

function initContactForm() {
    const form = document.getElementById('contact-form');
    if (!form) return;

    const btn = form.querySelector('.contact-form-submit-btn');
    const btnText = form.querySelector('.contact-form-submit-btn__text');
    const feedback = form.querySelector('.contact-form-feedback');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearErrors(form);
        hideFeedback(feedback);

        if (!validateForm(form)) return;

        setLoading(btn, btnText, true);

        const data = {
            name:    form.querySelector('[name="name"]').value.trim(),
            email:   form.querySelector('[name="email"]').value.trim(),
            subject: form.querySelector('[name="subject"]').value.trim(),
            message: form.querySelector('[name="message"]').value.trim(),
        };

        try {
            const res = await axios.post(window.__contactRoute, data);
            showFeedback(feedback, res.data.message, 'success');
            form.reset();
        } catch (err) {
            if (err.response && err.response.status === 422) {
                const errors = err.response.data.errors || {};
                Object.keys(errors).forEach(function (field) {
                    showFieldError(form, field, errors[field][0]);
                });
            } else {
                const msg = (err.response && err.response.data && err.response.data.message)
                    ? err.response.data.message
                    : 'Đã xảy ra lỗi. Vui lòng thử lại.';
                showFeedback(feedback, msg, 'error');
            }
        } finally {
            setLoading(btn, btnText, false);
        }
    });
}

function validateForm(form) {
    let ok = true;

    const name = form.querySelector('[name="name"]').value.trim();
    if (!name) { showFieldError(form, 'name', 'Vui lòng nhập họ tên.'); ok = false; }

    const email = form.querySelector('[name="email"]').value.trim();
    if (!email) {
        showFieldError(form, 'email', 'Vui lòng nhập email.'); ok = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showFieldError(form, 'email', 'Email không hợp lệ.'); ok = false;
    }

    const subject = form.querySelector('[name="subject"]').value.trim();
    if (!subject) { showFieldError(form, 'subject', 'Vui lòng nhập tiêu đề.'); ok = false; }

    const message = form.querySelector('[name="message"]').value.trim();
    if (!message) { showFieldError(form, 'message', 'Vui lòng nhập nội dung.'); ok = false; }

    return ok;
}

function showFieldError(form, field, msg) {
    const input = form.querySelector('[name="' + field + '"]');
    if (input) input.classList.add('is-error');
    const errEl = form.querySelector('[data-error="' + field + '"]');
    if (errEl) { errEl.textContent = msg; errEl.classList.add('is-visible'); }
}

function clearErrors(form) {
    form.querySelectorAll('.is-error').forEach(function (el) { el.classList.remove('is-error'); });
    form.querySelectorAll('.contact-form-group__error').forEach(function (el) {
        el.classList.remove('is-visible');
        el.textContent = '';
    });
}

function showFeedback(el, msg, type) {
    if (!el) return;
    el.textContent = (type === 'success' ? '✅ ' : '❌ ') + msg;
    el.className = 'contact-form-feedback is-' + type;
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function hideFeedback(el) {
    if (el) el.className = 'contact-form-feedback';
}

function setLoading(btn, textEl, loading) {
    btn.disabled = loading;
    if (textEl) textEl.textContent = loading ? 'Đang gửi…' : 'Gửi tin nhắn';
}
