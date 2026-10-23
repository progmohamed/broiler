const panel = document.querySelector('[data-vaccination-alerts]');

if (panel) {
    const setup = panel.querySelector('[data-vaccination-setup]');
    const button = panel.querySelector('[data-enable-vaccination-notifications]');
    const status = panel.querySelector('[data-vaccination-notification-status]');
    const connection = panel.querySelector('[data-vaccination-connection-status]');
    const list = panel.querySelector('[data-vaccination-reminders]');
    const supported = 'Notification' in window && 'serviceWorker' in navigator && window.isSecureContext;
    const seen = new Set();
    let busy = false;
    let registrationPromise;

    const syncVisibility = () => {
        const enabled = supported && Notification.permission === 'granted';
        setup.hidden = enabled;
        panel.hidden = enabled && list.children.length === 0 && !connection.textContent;
    };

    const registration = () => {
        registrationPromise ??= navigator.serviceWorker.register(panel.dataset.workerUrl, { scope: '/broiler-notifications/' }).then(async (result) => {
            if (result.active) return result;
            const worker = result.installing ?? result.waiting;
            await new Promise((resolve, reject) => {
                const timeout = setTimeout(() => reject(new Error('Notification worker timeout')), 10000);
                worker.addEventListener('statechange', () => {
                    if (worker.state === 'activated') { clearTimeout(timeout); resolve(); }
                    if (worker.state === 'redundant') { clearTimeout(timeout); reject(new Error('Notification worker unavailable')); }
                });
            });
            return result;
        }).catch((error) => { registrationPromise = undefined; throw error; });
        return registrationPromise;
    };

    const notify = async (reminder) => {
        if (!supported || Notification.permission !== 'granted') return;
        const key = `broiler-vaccination:${reminder.key}:${reminder.overdue ? 'overdue' : 'upcoming'}`;
        if (seen.has(key)) return;
        try { if (localStorage.getItem(key)) return; } catch { /* In-memory deduplication remains available. */ }
        const worker = await registration();
        await worker.showNotification(`${reminder.overdue ? 'تحصين متأخر' : 'تذكير بتحصين'}: ${reminder.title}`, {
            body: `${reminder.cycle} — ${reminder.house}\n${reminder.due}`,
            tag: key, dir: 'rtl', lang: 'ar', data: { url: reminder.url },
        });
        seen.add(key);
        try { localStorage.setItem(key, '1'); } catch { /* In-memory deduplication remains available. */ }
    };

    const refresh = async () => {
        if (busy) return;
        busy = true;
        try {
            const response = await fetch(panel.dataset.url, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(15000) });
            if (response.redirected || response.status === 401 || response.status === 403) {
                list.replaceChildren();
                connection.textContent = 'افتح قفل التطبيق لمتابعة تنبيهات التحصينات.';
                return;
            }
            if (!response.ok) throw new Error('Unable to load reminders');
            const { reminders } = await response.json();
            list.replaceChildren();
            connection.textContent = '';
            for (const reminder of reminders) {
                const item = document.createElement('li');
                const link = document.createElement('a');
                link.href = reminder.url;
                link.className = 'block rounded-xl bg-amber-50 p-3 text-sm text-amber-900';
                link.textContent = `${reminder.overdue ? 'متأخر' : 'اقترب الموعد'}: ${reminder.title} · ${reminder.cycle} · ${reminder.due}`;
                item.append(link);
                list.append(item);
                try { await notify(reminder); } catch { connection.textContent = 'تعذر عرض إشعار المتصفح؛ راجع صلاحيات الإشعارات.'; }
            }
        } catch {
            connection.textContent = 'تعذر تحديث التنبيهات. تحقق من الاتصال؛ سيُعاد المحاولة تلقائيًا.';
        } finally { busy = false; syncVisibility(); }
    };

    if (!supported) {
        button.disabled = true;
        status.textContent = 'إشعارات المتصفح غير متاحة هنا. تنبيهات الصفحة متاحة، ويمكن تنزيل المواعيد لتقويم الموبايل.';
    } else {
        button.addEventListener('click', async () => {
            try {
                const permission = await Notification.requestPermission();
                status.textContent = permission === 'granted'
                    ? ''
                    : 'لم يتم السماح بالإشعارات. يمكنك تفعيلها من إعدادات الموقع؛ تنبيهات الصفحة تعمل.';
                syncVisibility();
                if (permission === 'granted') await refresh();
            } catch { status.textContent = 'تعذر تفعيل الإشعارات في هذا المتصفح. استخدم تنبيهات الصفحة أو التقويم.'; }
        });
    }
    syncVisibility();
    refresh();
    setInterval(refresh, 60000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
}
