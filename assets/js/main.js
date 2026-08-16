import { createMovieCard } from './components/movie_card.js';

document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = new URL('./', document.baseURI).pathname.replace(/\/$/, '');

    document.querySelectorAll('.alert').forEach((alert) => {
        window.setTimeout(() => alert.remove(), 3500);
    });

    const menuToggle = document.getElementById('menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    const overlay = document.getElementById('drawer-overlay');

    const closeDrawer = () => {
        navMenu?.classList.remove('active');
        overlay?.classList.remove('active');
        menuToggle?.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('drawer-open');
    };

    menuToggle?.addEventListener('click', () => {
        const isOpen = navMenu?.classList.toggle('active') ?? false;
        overlay?.classList.toggle('active', isOpen);
        menuToggle.setAttribute('aria-expanded', String(isOpen));
        document.body.classList.toggle('drawer-open', isOpen);
    });

    overlay?.addEventListener('click', closeDrawer);
    navMenu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeDrawer));

    const searchInput = document.getElementById('live-search-input');
    const searchDropdown = document.getElementById('live-search-dropdown');
    let debounceTimer;
    let activeRequest;

    const hideResults = () => {
        searchDropdown?.replaceChildren();
        if (searchDropdown) searchDropdown.hidden = true;
    };

    const renderResults = (movies) => {
        if (!searchDropdown) return;
        searchDropdown.replaceChildren();

        if (!movies.length) {
            const empty = document.createElement('p');
            empty.className = 'search-item no-result';
            empty.textContent = 'Không tìm thấy phim phù hợp.';
            searchDropdown.append(empty);
        } else {
            movies.forEach((movie) => {
                const item = document.createElement('a');
                item.className = 'search-item';
                item.href = `${baseUrl}/movie?id=${encodeURIComponent(movie.id)}`;
                item.setAttribute('role', 'option');

                const image = document.createElement('img');
                image.src = movie.poster_path
                    ? `https://image.tmdb.org/t/p/w92${movie.poster_path}`
                    : 'https://placehold.co/92x138/171d32/e7edff?text=No+Poster';
                image.alt = '';
                image.loading = 'lazy';

                const text = document.createElement('div');
                const title = document.createElement('strong');
                title.textContent = movie.title;
                const year = document.createElement('span');
                year.textContent = `${movie.release_year || 'N/A'} · ★ ${Number(movie.rating || 0).toFixed(1)}`;
                text.append(title, year);
                item.append(image, text);
                searchDropdown.append(item);
            });
        }
        searchDropdown.hidden = false;
    };

    searchInput?.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        const query = searchInput.value.trim();

        if (query.length < 2) {
            activeRequest?.abort();
            hideResults();
            return;
        }

        debounceTimer = window.setTimeout(async () => {
            activeRequest?.abort();
            activeRequest = new AbortController();

            try {
                const response = await fetch(
                    `${baseUrl}/api/movies.php?search=${encodeURIComponent(query)}`,
                    { signal: activeRequest.signal }
                );
                if (!response.ok) throw new Error('Search request failed');
                const payload = await response.json();
                renderResults(payload.status === 'success' ? payload.data : []);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Live Search error:', error);
                    renderResults([]);
                }
            }
        }, 250);
    });

    document.addEventListener('click', (event) => {
        if (event.key === 'Escape') {
            closeDrawer();
        }
        if (searchInput && searchDropdown && !searchInput.contains(event.target) && !searchDropdown.contains(event.target)) {
            hideResults();
        }
    });

    const trailerModal = document.getElementById('trailer-modal');
    const trailerBody = document.getElementById('trailer-modal-body');
    const trailerTitle = document.getElementById('trailer-modal-title');
    let trailerRequest;

    const closeTrailer = () => {
        trailerRequest?.abort();
        trailerModal?.classList.remove('is-open');
        trailerModal?.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        if (trailerBody) trailerBody.replaceChildren();
    };

    document.querySelectorAll('[data-close-trailer]').forEach((button) => {
        button.addEventListener('click', closeTrailer);
    });

    document.querySelectorAll('.js-open-trailer').forEach((button) => {
        button.addEventListener('click', () => {
            if (!trailerModal || !trailerBody) return;

            const embedUrl = button.dataset.embedUrl;
            trailerTitle.textContent = `Trailer — ${button.dataset.movieTitle || ''}`;
            trailerModal.classList.add('is-open');
            trailerModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');

            trailerBody.innerHTML = `<iframe width="100%" height="400" src="${embedUrl}" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeDrawer();
            closeTrailer();
        }
    });

    const movieGrid = document.querySelector('[data-js-movie-grid]');
    if (movieGrid && Array.isArray(window.__MOVIES_DATA__)) {
        movieGrid.replaceChildren(...window.__MOVIES_DATA__.map((movie) => createMovieCard(movie)));
    }
});