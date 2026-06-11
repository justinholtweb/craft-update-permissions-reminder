/* global Craft */
(function () {
  'use strict';

  window.UpdatePermissionsReminder = {
    config: null,

    init: function (config) {
      if (!config || !config.reminders || !config.reminders.length) {
        return;
      }
      // Avoid double-injection on the same page.
      if (document.getElementById('upr-root')) {
        return;
      }
      this.config = config;

      var root = document.createElement('div');
      root.id = 'upr-root';
      document.body.appendChild(root);

      if (config.showBar) {
        this.renderBar(root);
      }
      if (config.showModal) {
        this.renderModal(root);
      }
    },

    count: function () {
      return this.config.reminders.length;
    },

    renderBar: function (root) {
      var self = this;
      var bar = document.createElement('div');
      bar.id = 'upr-bar';
      bar.className = 'upr-bar';

      var n = this.count();
      var label = n === 1
        ? '1 schema change may need a permissions review'
        : n + ' schema changes may need a permissions review';

      bar.innerHTML =
        '<div class="upr-bar__inner">' +
          '<span class="upr-bar__icon" aria-hidden="true">!</span>' +
          '<span class="upr-bar__text">' + self.escape(label) + '</span>' +
          '<span class="upr-bar__actions">' +
            (this.config.showModal
              ? '<button type="button" class="btn upr-bar__details">View details</button>'
              : '') +
            '<a class="btn submit upr-bar__review" href="' + self.attr(this.config.reviewUrl) + '">Review permissions</a>' +
            '<button type="button" class="btn upr-bar__dismissall">Dismiss all</button>' +
            '<button type="button" class="upr-bar__close" aria-label="Hide">×</button>' +
          '</span>' +
        '</div>';

      root.appendChild(bar);
      document.body.classList.add('upr-has-bar');

      var details = bar.querySelector('.upr-bar__details');
      if (details) {
        details.addEventListener('click', function () { self.openModal(); });
      }
      bar.querySelector('.upr-bar__dismissall').addEventListener('click', function () {
        self.dismissAll();
      });
      bar.querySelector('.upr-bar__close').addEventListener('click', function () {
        bar.style.display = 'none';
        document.body.classList.remove('upr-has-bar');
      });
    },

    renderModal: function (root) {
      var self = this;
      var overlay = document.createElement('div');
      overlay.id = 'upr-modal';
      overlay.className = 'upr-modal';
      overlay.setAttribute('role', 'dialog');
      overlay.setAttribute('aria-modal', 'true');
      overlay.style.display = 'none';

      var items = this.config.reminders.map(function (r) {
        return '<li class="upr-modal__item" data-id="' + r.id + '">' +
          '<span class="upr-modal__msg">' + self.escape(r.message) + '</span>' +
          '<button type="button" class="upr-modal__dismiss" data-id="' + r.id + '" aria-label="Dismiss">×</button>' +
          '</li>';
      }).join('');

      overlay.innerHTML =
        '<div class="upr-modal__box">' +
          '<div class="upr-modal__head">' +
            '<h2>Update user permissions?</h2>' +
            '<button type="button" class="upr-modal__close" aria-label="Close">×</button>' +
          '</div>' +
          '<p class="upr-modal__lead">The following changes were made since permissions were last reviewed. ' +
            'New sections, volumes, groups and plugins are not granted to existing user groups automatically.</p>' +
          '<ul class="upr-modal__list">' + items + '</ul>' +
          '<div class="upr-modal__foot">' +
            '<button type="button" class="btn upr-modal__dismissall">Dismiss all</button>' +
            '<a class="btn submit" href="' + self.attr(this.config.reviewUrl) + '">Review user permissions</a>' +
          '</div>' +
        '</div>';

      root.appendChild(overlay);

      overlay.querySelector('.upr-modal__close').addEventListener('click', function () { self.closeModal(); });
      overlay.querySelector('.upr-modal__dismissall').addEventListener('click', function () { self.dismissAll(); });
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay) { self.closeModal(); }
      });
      Array.prototype.forEach.call(overlay.querySelectorAll('.upr-modal__dismiss'), function (btn) {
        btn.addEventListener('click', function () {
          self.dismiss(parseInt(btn.getAttribute('data-id'), 10), btn);
        });
      });

      // If the bar is disabled, surface the modal automatically.
      if (!this.config.showBar) {
        this.openModal();
      }
    },

    openModal: function () {
      var m = document.getElementById('upr-modal');
      if (m) { m.style.display = 'flex'; }
    },

    closeModal: function () {
      var m = document.getElementById('upr-modal');
      if (m) { m.style.display = 'none'; }
    },

    post: function (url, id, cb) {
      var body = {};
      body[this.config.csrfTokenName] = this.config.csrfTokenValue;
      if (id != null) { body.id = id; }

      var params = Object.keys(body).map(function (k) {
        return encodeURIComponent(k) + '=' + encodeURIComponent(body[k]);
      }).join('&');

      fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: params,
        credentials: 'same-origin'
      }).then(function (res) {
        return res.json();
      }).then(function (json) {
        if (cb) { cb(json); }
      }).catch(function () {
        // On failure, fall back to reloading so the user isn't stuck.
        window.location.reload();
      });
    },

    dismiss: function (id, btn) {
      var self = this;
      this.post(this.config.dismissUrl, id, function (json) {
        // Remove from local state + DOM.
        self.config.reminders = self.config.reminders.filter(function (r) { return r.id !== id; });
        var item = document.querySelector('.upr-modal__item[data-id="' + id + '"]');
        if (item) { item.parentNode.removeChild(item); }
        if (!self.config.reminders.length) {
          self.teardown();
        } else {
          self.refreshBarLabel();
        }
      });
    },

    dismissAll: function () {
      var self = this;
      this.post(this.config.dismissAllUrl, null, function () {
        self.config.reminders = [];
        self.teardown();
      });
    },

    refreshBarLabel: function () {
      var text = document.querySelector('.upr-bar__text');
      if (!text) { return; }
      var n = this.count();
      text.textContent = n === 1
        ? '1 schema change may need a permissions review'
        : n + ' schema changes may need a permissions review';
    },

    teardown: function () {
      var root = document.getElementById('upr-root');
      if (root) { root.parentNode.removeChild(root); }
      document.body.classList.remove('upr-has-bar');
    },

    escape: function (str) {
      var div = document.createElement('div');
      div.textContent = str == null ? '' : String(str);
      return div.innerHTML;
    },

    attr: function (str) {
      return this.escape(str).replace(/"/g, '&quot;');
    }
  };
})();
