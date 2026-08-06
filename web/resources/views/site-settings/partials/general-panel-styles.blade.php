<style>
    .tich-site-identity-hero {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        justify-content: space-between;
        gap: 1.5rem;
        padding: 1.75rem 2rem;
        border-radius: var(--radius-md);
        background: var(--tich-surface);
        border: 1px solid var(--tich-border);
        border-left: 4px solid var(--tich-green);
    }

    .tich-site-identity-hero__eyebrow {
        margin: 0 0 0.35rem;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--tich-green);
    }

    .tich-site-identity-hero__title {
        margin: 0;
        font-size: clamp(1.5rem, 2.5vw, 2rem);
        line-height: 1.2;
        color: var(--tich-text);
    }

    .tich-site-identity-hero__tagline {
        margin: 0.65rem 0 0;
        max-width: 42rem;
        font-size: 1rem;
        line-height: 1.55;
        color: var(--tich-neutral-muted);
    }

    .tich-site-identity-hero__meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .tich-site-identity-hero__aside {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        min-width: min(100%, 220px);
    }

    .tich-site-identity-logo-frame {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 7rem;
        min-width: 12rem;
        padding: 1.25rem 1.5rem;
        border-radius: var(--radius-md);
        background: var(--tich-white);
        border: 1px solid var(--tich-border);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
    }

    .tich-site-identity-logo-frame img {
        max-height: 4.5rem;
        max-width: 100%;
        object-fit: contain;
    }

    .tich-site-identity-logo-frame__fallback {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.65rem;
        text-align: center;
        color: var(--tich-neutral-muted);
    }

    .tich-site-identity-logo-frame__mark {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3.25rem;
        height: 3.25rem;
        border-radius: var(--radius-md);
        background: var(--tich-green);
        color: var(--tich-on-brand);
        font-family: var(--font-heading);
        font-size: 1.25rem;
        font-weight: 700;
    }

    .tich-site-identity-preview {
        overflow: hidden;
        border-radius: var(--radius-md);
        border: 1px solid var(--tich-border);
        background: var(--tich-white);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .tich-site-identity-preview__label {
        padding: 0.65rem 1rem;
        border-bottom: 1px solid var(--tich-border);
        background: var(--tich-surface-muted);
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--tich-neutral-muted);
    }

    .tich-site-identity-preview__bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.9rem 1.25rem;
        background: var(--tich-white);
        border-bottom: 1px solid var(--tich-neutral-border);
    }

    .tich-site-identity-preview__nav {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .tich-site-identity-preview__nav li {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--tich-neutral-muted);
    }

    .tich-site-identity-preview__nav li.is-active {
        color: var(--tich-green);
    }

    .tich-site-identity-preview__footer {
        padding: 1rem 1.25rem 1.15rem;
        background: var(--tich-surface-muted);
    }

    .tich-site-identity-preview__footer p {
        margin: 0;
        font-size: 0.8125rem;
        color: var(--tich-neutral-muted);
        line-height: 1.5;
    }

    .tich-site-identity-asset {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        height: 100%;
    }

    .tich-site-identity-asset__stage {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 10rem;
        padding: 1.5rem;
        border-radius: var(--radius-md);
        background: var(--tich-surface-muted);
        border: 1px dashed var(--tich-border);
    }

    .tich-site-identity-asset__stage img {
        max-height: 5.5rem;
        max-width: 100%;
        object-fit: contain;
        filter: drop-shadow(0 8px 16px rgba(15, 23, 42, 0.12));
    }

    .tich-site-identity-asset__meta {
        display: grid;
        gap: 0.35rem;
    }

    .tich-site-identity-card-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .tich-site-identity-card-head h2 {
        margin: 0;
    }

    .tich-site-identity-card-head p {
        margin: 0.35rem 0 0;
    }

    .tich-site-identity-stat-link {
        color: inherit;
        text-decoration: none;
    }

    .tich-site-identity-stat-link:hover {
        color: var(--tich-green);
        text-decoration: underline;
    }

    [data-theme="dark"] .tich-site-identity-hero {
        background: var(--tich-surface);
    }

    [data-theme="dark"] .tich-site-identity-logo-frame,
    [data-theme="dark"] .tich-site-identity-preview,
    [data-theme="dark"] .tich-site-identity-preview__bar {
        background: var(--tich-surface-muted);
    }

    @media (max-width: 768px) {
        .tich-site-identity-hero {
            padding: 1.25rem 1.35rem;
        }

        .tich-site-identity-hero__aside {
            align-items: stretch;
            width: 100%;
        }

        .tich-site-identity-logo-frame {
            width: 100%;
        }
    }
</style>
