(function () {
  const config = window.slinganRsvpModal;
  if (!config) {
    return;
  }

  const dialog = document.getElementById('slingan-rsvp-modal');
  if (!dialog || typeof dialog.showModal !== 'function') {
    return;
  }

  const titleEl = dialog.querySelector('#slingan-rsvp-modal-title');
  const scheduleEl = dialog.querySelector('.slingan-rsvp-modal__schedule');
  const bodyEl = dialog.querySelector('[data-slingan-rsvp-body]');
  const articleLink = dialog.querySelector('[data-slingan-rsvp-article]');
  const cache = new Map();

  function closeModal() {
    if (dialog.open) {
      dialog.close();
    }
    document.body.classList.remove('slingan-rsvp-modal-open');
  }

  function setLoading() {
    if (bodyEl) {
      bodyEl.innerHTML = '<p class="slingan-rsvp-modal__loading">' + config.loading + '</p>';
    }
  }

  async function loadRsvp(eventId) {
    if (cache.has(eventId)) {
      return cache.get(eventId);
    }

    const formData = new FormData();
    formData.append('action', 'slingan_event_rsvp_modal');
    formData.append('nonce', config.nonce);
    formData.append('event_id', String(eventId));

    const response = await fetch(config.ajaxUrl, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin',
    });

    const payload = await response.json();
    if (!payload.success) {
      throw new Error(payload.data && payload.data.message ? payload.data.message : config.error);
    }

    cache.set(eventId, payload.data);
    return payload.data;
  }

  async function openModal(button) {
    const eventId = button.getAttribute('data-event-id');
    if (!eventId) {
      return;
    }

    const postUrl = button.getAttribute('data-post-url') || '';
    const postTitle = button.getAttribute('data-post-title') || '';

    if (titleEl) {
      titleEl.textContent = postTitle;
    }
    if (scheduleEl) {
      scheduleEl.textContent = '';
    }
    if (articleLink) {
      if (postUrl) {
        articleLink.href = postUrl;
        articleLink.hidden = false;
      } else {
        articleLink.hidden = true;
      }
    }

    setLoading();
    document.body.classList.add('slingan-rsvp-modal-open');
    dialog.showModal();

    try {
      const data = await loadRsvp(eventId);
      if (titleEl && data.title) {
        titleEl.textContent = data.title;
      }
      if (scheduleEl && data.schedule) {
        scheduleEl.textContent = data.schedule;
      }
      if (bodyEl) {
        bodyEl.innerHTML = data.html;
        document.dispatchEvent(
          new CustomEvent('slingan-rsvp-modal-loaded', { detail: { eventId: eventId } })
        );
      }
    } catch (error) {
      if (bodyEl) {
        bodyEl.innerHTML =
          '<p class="slingan-rsvp-modal__error">' +
          (error.message || config.error) +
          '</p>';
      }
    }
  }

  document.addEventListener('click', function (event) {
    const openBtn = event.target.closest('.slingan-open-rsvp-modal');
    if (openBtn) {
      event.preventDefault();
      openModal(openBtn);
      return;
    }

    if (event.target.closest('[data-slingan-rsvp-close]')) {
      event.preventDefault();
      closeModal();
    }
  });

  dialog.addEventListener('click', function (event) {
    if (event.target === dialog) {
      closeModal();
    }
  });

  dialog.addEventListener('cancel', function () {
    document.body.classList.remove('slingan-rsvp-modal-open');
  });
})();
