const cycleLayout = document.querySelector('[data-cycle-layout]');
if (cycleLayout) {
    const tabs = [...cycleLayout.querySelectorAll('[data-cycle-tab]')];
    const panels = [...cycleLayout.querySelectorAll('[data-cycle-panel]')];
    const hashTab = () => {
        const id = window.location.hash.slice(1);
        const target = document.getElementById(id);
        return target?.closest('[data-cycle-panel]')?.dataset.cyclePanel
            ?? (tabs.some(tab => tab.dataset.cycleTab === id) ? id : null);
    };
    const select = (name, updateUrl = false) => {
        if (!tabs.some(tab => tab.dataset.cycleTab === name)) return;
        tabs.forEach(tab => {
            const selected = tab.dataset.cycleTab === name;
            tab.setAttribute('aria-selected', String(selected));
            tab.tabIndex = selected ? 0 : -1;
        });
        panels.forEach(panel => { panel.hidden = panel.dataset.cyclePanel !== name; });
        if (name === 'vaccinations') cycleLayout.querySelector('#vaccinations').open = true;
        if (updateUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', name);
            url.hash = '';
            window.history.pushState(null, '', url);
            panels.find(panel => panel.dataset.cyclePanel === name)?.scrollIntoView({ block: 'start', behavior: 'instant' });
        }
    };
    select(cycleLayout.dataset.restoreTab === 'true' ? cycleLayout.dataset.initialTab : (hashTab() ?? cycleLayout.dataset.initialTab));
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => select(tab.dataset.cycleTab, true));
        tab.addEventListener('keydown', event => {
            let next;
            if (event.key === 'ArrowLeft') next = (index + 1) % tabs.length;
            if (event.key === 'ArrowRight') next = (index - 1 + tabs.length) % tabs.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabs.length - 1;
            if (next === undefined) return;
            event.preventDefault();
            tabs[next].focus();
            select(tabs[next].dataset.cycleTab, true);
        });
    });
    const restore = () => select(hashTab() ?? new URL(window.location.href).searchParams.get('tab') ?? cycleLayout.dataset.defaultTab ?? 'daily');
    window.addEventListener('hashchange', restore);
    window.addEventListener('popstate', restore);
}
