const sceneDefinitions = {
    radar: { label: 'Radar và đường bay', description: 'Máy bay tiến vào vùng kiểm soát của radar.', color: '#fb7185' },
    ant: { label: 'Kiến bò trong hộp', description: 'Đường đi được mở ra trên mặt phẳng để tìm đường ngắn nhất.', color: '#f59e0b' },
    graph: { label: 'Đồ thị chuyển động', description: 'Điểm chạy trên đồ thị theo tham số thời gian.', color: '#38bdf8' },
    surface: { label: 'Mặt cong và tiếp tuyến', description: 'Tiếp tuyến thay đổi theo vị trí trên mặt cong.', color: '#a78bfa' },
};

const sceneMarkup = (type, progress) => {
    const x = 12 + progress * 76;
    const y = type === 'radar' ? 72 - progress * 42 : 72 - Math.sin(progress * Math.PI) * 42;

    return `<svg viewBox="0 0 100 70" role="img" aria-label="${sceneDefinitions[type].label}" class="h-full w-full">
        <defs><pattern id="grid-${type}" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="#64748b" stroke-width=".25"/></pattern></defs>
        <rect width="100" height="70" fill="url(#grid-${type})" opacity=".5"/>
        ${type === 'radar' ? '<circle cx="50" cy="35" r="20" fill="#6366f1" fill-opacity=".18" stroke="#a5b4fc"/><text x="48" y="37" fill="white" font-size="4">R</text>' : ''}
        ${type === 'ant' ? '<path d="M15 58 H70 V18 H85" fill="none" stroke="#fbbf24" stroke-width="1.5" stroke-dasharray="3 2"/><rect x="68" y="16" width="18" height="44" fill="none" stroke="#94a3b8"/>' : ''}
        ${type === 'graph' ? '<path d="M8 58 C22 50 25 18 42 34 S67 58 92 12" fill="none" stroke="#38bdf8" stroke-width="1.5"/>' : ''}
        ${type === 'surface' ? '<path d="M8 50 Q50 5 92 50 M8 38 Q50 -7 92 38 M8 26 Q50 -19 92 26" fill="none" stroke="#a78bfa" stroke-width=".8"/>' : ''}
        <circle cx="${x}" cy="${y}" r="2.6" fill="${sceneDefinitions[type].color}" stroke="white" stroke-width=".8"><title>Điểm chuyển động</title></circle>
    </svg>`;
};

function mountScene(root) {
    const stage = root.querySelector('[data-scene-stage]');
    const progressInput = root.querySelector('[data-scene-progress]');
    const progressLabel = root.querySelector('[data-scene-progress-label]');
    const title = root.querySelector('[data-scene-title]');
    const description = root.querySelector('[data-scene-description]');
    const status = root.querySelector('[data-scene-status]');
    let type = root.dataset.sceneType || 'radar';
    let progress = Number(progressInput?.value || 0) / 100;
    let playing = false;
    let frame;

    const render = () => {
        stage.innerHTML = sceneMarkup(type, progress);
        progressLabel.textContent = `${Math.round(progress * 100)}%`;
        title.textContent = sceneDefinitions[type].label;
        description.textContent = sceneDefinitions[type].description;
        status.textContent = playing ? 'Đang chạy' : 'Tạm dừng';
    };
    const tick = (time) => {
        if (!playing) return;
        progress = (progress + ((time % 10000) / 1000000)) % 1;
        progressInput.value = String(Math.round(progress * 100));
        render();
        frame = requestAnimationFrame(tick);
    };
    const stop = () => { playing = false; cancelAnimationFrame(frame); render(); };

    root.querySelectorAll('[data-scene-type]').forEach((button) => button.addEventListener('click', () => {
        type = button.dataset.sceneType;
        root.dataset.sceneType = type;
        render();
    }));
    root.querySelector('[data-scene-play]')?.addEventListener('click', () => {
        playing = !playing;
        if (playing) frame = requestAnimationFrame(tick); else stop();
        render();
    });
    root.querySelector('[data-scene-reset]')?.addEventListener('click', () => { stop(); progress = 0; progressInput.value = '0'; render(); });
    progressInput?.addEventListener('input', (event) => { stop(); progress = Number(event.target.value) / 100; render(); });
    render();
}

document.querySelectorAll('[data-scene-runtime]').forEach(mountScene);
