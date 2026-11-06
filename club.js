// Book club session page: join / leave / end, send, like and edit messages.
document.addEventListener('DOMContentLoaded', function () {
    const room = document.getElementById('session-room');
    if (!room) return;

    const eventId = room.dataset.event;
    const list    = document.getElementById('message-list');
    const errorBox = document.getElementById('session-error');
    let editing = false;   // true while a message is being edited (pauses the auto-refresh)

    function showError(message) {
        errorBox.textContent = message || '';
        errorBox.hidden = !message;
    }

    // Send one action to session_action.php and return its JSON reply
    async function send(data) {
        data.append('event_id', eventId);
        try {
            const res = await fetch('session_action.php', { method: 'POST', body: data });
            return await res.json();
        } catch (err) {
            return { ok: false, message: 'Something went wrong. Try again.' };
        }
    }

    // Reload the message list from the server
    async function refresh() {
        if (editing) return;
        try {
            const res = await fetch('session_messages.php?id=' + encodeURIComponent(eventId));
            if (res.ok) list.innerHTML = await res.text();
        } catch (err) {
            // keep the messages that are already on the page
        }
    }

    // ---------- Join / Leave / End buttons ----------
    document.querySelectorAll('.session-actions button').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const action = btn.dataset.action;
            if (action === 'end' && !confirm('End this session for everyone? Messages will no longer be editable.')) return;

            showError('');
            const data = new FormData();
            data.append('action', action);
            const result = await send(data);
            if (result.ok) {
                window.location.reload();
            } else {
                showError(result.message);
            }
        });
    });

    // ---------- Send a message ----------
    const compose = document.getElementById('compose-form');
    if (compose) {
        compose.addEventListener('submit', async function (e) {
            e.preventDefault();
            showError('');
            const box = document.getElementById('body');
            const data = new FormData();
            data.append('action', 'post');
            data.append('body', box.value);
            const result = await send(data);
            if (result.ok) {
                box.value = '';
                await refresh();
                list.scrollTop = list.scrollHeight;
            } else {
                showError(result.message);
            }
        });
    }

    // ---------- Like and edit (buttons inside the message list) ----------
    list.addEventListener('click', async function (e) {
        const msg = e.target.closest('.msg');
        if (!msg) return;
        const target = e.target;

        if (target.classList.contains('like-btn')) {
            showError('');
            const data = new FormData();
            data.append('action', 'like');
            data.append('message_id', msg.dataset.id);
            const result = await send(data);
            if (result.ok) { refresh(); } else { showError(result.message); }
        }

        if (target.classList.contains('edit-btn')) {
            editing = true;
            const body = msg.querySelector('.msg-body');
            const tools = msg.querySelector('.msg-tools');

            const box = document.createElement('div');
            box.className = 'edit-box';
            const area = document.createElement('textarea');
            area.maxLength = 1000;
            area.value = msg.dataset.body;
            const save = document.createElement('button');
            save.type = 'button';
            save.className = 'btn save-edit';
            save.textContent = 'Save changes';
            const cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.className = 'btn cancel-edit';
            cancel.textContent = 'Cancel';
            box.append(area, save, cancel);

            body.hidden = true;
            tools.hidden = true;
            body.after(box);
            area.focus();
        }

        if (target.classList.contains('save-edit')) {
            showError('');
            const data = new FormData();
            data.append('action', 'edit');
            data.append('message_id', msg.dataset.id);
            data.append('body', msg.querySelector('.edit-box textarea').value);
            const result = await send(data);
            if (result.ok) {
                editing = false;
                refresh();
            } else {
                showError(result.message);
            }
        }

        if (target.classList.contains('cancel-edit')) {
            editing = false;
            showError('');
            refresh();
        }
    });

    // While the session is live, check for new messages every few seconds
    list.scrollTop = list.scrollHeight;
    if (room.dataset.state === 'live') {
        setInterval(refresh, 5000);
    }
});
