// fetch csrf token and set up auto-injection into forms and fetch headers
(function(){
  function safeFetchToken(){
    return fetch('csrf.php?token=1').then(r => r.json()).catch(()=>null);
  }

  safeFetchToken().then(j => {
    if (!j || !j.token) return;
    window.CSRF_TOKEN = j.token;

// inject csrf token into all post forms. nice timesaver
    document.querySelectorAll('form[method="POST"]').forEach(f => {
      if (!f.querySelector('input[name="csrf_token"]')) {
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'csrf_token'; inp.value = window.CSRF_TOKEN;
        f.appendChild(inp);
      }
    });

    // auto-inject csrf token into fetch headers. bit hacky but it works and saves a lot of time since i dont have to worry about it at all in the frontend code. 
    const _fetch = window.fetch.bind(window);
    window.fetch = function(resource, init){
      init = init || {};
      init.headers = init.headers || {};
      // handle both headers and plain object cases 
      try{
        if (init.headers instanceof Headers) {
          if (!init.headers.get('X-CSRF-Token')) init.headers.set('X-CSRF-Token', window.CSRF_TOKEN);
        } else {
          if (!init.headers['X-CSRF-Token']) init.headers['X-CSRF-Token'] = window.CSRF_TOKEN;
        }
      } catch(e){}
      return _fetch(resource, init);
    };
  }).catch(()=>{});
})();

function escHtml(str) {
  return String(str)
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;');
}

// nav account dropdown 
const navLinks = document.querySelector('.nav-links');
if (navLinks && !document.getElementById('nav-login-link')) {
  navLinks.insertAdjacentHTML('beforeend', '<a href="login.php" id="nav-login-link">Login</a>');
}

fetch('session_check.php')
  .then(r => r.json())
  .then(session => {
    const navLinks = document.querySelector('.nav-links');
    if (!navLinks) return;

    const loginLink = document.getElementById('nav-login-link');
    if (session.loggedIn) {
      if (loginLink) loginLink.remove();
      const existingDropdown = document.querySelector('.account-dropdown');
      if (existingDropdown) existingDropdown.remove();
      const adminLink = session.role === 'admin'
        ? `<a href="admin_comments.php">🛡 Admin</a>`
        : '';

      navLinks.insertAdjacentHTML('beforeend', `
        <div class="dropdown account-dropdown">
          <a href="#" class="dropbtn" style="display:flex;align-items:center;gap:4px;">
            <img src="${escHtml(session.avatar)}"
                 class="nav-avatar"
                 width="28"
                 height="28"
                 onerror="this.src='Images/default_avatar.svg'">
            ${escHtml(session.username)} ▾
          </a>
          <div class="dropdown-content">
            <a href="profile.php">👤 Profilul meu</a>
            ${adminLink}
            <a href="logout.php">🚪 Deconectare</a>
          </div>
        </div>
      `);

// autocomplete comment name input with username and make it readonly if logged in. pain to set up otherwise

      const nameInput = document.querySelector('.wiki-comments input[name="name"]');
      if (nameInput) {
        nameInput.value    = session.username;
        nameInput.readOnly = true;
        nameInput.style.background = '#f5f5f5';
      }

    } else {
      if (!loginLink) {
        navLinks.insertAdjacentHTML('beforeend', `<a href="login.php" id="nav-login-link">Login</a>`);
      }
    }
  })
  .catch(() => {
    const navLinks = document.querySelector('.nav-links');
    if (navLinks && !document.getElementById('nav-login-link')) {
      navLinks.insertAdjacentHTML('beforeend', `<a href="login.php" id="nav-login-link">Login</a>`);
    }
  });

// accessibility improvements and dropdown keyboard handling
(function(){
  if (!document.querySelector('.skip-link')) {
    const skip = document.createElement('a');
    skip.href = '#main-content';
    skip.className = 'skip-link';
    skip.textContent = 'Skip to content';
    document.body.insertBefore(skip, document.body.firstChild);
    const main = document.querySelector('main') || document.querySelector('.layout') || document.body;
    if (main && !main.id) main.id = 'main-content';
  }

  // ensure nav has ARIA role. who came up with this stuff
  const nav = document.querySelector('nav.site-nav');
  if (nav) nav.setAttribute('role', 'navigation');

  // dropdown accessibility
  document.querySelectorAll('.dropdown').forEach(drop => {
    const btn = drop.querySelector('.dropbtn') || drop.querySelector('a');
    const menu = drop.querySelector('.dropdown-content');
    if (!btn || !menu) return;
    btn.setAttribute('aria-haspopup','true');
    btn.setAttribute('aria-expanded','false');
    btn.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); btn.click(); }
      if (e.key === 'ArrowDown') { e.preventDefault(); const first = menu.querySelector('a'); if (first) first.focus(); }
      if (e.key === 'Escape') { btn.focus(); btn.setAttribute('aria-expanded','false'); }
    });
    btn.addEventListener('click', () => {
      const expanded = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', String(!expanded));
    });
  });
})();


// comment handing
function getCommentPage() {
  const controls = document.querySelector('.comment-controls');
  return controls ? controls.dataset.page : null;
}

function getCommentSort() {
  const select = document.getElementById('commentSortOrder');
  return select ? select.value : 'date_desc';
}

function reloadComments(page, sort = 'date_desc') {
  const container = document.getElementById('comments');
  if (!container || !page) return;
  fetch('display_comments.php?page=' + encodeURIComponent(page) + '&sort=' + encodeURIComponent(sort))
    .then(res => res.text())
    .then(html => {
      container.innerHTML = html;
    })
    .catch(() => {
      console.error('Nu s-au putut reîncărca comentariile.');
    });
}

document.addEventListener('change', function (e) {
  if (e.target.matches('#commentSortOrder')) {
    const page = getCommentPage();
    if (page) {
      reloadComments(page, e.target.value);
    }
  }
});

document.addEventListener('click', function (e) {

  // this is where we show the edit form
  if (e.target.matches('.comment-edit-btn')) {
    const id = e.target.dataset.id;
    document.getElementById('text-' + id).style.display     = 'none';
    document.getElementById('editform-' + id).style.display = 'block';
    e.target.style.display = 'none';
  }

  // cancel edit
  if (e.target.matches('.comment-cancel-btn')) {
    const id = e.target.dataset.id;
    document.getElementById('text-' + id).style.display     = 'block';
    document.getElementById('editform-' + id).style.display = 'none';
    const editBtn = document.querySelector('.comment-edit-btn[data-id="' + id + '"]');
    if (editBtn) editBtn.style.display = '';
  }

  // save edit
  if (e.target.matches('.comment-save-btn')) {
    const id   = e.target.dataset.id;
    const text = document.getElementById('edittextarea-' + id).value.trim();
    if (!text) return;

    e.target.disabled = true;
    e.target.textContent = 'Se salvează...';

    fetch('edit_comment.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ id, text })
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        const textEl = document.getElementById('text-' + id);
        textEl.innerHTML    = res.html;
        textEl.style.display = 'block';
        document.getElementById('editform-' + id).style.display = 'none';
        const editBtn = document.querySelector('.comment-edit-btn[data-id="' + id + '"]');
        if (editBtn) { editBtn.style.display = ''; }

        // mark as edited. even if the comment stays unchanged after editing, 
        // it should still get the "(editat)" tag to indicate that it was reviewed by the author.

        const meta = document.querySelector('#comment-' + id + ' .comment-meta span');
        if (meta && !meta.innerHTML.includes('editat')) {
          meta.innerHTML += ' <span class="edited-tag">(editat)</span>';
        }
      } else {
        alert('Eroare: ' + (res.error || 'necunoscut'));
        e.target.disabled = false;
        e.target.textContent = '💾 Salvează';
      }
    })
    .catch(() => {
      alert('Eroare de rețea.');
      e.target.disabled = false;
      e.target.textContent = '💾 Salvează';
    });
  }

  // delete comment
  if (e.target.matches('.comment-delete-btn')) {
    const id = e.target.dataset.id;
    if (!confirm('Ești sigur că vrei să ștergi acest comentariu?')) return;

    fetch('delete_comment.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ id })
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        const el = document.getElementById('comment-' + id);
        if (el) {
          el.style.transition = 'opacity 0.3s';
          el.style.opacity    = '0';
          setTimeout(() => el.remove(), 300);
        }
      } else {
        alert('Eroare: ' + (res.error || 'necunoscut'));
      }
    });
  }

// toggle comment collapse
  const toggleBtn = e.target.closest('.comment-toggle-btn');
  if (toggleBtn) {
    const id = toggleBtn.dataset.id;
    const item = document.getElementById('comment-' + id);
    if (!item) return;
    const collapsed = item.classList.toggle('comment-collapsed');
    toggleBtn.textContent = collapsed ? 'Arată' : 'Ascunde';
    return;
  }

  // like / unlike comment
  const likeBtn = e.target.closest('.comment-like-btn');
  if (likeBtn) {
    const id = likeBtn.dataset.id;
    const page = getCommentPage();
    if (!page) return;

    fetch('comment_like.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    })
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        alert(res.error || 'Nu s-a putut actualiza like-ul.');
        return;
      }
      likeBtn.classList.toggle('liked', res.liked);
      likeBtn.dataset.liked = res.liked ? '1' : '0';
      const countSpan = likeBtn.querySelector('.like-count');
      if (countSpan) countSpan.textContent = res.likes;
    })
    .catch(() => {
      alert('Eroare de rețea.');
    });
    return;
  }

  // reply form. not the best but it works
  const replyBtn = e.target.closest('.comment-reply-btn');
  if (replyBtn) {
    const id = replyBtn.dataset.id;
    const form = document.getElementById('replyform-' + id);
    if (form) {
      form.style.display = form.style.display === 'block' ? 'none' : 'block';
    }
    return;
  }

  // cancel reply. can you tell its cancel reply from the replycancel? wwow
  const replyCancel = e.target.closest('.comment-reply-cancel-btn');
  if (replyCancel) {
    const id = replyCancel.dataset.id;
    const form = document.getElementById('replyform-' + id);
    if (form) {
      form.style.display = 'none';
    }
    return;
  }

  // submit the reply. again, not the best but it works. 
  // ideally this would be a SINGLE form but it was easier to implement this way given the current structure.
  const replySubmit = e.target.closest('.comment-reply-submit-btn');
  if (replySubmit) {
    const parentId = replySubmit.dataset.id;
    const form = document.getElementById('replyform-' + parentId);
    if (!form) return;

    const textarea = form.querySelector('.reply-textarea');
    const nameInput = form.querySelector('.reply-name-input');
    const message = textarea ? textarea.value.trim() : '';
    const name = nameInput ? nameInput.value.trim() : '';
    const page = getCommentPage();
    const sort = getCommentSort();

    if (!page) return;
    if (!message) {
      alert('Completează mesajul pentru răspuns.');
      return;
    }
    if (!name) {
      alert('Completează numele tău.');
      return;
    }

    e.target.disabled = true;
    e.target.textContent = '⏳ Trimit...';

    const formData = new URLSearchParams();
    formData.append('name', name);
    formData.append('message', message);
    formData.append('page', page);
    formData.append('parent_id', parentId);

    fetch('submit_comment.php', {
      method: 'POST',
      body: formData
    })
    .then(() => {
      reloadComments(page, sort);
    })
    .catch(() => {
      alert('Eroare de rețea.');
    })
    .finally(() => {
      e.target.disabled = false;
      e.target.textContent = 'Trimite';
    });
  }
});


// quiz score saving and new highscore toast
window.saveQuizScore = function (score, total) {
  fetch('save_score.php', {
    method:  'POST',
    headers: { 'Content-Type': 'application/json' },
    body:    JSON.stringify({ score, total })
  })
  .then(r => r.json())
  .then(res => {
    if (res.success && res.is_new_highscore) {
      const toast = document.createElement('div');
      toast.textContent = '🏆 Nou highscore: ' + res.score + '%!';
      Object.assign(toast.style, {
        position:'fixed', bottom:'30px', right:'20px',
        background:'#982000', color:'white',
        padding:'12px 18px', borderRadius:'12px',
        fontFamily:'Verdana, sans-serif', fontSize:'14px',
        boxShadow:'0 4px 16px rgba(0,0,0,0.2)',
        zIndex:'9999', animation:'fadeInSite 0.4s ease'
      });
      document.body.appendChild(toast);
      setTimeout(() => toast.remove(), 3500);
    }
  })
  .catch(() => {}); // fail if not logged in or network error, or whatever else might happen. 
  // not THAT big of a big deal.
};
