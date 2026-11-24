const chatShell = document.querySelector('[data-chat-shell]');

if (chatShell) {
    const form = chatShell.querySelector('[data-chat-form]');
    const input = chatShell.querySelector('[data-chat-input]');
    const messages = chatShell.querySelector('[data-chat-messages]');
    const status = chatShell.querySelector('[data-chat-status]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const addMessage = (role, content) => {
        const article = document.createElement('article');
        article.className = `rounded-2xl p-4 ${role === 'assistant' ? 'bg-brand-50 text-slate-800' : 'bg-slate-100 text-slate-700'}`;
        article.innerHTML = `<p class="text-xs font-semibold uppercase tracking-wide text-slate-500">${role === 'assistant' ? 'Gia sư' : 'Bạn'}</p><p class="mt-2 text-sm leading-6"></p>`;
        article.querySelector('p:last-child').textContent = content;
        messages.append(article);
        article.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        status.textContent = 'Đang trả lời...';
        addMessage('user', input.value);
        try {
            const response = await fetch('/student/chat/messages', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ thread_id: chatShell.dataset.threadId || null, content: input.value }) });
            if (!response.ok) throw new Error('send_failed');
            const data = await response.json();
            chatShell.dataset.threadId = data.thread_id;
            addMessage('assistant', data.assistant_message.content);
            status.textContent = 'Đã trả lời';
            input.value = '';
        } catch { status.textContent = 'Có lỗi; hãy thử lại.'; }
    });
    chatShell.querySelectorAll('[data-scene-command]').forEach((button) => button.addEventListener('click', async () => {
        if (!chatShell.dataset.threadId) { status.textContent = 'Hãy gửi một câu hỏi trước.'; return; }
        const response = await fetch(`/student/chat/${chatShell.dataset.threadId}/command`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({ command: button.dataset.sceneCommand }) });
        status.textContent = response.ok ? `Đã gửi lệnh scene: ${button.dataset.sceneCommand}` : 'Lệnh scene không được phép.';
    }));
}
