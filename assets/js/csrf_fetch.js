// csrf_fetch.js — transparently attaches the CSRF token (window.CSRF_TOKEN, set by
// templates/sidebar.php) to same-origin, state-changing fetch() requests so every
// existing fetch() call site doesn't need to be edited individually.
(function () {
    if (typeof window.fetch !== 'function' || window.__csrfFetchPatched) return;
    window.__csrfFetchPatched = true;

    const originalFetch = window.fetch;
    const SAFE_METHODS = ['GET', 'HEAD'];

    function isSameOrigin(url) {
        try {
            return new URL(url, window.location.href).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    window.fetch = function (input, init) {
        init = init || {};
        const requestMethod = input instanceof Request ? input.method : undefined;
        const method = (init.method || requestMethod || 'GET').toUpperCase();
        const url = input instanceof Request ? input.url : input;

        if (!SAFE_METHODS.includes(method) && window.CSRF_TOKEN && isSameOrigin(url)) {
            const headers = new Headers(init.headers || (input instanceof Request ? input.headers : undefined));
            headers.set('X-CSRF-Token', window.CSRF_TOKEN);
            init = Object.assign({}, init, { headers });
        }

        return originalFetch.call(this, input, init);
    };
})();
