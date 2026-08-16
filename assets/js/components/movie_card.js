/**
 * Tạo thẻ phim bằng DOM API để title/overview không bị chèn trực tiếp vào HTML.
 * Hỗ trợ dữ liệu TMDB (release_date, overview) và dữ liệu MySQL (release_year, description).
 */
export function createMovieCard(movie, options = {}) {
    const {
        imageBaseUrl = 'https://image.tmdb.org/t/p/w500',
        detailUrl = `movie?id=${encodeURIComponent(movie.id ?? '')}`,
    } = options;

    const card = document.createElement('article');
    card.className = 'movie-card';

    const image = document.createElement('img');
    image.src = movie.poster_path
        ? `${imageBaseUrl}${movie.poster_path}`
        : 'https://placehold.co/500x750?text=No+Poster';
    image.alt = movie.title ? `Poster phim ${movie.title}` : 'Poster phim';
    image.loading = 'lazy';

    const popup = document.createElement('div');
    popup.className = 'movie-card-popup';

    const link = document.createElement('a');
    link.className = 'movie-card-link';
    link.href = detailUrl;
    link.textContent = movie.title || 'Chưa có tiêu đề';

    const meta = document.createElement('p');
    const releaseYear = movie.release_year || (movie.release_date || '').slice(0, 4);
    const rating = movie.rating || movie.vote_average;
    meta.textContent = [releaseYear, rating ? `★ ${Number(rating).toFixed(1)}` : '']
        .filter(Boolean)
        .join(' • ');

    popup.append(link, meta);
    card.append(image, popup);

    return card;
}