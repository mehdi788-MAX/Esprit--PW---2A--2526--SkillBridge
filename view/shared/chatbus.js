/**
 * ChatBus — poller singleton + cloche de notifications.
 * Architecture : un seul setInterval qui interroge api/chat.php?action=poll
 * et notifie ses abonnés via des événements. Throttle automatique sur
 * Page Visibility et inactivité utilisateur.
 */
(function () {
  'use strict';

  if (window.ChatBus) return;

  // CSS injectée une seule fois
  (function injectCss() {
    if (document.getElementById('chatbus-css')) return;
    const style = document.createElement('style');
    style.id = 'chatbus-css';
    style.textContent = ''
      + '#navmenu li#bellSlot, .navmenu li#bellSlot, .sb-bell-btn { list-style:none !important; }'
      + '#navmenu li#bellSlot .chatbus-bell, .navmenu li#bellSlot .chatbus-bell, .sb-bell-btn .chatbus-bell { display:inline-flex !important; }'
      + '#navmenu li#bellSlot .chatbus-bell-toggle, .sb-bell-btn .chatbus-bell-toggle { color:#0f0f0f !important; padding:6px !important; transition: color .2s ease, background .2s ease; }'
      + '#navmenu li#bellSlot .chatbus-bell-toggle:hover, .sb-bell-btn .chatbus-bell-toggle:hover { color:#1F5F4D !important; background: rgba(31,95,77,.08) !important; }'
      + '.chatbus-item { transition: background .15s ease; }'
      + '.chatbus-item:hover { background:#F7F4ED !important; }'
      + '.chatbus-item.unread:hover { background:rgba(31,95,77,.12) !important; }'
      + '.chatbus-mark-all { transition: opacity .2s ease; opacity:.85; }'
      + '.chatbus-mark-all:hover { opacity:1; }'
      + '@keyframes chatbus-bubble-in { from { opacity:0; transform:translateY(8px) scale(.96); } to { opacity:1; transform:none; } }'
      + '@keyframes chatbus-bubble-out { to { opacity:0; transform:scale(.92); height:0; margin:0; padding:0; overflow:hidden; } }'
      + '.message-bubble, .msg-bubble { position: relative; animation: chatbus-bubble-in .25s ease-out both; }'
      + '[data-msg-id].chatbus-removing { animation: chatbus-bubble-out .22s ease-in both; pointer-events:none; }'
      + '@keyframes chatbus-chip-in { from { opacity:0; transform:scale(.4); } to { opacity:1; transform:none; } }'
      + '.reaction-chip { animation: chatbus-chip-in .18s ease-out both; }'
      + '@keyframes chatbus-badge-pulse { 0%,100% { transform:scale(1); } 50% { transform:scale(1.45); } }'
      + '.chatbus-badge.chatbus-pulse { animation: chatbus-badge-pulse .5s ease-out 2; }'
      + '.chatbus-toast-host { position:fixed; top:84px; right:20px; z-index:3000; display:flex; flex-direction:column; gap:10px; pointer-events:none; max-width:380px; font-family:Manrope,system-ui,-apple-system,sans-serif; }'
      + '.chatbus-toast { background:#fff; border:1px solid #E8E2D5; border-radius:16px; '
      + '  box-shadow:0 20px 40px -15px rgba(31,95,77,.18),0 4px 12px -4px rgba(15,15,15,.08); '
      + '  padding:14px 16px; min-width:300px; pointer-events:auto; animation:chatbus-toast-in .3s cubic-bezier(.4,0,.2,1) both; '
      + '  display:flex; gap:12px; align-items:flex-start; cursor:pointer; transition:transform .2s ease,box-shadow .2s ease;'
      + '  border-left:3px solid #F5C842; }'
      + '.chatbus-toast:hover { transform: translateX(-3px); box-shadow:0 24px 48px -15px rgba(31,95,77,.25),0 8px 16px -4px rgba(15,15,15,.12); }'
      + '.chatbus-toast .ct-icon { flex-shrink:0; width:36px; height:36px; border-radius:10px; '
      + '  background:rgba(31,95,77,.12); color:#1F5F4D; display:flex; align-items:center; justify-content:center; }'
      + '.chatbus-toast .ct-body { flex:1; font-size:13px; color:#0f0f0f; line-height:1.45; font-weight:500; }'
      + '.chatbus-toast .ct-time { font-size:11px; color:#A3A3A3; margin-top:3px; font-weight:500; }'
      + '.chatbus-toast.leaving { animation:chatbus-toast-out .22s ease-in forwards; }'
      + '@keyframes chatbus-toast-in { from { opacity:0; transform:translateX(24px); } to { opacity:1; transform:none; } }'
      + '@keyframes chatbus-toast-out { to { opacity:0; transform:translateX(24px); height:0; margin:0; padding:0; } }'
      + '.message-bubble, .msg-bubble { position: relative; }'
      + '.msg-trigger { position:absolute; top:-10px; right:-10px; width:30px; height:30px; '
      + '  border-radius:50%; border:1.5px solid #E8E2D5; background:#fff; color:#1F5F4D; '
      + '  font-size:14px; font-weight:700; cursor:pointer; opacity:0; transition:all .18s ease; '
      + '  display:flex; align-items:center; justify-content:center; box-shadow:0 4px 10px -4px rgba(31,95,77,.25); z-index:5; }'
      + '.message-bubble:hover .msg-trigger, .msg-bubble:hover .msg-trigger { opacity:1; }'
      + '.msg-trigger:hover { background:#1F5F4D; color:#F5C842; border-color:#1F5F4D; transform:scale(1.08); }'
      + '.chatbus-msg-popover { position:absolute; z-index:2200; background:#fff; '
      + '  border:1px solid #E8E2D5; border-radius:18px; box-shadow:0 18px 40px -16px rgba(31,95,77,.22),0 4px 10px -4px rgba(15,15,15,.06); '
      + '  padding:8px; min-width:260px; font-family:Manrope,system-ui,-apple-system,sans-serif; }'
      + '.chatbus-msg-popover .msg-quickrow { display:flex; gap:4px; padding:6px; }'
      + '.chatbus-msg-popover .msg-quick { background:none; border:none; font-size:22px; '
      + '  width:40px; height:40px; border-radius:50%; cursor:pointer; transition:background .15s, transform .15s; }'
      + '.chatbus-msg-popover .msg-quick:hover { background:#F7F4ED; transform:scale(1.15); }'
      + '.chatbus-msg-popover .msg-more { font-size:18px; color:#5C5C5C; }'
      + '.chatbus-msg-popover .msg-actrow { border-top:1px solid #E8E2D5; padding-top:6px; margin-top:6px; }'
      + '.chatbus-msg-popover .msg-act { background:none; border:none; width:100%; text-align:left; '
      + '  padding:9px 12px; font-size:13px; font-weight:500; color:#0f0f0f; cursor:pointer; border-radius:10px; transition:background .15s; }'
      + '.chatbus-msg-popover .msg-act:hover { background:#F7F4ED; }'
      + '.chatbus-msg-popover .msg-act-danger { color:#DC2626; }'
      + '.chatbus-msg-popover .msg-act-danger:hover { background:#FEF2F2; }'
      + '.reactions { display:flex; flex-wrap:wrap; gap:4px; margin-top:6px; }'
      + '.reaction-chip { background:#fff; border:1px solid #E8E2D5; '
      + '  border-radius:14px; padding:2px 8px; font-size:13px; cursor:pointer; line-height:1.4; '
      + '  display:inline-flex; align-items:center; gap:4px; transition: all .15s ease; color:#0f0f0f; }'
      + '.reaction-chip.by-me { background:#E8F0EC; border-color:#1F5F4D; color:#1F5F4D; font-weight:600; }'
      + '.reaction-chip:hover { transform:scale(1.05); border-color:#1F5F4D; }'
      + '.reaction-chip .count { font-size:11px; font-weight:600; color:#5C5C5C; }'
      + '.reaction-chip.by-me .count { color:#1F5F4D; }'
      + '.message-sent .reaction-chip, .msg-sent .reaction-chip { background:rgba(255,255,255,0.95); color:#0f0f0f; border-color:rgba(255,255,255,.4); }'
      + '.message-sent .reaction-chip.by-me, .msg-sent .reaction-chip.by-me { background:#F5C842; color:#0f0f0f; border-color:#F5C842; }'
      + '.message-sent .reaction-chip.by-me .count, .msg-sent .reaction-chip.by-me .count { color:#0f0f0f; }';
    document.head.appendChild(style);
  })();

  const state = {
    apiBase: '',
    user: 0,
    conv: 0,
    sinceNotif: 0,
    activeMs: 600,
    idleMs: 5000,
    pauseAfterIdleMs: 60000,
    lastActivity: Date.now(),
    timer: null,
    inFlight: false,
    handlers: {
      message: [],
      messagesSync: [],
      typing: [],
      moderation: [],
      unread: [],
      notif: [],
      seenUpdate: [],
      reactionUpdate: [],
    },
    lastSeenForMe: 0,
    lastReactions: {},
    seenMsgIds: null, // Set, peuplé au premier poll
    firstPoll: true,
    bellEl: null,
    panelEl: null,
    badgeEl: null,
    listEl: null,
    lastUnread: -1,
    seenIds: new Set(), // pour l'animation flash uniquement
  };

  function emit(event, payload) {
    (state.handlers[event] || []).forEach(fn => {
      try { fn(payload); } catch (e) { console.warn('ChatBus handler error', event, e); }
    });
  }

  function on(event, fn) {
    if (!state.handlers[event]) state.handlers[event] = [];
    state.handlers[event].push(fn);
  }

  function url(action) {
    return state.apiBase + '?action=' + encodeURIComponent(action) + '&as_user=' + state.user;
  }

  async function poll() {
    if (state.inFlight) return;
    state.inFlight = true;
    try {
      const params = new URLSearchParams({
        conv: state.conv,
        since_notif: state.sinceNotif,
      });
      const res = await fetch(url('poll') + '&' + params.toString(), {
        credentials: 'same-origin',
        cache: 'no-store',
      });
      if (!res.ok) throw new Error('poll http ' + res.status);
      const data = await res.json();
      if (!data.success) throw new Error(data.error || 'poll failed');

      // Messages : snapshot complet de la conversation.
      // Premier poll : peuple seenMsgIds sans déclencher 'message' (le DOM est déjà rendu).
      // Polls suivants : émet 'message' uniquement pour les NOUVEAUX, et 'messagesSync'
      // pour la liste complète (le réconciliateur traite éditions + suppressions).
      if (Array.isArray(data.messages)) {
        if (!state.seenMsgIds) state.seenMsgIds = new Set();
        if (state.firstPoll) {
          data.messages.forEach(m => state.seenMsgIds.add(String(m.id_message)));
          state.firstPoll = false;
        } else {
          data.messages.forEach(m => {
            const k = String(m.id_message);
            if (!state.seenMsgIds.has(k)) {
              state.seenMsgIds.add(k);
              emit('message', m);
            }
          });
        }
        emit('messagesSync', data.messages);
      }

      // Typing
      emit('typing', data.typing_users || []);

      // Notifs nouvelles (badge flash + toast)
      if (Array.isArray(data.new_notifs) && data.new_notifs.length) {
        let hasCurrentConvNotif = false;
        data.new_notifs.forEach(n => {
          emit('notif', n);
          if (state.conv > 0 && parseInt(n.conversation_id, 10) === state.conv) {
            hasCurrentConvNotif = true;
          }
        });
        state.sinceNotif = data.last_notif_id;
        // Si la conversation active a reçu des notifs, on les marque vues
        // pour ne pas accumuler le badge pendant qu'on est dedans.
        if (hasCurrentConvNotif) {
          postForm('mark-read', { conv: state.conv }).catch(() => {});
        }
      }

      // Unread total + recent (pour le panneau)
      if (data.unread_total !== state.lastUnread) {
        state.lastUnread = data.unread_total;
        emit('unread', { total: data.unread_total, recent: data.recent_notifs || [] });
        renderBell(data.unread_total, data.recent_notifs || []);
      } else if (state.listEl) {
        renderList(data.recent_notifs || []);
      }

      // Seen update (pour la mise à jour live des coches "vu")
      if (typeof data.seen_for_me === 'number' && data.seen_for_me > state.lastSeenForMe) {
        state.lastSeenForMe = data.seen_for_me;
        emit('seenUpdate', data.seen_for_me);
      }

      // Reactions (toujours renvoyé, même vide)
      if (data.reactions !== undefined) {
        state.lastReactions = data.reactions || {};
        emit('reactionUpdate', state.lastReactions);
      }
    } catch (e) {
      // Silencieux : un échec ne doit pas casser la conversation.
      // console.debug('poll error', e);
    } finally {
      state.inFlight = false;
    }
  }

  function currentInterval() {
    if (document.hidden) return state.idleMs;
    const idleFor = Date.now() - state.lastActivity;
    if (idleFor > state.pauseAfterIdleMs) return state.idleMs * 4; // ~32s presque pause
    return state.activeMs;
  }

  function loop() {
    poll();
    state.timer = window.setTimeout(loop, currentInterval());
  }

  function bumpActivity() {
    state.lastActivity = Date.now();
  }

  // ---------------------------------------------------------
  // Actions (POST)
  // ---------------------------------------------------------
  async function postForm(action, body) {
    const fd = new URLSearchParams(body);
    fd.set('as_user', state.user);
    const res = await fetch(state.apiBase + '?action=' + encodeURIComponent(action), {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: fd.toString(),
    });
    let data = null;
    try { data = await res.json(); } catch (e) { /* ignore */ }
    return { ok: res.ok, status: res.status, data };
  }

  async function send(contenu) {
    const r = await postForm('send', { conv: state.conv, contenu });
    if (r.ok) poll();
    return r;
  }

  async function react(msgId, emoji) {
    const r = await postForm('react', { msg_id: msgId, emoji: emoji });
    if (r.ok) poll();
    return r;
  }

  async function uploadFile(file) {
    if (!file) return { ok: false, status: 0, data: { errors: ['Aucun fichier'] } };
    const fd = new FormData();
    fd.append('action', 'upload');
    fd.append('as_user', state.user);
    fd.append('conv', state.conv);
    fd.append('file', file);
    let res, data;
    try {
      res = await fetch(state.apiBase + '?action=upload', {
        method: 'POST',
        credentials: 'same-origin',
        body: fd,
      });
      try { data = await res.json(); } catch (e) { data = null; }
    } catch (e) {
      return { ok: false, status: 0, data: { errors: ['Réseau indisponible.'] } };
    }
    if (res.ok && data && data.success) poll();
    return { ok: res.ok, status: res.status, data };
  }

  let typingDebounce = 0;
  function signalTyping() {
    bumpActivity();
    const now = Date.now();
    if (now - typingDebounce < 1000) return;
    typingDebounce = now;
    postForm('typing', { conv: state.conv });
  }

  async function markConversationRead() {
    return postForm('mark-read', { conv: state.conv });
  }

  async function seen() {
    const r = await postForm('seen', { conv: state.conv });
    // Sibling tabs: refresh their bell badge / unread count
    broadcastSync({ type: 'seen', conv: state.conv });
    return r;
  }

  async function editMsg(msgId, contenu) {
    const r = await postForm('edit-msg', { msg_id: msgId, contenu });
    if (r.ok) poll();
    return r;
  }

  async function deleteMsg(msgId) {
    const r = await postForm('delete-msg', { msg_id: msgId });
    if (r.ok) poll();
    return r;
  }

  // ---------------------------------------------------------
  // Bell + dropdown rendering
  // ---------------------------------------------------------
  const SVG_BELL = '<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">'
    + '<path d="M10 18a2 2 0 002-2H8a2 2 0 002 2zM16 14V9c0-3.07-1.63-5.64-4.5-6.32V2a1.5 1.5 0 10-3 0v.68C5.64 3.36 4 5.92 4 9v5l-2 2v1h16v-1l-2-2z"/></svg>';

  function bellHtml() {
    return ''
      + '<div class="chatbus-bell" style="position:relative;">'
      + '  <a href="#" class="chatbus-bell-toggle" aria-label="Notifications" '
      + '     style="position:relative;display:inline-flex;align-items:center;justify-content:center;'
      + '            width:40px;height:40px;border-radius:50%;color:#0f172a;text-decoration:none;">'
      +     SVG_BELL
      + '    <span class="chatbus-badge" style="display:none;position:absolute;top:1px;right:1px;'
      + '          background:#F5C842;color:#0f0f0f;border-radius:999px;font-size:10px;font-weight:700;'
      + '          padding:2px 6px;line-height:1.1;border:2px solid #fff;box-shadow:0 2px 6px rgba(245,200,66,.55);">0</span>'
      + '  </a>'
      + '  <div class="chatbus-panel" style="display:none;position:absolute;right:0;top:50px;'
      + '       width:360px;background:#fff;border:1px solid #E8E2D5;border-radius:18px;'
      + '       box-shadow:0 30px 60px -25px rgba(31,95,77,.22),0 8px 20px -10px rgba(15,15,15,.1);'
      + '       z-index:1050;overflow:hidden;font-family:Manrope,system-ui,-apple-system,sans-serif;">'
      + '    <div style="padding:14px 18px;background:#1F5F4D;color:#fff;'
      + '         font-weight:700;font-size:14px;letter-spacing:-.01em;'
      + '         display:flex;justify-content:space-between;align-items:center;">'
      + '      <span style="display:inline-flex;align-items:center;gap:8px;">'
      + '        <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;'
      + '              background:rgba(255,255,255,.2);border-radius:8px;">' + SVG_BELL + '</span>'
      + '        Notifications'
      + '      </span>'
      + '      <a href="#" class="chatbus-mark-all" style="color:#fff;font-weight:600;font-size:11px;'
      + '         text-decoration:none;background:rgba(255,255,255,.18);padding:5px 10px;border-radius:999px;'
      + '         letter-spacing:.01em;">Tout marquer lu</a>'
      + '    </div>'
      + '    <div class="chatbus-list" style="max-height:380px;overflow-y:auto;background:#fff;">'
      + '      <div class="chatbus-empty" style="padding:36px 24px;text-align:center;color:#A3A3A3;font-size:13px;">'
      + '        <div style="width:54px;height:54px;border-radius:16px;background:#F7F4ED;'
      + '             display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#1F5F4D;">'
      + '          <svg width="26" height="26" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">'
      + '            <path d="M10 18a2 2 0 002-2H8a2 2 0 002 2zM16 14V9c0-3.07-1.63-5.64-4.5-6.32V2a1.5 1.5 0 10-3 0v.68C5.64 3.36 4 5.92 4 9v5l-2 2v1h16v-1l-2-2z"/>'
      + '          </svg>'
      + '        </div>'
      + '        <div style="font-weight:600;color:#0f0f0f;font-size:14px;margin-bottom:4px;">Aucune notification</div>'
      + '        <div style="font-size:12px;color:#A3A3A3;">Vos nouvelles activités apparaîtront ici.</div>'
      + '      </div>'
      + '    </div>'
      + '  </div>'
      + '</div>';
  }

  function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  const SVG_CHAT = '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'
    + '<path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>';
  const SVG_PENCIL = '<svg width="18" height="18" viewBox="0 0 24 24" fill="#f6c23e" aria-hidden="true">'
    + '<path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>';
  const SVG_TRASH = '<svg width="18" height="18" viewBox="0 0 24 24" fill="#e74a3b" aria-hidden="true">'
    + '<path d="M6 19a2 2 0 002 2h8a2 2 0 002-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>';
  const SVG_REACT = '<svg width="18" height="18" viewBox="0 0 24 24" fill="#1cc88a" aria-hidden="true">'
    + '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-3.5 6a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm7 0a1.5 1.5 0 110 3 1.5 1.5 0 010-3zM12 17.5c-2.33 0-4.31-1.46-5.11-3.5h10.22c-.8 2.04-2.78 3.5-5.11 3.5z"/></svg>';

  function notifIcon(type) {
    if (type === 'message_edited')  return SVG_PENCIL;
    if (type === 'message_deleted') return SVG_TRASH;
    if (type === 'message_reacted') return SVG_REACT;
    return SVG_CHAT;
  }

  function notifLabel(n) {
    let payload = {};
    try { payload = JSON.parse(n.payload_json || '{}'); } catch (e) {}
    const actorName = payload.actor_name
      || ((n.sender_prenom || '') + ' ' + (n.sender_nom || '')).trim()
      || 'Quelqu\'un';
    const safeActor = '<strong>' + escapeHtml(actorName) + '</strong>';
    if (n.type === 'message_edited') {
      return safeActor + ' a modifié un message : ' + escapeHtml(payload.preview || '');
    }
    if (n.type === 'message_deleted') {
      return safeActor + ' a supprimé un message.';
    }
    if (n.type === 'message_reacted') {
      return safeActor + ' a réagi avec ' + escapeHtml(payload.emoji || '👍') + '.';
    }
    return safeActor + ' : ' + escapeHtml(payload.preview || '');
  }

  function renderList(recent) {
    if (!state.listEl) return;
    if (!recent || !recent.length) {
      state.listEl.innerHTML = ''
        + '<div class="chatbus-empty" style="padding:36px 24px;text-align:center;color:#A3A3A3;font-size:13px;">'
        + '  <div style="width:54px;height:54px;border-radius:16px;background:#F7F4ED;'
        + '       display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#1F5F4D;">'
        + '    <svg width="26" height="26" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">'
        + '      <path d="M10 18a2 2 0 002-2H8a2 2 0 002 2zM16 14V9c0-3.07-1.63-5.64-4.5-6.32V2a1.5 1.5 0 10-3 0v.68C5.64 3.36 4 5.92 4 9v5l-2 2v1h16v-1l-2-2z"/>'
        + '    </svg>'
        + '  </div>'
        + '  <div style="font-weight:600;color:#0f0f0f;font-size:14px;margin-bottom:4px;">Aucune notification</div>'
        + '  <div style="font-size:12px;color:#A3A3A3;">Vos nouvelles activités apparaîtront ici.</div>'
        + '</div>';
      return;
    }
    state.listEl.innerHTML = recent.map(n => {
      const unread = !n.is_read;
      const iconBg = unread ? 'rgba(31,95,77,.12)' : '#F7F4ED';
      const iconColor = unread ? '#1F5F4D' : '#5C5C5C';
      return ''
        + '<a href="#" data-conv="' + n.conversation_id + '" class="chatbus-item' + (unread ? ' unread' : '') + '" '
        + '   style="display:flex;gap:12px;padding:12px 16px;border-bottom:1px solid #E8E2D5;'
        + '          color:#0f0f0f;text-decoration:none;align-items:flex-start;'
        + '          ' + (unread ? 'background:rgba(31,95,77,.06);' : '') + '">'
        + '  <div style="width:36px;height:36px;border-radius:10px;background:' + iconBg + ';color:' + iconColor + ';'
        + '       display:flex;align-items:center;justify-content:center;flex-shrink:0;">'
        +      notifIcon(n.type)
        + '  </div>'
        + '  <div style="flex:1;min-width:0;font-size:13px;line-height:1.4;">'
        + '    <div style="color:#0f0f0f;font-weight:' + (unread ? '600' : '500') + ';">' + notifLabel(n) + '</div>'
        + '    <div style="color:#A3A3A3;font-size:11px;margin-top:3px;font-weight:500;">' + escapeHtml(n.created_at || '') + '</div>'
        + '  </div>'
        + (unread ? '<span style="width:8px;height:8px;border-radius:50%;background:#F5C842;flex-shrink:0;margin-top:14px;box-shadow:0 0 0 3px rgba(245,200,66,.25);"></span>' : '')
        + '</a>';
    }).join('');
  }

  function renderBell(unread, recent) {
    if (!state.bellEl) return;
    if (state.badgeEl) {
      const prev = parseInt(state.badgeEl.textContent, 10) || 0;
      state.badgeEl.textContent = unread > 99 ? '99+' : unread;
      state.badgeEl.style.display = unread > 0 ? 'inline-block' : 'none';
      if (unread > prev) {
        state.badgeEl.classList.remove('chatbus-pulse');
        // force reflow then re-add to retrigger animation
        void state.badgeEl.offsetWidth;
        state.badgeEl.classList.add('chatbus-pulse');
      }
    }
    renderList(recent);
  }

  // ---------------------------------------------------------
  // Toasts
  // ---------------------------------------------------------
  function ensureToastHost() {
    let host = document.getElementById('chatbus-toast-host');
    if (!host) {
      host = document.createElement('div');
      host.id = 'chatbus-toast-host';
      host.className = 'chatbus-toast-host';
      document.body.appendChild(host);
    }
    return host;
  }

  function showToast(notif) {
    const host = ensureToastHost();
    const toast = document.createElement('div');
    toast.className = 'chatbus-toast';
    toast.innerHTML = ''
      + '<div class="ct-icon">' + notifIcon(notif.type) + '</div>'
      + '<div class="ct-body">'
      + notifLabel(notif)
      + '<div class="ct-time">à l\'instant</div>'
      + '</div>';

    // Cliquer → ouvre la conversation associée
    toast.addEventListener('click', function () {
      if (notif.conversation_id) {
        // Don't redirect if we're already in this conversation
        const inThisConv = new RegExp('[?&]id=' + notif.conversation_id + '(&|$)').test(window.location.search)
                         && /\/chat\.php$/.test(window.location.pathname);
        if (!inThisConv) {
          const href = chatHrefFor(notif.conversation_id);
          if (href) window.location.href = href;
        }
      }
      dismiss();
    });
    function dismiss() {
      toast.classList.add('leaving');
      setTimeout(() => toast.remove(), 220);
    }
    host.appendChild(toast);
    setTimeout(dismiss, 4500);
  }

  function mountFloating(opts) {
    opts = opts || {};
    let host = document.getElementById('chatbus-floating-host');
    if (!host) {
      host = document.createElement('div');
      host.id = 'chatbus-floating-host';
      host.style.cssText = 'position:fixed;top:14px;right:18px;z-index:2000;'
        + 'background:#fff;border-radius:50%;box-shadow:0 2px 10px rgba(0,0,0,0.12);';
      document.body.appendChild(host);
    }
    return mountBell(host);
  }

  // ---------------------------------------------------------
  // Real-time layers — title flicker, sound, native notifications, cross-tab sync
  // ---------------------------------------------------------
  let _origTitle = '';
  let _audioCtx = null;
  let _bc = null;
  let _suppressBcEcho = false;

  function _stripTags(html) {
    return String(html || '').replace(/<[^>]+>/g, '');
  }

  function _origDocTitle() {
    if (!_origTitle) {
      _origTitle = (document.title || 'SkillBridge').replace(/^\(\d+\+?\)\s*/, '');
    }
    return _origTitle;
  }

  function setTabTitle(unread) {
    const base = _origDocTitle();
    const n = parseInt(unread, 10) || 0;
    document.title = (n > 0 ? '(' + (n > 99 ? '99+' : n) + ') ' : '') + base;
  }

  function blip() {
    try {
      if (!_audioCtx) _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      const o = _audioCtx.createOscillator();
      const g = _audioCtx.createGain();
      o.connect(g); g.connect(_audioCtx.destination);
      o.type = 'sine';
      o.frequency.value = 880;
      g.gain.setValueAtTime(0.0001, _audioCtx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.05, _audioCtx.currentTime + 0.01);
      g.gain.exponentialRampToValueAtTime(0.0001, _audioCtx.currentTime + 0.18);
      o.start();
      o.stop(_audioCtx.currentTime + 0.2);
    } catch (e) {}
  }

  function chatHrefFor(convId) {
    if (!convId) return null;
    const path = window.location.pathname;
    if (/\/chat\//.test(path)) return 'chat.php?id=' + convId;
    if (/\/EasyFolio\//.test(path)) return '../chat/chat.php?id=' + convId;
    return 'chat.php?id=' + convId;
  }

  function ensureNotificationPermission() {
    if (typeof Notification === 'undefined') return;
    if (Notification.permission === 'default') {
      try { Notification.requestPermission().catch(() => {}); } catch (e) {}
    }
  }

  function nativeNotify(notif) {
    if (typeof Notification === 'undefined' || Notification.permission !== 'granted') return;
    if (!document.hidden) return; // tab is visible — toast is enough
    try {
      const body = _stripTags(notifLabel(notif));
      const tag  = 'sb-notif-' + (notif.id || notif.conversation_id || Date.now());
      const native = new Notification('SkillBridge', { body: body, tag: tag, renotify: false });
      native.onclick = function () {
        try { native.close(); } catch (e) {}
        window.focus();
        const href = chatHrefFor(notif.conversation_id);
        if (href) window.location.href = href;
      };
    } catch (e) {}
  }

  function broadcastSync(payload) {
    if (!_bc) return;
    try { _bc.postMessage(payload); } catch (e) {}
  }

  function setupCrossTabSync() {
    if (_bc || typeof BroadcastChannel === 'undefined' || !state.user) return;
    try {
      _bc = new BroadcastChannel('sb-chatbus-' + state.user);
      _bc.addEventListener('message', function (ev) {
        const data = ev.data || {};
        if (data.type === 'mark-read' || data.type === 'seen') {
          // Sibling tab cleared something — re-poll to pick up the new state
          poll();
        }
      });
    } catch (e) {}
  }

  let _toastsBound = false;
  function bindToastsOnce() {
    if (_toastsBound) return;
    _toastsBound = true;

    setupCrossTabSync();

    // Toast + sound + native notification on every new server-side notification
    on('notif', function (n) {
      showToast(n);
      blip();
      nativeNotify(n);
    });

    // Reflect unread count in the browser-tab title
    on('unread', function (data) {
      const total = (data && typeof data === 'object') ? (data.total || 0) : (parseInt(data, 10) || 0);
      setTabTitle(total);
    });

    // When the tab becomes visible again, clean the title hint and force a fresh poll
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) poll();
    });
  }

  function mountBell(target) {
    const el = (typeof target === 'string') ? document.querySelector(target) : target;
    if (!el) { console.warn('ChatBus.mountBell: target not found', target); return; }
    el.innerHTML = bellHtml();
    bindToastsOnce();
    state.bellEl   = el.querySelector('.chatbus-bell');
    state.badgeEl  = el.querySelector('.chatbus-badge');
    state.panelEl  = el.querySelector('.chatbus-panel');
    state.listEl   = el.querySelector('.chatbus-list');
    const toggle   = el.querySelector('.chatbus-bell-toggle');
    const markAll  = el.querySelector('.chatbus-mark-all');

    toggle.addEventListener('click', (e) => {
      e.preventDefault();
      // First user gesture is a good time to ask for native-notification permission
      ensureNotificationPermission();
      const opened = state.panelEl.style.display === 'block';
      state.panelEl.style.display = opened ? 'none' : 'block';
    });

    markAll.addEventListener('click', async (e) => {
      e.preventDefault();
      await postForm('mark-read', {});
      poll();
      broadcastSync({ type: 'mark-read' });
    });

    state.listEl.addEventListener('click', (e) => {
      const item = e.target.closest('.chatbus-item');
      if (!item) return;
      e.preventDefault();
      const conv = item.getAttribute('data-conv');
      if (conv && conv !== '0') {
        const href = chatHrefFor(conv);
        if (href) window.location.href = href;
      }
    });

    // Fermer si clic à l'extérieur
    document.addEventListener('click', (e) => {
      if (!state.bellEl) return;
      if (!state.bellEl.contains(e.target)) {
        state.panelEl.style.display = 'none';
      }
    });
  }

  // ---------------------------------------------------------
  // Emoji picker (tout-en-un, sans dépendance)
  // ---------------------------------------------------------
  const EMOJIS = [
    // Smileys
    '😀','😃','😄','😁','😆','😅','🤣','😂','🙂','🙃',
    '😉','😊','😇','🥰','😍','🤩','😘','😗','☺️','😚',
    '😙','🥲','😋','😛','😜','🤪','😝','🤑','🤗','🤭',
    '🤫','🤔','🤐','🤨','😐','😑','😶','😏','😒','🙄',
    '😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕',
    '🤢','🤮','🥵','🥶','🥴','😵','🤯','🤠','🥳','🥸',
    '😎','🤓','🧐','😕','😟','🙁','☹️','😮','😯','😲',
    '😳','🥺','😦','😧','😨','😰','😥','😢','😭','😱',
    '😖','😣','😞','😓','😩','😫','🥱','😤','😡','😠',
    '🤬','😈','👿','💀','☠️','💩','🤡','👹','👺','👻',
    // Hands & gestures
    '👍','👎','👌','✌️','🤞','🤟','🤘','🤙','👈','👉',
    '👆','👇','☝️','✋','🤚','🖐️','🖖','👋','🤝','🙏',
    '✍️','💪','🦾','👏','🙌','👐','🤲','🤜','🤛',
    // Hearts & symbols
    '❤️','🧡','💛','💚','💙','💜','🤎','🖤','🤍','💔',
    '❤️‍🔥','❤️‍🩹','💕','💞','💓','💗','💖','💘','💝','💟',
    '✨','⭐','🌟','💫','💥','🔥','🎉','🎊','🎁','🏆',
    '💯','✅','❌','⚠️','❓','❗','💡','💎','🔔','🔕',
    // Work & freelance
    '💼','💰','💵','💳','📈','📉','📊','📝','📌','📎',
    '📁','📂','🗂️','📅','📆','📇','📋','📃','📄','📑',
    '✉️','📧','📨','📩','📤','📥','📞','📱','💻','🖥️',
    '⌨️','🖱️','🖨️','🕒','⏰','⌛','⏳','🚀','🎯','🆗'
  ];

  function installEmojiPicker(opts) {
    opts = opts || {};
    const buttons = document.querySelectorAll(opts.buttonSelector || '[data-emoji-target]');
    buttons.forEach(btn => {
      const targetSel = btn.getAttribute('data-emoji-target') || opts.textarea;
      const target = document.querySelector(targetSel);
      if (!target) return;
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        openEmojiPicker(btn, target);
      });
    });
  }

  function openEmojiPickerForCallback(anchor, callback) {
    openEmojiPicker(anchor, null, callback);
  }

  function openEmojiPicker(anchor, textarea, onPick) {
    let panel = document.getElementById('chatbus-emoji-panel');
    if (panel) { panel.remove(); }
    panel = document.createElement('div');
    panel.id = 'chatbus-emoji-panel';
    panel.style.cssText = 'position:absolute;z-index:2100;background:#fff;border:1px solid #e3e6f0;'
      + 'border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.12);padding:8px;'
      + 'display:grid;grid-template-columns:repeat(10,1fr);gap:2px;width:340px;max-height:280px;overflow-y:auto;';
    panel.innerHTML = EMOJIS.map(e =>
      '<button type="button" class="chatbus-emoji" '
      + 'style="background:none;border:none;font-size:20px;line-height:1.5;padding:2px;cursor:pointer;border-radius:6px;">'
      + e + '</button>'
    ).join('');
    document.body.appendChild(panel);
    const r = anchor.getBoundingClientRect();
    panel.style.top = (window.scrollY + r.bottom + 6) + 'px';
    panel.style.left = (window.scrollX + Math.max(8, r.right - 340)) + 'px';

    panel.addEventListener('click', (e) => {
      const btn = e.target.closest('.chatbus-emoji');
      if (!btn) return;
      e.preventDefault();
      const emoji = btn.textContent;
      if (typeof onPick === 'function') {
        onPick(emoji);
        panel.remove();
        return;
      }
      const start = textarea.selectionStart || 0;
      const end = textarea.selectionEnd || 0;
      const v = textarea.value;
      textarea.value = v.slice(0, start) + emoji + v.slice(end);
      textarea.dispatchEvent(new Event('input', { bubbles: true }));
      textarea.focus();
      textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
    });

    setTimeout(() => {
      const close = (e) => {
        if (panel && !panel.contains(e.target) && e.target !== anchor) {
          panel.remove();
          document.removeEventListener('click', close, true);
        }
      };
      document.addEventListener('click', close, true);
    }, 0);
  }

  // ---------------------------------------------------------
  // Message action menu (style Messenger : ⋯ → React / Modifier / Supprimer)
  // ---------------------------------------------------------
  const QUICK_REACTIONS = ['👍','❤️','😂','😮','😢','🔥'];

  function installMessageMenu(opts) {
    const container = (typeof opts.container === 'string') ? document.querySelector(opts.container) : opts.container;
    if (!container) return;
    const me = parseInt(opts.currentUser, 10) || 0;
    const bubbleSelector = opts.bubbleSelector || '.message-bubble, .msg-bubble';
    const editIconHtml = opts.editIcon || '<i class="fas fa-pencil-alt"></i>';
    const delIconHtml  = opts.deleteIcon || '<i class="fas fa-trash"></i>';

    function decorate(bubble) {
      if (bubble.querySelector('.msg-trigger')) return;
      const wrap = bubble.closest('[data-msg-id]');
      if (!wrap) return;
      const msgId = wrap.getAttribute('data-msg-id');
      if (!msgId || msgId.indexOf('opt-') === 0) return; // pas sur les optimistes
      const isMine = bubble.classList.contains('message-sent') || bubble.classList.contains('msg-sent');
      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'msg-trigger';
      trigger.title = 'Plus';
      trigger.innerHTML = '<i class="fas fa-ellipsis-h"></i>';
      bubble.appendChild(trigger);
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openPopover(trigger, { msgId: msgId, isMine: isMine });
      });
    }

    function openPopover(anchor, ctx) {
      let pop = document.getElementById('chatbus-msg-popover');
      if (pop) pop.remove();
      pop = document.createElement('div');
      pop.id = 'chatbus-msg-popover';
      pop.className = 'chatbus-msg-popover';
      const quick = QUICK_REACTIONS.map(e =>
        '<button type="button" class="msg-quick" data-emoji="' + e + '">' + e + '</button>'
      ).join('');
      const more = '<button type="button" class="msg-quick msg-more" title="Plus d\'emojis">+</button>';
      const editBtn = ctx.isMine
        ? '<button type="button" class="msg-act" data-act="edit">' + editIconHtml + ' Modifier</button>'
        : '';
      const delBtn = ctx.isMine
        ? '<button type="button" class="msg-act msg-act-danger" data-act="delete">' + delIconHtml + ' Supprimer</button>'
        : '';
      pop.innerHTML = '<div class="msg-quickrow">' + quick + more + '</div>'
        + (ctx.isMine ? '<div class="msg-actrow">' + editBtn + delBtn + '</div>' : '');
      // On place hors écran le temps de mesurer
      pop.style.top = '-9999px';
      pop.style.left = '-9999px';
      document.body.appendChild(pop);

      const r = anchor.getBoundingClientRect();
      const popW = pop.offsetWidth || 260;
      const popH = pop.offsetHeight || 90;
      const viewW = window.innerWidth;
      const viewH = window.innerHeight;

      // Pour une bulle envoyée (à droite) : on aligne le bord DROIT du popover
      // sur le bord droit du trigger. Pour une bulle reçue (à gauche) : on aligne
      // le bord GAUCHE du popover sur le bord gauche du trigger.
      let left = ctx.isMine
        ? (r.right - popW)
        : r.left;
      // Clamp dans la fenêtre visible
      if (left < 8) left = 8;
      if (left + popW > viewW - 8) left = viewW - popW - 8;

      // Verticalement : sous le trigger, sauf s'il n'y a pas la place → au-dessus
      let top = r.bottom + 6;
      if (top + popH > viewH - 8) top = Math.max(8, r.top - popH - 6);

      pop.style.top  = (window.scrollY + top) + 'px';
      pop.style.left = (window.scrollX + left) + 'px';

      pop.addEventListener('click', function (e) {
        const q = e.target.closest('.msg-quick');
        const a = e.target.closest('.msg-act');
        if (q) {
          e.preventDefault();
          if (q.classList.contains('msg-more')) {
            openEmojiPicker(q, null, function (emo) { react(ctx.msgId, emo); });
            pop.remove();
          } else {
            react(ctx.msgId, q.getAttribute('data-emoji'));
            pop.remove();
          }
        } else if (a) {
          e.preventDefault();
          const act = a.getAttribute('data-act');
          if (act === 'edit') {
            const wrap = container.querySelector('[data-msg-id="' + ctx.msgId + '"]');
            const body = wrap && wrap.querySelector('.msg-body');
            const cur = body ? body.innerText : '';
            const v = window.prompt('Modifier le message :', cur);
            if (v !== null && v.trim() !== '') {
              editMsg(ctx.msgId, v.trim()).then(r => {
                if (r.ok && r.data && r.data.success && body) {
                  body.innerHTML = escapeHtml(v.trim()).replace(/\n/g, '<br>');
                }
              });
            }
          } else if (act === 'delete') {
            if (window.confirm('Supprimer ce message ?')) {
              deleteMsg(ctx.msgId).then(r => {
                if (r.ok && r.data && r.data.success) {
                  const wrap = container.querySelector('[data-msg-id="' + ctx.msgId + '"]');
                  if (wrap) wrap.remove();
                }
              });
            }
          }
          pop.remove();
        }
      });

      setTimeout(() => {
        const close = (e) => {
          if (pop && !pop.contains(e.target) && e.target !== anchor) {
            pop.remove();
            document.removeEventListener('click', close, true);
          }
        };
        document.addEventListener('click', close, true);
      }, 0);
    }

    container.querySelectorAll(bubbleSelector).forEach(decorate);
    const obs = new MutationObserver(function (muts) {
      muts.forEach(function (m) {
        m.addedNodes.forEach(function (n) {
          if (n.nodeType !== 1) return;
          if (n.matches && n.matches(bubbleSelector)) decorate(n);
          if (n.querySelectorAll) n.querySelectorAll(bubbleSelector).forEach(decorate);
        });
      });
    });
    obs.observe(container, { childList: true, subtree: true });
  }

  // ---------------------------------------------------------
  // Messages reconciler (synchronise le DOM avec le snapshot serveur)
  // → propage en live les éditions et suppressions vers tous les onglets
  // ---------------------------------------------------------
  function installMessagesReconciler(opts) {
    const container = (typeof opts.container === 'string') ? document.querySelector(opts.container) : opts.container;
    if (!container) return;
    const render = opts.render;
    if (typeof render !== 'function') {
      console.warn('installMessagesReconciler: render(msg) is required');
      return;
    }
    const onAppend = opts.onAppend || function () {};

    function applyEdit(wrap, msg) {
      // Seuls les messages texte sont éditables — les images/fichiers/devis sont immuables.
      if (msg.type && msg.type !== 'text') return;
      const body = wrap.querySelector('.msg-body');
      if (!body) return;
      if (body.textContent === msg.contenu) return;
      const escaped = msg.contenu == null ? '' : String(msg.contenu)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
      body.innerHTML = escaped.replace(/\n/g, '<br>');
    }

    on('messagesSync', function (allMsgs) {
      const seen = {};
      allMsgs.forEach(function (msg) {
        const id = String(msg.id_message);
        seen[id] = true;
        const wrap = container.querySelector('[data-msg-id="' + id + '"]');
        if (!wrap) {
          // Nouveau message : appendMessage devrait déjà l'avoir traité via 'message' event,
          // mais on garde le filet de sécurité.
          if (!container.querySelector('[data-msg-id="' + id + '"]')) {
            const node = render(msg);
            if (node) {
              container.appendChild(node);
              onAppend(node, msg);
            }
          }
        } else {
          applyEdit(wrap, msg);
        }
      });
      // Suppressions : tout ce qui est dans le DOM mais plus dans le snapshot
      container.querySelectorAll('[data-msg-id]').forEach(function (wrap) {
        const id = wrap.getAttribute('data-msg-id');
        if (!id) return;
        if (id.indexOf('opt-') === 0) return; // ignore les bulles optimistes
        if (!seen[id] && !wrap.classList.contains('chatbus-removing')) {
          wrap.classList.add('chatbus-removing');
          setTimeout(function () { wrap.remove(); }, 240);
        }
      });
    });
  }

  // ---------------------------------------------------------
  // Reaction renderer (chips sous chaque bulle, live update)
  // ---------------------------------------------------------
  function installReactionRenderer(opts) {
    const container = (typeof opts.container === 'string') ? document.querySelector(opts.container) : opts.container;
    if (!container) return;
    const me = parseInt(opts.currentUser, 10) || 0;

    let lastFingerprint = ''; // évite de réanimer les chips à chaque poll

    function fingerprint(map) {
      // Sérialisation déterministe (clés triées) pour détecter le moindre changement
      const keys = Object.keys(map || {}).map(Number).sort((a, b) => a - b);
      return keys.map(k => {
        const arr = (map[k] || []).slice().sort((a, b) =>
          (a.user_id - b.user_id) || (a.emoji < b.emoji ? -1 : a.emoji > b.emoji ? 1 : 0));
        return k + ':' + arr.map(r => r.user_id + r.emoji).join(',');
      }).join('|');
    }

    function applyAll(map) {
      const fp = fingerprint(map);
      if (fp === lastFingerprint) return; // rien n'a changé, on ne touche pas au DOM
      lastFingerprint = fp;
      const seenIds = {};
      Object.keys(map || {}).forEach(function (mid) {
        seenIds[mid] = true;
        const wrap = container.querySelector('[data-msg-id="' + mid + '"]');
        if (!wrap) return;
        const list = map[mid];
        const grouped = {};
        list.forEach(function (r) {
          if (!grouped[r.emoji]) grouped[r.emoji] = { count: 0, byMe: false };
          grouped[r.emoji].count += 1;
          if (r.user_id === me) grouped[r.emoji].byMe = true;
        });
        let div = wrap.querySelector('.reactions');
        if (!div) {
          div = document.createElement('div');
          div.className = 'reactions';
          // Insérer à l'intérieur de la bulle, à la fin
          const bubble = wrap.querySelector('.message-bubble, .msg-bubble');
          (bubble || wrap).appendChild(div);
        }
        div.innerHTML = Object.keys(grouped).map(function (emo) {
          const g = grouped[emo];
          return '<button type="button" class="reaction-chip' + (g.byMe ? ' by-me' : '') + '" '
            + 'data-emoji="' + emo + '" data-msg-id="' + mid + '">'
            + emo + ' <span class="count">' + g.count + '</span></button>';
        }).join('');
      });
      // Nettoyer les bulles qui n'ont plus aucune réaction
      container.querySelectorAll('.reactions').forEach(function (div) {
        const wrap = div.closest('[data-msg-id]');
        if (!wrap) return;
        const id = wrap.getAttribute('data-msg-id');
        if (!seenIds[id]) div.remove();
      });
    }

    container.addEventListener('click', function (e) {
      const chip = e.target.closest('.reaction-chip');
      if (!chip) return;
      e.preventDefault();
      react(chip.getAttribute('data-msg-id'), chip.getAttribute('data-emoji'));
    });

    on('reactionUpdate', applyAll);
    // Premier rendu (depuis l'état actuel s'il y en a déjà)
    if (state.lastReactions && Object.keys(state.lastReactions).length) {
      applyAll(state.lastReactions);
    }
  }

  // ---------------------------------------------------------
  // Composer : heartbeat de saisie tant que la zone n'est pas vide
  // ---------------------------------------------------------
  function installComposer(opts) {
    const ta = (typeof opts.textarea === 'string') ? document.querySelector(opts.textarea) : opts.textarea;
    if (!ta) return;
    let hb = null;
    function start() {
      if (hb) return;
      signalTyping();
      hb = setInterval(function () {
        if (ta.value && ta.value.trim() !== '') signalTyping();
        else stop();
      }, 2000);
    }
    function stop() {
      if (hb) { clearInterval(hb); hb = null; }
    }
    ta.addEventListener('input', function () {
      if (ta.value && ta.value.trim() !== '') start();
      else stop();
    });
    if (opts.form) {
      const f = (typeof opts.form === 'string') ? document.querySelector(opts.form) : opts.form;
      if (f) f.addEventListener('submit', stop);
    }
    return { stop: stop };
  }

  // ---------------------------------------------------------
  // Init
  // ---------------------------------------------------------
  function init(opts) {
    state.apiBase = opts.apiBase || '/api/chat.php';
    state.user    = parseInt(opts.user, 10) || 0;
    state.conv    = parseInt(opts.conv, 10) || 0;
    state.sinceNotif = parseInt(opts.sinceNotif || 0, 10);
    if (opts.activeMs) state.activeMs = opts.activeMs;
    if (opts.idleMs)   state.idleMs = opts.idleMs;

    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) bumpActivity();
    });
    ['mousemove', 'keydown', 'scroll', 'touchstart'].forEach(evt =>
      window.addEventListener(evt, bumpActivity, { passive: true }));

    if (state.timer) window.clearTimeout(state.timer);
    loop();
  }

  window.ChatBus = {
    init, on,
    send, react, uploadFile,
    signalTyping, markConversationRead, seen,
    editMsg, deleteMsg,
    mountBell, mountFloating,
    installEmojiPicker, installMessageMenu, installReactionRenderer,
    installComposer, installMessagesReconciler,
    forcePoll: poll,
    state,
  };
})();
