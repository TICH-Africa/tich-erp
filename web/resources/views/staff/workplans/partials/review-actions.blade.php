<article class="tich-card">
    <h2 class="tich-h3">Review actions</h2>
    <form method="POST" action="{{ $approveRoute }}" class="tich-mt-4">
        @csrf
        <label class="tich-label" for="approve_comments">Comments (optional)</label>
        <textarea id="approve_comments" name="comments" class="tich-input" rows="2"></textarea>
        <button type="submit" class="tich-btn tich-btn-primary tich-mt-4">Approve</button>
    </form>

    <form method="POST" action="{{ $changesRoute }}" class="tich-mt-6">
        @csrf
        <label class="tich-label" for="changes_comments">Request changes</label>
        <textarea id="changes_comments" name="comments" class="tich-input" rows="2" required></textarea>
        <button type="submit" class="tich-btn tich-btn-secondary tich-mt-4">Request changes</button>
    </form>

    <form method="POST" action="{{ $rejectRoute }}" class="tich-mt-6">
        @csrf
        <label class="tich-label" for="reject_comments">Reject</label>
        <textarea id="reject_comments" name="comments" class="tich-input" rows="2" required></textarea>
        <button type="submit" class="tich-btn tich-btn-danger tich-mt-4">Reject</button>
    </form>
</article>
