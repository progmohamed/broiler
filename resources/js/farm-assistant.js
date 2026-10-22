const form = document.querySelector('[data-assistant-form]');

if (form) {
    const question = form.querySelector('[name="question"]');
    const messages = document.querySelector('[data-assistant-messages]');
    const loading = document.querySelector('[data-assistant-loading]');
    const error = document.querySelector('[data-assistant-error]');
    const resetForm = document.querySelector('[data-assistant-reset]');
    const suggestions = Array.from(document.querySelectorAll('[data-assistant-suggestion]'));
    const enabled = !question.disabled;
    let busy = false;
    const charts = new Map();

    const renderCharts = async () => {
        if (!messages.querySelector('[data-assistant-chart]')) {
            return;
        }

        try {
            const { mountCharts } = await import('./farm-assistant-charts');
            mountCharts(messages, charts);
        } catch {
            // The server-rendered chart remains available if the chart module cannot load.
        }
    };

    renderCharts();

    const setBusy = (value) => {
        busy = value;
        form.querySelector('[type="submit"]').disabled = value || !enabled;
        question.readOnly = value;
        resetForm.querySelector('button').disabled = value;
        suggestions.forEach((button) => { button.disabled = value || !enabled; });
        messages.setAttribute('aria-busy', String(value));
    };

    const send = async (targetForm) => {
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 40000);

        try {
            const response = await fetch(targetForm.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: new FormData(targetForm),
                credentials: 'same-origin',
                signal: controller.signal,
            });

            if (response.redirected || response.status === 401 || response.status === 403) {
                throw new Error('التطبيق مقفل أو انتهت الجلسة. افتح التطبيق مجددًا ثم أعد المحاولة.');
            }

            if (response.status === 419) {
                throw new Error('انتهت صلاحية الجلسة. حدّث الصفحة ثم أعد إرسال السؤال.');
            }

            if (response.status === 429) {
                throw new Error('أرسلت أسئلة كثيرة. انتظر دقيقة ثم حاول مرة أخرى.');
            }

            if (!response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('تعذّر تحميل التقرير. تحقق من الاتصال وحدّث الصفحة.');
            }

            const payload = await response.json();

            if (!response.ok) {
                throw new Error([422, 503].includes(response.status)
                    ? payload.errors?.question?.[0] ?? payload.message ?? 'تعذّر تجهيز التقرير.'
                    : 'حدث خطأ أثناء تجهيز التقرير. حاول مرة أخرى لاحقًا.');
            }

            return payload;
        } finally {
            window.clearTimeout(timeout);
        }
    };

    const showError = (failure) => {
        error.textContent = failure.name === 'AbortError'
            ? 'استغرق تجهيز التقرير وقتًا طويلًا. حاول مرة أخرى.'
            : failure instanceof TypeError
                ? 'تعذّر الاتصال بالخادم. تحقق من اتصال الإنترنت ثم حاول مجددًا.'
                : failure.message;
        error.hidden = false;
    };

    suggestions.forEach((button) => {
        button.addEventListener('click', () => {
            question.value = button.textContent.trim();
            question.focus();
            form.scrollIntoView({ block: 'nearest' });
        });
    });

    question.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (busy || !enabled || !form.reportValidity()) {
            return;
        }

        const text = question.value.trim();

        if (!text) {
            error.textContent = 'اكتب سؤالك أولًا.';
            error.hidden = false;
            return;
        }

        error.hidden = true;
        loading.hidden = false;
        setBusy(true);

        try {
            const payload = await send(form);

            if (typeof payload.html !== 'string') {
                throw new Error('تعذّر عرض التقرير. حاول مرة أخرى.');
            }

            const userMessage = document.createElement('p');
            userMessage.className = 'self-start rounded-2xl bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-900';
            userMessage.textContent = text;
            messages.append(userMessage);
            messages.insertAdjacentHTML('beforeend', payload.html);
            await renderCharts();
            question.value = '';
            userMessage.scrollIntoView({ block: 'start' });
        } catch (failure) {
            showError(failure);
        } finally {
            loading.hidden = true;
            setBusy(false);
        }
    });

    resetForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (busy) {
            return;
        }

        error.hidden = true;
        setBusy(true);

        try {
            await send(resetForm);
            charts.forEach((chart) => chart.destroy());
            charts.clear();
            const welcome = messages.querySelector('[data-assistant-welcome]');
            messages.replaceChildren(welcome);
            question.value = '';
            question.focus();
        } catch (failure) {
            showError(failure);
        } finally {
            setBusy(false);
        }
    });
}
