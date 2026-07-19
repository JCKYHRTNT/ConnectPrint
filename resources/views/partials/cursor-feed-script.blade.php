<button class="cp-return-top btn btn-outline-secondary btn-sm" type="button" data-cursor-return-top hidden>Return to Top</button>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const feeds = document.querySelectorAll('[data-cursor-feed]');
        const returnTop = document.querySelector('[data-cursor-return-top]');

        if (returnTop) {
            function syncReturnTop() {
                returnTop.hidden = window.scrollY <= 480;
            }

            returnTop.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            window.addEventListener('scroll', syncReturnTop, { passive: true });
            syncReturnTop();
        }

        feeds.forEach(function (feed) {
            const list = feed.querySelector('[data-cursor-list]');
            const sentinel = feed.querySelector('[data-cursor-sentinel]');
            const loadMore = feed.querySelector('[data-cursor-load-more]');
            const retry = feed.querySelector('[data-cursor-retry]');
            const loading = feed.querySelector('[data-cursor-loading]');
            const error = feed.querySelector('[data-cursor-error]');
            const end = feed.querySelector('[data-cursor-end]');
            const seen = new Set(Array.from(feed.querySelectorAll('[data-cursor-item]')).map(function (item) {
                return item.dataset.cursorItem;
            }));

            let nextCursor = feed.dataset.nextCursor || '';
            let hasMore = feed.dataset.hasMore === '1';
            let isLoading = false;
            let abortController = null;

            function syncState(state) {
                if (loading) loading.hidden = state !== 'loading';
                if (error) error.hidden = state !== 'error';
                if (end) end.hidden = hasMore || state === 'loading' || state === 'error';
                if (loadMore) loadMore.hidden = !hasMore || state === 'loading' || state === 'error';
            }

            function endpointUrl() {
                const url = new URL(feed.dataset.cursorEndpoint || window.location.href, window.location.origin);
                url.searchParams.set('cursor', nextCursor);
                return url.toString();
            }

            function appendHtml(html) {
                const template = document.createElement('template');
                template.innerHTML = html.trim();

                Array.from(template.content.children).forEach(function (node) {
                    const key = node.dataset ? node.dataset.cursorItem : null;
                    if (key && seen.has(key)) {
                        return;
                    }

                    if (key) {
                        seen.add(key);
                    }

                    list.appendChild(node);
                });
            }

            function loadNextPage() {
                if (!hasMore || !nextCursor || isLoading || !list) {
                    return;
                }

                isLoading = true;
                syncState('loading');
                abortController?.abort();
                abortController = new AbortController();

                fetch(endpointUrl(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal: abortController.signal,
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Cursor request failed.');
                        }

                        return response.json();
                    })
                    .then(function (payload) {
                        (payload.data || []).forEach(appendHtml);
                        nextCursor = payload.next_cursor || '';
                        hasMore = Boolean(payload.has_more && nextCursor);
                        feed.dataset.nextCursor = nextCursor;
                        feed.dataset.hasMore = hasMore ? '1' : '0';
                        syncState('ready');
                    })
                    .catch(function (err) {
                        if (err.name !== 'AbortError') {
                            syncState('error');
                        }
                    })
                    .finally(function () {
                        isLoading = false;
                    });
            }

            if (loadMore) {
                loadMore.addEventListener('click', loadNextPage);
            }

            if (retry) {
                retry.addEventListener('click', loadNextPage);
            }

            if (sentinel) {
                const observer = new IntersectionObserver(function (entries) {
                    if (entries[0]?.isIntersecting) {
                        loadNextPage();
                    }
                }, { root: null, rootMargin: '900px 0px', threshold: 0 });

                observer.observe(sentinel);
            }

            syncState('ready');
        });
    });
</script>
