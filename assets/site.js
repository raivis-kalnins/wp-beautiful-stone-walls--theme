/* Beautiful Stone Walls — progressive navigation, galleries and lightbox. */
(function () {
  'use strict';

  document.documentElement.classList.add('js');

  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback, { once: true });
    } else {
      callback();
    }
  }

  ready(function () {
    var translations = window.bswI18n && typeof window.bswI18n === 'object' ? window.bswI18n : {};
    var messages = {
      filterProjectsLabel: 'Filter projects by type',
      filterPhotosLabel: 'Filter photographs by type',
      allProjects: 'All projects',
      allPhotos: 'All photographs',
      moreProjects: 'View more projects',
      morePhotos: 'View more photographs',
      statusProject: 'Showing {shown} of {total} project.',
      statusProjects: 'Showing {shown} of {total} projects.',
      statusPhoto: 'Showing {shown} of {total} photograph.',
      statusPhotos: 'Showing {shown} of {total} photographs.',
      statusProjectIn: 'Showing {shown} of {total} project in {category}.',
      statusProjectsIn: 'Showing {shown} of {total} projects in {category}.',
      statusPhotoIn: 'Showing {shown} of {total} photograph in {category}.',
      statusPhotosIn: 'Showing {shown} of {total} photographs in {category}.',
      photoLoadError: 'This photograph could not be loaded. Please try the next image or close the gallery and try again.',
      viewerLabel: 'Project photograph viewer',
      closeLabel: 'Close photograph viewer',
      previousLabel: 'Previous photograph',
      nextLabel: 'Next photograph',
      fallbackCaption: 'Stone masonry project photograph',
      imageCount: '{current} / {total}',
      beforeLabel: 'Before',
      afterLabel: 'After'
    };
    function message(key, values) {
      var template = typeof translations[key] === 'string' && translations[key] ? translations[key] : messages[key];
      return String(template || '').replace(/\{([a-z]+)\}/g, function (token, name) {
        return values && Object.prototype.hasOwnProperty.call(values, name) ? String(values[name]) : token;
      });
    }
    document.querySelectorAll('.bsw-gallery .bsw-stage-before, .bsw-gallery .bsw-stage-after').forEach(function (figure) {
      figure.dataset.bswStageLabel = message(figure.classList.contains('bsw-stage-before') ? 'beforeLabel' : 'afterLabel');
    });

    var menuButton = document.querySelector('.menu-toggle');
    var navigation = document.getElementById('primary-navigation');
    if (menuButton && navigation) {
      var closeMenu = function (restoreFocus) {
        navigation.classList.remove('is-open');
        menuButton.setAttribute('aria-expanded', 'false');
        if (restoreFocus) menuButton.focus();
      };
      menuButton.setAttribute('aria-expanded', 'false');
      menuButton.addEventListener('click', function () {
        var open = menuButton.getAttribute('aria-expanded') !== 'true';
        menuButton.setAttribute('aria-expanded', String(open));
        navigation.classList.toggle('is-open', open);
      });
      var menuKeydown = function (event) {
        if (event.key === 'Escape' && navigation.classList.contains('is-open')) {
          event.preventDefault();
          closeMenu(true);
        }
      };
      navigation.addEventListener('keydown', menuKeydown);
      menuButton.addEventListener('keydown', menuKeydown);
      navigation.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (link && window.matchMedia('(max-width: 1399px)').matches) closeMenu(false);
      });
      document.addEventListener('click', function (event) {
        if (!navigation.contains(event.target) && !menuButton.contains(event.target)) closeMenu(false);
      });
      var desktopQuery = window.matchMedia('(min-width: 1400px)');
      var resetMenu = function (event) { if (event.matches) closeMenu(false); };
      if (desktopQuery.addEventListener) desktopQuery.addEventListener('change', resetMenu);
      else if (desktopQuery.addListener) desktopQuery.addListener(resetMenu);
    }

    var categoryLabels = {
      houses: 'Houses & façades',
      garages: 'Garages',
      gardens: 'Garden features',
      fireplaces: 'Fireplaces',
      pillars: 'Stone pillars',
      restoration: 'Wall restoration',
      materials: 'Liscannor stone'
    };
    Object.keys(categoryLabels).forEach(function (name) {
      if (translations.categories && typeof translations.categories[name] === 'string') categoryLabels[name] = translations.categories[name];
    });
    var projectCategoryLabels = { houses: 'Houses & garages' };
    if (translations.projectCategories && typeof translations.projectCategories === 'object') {
      Object.keys(translations.projectCategories).forEach(function (name) {
        if (typeof translations.projectCategories[name] === 'string') projectCategoryLabels[name] = translations.projectCategories[name];
      });
    }
    var categoryOrder = ['houses', 'garages', 'gardens', 'fireplaces', 'pillars', 'restoration', 'materials'];
    var urlCategory = new URLSearchParams(window.location.search).get('category') || 'all';
    var panelStates = new WeakMap();

    document.querySelectorAll('.gallery-panel[data-bsw-filter]').forEach(function (panel, panelIndex) {
      var grid = panel.querySelector('.project-grid') || panel.querySelector('.bsw-gallery');
      if (!grid) return;
      var isProjects = grid.classList.contains('project-grid');
      var selector = isProjects ? '.project-card' : '.wp-block-image';
      var items = Array.from(grid.children).filter(function (item) { return item.matches(selector); });
      if (!items.length) return;
      var pageSize = isProjects ? 12 : 18;
      var categoryLabel = function (name) { return (isProjects && projectCategoryLabels[name]) || categoryLabels[name] || name.replace(/-/g, ' '); };
      var categories = new Map();
      var itemCategories = new Map();
      items.forEach(function (item) {
        var names = [];
        if (item.dataset.category) names = item.dataset.category.split(/[\s,]+/).filter(Boolean);
        Array.from(item.classList).forEach(function (name) {
          if (name.indexOf('bsw-category-') === 0) names.push(name.slice(13));
        });
        names = Array.from(new Set(names));
        itemCategories.set(item, names);
        names.forEach(function (name) { categories.set(name, (categories.get(name) || 0) + 1); });
      });
      var state = {
        panel: panel,
        items: items,
        selected: categories.has(urlCategory) ? urlCategory : 'all',
        limit: pageSize,
        matching: items,
        categories: itemCategories
      };
      panelStates.set(panel, state);
      var status = panel.querySelector('.gallery-status');
      if (!status) {
        status = document.createElement('p');
        status.className = 'gallery-status';
        grid.insertAdjacentElement('afterend', status);
      }
      status.setAttribute('role', 'status');
      status.setAttribute('aria-live', 'polite');
      status.setAttribute('aria-atomic', 'true');
      var more = panel.querySelector('.gallery-more');
      if (!more) {
        more = document.createElement('button');
        more.className = 'gallery-more';
        more.type = 'button';
        status.insertAdjacentElement('afterend', more);
      }
      more.type = 'button';
      more.hidden = true;
      var buttons = [];
      var gridId = grid.id || 'bsw-gallery-' + (panelIndex + 1);
      grid.id = gridId;
      more.setAttribute('aria-controls', gridId);
      if (categories.size > 1) {
        var toolbar = document.createElement('div');
        toolbar.className = 'gallery-toolbar';
        toolbar.setAttribute('role', 'group');
        toolbar.setAttribute('aria-label', message(isProjects ? 'filterProjectsLabel' : 'filterPhotosLabel'));
        var names = Array.from(categories.keys()).sort(function (a, b) {
          var aIndex = categoryOrder.indexOf(a);
          var bIndex = categoryOrder.indexOf(b);
          return (aIndex < 0 ? 99 : aIndex) - (bIndex < 0 ? 99 : bIndex) || a.localeCompare(b);
        });
        ['all'].concat(names).forEach(function (name) {
          var button = document.createElement('button');
          button.type = 'button';
          button.className = 'gallery-filter';
          button.dataset.filter = name;
          button.setAttribute('aria-controls', gridId);
          var label = name === 'all' ? message(isProjects ? 'allProjects' : 'allPhotos') : categoryLabel(name);
          button.appendChild(document.createTextNode(label));
          var count = document.createElement('span');
          count.className = 'gallery-filter-count';
          count.setAttribute('aria-hidden', 'true');
          count.textContent = String(name === 'all' ? items.length : categories.get(name));
          button.appendChild(count);
          button.addEventListener('click', function () {
            if (state.selected === name) return;
            state.selected = name;
            state.limit = pageSize;
            render();
            if (panelIndex === 0 && window.history && window.history.replaceState) {
              try {
                var url = new URL(window.location.href);
                if (name === 'all') url.searchParams.delete('category');
                else url.searchParams.set('category', name);
                window.history.replaceState(window.history.state, '', url.href);
              } catch (ignore) { /* Filtering remains available when history is restricted. */ }
            }
          });
          toolbar.appendChild(button);
          buttons.push(button);
        });
        grid.insertAdjacentElement('beforebegin', toolbar);
      }
      function render() {
        state.matching = items.filter(function (item) {
          return state.selected === 'all' || itemCategories.get(item).indexOf(state.selected) !== -1;
        });
        var shown = new Set(state.matching.slice(0, state.limit));
        items.forEach(function (item) {
          item.hidden = !shown.has(item);
          item.dataset.bswFilterMatch = String(state.matching.indexOf(item) !== -1);
        });
        buttons.forEach(function (button) {
          button.setAttribute('aria-pressed', String(button.dataset.filter === state.selected));
        });
        var visibleCount = Math.min(state.limit, state.matching.length);
        var statusKey = 'status' + (isProjects ? 'Project' : 'Photo') + (state.matching.length === 1 ? '' : 's') + (state.selected === 'all' ? '' : 'In');
        status.textContent = message(statusKey, { shown: visibleCount, total: state.matching.length, category: categoryLabel(state.selected) });
        more.hidden = visibleCount >= state.matching.length;
        more.textContent = message(isProjects ? 'moreProjects' : 'morePhotos');
        panel.classList.add('is-enhanced');
      }
      more.addEventListener('click', function () {
        var previousLimit = state.limit;
        state.limit += pageSize;
        render();
        var firstNewItem = state.matching[previousLimit];
        var firstNewLink = firstNewItem && firstNewItem.querySelector('a[href]');
        if (firstNewLink) firstNewLink.focus({ preventScroll: true });
      });
      render();
    });

    var dialog = document.getElementById('bsw-lightbox');
    if (!dialog || typeof dialog.showModal !== 'function') return;
    var image = dialog.querySelector('.lightbox-image');
    var caption = dialog.querySelector('.lightbox-caption');
    var count = dialog.querySelector('.lightbox-count');
    var close = dialog.querySelector('.lightbox-close');
    var previous = dialog.querySelector('.lightbox-prev');
    var next = dialog.querySelector('.lightbox-next');
    if (!image || !close) return;
    var lightboxLinks = [];
    var currentIndex = 0;
    var returnFocus = null;
    var error = document.createElement('p');
    error.className = 'lightbox-error';
    error.hidden = true;
    error.setAttribute('role', 'status');
    error.textContent = message('photoLoadError');
    image.insertAdjacentElement('afterend', error);
    if (!dialog.hasAttribute('aria-labelledby')) dialog.setAttribute('aria-label', message('viewerLabel'));
    close.type = 'button';
    close.setAttribute('aria-label', message('closeLabel'));
    if (previous) {
      previous.type = 'button';
      previous.setAttribute('aria-label', message('previousLabel'));
    }
    if (next) {
      next.type = 'button';
      next.setAttribute('aria-label', message('nextLabel'));
    }
    if (count) count.setAttribute('aria-live', 'polite');

    function validImageLink(link) {
      if (!link || !link.href) return false;
      try {
        var url = new URL(link.href, window.location.href);
        return (url.protocol === 'http:' || url.protocol === 'https:') && /\.(?:jpe?g|png|gif|webp|avif)(?:$)/i.test(url.pathname);
      } catch (ignore) { return false; }
    }
    function collectLinks(trigger) {
      var group = trigger.closest('.bsw-gallery, [data-bsw-gallery]') || trigger.closest('article') || trigger.parentElement;
      var links = Array.from(group.querySelectorAll('.bsw-gallery a[href], a[data-bsw-lightbox], a[href]')).filter(validImageLink);
      var panel = trigger.closest('.gallery-panel[data-bsw-filter]');
      var state = panel && panelStates.get(panel);
      if (state) {
        links = links.filter(function (link) {
          var item = link.closest('.project-card, .wp-block-image');
          return !item || state.matching.indexOf(item) !== -1;
        });
      }
      links = Array.from(new Map(links.map(function (link) { return [link.href, link]; })).values());
      return links.length ? links : [trigger];
    }
    function showImage(index) {
      if (!lightboxLinks.length) return;
      currentIndex = (index + lightboxLinks.length) % lightboxLinks.length;
      var link = lightboxLinks[currentIndex];
      var thumbnail = link.querySelector('img');
      var figure = link.closest('figure');
      var figcaption = figure && figure.querySelector('figcaption');
      var description = link.dataset.caption || (figcaption ? figcaption.textContent.trim() : '') || (thumbnail ? thumbnail.alt : '') || message('fallbackCaption');
      image.hidden = false;
      error.hidden = true;
      image.alt = thumbnail && thumbnail.alt ? thumbnail.alt : description;
      image.removeAttribute('srcset');
      image.src = link.href;
      if (caption) caption.textContent = description;
      if (count) count.textContent = message('imageCount', { current: currentIndex + 1, total: lightboxLinks.length });
      if (previous) previous.hidden = lightboxLinks.length < 2;
      if (next) next.hidden = lightboxLinks.length < 2;
    }
    image.addEventListener('error', function () {
      image.hidden = true;
      error.hidden = false;
    });
    image.addEventListener('load', function () {
      image.hidden = false;
      error.hidden = true;
    });
    document.addEventListener('click', function (event) {
      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      var trigger = event.target.closest('.bsw-gallery a[href], a[data-bsw-lightbox]');
      if (!validImageLink(trigger)) return;
      lightboxLinks = collectLinks(trigger);
      var selectedIndex = lightboxLinks.findIndex(function (link) { return link.href === trigger.href; });
      returnFocus = trigger;
      showImage(selectedIndex < 0 ? 0 : selectedIndex);
      try {
        if (!dialog.open) dialog.showModal();
      } catch (ignore) { return; }
      event.preventDefault();
      document.body.classList.add('bsw-lightbox-open');
      close.focus();
    });
    close.addEventListener('click', function () { dialog.close(); });
    if (previous) previous.addEventListener('click', function () { showImage(currentIndex - 1); });
    if (next) next.addEventListener('click', function () { showImage(currentIndex + 1); });
    dialog.addEventListener('keydown', function (event) {
      if (event.key === 'ArrowLeft') { event.preventDefault(); showImage(currentIndex - 1); }
      if (event.key === 'ArrowRight') { event.preventDefault(); showImage(currentIndex + 1); }
    });
    dialog.addEventListener('click', function (event) {
      if (event.target !== dialog) return;
      var rect = dialog.getBoundingClientRect();
      if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
    });
    dialog.addEventListener('close', function () {
      document.body.classList.remove('bsw-lightbox-open');
      if (returnFocus && returnFocus.isConnected) returnFocus.focus({ preventScroll: true });
    });
    var touchStart = null;
    image.addEventListener('touchstart', function (event) {
      if (event.touches.length !== 1) { touchStart = null; return; }
      touchStart = { x: event.touches[0].clientX, y: event.touches[0].clientY };
    }, { passive: true });
    image.addEventListener('touchend', function (event) {
      if (!touchStart || !event.changedTouches.length) return;
      var x = event.changedTouches[0].clientX - touchStart.x;
      var y = event.changedTouches[0].clientY - touchStart.y;
      touchStart = null;
      if (Math.abs(x) > 65 && Math.abs(x) > Math.abs(y) * 1.5) showImage(currentIndex + (x < 0 ? 1 : -1));
    }, { passive: true });
  });
}());
