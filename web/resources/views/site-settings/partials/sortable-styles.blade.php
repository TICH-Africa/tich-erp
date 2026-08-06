<style>
    .tich-site-settings-sortable tbody tr[data-sortable-row] { cursor: default; }
    .tich-site-settings-sortable tbody tr.is-dragging { opacity: 0.45; cursor: grabbing; }
    .tich-drag-handle {
        color: var(--tich-muted, #64748b);
        user-select: none;
        width: 2rem;
        text-align: center;
        font-size: 1.1rem;
        line-height: 1;
        cursor: grab;
    }
    .tich-drag-handle:active { cursor: grabbing; }
</style>
