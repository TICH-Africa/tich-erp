<style>
    .doc-viewer {
        display: grid;
        grid-template-columns: minmax(14rem, 18rem) minmax(0, 1fr);
        gap: 1rem;
        min-height: 32rem;
    }

    .doc-viewer__list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        max-height: 40rem;
        overflow: auto;
        padding-right: 0.25rem;
    }

    .doc-viewer__item {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
        width: 100%;
        padding: 0.875rem 1rem;
        border: 1px solid var(--tich-border, #e2e4e5);
        border-radius: 0.375rem;
        background: var(--tich-surface, #fff);
        text-align: left;
        cursor: pointer;
        transition: border-color 0.15s ease, background 0.15s ease;
    }

    .doc-viewer__item:hover,
    .doc-viewer__item.is-active {
        border-color: var(--tich-blue, #1669a6);
        background: rgba(22, 105, 166, 0.06);
    }

    .doc-viewer__item-label {
        font-weight: 600;
        color: var(--tich-text, #494c50);
        font-size: 0.9375rem;
    }

    .doc-viewer__item-file {
        font-size: 0.75rem;
        color: #6b6e72;
        word-break: break-word;
    }

    .doc-viewer__panel {
        display: flex;
        flex-direction: column;
        min-width: 0;
        border: 1px solid var(--tich-border, #e2e4e5);
        border-radius: 0.375rem;
        overflow: hidden;
        background: #fafafa;
    }

    .doc-viewer__toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 1rem;
        padding: 0.875rem 1rem;
        background: var(--tich-surface, #fff);
        border-bottom: 1px solid var(--tich-border, #e2e4e5);
    }

    .doc-viewer__stage {
        flex: 1;
        min-height: 28rem;
        background: #525659;
    }

    .doc-viewer__frame,
    .doc-viewer__image {
        width: 100%;
        height: 100%;
        min-height: 28rem;
        border: 0;
        display: block;
        object-fit: contain;
        background: #525659;
    }

    .doc-viewer__fallback {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 28rem;
        padding: 2rem;
        text-align: center;
        background: var(--tich-surface, #fff);
    }

    @media (max-width: 900px) {
        .doc-viewer {
            grid-template-columns: 1fr;
        }

        .doc-viewer__list {
            max-height: none;
        }
    }
</style>
