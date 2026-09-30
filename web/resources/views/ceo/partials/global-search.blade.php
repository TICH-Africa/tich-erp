<div class="ceo-search" data-ceo-search data-search-url="{{ $executiveSearchUrl ?? route('ceo.search') }}">
    <label class="ceo-search__label" for="ceo-global-search">Search</label>
    <div class="ceo-search__shell">
        <svg class="ceo-search__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
            <path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <input
            id="ceo-global-search"
            class="ceo-search__input"
            type="search"
            autocomplete="off"
            spellcheck="false"
            placeholder="Search staff, students, budgets, modules, vacancies…"
            aria-controls="ceo-search-results"
            aria-expanded="false"
        >
        <kbd class="ceo-search__kbd" aria-hidden="true">/</kbd>
    </div>
    <div id="ceo-search-results" class="ceo-search__results" role="listbox" hidden></div>
</div>

<style>
.ceo-search { position: relative; margin-bottom: 1.1rem; }
.ceo-search__label {
    position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
    overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0;
}
.ceo-search__shell {
    display: flex; align-items: center; gap: .65rem;
    padding: .7rem .9rem; border-radius: 12px;
    border: 1px solid var(--tich-border, #d7dde6);
    background: var(--tich-surface, #fff);
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
}
.ceo-search__shell:focus-within {
    border-color: color-mix(in srgb, var(--tich-blue, #1669A6) 55%, var(--tich-border, #d7dde6));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--tich-blue, #1669A6) 16%, transparent);
}
.ceo-search__icon { flex: 0 0 auto; opacity: .55; }
.ceo-search__input {
    flex: 1 1 auto; border: 0; outline: none; background: transparent;
    font: inherit; color: inherit; min-width: 0;
}
.ceo-search__kbd {
    flex: 0 0 auto; font-size: .72rem; opacity: .45;
    border: 1px solid var(--tich-border, #d7dde6); border-radius: 6px;
    padding: .1rem .35rem; line-height: 1.2;
}
.ceo-search__results {
    position: absolute; z-index: 40; left: 0; right: 0; top: calc(100% + .35rem);
    max-height: min(26rem, 70vh); overflow: auto;
    border-radius: 12px; border: 1px solid var(--tich-border, #d7dde6);
    background: var(--tich-surface, #fff);
    box-shadow: 0 18px 40px rgba(15, 23, 42, .14);
}
.ceo-search__group {
    padding: .55rem .75rem .25rem;
    font-size: .72rem; letter-spacing: .06em; text-transform: uppercase; opacity: .6;
}
.ceo-search__item {
    display: block; width: 100%; text-align: left; text-decoration: none; color: inherit;
    padding: .7rem .9rem; border: 0; background: transparent; cursor: pointer; font: inherit;
    border-top: 1px solid color-mix(in srgb, var(--tich-border, #d7dde6) 70%, transparent);
}
.ceo-search__item:hover,
.ceo-search__item.is-active {
    background: color-mix(in srgb, var(--tich-blue-light, #D6E8F5) 55%, var(--tich-surface, #fff));
}
.ceo-search__item-title { margin: 0; font-weight: 600; }
.ceo-search__item-sub { margin: .2rem 0 0; font-size: .85rem; opacity: .72; }
.ceo-search__empty,
.ceo-search__status {
    padding: .95rem 1rem; opacity: .75; font-size: .92rem;
}
</style>
