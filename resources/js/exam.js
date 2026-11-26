const shell = document.querySelector('[data-exam-shell]');

if (shell) {
    const timer = shell.querySelector('[data-exam-timer]');
    const connection = shell.querySelector('[data-exam-connection]');
    const feedback = shell.querySelector('[data-exam-feedback]');
    const deadline = new Date(shell.dataset.deadline).getTime();
    const serverNow = new Date(shell.dataset.serverNow).getTime();
    const clientLoadedAt = Date.now();
    const attemptId = shell.dataset.attemptId;
    let timerId;

    const updateTimer = () => {
        const remaining = Math.max(0, deadline - (serverNow + (Date.now() - clientLoadedAt)));
        const seconds = Math.floor(remaining / 1000);
        timer.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
        if (remaining === 0) clearInterval(timerId);
    };
    updateTimer();
    timerId = window.setInterval(updateTimer, 1000);

    window.addEventListener('online', () => { connection.textContent = 'Đã kết nối'; connection.className = 'text-xs text-emerald-700'; });
    window.addEventListener('offline', () => { connection.textContent = 'Mất kết nối · đang giữ bản nháp'; connection.className = 'text-xs text-amber-700'; });

    shell.querySelectorAll('[data-save-answer]').forEach((button) => button.addEventListener('click', async () => {
        const card = button.closest('[data-exam-question]');
        const input = card.querySelector('[data-answer-input]');
        const state = card.querySelector('[data-answer-state]');
        const draftKey = `exam-draft-${attemptId}-${card.dataset.questionId}`;
        window.localStorage.setItem(draftKey, input.value);
        state.textContent = 'Đang lưu...';
        try {
            const response = await fetch(`/student/exams/attempts/${attemptId}/answers`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' }, body: JSON.stringify({ exam_attempt_question_id: card.dataset.questionId, answer: input.value, save_version: Number(card.dataset.saveVersion) }) });
            if (!response.ok) throw new Error('save_failed');
            const data = await response.json();
            card.dataset.saveVersion = data.save_version;
            window.localStorage.removeItem(draftKey);
            state.textContent = 'Đã lưu';
        } catch { state.textContent = 'Chưa đồng bộ · thử lại khi có mạng'; }
    }));

    shell.querySelectorAll('[data-exam-question]').forEach((card) => {
        const draft = window.localStorage.getItem(`exam-draft-${attemptId}-${card.dataset.questionId}`);
        if (draft !== null) card.querySelector('[data-answer-input]').value = draft;
    });

    shell.querySelector('[data-submit-exam]')?.addEventListener('click', async () => {
        if (!window.confirm('Bạn chắc chắn muốn nộp bài?')) return;
        const response = await fetch(`/student/exams/attempts/${attemptId}/submit`, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' } });
        feedback.textContent = response.ok ? 'Bài thi đã được nộp.' : 'Không thể nộp bài, hãy thử lại.';
    });
}
