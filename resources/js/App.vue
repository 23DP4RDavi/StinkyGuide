<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const interestOptions = [
  { name: 'Adventure', icon: '◈' },
  { name: 'Cozy', icon: '☁' },
  { name: 'RPG', icon: '✳' },
  { name: 'Strategy', icon: '▦' },
  { name: 'Indie', icon: '✦' },
  { name: 'Shooter', icon: '⌖' },
  { name: 'Racing', icon: '↗' },
  { name: 'Simulation', icon: '▤' },
  { name: 'Sports', icon: '⚽' },
];
const guideTypes = [
  { value: 'all', label: 'All guides' },
  { value: 'beginner', label: 'Beginner friendly' },
  { value: 'walkthrough', label: 'Walkthroughs' },
  { value: 'builds', label: 'Builds & loadouts' },
  { value: 'bosses', label: 'Boss strategies' },
  { value: 'secrets', label: 'Secrets & quests' },
  { value: 'collectibles', label: 'Collectibles' },
  { value: 'achievements', label: 'Achievements' },
  { value: 'tips', label: 'Tips & tricks' },
  { value: 'other', label: 'Other' },
];
const libraryStatuses = [
  { value: 'wishlist', label: 'Backlog' },
  { value: 'playing', label: 'Playing' },
  { value: 'completed', label: 'Completed' },
  { value: 'dropped', label: 'Dropped (we do not judge)' },
];
const state = ref({
  loading: true,
  user: null,
  games: [],
  guides: [],
  reviews: [],
  interests: ['Adventure'],
  library: [],
  savedGuides: [],
  adminData: null,
  adminLoading: false,
  adminDeleting: '',
  stats: { games: 0, guides: 0, players: 0, reviews: 0, averageRating: 0 },
  summary: { count: 0, averageRating: 0 },
  search: '',
  showAllGames: false,
  guideFilter: 'all',
  guideSort: 'helpful',
  reviewFilter: 0,
  reviewSearch: '',
  reviewGameFilter: '',
  reviewSort: 'newest',
  showAllReviews: false,
  view: 'discover',
  showAllGuides: false,
  modal: '',
  authMode: 'login',
  selectedGuide: null,
  selectedGame: null,
  selectedReview: null,
  comments: [],
  toast: '',
  error: '',
});
const dialog = ref(null);
const toastTimer = ref(null);
const authForm = ref({ name: '', email: '', password: '' });
const guideForm = ref({ title: '', game: '', type: 'beginner', body: '' });
const reviewForm = ref({ game: '', rating: 5, hoursPlayed: '', body: '' });
const commentDraft = ref('');
const profileForm = ref({ name: '', bio: '' });
const submitting = ref(false);
const mobileNav = ref(false);

function gameForReview(name) {
  return state.value.games.find((game) => game.name.toLowerCase() === name.toLowerCase());
}

function timeAgo(value) {
  if (!value) return 'Just now';
  const minutes = Math.max(0, Math.floor((Date.now() - new Date(value).getTime()) / 60_000));
  if (minutes < 1) return 'Just now';
  if (minutes < 60) return `${minutes}m ago`;
  if (minutes < 1440) return `${Math.floor(minutes / 60)}h ago`;
  if (minutes < 2880) return 'Yesterday';
  if (minutes < 365 * 1440) return `${Math.floor(minutes / 1440)}d ago`;
  return new Date(value).toLocaleDateString();
}

function focusSearch() {
  document.querySelector('#search-input')?.focus();
}

const filteredGames = computed(() => {
  const term = state.value.search.trim().toLowerCase();
  let games = state.value.games.filter((game) => {
    const interestMatch = state.value.interests.length === 0 || game.tags.some((tag) => state.value.interests.includes(tag));
    const searchMatch = !term || `${game.name} ${game.genre} ${game.tags.join(' ')}`.toLowerCase().includes(term);
    return interestMatch && searchMatch;
  });
  if (term && !games.length) {
    games = state.value.games.filter((game) => `${game.name} ${game.genre} ${game.tags.join(' ')}`.toLowerCase().includes(term));
  }
  return games.sort((a, b) => b.match - a.match || a.name.localeCompare(b.name));
});

const filteredGuides = computed(() => {
  const term = state.value.search.trim().toLowerCase();
  let guides = state.value.guides.filter((guide) => {
    const categoryMatches = state.value.guideFilter === 'all' || guide.type === state.value.guideFilter;
    const searchMatches = !term || `${guide.game} ${guide.title} ${guide.summary} ${guide.author}`.toLowerCase().includes(term);
    return categoryMatches && searchMatches;
  });
  if (state.value.guideSort === 'helpful') guides = [...guides].sort((a, b) => b.helpful - a.helpful);
  return state.value.showAllGuides ? guides : guides.slice(0, 6);
});

const filteredReviews = computed(() => {
  const term = state.value.reviewSearch.trim().toLowerCase();
  const reviews = state.value.reviews.filter((review) => {
    const searchMatches = !term || `${review.game} ${review.body} ${review.author}`.toLowerCase().includes(term);
    const ratingMatches = state.value.reviewFilter === 0 || review.rating === state.value.reviewFilter;
    const gameMatches = !state.value.reviewGameFilter || review.game === state.value.reviewGameFilter;
    return searchMatches && ratingMatches && gameMatches;
  });
  return reviews.sort((first, second) => {
    if (state.value.reviewSort === 'highest') return second.rating - first.rating || new Date(second.createdAt) - new Date(first.createdAt);
    if (state.value.reviewSort === 'lowest') return first.rating - second.rating || new Date(second.createdAt) - new Date(first.createdAt);
    return new Date(second.createdAt) - new Date(first.createdAt);
  });
});

const featuredGame = computed(() => state.value.games.find((game) => game.name === 'Elden Ring') ?? state.value.games[0]);
const reviewGames = computed(() => [...new Set(state.value.reviews.map((review) => review.game))].sort((first, second) => first.localeCompare(second)));
const featuredFilteredReview = computed(() => filteredReviews.value[0] ?? null);
const otherVisibleReviews = computed(() => {
  const remainingReviews = filteredReviews.value.slice(1);
  return state.value.showAllReviews ? remainingReviews : remainingReviews.slice(0, 6);
});
const savedGameIds = computed(() => new Set(state.value.library.map((entry) => entry.game.id)));

function recommendationReason(game) {
  const matchingTags = game.tags.filter((tag) => state.value.interests.includes(tag));
  return matchingTags.length ? `Because you like ${matchingTags.join(' + ')}` : 'A community favorite';
}

async function api(url, options = {}) {
  const method = options.method ?? 'GET';
  const headers = { Accept: 'application/json', ...(options.body ? { 'Content-Type': 'application/json' } : {}), ...options.headers };
  if (method !== 'GET' && method !== 'HEAD') {
    headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  }
  const response = await fetch(url, {
    ...options,
    method,
    headers,
    credentials: 'same-origin',
    body: options.body && typeof options.body !== 'string' ? JSON.stringify(options.body) : options.body,
  });
  const result = response.status === 204 ? null : await response.json();
  if (!response.ok) {
    const validationMessage = result?.errors ? Object.values(result.errors).flat()[0] : null;
    throw new Error(validationMessage || result?.message || `Request failed (${response.status}).`);
  }
  return result;
}

function notify(message) {
  state.value.toast = message;
  clearTimeout(toastTimer.value);
  toastTimer.value = setTimeout(() => { state.value.toast = ''; }, 3700);
}

function navigate(view) {
  state.value.view = view;
  nextTick(() => {
    const target = { discover: '#for-you', guides: '#guides', reviews: '#reviews' }[view];
    if (target) document.querySelector(target)?.scrollIntoView({ behavior: 'smooth' });
    else window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  if (view === 'library' || view === 'saved') loadPersonalLists();
  if (view === 'admin') loadAdminData();
}

async function loadAdminData() {
  if (!state.value.user?.is_admin) return;
  state.value.adminLoading = true;
  try {
    state.value.adminData = await api('/api/admin');
  } catch (error) {
    notify(error.message);
    state.value.view = 'discover';
  } finally {
    state.value.adminLoading = false;
  }
}

async function deleteCommunityContent(type, item) {
  if (!state.value.user?.is_admin || !window.confirm(`Delete this ${type.slice(0, -1)}? This cannot be undone.`)) return;

  state.value.adminDeleting = `${type}:${item.id}`;
  try {
    await api(`/api/admin/${type}/${item.id}`, { method: 'DELETE' });
    await Promise.all([loadAdminData(), reloadData()]);
    notify('Post removed. The swamp is a little tidier.');
  } catch (error) {
    notify(error.message);
  } finally {
    state.value.adminDeleting = '';
  }
}

function searchEverything() {
  state.value.view = 'discover';
  state.value.showAllGames = true;
  nextTick(() => document.querySelector('#for-you')?.scrollIntoView({ behavior: 'smooth' }));
}

function openAuth(mode = 'login') {
  state.value.authMode = mode;
  state.value.error = '';
  authForm.value = { name: '', email: '', password: '' };
  state.value.modal = 'auth';
}

function requireLogin(message) {
  if (state.value.user) return true;
  openAuth('signup');
  notify(message);
  return false;
}

function openGuideForm() {
  if (!requireLogin('Log in to publish a guide. Become somebody’s favorite browser tab.')) return;
  guideForm.value = { title: '', game: '', type: 'beginner', body: '' };
  state.value.error = '';
  state.value.modal = 'guide';
}

function openReviewForm(game = '') {
  if (!requireLogin('Log in to share a review. Your backlog can wait a minute.')) return;
  reviewForm.value = { game, rating: 5, hoursPlayed: '', body: '' };
  state.value.error = '';
  state.value.modal = 'review';
}

watch(() => state.value.modal, async (modal) => {
  await nextTick();
  if (modal && dialog.value && !dialog.value.open) dialog.value.showModal();
  else if (!modal && dialog.value?.open) dialog.value.close();
});

watch(() => state.value.user, (user) => {
  if (user) profileForm.value = { name: user.name, bio: user.bio ?? '' };
});

async function refreshStats() {
  const [stats, reviews] = await Promise.all([api('/api/stats'), api('/api/reviews')]);
  state.value.stats = stats;
  state.value.summary = reviews.summary;
  state.value.reviews = reviews.reviews;
}

async function refreshGuides() {
  const response = await api('/api/guides');
  state.value.guides = response.guides;
}

async function refreshGames() {
  const response = await api('/api/games');
  state.value.games = response.games;
  state.value.interests = response.interests;
}

async function reloadData() {
  await Promise.all([refreshGames(), refreshGuides(), refreshStats()]);
}

async function loadPersonalLists() {
  if (!state.value.user) return;
  const endpoints = [
    api('/api/library').then((result) => { state.value.library = result.library; }),
    api('/api/saved-guides').then((result) => { state.value.savedGuides = result.guides; }),
  ];
  const outcomes = await Promise.allSettled(endpoints);
  const failure = outcomes.find((outcome) => outcome.status === 'rejected');
  if (failure) notify(failure.reason.message);
}

async function submitAuth() {
  submitting.value = true;
  state.value.error = '';
  try {
    const signup = state.value.authMode === 'signup';
    const result = await api(signup ? '/api/register' : '/api/login', {
      method: 'POST',
      body: signup
        ? authForm.value
        : { email: authForm.value.email, password: authForm.value.password },
    });
    state.value.user = result.user;
    await reloadData();
    state.value.modal = '';
    notify(signup ? `Welcome, ${result.user.name}! Your side quest starts now. 🎮` : `Welcome back, ${result.user.name}. The backlog awaits.`);
  } catch (error) {
    state.value.error = error.message;
  } finally {
    submitting.value = false;
  }
}

async function logout() {
  try {
    await api('/api/logout', { method: 'POST' });
    state.value.user = null;
    state.value.interests = ['Adventure'];
    state.value.library = [];
    state.value.savedGuides = [];
    state.value.view = 'discover';
    await reloadData();
    notify('Logged out. Your save file is safe; your session is gone.');
  } catch (error) { notify(error.message); }
}

async function toggleInterest(interest) {
  if (!state.value.interests.includes(interest) && state.value.interests.length >= 5) {
    notify('Pick up to five game vibes at once. The algorithm is powerful, not a wizard.');
    return;
  }
  const previous = [...state.value.interests];
  state.value.showAllGames = false;
  state.value.interests = previous.includes(interest)
    ? previous.filter((item) => item !== interest)
    : [...previous, interest];
  if (!state.value.user) {
    notify('Log in to sync your game taste across sessions.');
    await refreshGames();
    state.value.interests = previous.includes(interest)
      ? previous.filter((item) => item !== interest)
      : [...previous, interest];
    state.value.games = state.value.games.map((game) => {
      const overlap = game.tags.filter((tag) => state.value.interests.includes(tag)).length;
      return { ...game, match: state.value.interests.length ? Math.round(overlap / Math.max(game.tags.length, 1) * 100) : game.match };
    });
    state.value.games.sort((a, b) => b.match - a.match || a.name.localeCompare(b.name));
    return;
  }
  try {
    await api('/api/interests', { method: 'PUT', body: { interests: state.value.interests } });
    await refreshGames();
  } catch (error) {
    state.value.interests = previous;
    notify(error.message);
  }
}

async function setLibraryStatus(game, status) {
  if (!requireLogin('Log in to keep track of your backlog. Yes, even the games you bought on sale “for later.”')) return;
  try {
    const result = await api(`/api/games/${game.id}/library`, { method: 'PUT', body: { status } });
    await Promise.all([refreshGames(), loadPersonalLists()]);
    if (state.value.selectedGame?.id === game.id) state.value.selectedGame.libraryStatus = result.status;
    const names = { wishlist: 'Backlog', playing: 'Playing', completed: 'Completed', dropped: 'Dropped' };
    notify(result.status ? `${game.name} added to ${names[result.status]}. Future-you says thanks.` : 'Game removed from your library.');
  } catch (error) { notify(error.message); }
}

async function cycleLibrary(game) {
  const order = [null, 'wishlist', 'playing', 'completed', 'dropped'];
  const current = order.indexOf(game.libraryStatus ?? null);
  await setLibraryStatus(game, order[(current + 1) % order.length]);
}

async function toggleGuideAction(guide, action) {
  if (!requireLogin('Log in to save guides and give the helpful ones their flowers.')) return;
  const endpoint = action === 'save' ? 'save' : 'helpful';
  try {
    const response = await api(`/api/guides/${guide.id}/${endpoint}`, { method: 'POST' });
    if (action === 'save') {
      guide.savedByMe = response.saved;
      await loadPersonalLists();
      notify(response.saved ? 'Guide saved. Future-you owes present-you a high five.' : 'Guide removed from your saves.');
    } else {
      guide.helpful = response.helpful;
      guide.helpfulByMe = response.helpfulByMe;
      notify(response.helpfulByMe ? 'Helpful vote counted. The guide goblin approves.' : 'Helpful vote removed.');
    }
  } catch (error) { notify(error.message); }
}

async function submitGuide() {
  submitting.value = true;
  state.value.error = '';
  try {
    await api('/api/guides', { method: 'POST', body: guideForm.value });
    state.value.modal = '';
    state.value.view = 'guides';
    state.value.showAllGuides = true;
    await Promise.all([refreshGuides(), refreshStats()]);
    notify('Guide published. May your readers never need a 40-minute tutorial.');
  } catch (error) {
    state.value.error = error.message;
  } finally { submitting.value = false; }
}

async function submitReview() {
  submitting.value = true;
  state.value.error = '';
  try {
    await api('/api/reviews', {
      method: 'POST',
      body: { ...reviewForm.value, rating: Number(reviewForm.value.rating), hoursPlayed: Number(reviewForm.value.hoursPlayed || 0) },
    });
    state.value.modal = '';
    await Promise.all([refreshStats(), refreshGames()]);
    notify('Review posted. Your honest take: more useful than another 90-minute video.');
    state.value.view = 'reviews';
  } catch (error) {
    state.value.error = error.message;
  } finally { submitting.value = false; }
}

async function openGuide(guide) {
  state.value.selectedGuide = guide;
  state.value.comments = [];
  state.value.modal = 'guide-detail';
  try {
    const result = await api(`/api/guides/${guide.id}`);
    state.value.selectedGuide = { ...guide, ...result.guide };
    state.value.comments = result.comments;
  } catch (error) { notify(error.message); }
}

async function submitComment() {
  if (!state.value.selectedGuide || !requireLogin('Log in to join the comments. Be nice; it is just a game.')) return;
  const body = commentDraft.value.trim();
  if (body.length < 2) {
    state.value.error = 'Add at least two characters. Even “gg” deserves a little more.';
    return;
  }
  submitting.value = true;
  state.value.error = '';
  try {
    const result = await api(`/api/guides/${state.value.selectedGuide.id}/comments`, { method: 'POST', body: { body } });
    state.value.comments.push(result.comment);
    state.value.selectedGuide.commentsCount += 1;
    commentDraft.value = '';
  } catch (error) { state.value.error = error.message; }
  finally { submitting.value = false; }
}

async function openGame(game) {
  state.value.selectedGame = game;
  state.value.modal = 'game-detail';
  try {
    const result = await api(`/api/games/${game.id}`);
    state.value.selectedGame = { ...game, ...result.game };
    state.value.gameReviews = result.reviews;
  } catch (error) { notify(error.message); }
}

function openReview(review) {
  state.value.selectedReview = review;
  state.value.modal = 'review-detail';
}

async function saveProfile() {
  submitting.value = true;
  state.value.error = '';
  try {
    const result = await api('/api/profile', { method: 'PUT', body: profileForm.value });
    state.value.user = result.user;
    state.value.modal = '';
    notify('Profile updated. Extremely normal profile-picture reveal pending.');
  } catch (error) { state.value.error = error.message; }
  finally { submitting.value = false; }
}

async function sharePage() {
  try {
    await navigator.clipboard.writeText(window.location.href);
    notify('Link copied. Go forth and enable your friends’ backlog.');
  } catch {
    notify(`Your share link is ${window.location.href}`);
  }
}

onMounted(async () => {
  try {
    const [session, games, guides, reviewResult, stats] = await Promise.all([
      api('/api/session'),
      api('/api/games'),
      api('/api/guides'),
      api('/api/reviews'),
      api('/api/stats'),
    ]);
    state.value.user = session.user;
    state.value.interests = session.interests?.length ? session.interests : ['Adventure'];
    state.value.games = games.games;
    state.value.guides = guides.guides;
    state.value.reviews = reviewResult.reviews;
    state.value.summary = reviewResult.summary;
    state.value.stats = stats;
    if (session.user) await loadPersonalLists();
  } catch (error) {
    state.value.error = error.message;
    notify(`The database did a little oopsie: ${error.message}`);
  } finally { state.value.loading = false; }
});
</script>

<template>
  <header class="topbar">
    <a class="brand" href="#discover" aria-label="StinkyGuide home" @click="navigate('discover')">
      <span class="brand-mark">s<span>.</span></span><span>stinky<span class="brand-light">guide</span></span>
    </a>
    <nav class="main-nav" :class="{ open: mobileNav }" aria-label="Main navigation">
      <a class="nav-link" :class="{ active: state.view === 'discover' }" href="#discover" @click="navigate('discover')">Discover</a>
      <a class="nav-link" :class="{ active: state.view === 'guides' }" href="#guides" @click="navigate('guides')">Guides</a>
      <a class="nav-link" :class="{ active: state.view === 'reviews' }" href="#reviews" @click="navigate('reviews')">Reviews</a>
      <a class="nav-link" :class="{ active: state.view === 'saved' }" href="#saved" @click.prevent="navigate('saved')">Saved <span class="nav-spark">♡</span></a>
      <a class="nav-link" :class="{ active: state.view === 'library' }" href="#library" @click.prevent="navigate('library')">My games <span class="nav-spark">▤</span></a>
      <a v-if="state.user?.is_admin" class="nav-link" :class="{ active: state.view === 'admin' }" href="#admin" @click.prevent="navigate('admin')">Admin <span class="nav-spark">⚙</span></a>
    </nav>
    <div class="topbar-actions">
      <button class="icon-button search-toggle" aria-label="Focus search" title="Search" @click="focusSearch">⌕</button>
      <template v-if="!state.user">
        <button class="button button-quiet" @click="openAuth('login')">Log in</button>
        <button class="button button-accent" @click="openAuth('signup')">Join free <span aria-hidden="true">↗</span></button>
      </template>
      <template v-else>
        <button class="button button-quiet profile-name" @click="state.modal = 'profile'">Hi, {{ state.user.name }} <span>↗</span></button>
        <button class="avatar-button" aria-label="Open your profile" @click="state.modal = 'profile'">{{ state.user.name.slice(0, 1).toUpperCase() }}</button>
      </template>
      <button class="mobile-menu" aria-label="Open navigation" @click="mobileNav = !mobileNav">☰</button>
    </div>
  </header>

  <div v-if="state.loading" class="loading-screen"><span class="loading-mark">s.</span><span>Loading the backlog…</span><span class="loading-caption">It's a big one.</span></div>

  <template v-else>
    <main v-if="state.view === 'discover' || state.view === 'guides' || state.view === 'reviews'">
      <section class="hero page-width" id="discover">
        <div class="hero-copy">
            <div class="eyebrow"><span class="live-dot"></span> A LITTLE SIDE QUEST FOR YOUR NEXT FAVORITE</div>
            <h1>Good games.<br /><span>Great game pals.</span></h1>
            <p class="hero-intro">Find your next obsession, trade the tips you wish you had, and help somebody else finally beat that boss.</p>
          <form class="search-bar" role="search" @submit.prevent="searchEverything">
            <span class="search-icon" aria-hidden="true">⌕</span>
            <input id="search-input" v-model="state.search" type="search" placeholder="Search games, guides, creators..." aria-label="Search games, guides, and creators" />
            <kbd>↵</kbd>
          </form>
          <div class="trending-row"><span>TRY:</span><button class="trend-chip" @click="state.search = 'Elden Ring'">Elden Ring</button><button class="trend-chip" @click="state.search = 'cozy'">cozy games</button><button class="trend-chip" @click="state.search = 'beginner'">beginner tips</button></div>
        </div>
        <button v-if="featuredGame" class="hero-art" :style="{ backgroundImage: `linear-gradient(90deg,rgba(11,13,11,.62),rgba(11,13,11,.04) 95%),linear-gradient(0deg,rgba(10,11,9,.72),transparent 57%),url('${featuredGame.imageUrl}')` }" aria-label="Explore Elden Ring" @click="openGame(featuredGame)">
          <span class="art-content"><span class="art-tag">THE COMMUNITY PICK</span><span class="art-game-title">{{ featuredGame.name.toUpperCase() }}</span><span class="art-caption">An extremely normal amount of boss attempts.</span></span>
          <span class="art-stats"><span>✦ BOSS-FIGHT ENERGY</span><span>★ {{ featuredGame.rating }}</span></span>
          <span class="art-label">FEATURED GAME <span>◈ {{ featuredGame.genre }}</span></span>
        </button>
      </section>

      <section class="stats-strip page-width" aria-label="Community statistics">
        <div><strong>{{ state.stats.guides.toLocaleString() }}</strong><span>guides shared</span></div>
        <div><strong>{{ state.stats.games.toLocaleString() }}</strong><span>games covered</span></div>
        <div><strong>{{ state.stats.players.toLocaleString() }}</strong><span>player accounts</span></div>
        <div class="stats-note">Built by players, <span>for players (and respawns).</span></div>
      </section>

      <section class="section page-width" id="for-you">
        <div class="section-heading"><div><div class="eyebrow">HAND-PICKED FOR YOUR PLAYER TWO ENERGY <span class="heading-spark">✳</span></div><h2>Your next <span>side quest.</span></h2><p class="section-description">Pick what you love. We’ll put the closest matches first—no loot boxes, just good games.</p></div><button class="text-button" @click="notify('Pick a few game vibes and we’ll sort your recommendations.')">Tune my picks <span>↗</span></button></div>
        <div class="interest-row" aria-label="Choose your gaming interests">
          <button v-for="interest in interestOptions" :key="interest.name" class="interest-chip" :class="{ selected: state.interests.includes(interest.name) }" @click="toggleInterest(interest.name)">{{ interest.icon }} &nbsp;{{ interest.name === 'Indie' ? 'Indie gems' : interest.name }}</button>
          <span class="interest-hint">Pick a few and your best matches bubble to the top.</span>
        </div>
        <div v-if="filteredGames.length" class="game-grid">
          <article v-for="game in filteredGames.slice(0, state.showAllGames ? filteredGames.length : 4)" :key="game.id" class="game-card">
            <button class="game-art" :style="{ backgroundImage: `linear-gradient(0deg,rgba(13,14,12,.45),transparent 70%),url('${game.imageUrl}')` }" :aria-label="`View ${game.name}`" @click="openGame(game)"><span class="game-platform">{{ game.platform }}</span><span class="art-word">{{ game.name.toUpperCase() }}</span></button>
            <div class="game-card-info"><div class="game-meta"><button class="plain-title" @click="openGame(game)">{{ game.name }}</button><span class="game-rating">★ {{ game.rating }}</span></div><div class="game-genre">{{ game.genre }}</div><div class="game-match"><span>{{ game.match }}% match</span><span class="match-bar"><span :style="{ width: `${game.match}%` }"></span></span></div><span class="game-match-reason">{{ recommendationReason(game) }}</span><button class="game-library-button" :class="`library-${game.libraryStatus || 'none'}`" @click="cycleLibrary(game)">{{ game.libraryStatus ? `${libraryStatuses.find((item) => item.value === game.libraryStatus)?.label} · change` : '＋ Add to my games' }}</button></div>
          </article>
        </div>
        <p v-else class="section-description empty-state">No games match that search. Try the other 47,000 on your wishlist.</p>
        <div class="center-action"><button v-if="filteredGames.length > 4" class="button button-outline" @click="state.showAllGames = !state.showAllGames">{{ state.showAllGames ? 'Show top matches' : `See all ${filteredGames.length} game picks` }} <span>{{ state.showAllGames ? '↑' : '→' }}</span></button><button class="button button-outline" @click="navigate('library')">View my game library <span>→</span></button></div>
      </section>

      <section class="section guides-section" id="guides">
        <div class="page-width">
          <div class="section-heading"><div><div class="eyebrow">PLAYER KNOW-HOW, FRESH FROM THE CAMPFIRE</div><h2>Guides that <span>get it.</span></h2><p class="section-description">Any game, any genre, any “how do I open this door?” moment. Your guide belongs here.</p></div><button class="button button-outline" @click="openGuideForm">＋ &nbsp;Write a guide</button></div>
          <div class="guide-toolbar"><div class="filter-tabs" role="tablist" aria-label="Filter guides"><button v-for="filter in guideTypes" :key="filter.value" class="filter-tab" :class="{ active: state.guideFilter === filter.value }" role="tab" @click="state.guideFilter = filter.value; state.showAllGuides = false">{{ filter.label }} <span v-if="filter.value === 'all'">{{ state.guides.length }}</span></button></div><button class="sort-button" @click="state.guideSort = state.guideSort === 'helpful' ? 'newest' : 'helpful'">↕ &nbsp;{{ state.guideSort === 'helpful' ? 'Most helpful' : 'Newest first' }}</button></div>
          <div v-if="filteredGuides.length" class="guide-grid">
            <article v-for="guide in filteredGuides" :key="guide.id" class="guide-card">
              <div class="guide-card-top"><span class="guide-game">{{ guide.game }}</span><span class="guide-category">{{ guide.tag }}</span></div>
              <button class="guide-title-button" @click="openGuide(guide)"><h3>{{ guide.title }}</h3></button>
              <p class="guide-summary">{{ guide.summary }}</p>
              <div class="guide-card-bottom"><div class="guide-author"><span class="guide-author-avatar">{{ guide.avatar }}</span><span><strong>{{ guide.author }}</strong><small>{{ timeAgo(guide.createdAt) }} · {{ guide.commentsCount }} chat</small></span></div><div class="guide-card-actions"><button class="guide-save" :class="{ saved: guide.savedByMe }" :aria-label="guide.savedByMe ? 'Unsave guide' : 'Save guide'" @click="toggleGuideAction(guide, 'save')">{{ guide.savedByMe ? '▣' : '♧' }}</button><button class="guide-helpful" :class="{ liked: guide.helpfulByMe }" aria-label="Mark guide helpful" @click="toggleGuideAction(guide, 'helpful')">{{ guide.helpfulByMe ? '♥' : '♡' }} &nbsp;{{ guide.helpful }}</button></div></div>
            </article>
          </div>
          <p v-else class="section-description empty-state">No guides found. This is your sign to write the one you needed.</p>
          <div class="center-action"><button class="button button-outline" @click="state.showAllGuides = !state.showAllGuides">{{ state.showAllGuides ? 'Show fewer guides' : 'Explore all guides' }} <span>{{ state.showAllGuides ? '↑' : '→' }}</span></button></div>
        </div>
      </section>

      <section class="section reviews-section page-width" id="reviews">
        <div class="section-heading"><div><div class="eyebrow">REAL TAKES, NO HYPE</div><h2>Before you <span>press play.</span></h2><p class="section-description">Hot takes from people with actual hours on the clock. You're welcome.</p></div><button class="button button-outline" @click="openReviewForm()">✎ &nbsp;Write a review</button></div>
        <div class="review-toolbar review-browser-toolbar">
          <label class="review-search"><span aria-hidden="true">⌕</span><input v-model="state.reviewSearch" type="search" placeholder="Search reviews, games, players…" aria-label="Search player reviews" /></label>
          <select v-model="state.reviewGameFilter" aria-label="Filter reviews by game"><option value="">All games</option><option v-for="game in reviewGames" :key="game" :value="game">{{ game }}</option></select>
          <select v-model.number="state.reviewFilter" aria-label="Filter reviews by rating"><option :value="0">Any rating</option><option v-for="rating in [5,4,3,2,1]" :key="rating" :value="rating">{{ rating }} stars</option></select>
          <select v-model="state.reviewSort" aria-label="Sort player reviews"><option value="newest">Newest first</option><option value="highest">Highest rated</option><option value="lowest">Lowest rated</option></select>
        </div>
        <div class="review-browser-summary"><span>{{ filteredReviews.length }} player-submitted review{{ filteredReviews.length === 1 ? '' : 's' }}</span><span>Only reviews from StinkyGuide accounts. No made-up critics.</span></div>
        <div v-if="featuredFilteredReview" class="review-layout">
          <article class="review-feature"><div class="review-topline"><span class="review-label">PLAYER SPOTLIGHT</span><span class="review-score">{{ (featuredFilteredReview.rating * 2).toFixed(1) }} <small>/ 10</small></span></div><div class="stars" :aria-label="`${featuredFilteredReview.rating} out of 5 stars`">{{ '★'.repeat(featuredFilteredReview.rating) }}{{ '☆'.repeat(5 - featuredFilteredReview.rating) }}</div><h3>“{{ featuredFilteredReview.game }}: {{ featuredFilteredReview.body.slice(0, 70) }}{{ featuredFilteredReview.body.length > 70 ? '…' : '' }}”</h3><p>{{ featuredFilteredReview.body.slice(0, 260) }}{{ featuredFilteredReview.body.length > 260 ? '…' : '' }}</p><div class="review-byline"><span class="mini-avatar avatar-pink">{{ featuredFilteredReview.author.slice(0, 1).toUpperCase() }}</span><span><strong>{{ featuredFilteredReview.author }}</strong><small>Played {{ featuredFilteredReview.hoursPlayed }} hours · {{ timeAgo(featuredFilteredReview.createdAt) }}</small></span><span class="reader-rating">★ {{ featuredFilteredReview.rating }}/5</span></div><button class="button button-outline review-read-button" @click="openReview(featuredFilteredReview)">Read full review <span>↗</span></button></article>
          <aside class="review-sidebar"><div class="review-scorecard"><span>COMMUNITY SCORE</span><strong>{{ state.summary.count ? state.summary.averageRating.toFixed(1) : '—' }} <small>/ 5</small></strong><span class="score-stars">{{ state.summary.count ? '★'.repeat(Math.round(state.summary.averageRating)) + '☆'.repeat(5 - Math.round(state.summary.averageRating)) : '☆☆☆☆☆' }}</span><small>From {{ state.summary.count }} player-submitted reviews</small></div><div class="review-trust-card"><span>✓</span><div><strong>Real player takes</strong><p>Reviews are written by logged-in StinkyGuide players. Sample showcase entries never count.</p></div></div><button class="button button-accent review-sidebar-cta" @click="openReviewForm()">✎ &nbsp;Write your review</button></aside>
        </div>
        <div v-else class="review-empty"><span>★</span><h3>{{ state.reviews.length ? 'No reviews match those filters.' : 'No player reviews yet.' }}</h3><p>{{ state.reviews.length ? 'Try clearing a filter—or blame the search goblin.' : 'The showcase samples are not real player reviews, so they do not appear here. Be the first logged-in player to leave a real one.' }}</p><button v-if="!state.reviews.length" class="button button-accent" @click="openReviewForm()">Be the first to review</button><button v-else class="button button-outline" @click="state.reviewSearch = ''; state.reviewGameFilter = ''; state.reviewFilter = 0; state.reviewSort = 'newest'; state.showAllReviews = false">Clear filters</button></div>
        <div v-if="otherVisibleReviews.length" class="review-list">
          <article v-for="review in otherVisibleReviews" :key="review.id" class="review-list-card">
            <span class="mini-avatar">{{ review.author.slice(0, 1).toUpperCase() }}</span>
            <div class="review-list-content">
              <div class="review-list-title"><strong>{{ review.game }}</strong><span class="mini-stars">{{ '★'.repeat(review.rating) }}{{ '☆'.repeat(5 - review.rating) }}</span></div>
              <p>{{ review.body.slice(0, 180) }}{{ review.body.length > 180 ? '…' : '' }}</p>
              <small>{{ review.author }} · {{ review.hoursPlayed }} hours played · {{ timeAgo(review.createdAt) }}</small>
              <button class="text-button review-card-open" @click="openReview(review)">Read full review →</button>
            </div>
          </article>
        </div>
        <div v-if="filteredReviews.length > 7" class="center-action"><button class="button button-outline" @click="state.showAllReviews = !state.showAllReviews">{{ state.showAllReviews ? 'Show fewer reviews' : `Show all ${filteredReviews.length} reviews` }} <span>{{ state.showAllReviews ? '↑' : '→' }}</span></button></div>
      </section>

      <section class="creator-cta page-width"><div class="cta-decoration">✳</div><div><div class="eyebrow">YOU KNOW THINGS. SHARE THEM.</div><h2>Your next great guide<br />starts <span>right here.</span></h2><p>Help someone get unstuck. Share that build. Become the tab they never close.</p></div><button class="button button-dark" @click="openGuideForm">Start writing <span>↗</span></button><span class="cta-footnote">Takes about 5 minutes. Gets read for years.</span></section>
    </main>

    <main v-else-if="state.view === 'admin'" class="section page-width admin-dashboard">
      <div class="section-heading">
        <div><div class="eyebrow">THE SWAMP CONTROL ROOM</div><h2>Admin <span>dashboard.</span></h2><p class="section-description">Community traffic and moderation. Visitor IPs, query strings, and request contents are not collected.</p></div>
        <button class="button button-outline" :disabled="state.adminLoading" @click="loadAdminData">↻ &nbsp;Refresh</button>
      </div>
      <p v-if="state.adminLoading && !state.adminData" class="section-description">Counting the little footsteps…</p>
      <template v-else-if="state.adminData">
        <section class="admin-metrics" aria-label="Traffic overview">
          <article class="admin-metric"><span>REQUESTS · 24 HOURS</span><strong>{{ state.adminData.traffic.requests24h.toLocaleString() }}</strong><small>Web and API requests</small></article>
          <article class="admin-metric"><span>SIGNED-IN PLAYERS · 24 HOURS</span><strong>{{ state.adminData.traffic.players24h.toLocaleString() }}</strong><small>Distinct authenticated accounts</small></article>
          <article class="admin-metric"><span>GUIDES</span><strong>{{ state.adminData.content.guides.length }}</strong><small>Latest 20 shown below</small></article>
          <article class="admin-metric"><span>REVIEWS</span><strong>{{ state.adminData.content.reviews.length }}</strong><small>Latest 20 shown below</small></article>
        </section>
        <section class="admin-panel">
          <div class="admin-panel-heading"><div><h3>Traffic over the last 7 days</h3><p>Counts are based on the route template, not full URLs.</p></div></div>
          <div class="admin-chart">
            <div v-for="day in state.adminData.traffic.daily" :key="day.day" class="admin-chart-column">
              <span>{{ day.requests }}</span>
              <div class="admin-chart-track"><i :style="{ height: `${Math.max(8, day.requests / Math.max(...state.adminData.traffic.daily.map((point) => point.requests), 1) * 100)}%` }"></i></div>
              <small>{{ new Date(`${day.day}T00:00:00`).toLocaleDateString(undefined, { weekday: 'short' }) }}</small>
            </div>
            <p v-if="!state.adminData.traffic.daily.length" class="section-description">No recorded traffic yet.</p>
          </div>
          <div class="admin-traffic-details">
            <div><h4>Popular pages · 24h</h4><ul v-if="state.adminData.traffic.popularPages.length"><li v-for="page in state.adminData.traffic.popularPages" :key="page.path"><code>{{ page.path }}</code><strong>{{ page.requests }}</strong></li></ul><p v-else class="section-description">No page views yet.</p></div>
            <div><h4>Response statuses · 24h</h4><ul v-if="state.adminData.traffic.statusCodes.length"><li v-for="status in state.adminData.traffic.statusCodes" :key="status.status"><code>{{ status.status }}</code><strong>{{ status.requests }}</strong></li></ul><p v-else class="section-description">No requests yet.</p></div>
          </div>
        </section>
        <section class="admin-panel">
          <div class="admin-panel-heading"><div><h3>Community moderation</h3><p>Remove guides, reviews, or comments that violate community rules.</p></div></div>
          <div class="admin-content-grid">
            <section class="admin-content-list"><h4>Guides <span>{{ state.adminData.content.guides.length }}</span></h4><p v-if="!state.adminData.content.guides.length" class="section-description">No guides to moderate.</p><article v-for="guide in state.adminData.content.guides" :key="guide.id" class="admin-content-row"><div><strong>{{ guide.title }}</strong><small>{{ guide.game }} · by {{ guide.author }}</small></div><button class="admin-delete" :disabled="state.adminDeleting === `guides:${guide.id}`" @click="deleteCommunityContent('guides', guide)">Delete</button></article></section>
            <section class="admin-content-list"><h4>Reviews <span>{{ state.adminData.content.reviews.length }}</span></h4><p v-if="!state.adminData.content.reviews.length" class="section-description">No reviews to moderate.</p><article v-for="review in state.adminData.content.reviews" :key="review.id" class="admin-content-row"><div><strong>{{ review.game }}</strong><small>{{ review.body }} · by {{ review.author }}</small></div><button class="admin-delete" :disabled="state.adminDeleting === `reviews:${review.id}`" @click="deleteCommunityContent('reviews', review)">Delete</button></article></section>
            <section class="admin-content-list"><h4>Comments <span>{{ state.adminData.content.comments.length }}</span></h4><p v-if="!state.adminData.content.comments.length" class="section-description">No comments to moderate.</p><article v-for="comment in state.adminData.content.comments" :key="comment.id" class="admin-content-row"><div><strong>{{ comment.guide }}</strong><small>{{ comment.body }} · by {{ comment.author }}</small></div><button class="admin-delete" :disabled="state.adminDeleting === `comments:${comment.id}`" @click="deleteCommunityContent('comments', comment)">Delete</button></article></section>
          </div>
        </section>
      </template>
    </main>

    <main v-else class="section page-width dashboard-view">
      <div class="section-heading"><div><div class="eyebrow">{{ state.view === 'library' ? 'YOUR BACKLOG, NO JUDGMENT' : 'THE GOOD STUFF YOU SAVED' }}</div><h2>{{ state.view === 'library' ? 'My games <span>& backlog.</span>' : 'Saved for <span>later.</span>' }}</h2><p class="section-description">{{ state.view === 'library' ? 'A place for the games you finished, play, or absolutely bought “on sale for later.”' : 'Future-you knew what was good. Past-you saved it.' }}</p></div><div class="dashboard-actions"><button class="button button-outline" @click="sharePage">↗ &nbsp;Share</button><button class="button button-accent" @click="navigate('discover')">Find more games</button></div></div>
      <template v-if="state.view === 'library'">
        <div v-if="!state.user" class="empty-dashboard"><span>▤</span><h3>Your games, your little digital shelf.</h3><p>Log in and add games to your backlog. You are allowed to put “finish that RPG” on the list again.</p><button class="button button-accent" @click="openAuth('login')">Log in to see my games</button></div>
        <div v-else-if="state.library.length" class="library-grid"><article v-for="entry in state.library" :key="entry.game.id" class="library-card"><img :src="entry.game.imageUrl" :alt="`${entry.game.name} cover art`" loading="lazy" /><div><span class="guide-category">{{ libraryStatuses.find((item) => item.value === entry.status)?.label }}</span><h3>{{ entry.game.name }}</h3><small>{{ entry.game.genre }}</small><button class="text-button" @click="cycleLibrary({ ...entry.game, libraryStatus: entry.status })">Change status ↗</button></div></article></div>
        <div v-else class="empty-dashboard"><span>🎮</span><h3>Your backlog is empty. Suspicious.</h3><p>Add games to your library and watch your “I’ll play it eventually” collection grow.</p><button class="button button-accent" @click="navigate('discover')">Find your next game</button></div>
      </template>
      <template v-else>
        <div v-if="!state.user" class="empty-dashboard"><span>♡</span><h3>Save guides for your next boss fight.</h3><p>Make an account to bookmark guides so they’re actually there when you need them.</p><button class="button button-accent" @click="openAuth('login')">Log in to see saved guides</button></div>
        <div v-else-if="state.savedGuides.length" class="guide-grid"><article v-for="guide in state.savedGuides" :key="guide.id" class="guide-card"><div class="guide-card-top"><span class="guide-game">{{ guide.game }}</span><span class="guide-category">{{ guide.tag }}</span></div><button class="guide-title-button" @click="openGuide(guide)"><h3>{{ guide.title }}</h3></button><p class="guide-summary">{{ guide.summary }}</p><div class="guide-card-bottom"><div class="guide-author"><span class="guide-author-avatar">{{ guide.avatar }}</span><span><strong>{{ guide.author }}</strong><small>{{ guide.commentsCount }} comments</small></span></div><button class="guide-helpful liked" @click="toggleGuideAction(guide, 'save')">▣ &nbsp;Saved</button></div></article></div>
        <div v-else class="empty-dashboard"><span>♧</span><h3>Nothing saved yet. Not even one guide.</h3><p>Find a guide worth remembering. Your next play session can have fewer wiki tabs.</p><button class="button button-accent" @click="navigate('guides')">Explore guides</button></div>
      </template>
    </main>

    <footer class="footer page-width"><a class="brand footer-brand" href="#discover" @click="navigate('discover')"><span class="brand-mark">s<span>.</span></span><span>stinky<span class="brand-light">guide</span></span></a><p>Made for the love of the game (and patch notes). <span>✳</span></p><div><a href="#discover" @click="navigate('discover')">Discover</a><a href="#guides" @click="navigate('guides')">Guides</a><a href="#reviews" @click="navigate('reviews')">Reviews</a><button @click="sharePage">Share</button></div><small>© {{ new Date().getFullYear() }} StinkyGuide · Keep it kind. Keep it helpful. Backlogs are forever.</small></footer>
  </template>

  <div class="toast" :class="{ visible: state.toast }" role="status" aria-live="polite">{{ state.toast }}</div>

  <dialog ref="dialog" class="modal" :class="{ 'wide-modal': ['guide', 'review', 'review-detail', 'guide-detail', 'game-detail', 'profile'].includes(state.modal) }" @close="state.modal = ''; state.error = ''">
    <form method="dialog" class="modal-close-form"><button class="modal-close" aria-label="Close">×</button></form>
    <template v-if="state.modal === 'auth'">
      <div class="modal-eyebrow">YOUR CORNER OF THE INTERNET</div><h2>{{ state.authMode === 'signup' ? 'Find your people.' : 'Good to have you.' }}</h2><p>{{ state.authMode === 'signup' ? 'Create an account. Your game backlog deserves better than a Notes app.' : 'Log in to save guides, review games, and resume your very important research.' }}</p>
      <form class="modal-form" @submit.prevent="submitAuth"><label v-if="state.authMode === 'signup'">Your player name<input v-model="authForm.name" minlength="2" maxlength="24" placeholder="e.g. mossy_moth" required /></label><label>Email address<input v-model="authForm.email" type="email" placeholder="you@example.com" required /></label><label>Password<input v-model="authForm.password" type="password" minlength="8" maxlength="128" placeholder="At least 8 characters" required /></label><p v-if="state.error" class="form-error" role="alert">{{ state.error }}</p><button class="button button-accent modal-submit" :disabled="submitting">{{ submitting ? 'One sec…' : state.authMode === 'signup' ? 'Create my account →' : 'Log in →' }}</button></form>
      <p class="auth-switch">{{ state.authMode === 'signup' ? 'Already one of us?' : 'New around here?' }} <button type="button" @click="state.authMode = state.authMode === 'signup' ? 'login' : 'signup'; state.error = ''">{{ state.authMode === 'signup' ? 'Log in' : 'Join the community' }}</button></p><p class="demo-note">Your account is stored in this site's database. We hash passwords and never put them in your profile.</p>
    </template>

    <template v-else-if="state.modal === 'guide'">
      <div class="modal-eyebrow">MAKE SOMEONE'S NEXT RUN BETTER</div><h2>Write a <span>guide.</span></h2><p>Any game is welcome—even the tiny indie one with 14 players. Be specific, be kind, and share what you wish you'd known.</p>
      <form class="modal-form" @submit.prevent="submitGuide"><label>Guide title<input v-model="guideForm.title" minlength="8" maxlength="90" placeholder="e.g. The no-stress guide to your first run" required /></label><div class="form-row"><label>Game <span class="optional-label">Any game, not just our picks</span><input v-model="guideForm.game" list="guide-game-options" minlength="2" maxlength="60" placeholder="Type any game name" required /><datalist id="guide-game-options"><option v-for="game in state.games" :key="game.id" :value="game.name" /></datalist></label><label>Guide type<select v-model="guideForm.type"><option value="beginner">Beginner friendly</option><option value="walkthrough">Walkthrough</option><option value="builds">Builds & loadouts</option><option value="bosses">Boss strategies</option><option value="secrets">Quests & secrets</option><option value="collectibles">Collectibles</option><option value="achievements">Achievements</option><option value="tips">Tips & tricks</option><option value="other">Something else</option></select></label></div><label>Your guide<textarea v-model="guideForm.body" rows="6" minlength="30" maxlength="3000" placeholder="Break it down into useful steps. What should a player know?" required></textarea><small class="character-count">{{ guideForm.body.length }} / 3,000 characters. Concision is a build.</small></label><p v-if="state.error" class="form-error" role="alert">{{ state.error }}</p><button class="button button-accent modal-submit" :disabled="submitting">{{ submitting ? 'Publishing…' : 'Publish guide ↗' }}</button></form>
    </template>

    <template v-else-if="state.modal === 'review'">
      <div class="modal-eyebrow">TELL IT LIKE IT IS</div><h2>Leave a <span>review.</span></h2><p>Help someone decide if this one's for them. No spoilers without a heads-up.</p>
      <form class="modal-form" @submit.prevent="submitReview"><label>Game<input v-model="reviewForm.game" list="review-game-options" minlength="2" maxlength="60" placeholder="What have you been playing?" required /><datalist id="review-game-options"><option v-for="game in state.games" :key="game.id" :value="game.name" /></datalist></label><label>Your rating<select v-model.number="reviewForm.rating"><option :value="5">★★★★★ — Loved it</option><option :value="4">★★★★☆ — Really good</option><option :value="3">★★★☆☆ — It was fine</option><option :value="2">★★☆☆☆ — Not for me</option><option :value="1">★☆☆☆☆ — Disappointed</option></select></label><label>Hours played (optional)<input v-model="reviewForm.hoursPlayed" type="number" min="0" max="100000" placeholder="e.g. 42" /></label><label>Your review<textarea v-model="reviewForm.body" rows="5" minlength="20" maxlength="1200" placeholder="What stood out? Who would enjoy it?" required></textarea></label><p v-if="state.error" class="form-error" role="alert">{{ state.error }}</p><button class="button button-accent modal-submit" :disabled="submitting">{{ submitting ? 'Posting…' : 'Post review ↗' }}</button></form>
    </template>

    <template v-else-if="state.modal === 'review-detail' && state.selectedReview">
      <div v-if="gameForReview(state.selectedReview.game)" class="review-detail-cover" :style="{ backgroundImage: `linear-gradient(0deg,#141510 0%,transparent 95%),url('${gameForReview(state.selectedReview.game).imageUrl}')` }"></div>
      <div class="modal-eyebrow">PLAYER REVIEW · {{ state.selectedReview.game }}</div>
      <h2 class="review-detail-title">{{ state.selectedReview.game }}</h2>
      <div class="review-detail-score"><span class="stars">{{ '★'.repeat(state.selectedReview.rating) }}{{ '☆'.repeat(5 - state.selectedReview.rating) }}</span><strong>{{ state.selectedReview.rating }}/5</strong><span>{{ state.selectedReview.hoursPlayed }} hours played</span></div>
      <p class="review-detail-body">{{ state.selectedReview.body }}</p>
      <div class="review-detail-author"><span class="mini-avatar">{{ state.selectedReview.author.slice(0, 1).toUpperCase() }}</span><div><strong>{{ state.selectedReview.author }}</strong><small>Registered player · {{ timeAgo(state.selectedReview.createdAt) }}</small></div></div>
    </template>

    <template v-else-if="state.modal === 'guide-detail' && state.selectedGuide">
      <div class="modal-eyebrow">{{ state.selectedGuide.game }} · {{ state.selectedGuide.tag }}</div><h2 class="detail-title">{{ state.selectedGuide.title }}</h2><div class="detail-byline"><span class="guide-author-avatar">{{ state.selectedGuide.avatar }}</span><strong>{{ state.selectedGuide.author }}</strong><span>{{ timeAgo(state.selectedGuide.createdAt) }}</span></div><p class="guide-full-text">{{ state.selectedGuide.summary }}</p><div class="detail-actions"><button class="guide-helpful" :class="{ liked: state.selectedGuide.helpfulByMe }" @click="toggleGuideAction(state.selectedGuide, 'helpful')">{{ state.selectedGuide.helpfulByMe ? '♥ Helpful' : '♡ This helped me' }} · {{ state.selectedGuide.helpful }}</button><button class="guide-helpful" :class="{ liked: state.selectedGuide.savedByMe }" @click="toggleGuideAction(state.selectedGuide, 'save')">{{ state.selectedGuide.savedByMe ? '▣ Saved' : '♧ Save guide' }}</button><button class="guide-helpful" @click="sharePage">↗ Share</button></div><div class="comments-section"><h3>Campfire chat <span>{{ state.comments.length }} player{{ state.comments.length === 1 ? '' : 's' }}</span></h3><div v-if="state.comments.length" class="comment-list"><article v-for="comment in state.comments" :key="comment.id" class="comment"><span class="guide-author-avatar">{{ comment.author.slice(0, 1).toUpperCase() }}</span><div><strong>{{ comment.author }} <small>{{ timeAgo(comment.createdAt) }}</small></strong><p>{{ comment.body }}</p></div></article></div><p v-else class="section-description">It's quiet around this campfire. Say hi (or drop a well-earned “gg”).</p><form class="comment-form" @submit.prevent="submitComment"><input v-model="commentDraft" maxlength="1000" placeholder="Add a kind, useful comment…" aria-label="Add a comment" /><button class="button button-accent" :disabled="submitting">Send →</button></form><p v-if="state.error" class="form-error">{{ state.error }}</p></div>
    </template>

    <template v-else-if="state.modal === 'game-detail' && state.selectedGame">
      <div class="game-detail-cover" :style="{ backgroundImage: `linear-gradient(0deg,#141510 0%,transparent 95%),url('${state.selectedGame.imageUrl}')` }"></div><div class="modal-eyebrow">{{ state.selectedGame.genre }}</div><h2>{{ state.selectedGame.name }}</h2><div class="game-detail-meta"><span class="review-score">★ {{ state.selectedGame.rating }}</span><span>{{ state.selectedGame.reviewCount }} player reviews</span><span>{{ state.selectedGame.platform }}</span></div><div class="detail-actions"><button class="button button-accent" @click="openReviewForm(state.selectedGame.name)">✎ Write a review</button><button class="button button-outline" @click="cycleLibrary(state.selectedGame)">{{ state.selectedGame.libraryStatus ? `${state.selectedGame.libraryStatus} · change` : '＋ Add to my games' }}</button></div><div class="comments-section"><h3>Player reviews <span>{{ state.gameReviews?.length || 0 }}</span></h3><article v-for="review in state.gameReviews || []" :key="review.id" class="review-list-card"><span class="mini-avatar">{{ review.author.slice(0, 1).toUpperCase() }}</span><div class="review-list-content"><div class="review-list-title"><strong>{{ review.author }}</strong><span class="mini-stars">{{ '★'.repeat(review.rating) }}{{ '☆'.repeat(5 - review.rating) }}</span></div><p>{{ review.body.slice(0, 160) }}{{ review.body.length > 160 ? '…' : '' }}</p><small>{{ review.hoursPlayed }} hours played</small><button class="text-button review-card-open" @click="openReview(review)">Read full review →</button></div></article><p v-if="!state.gameReviews?.length" class="section-description">No registered player has reviewed this game yet. Your hot take awaits.</p></div>
    </template>

    <template v-else-if="state.modal === 'profile'">
      <div class="modal-eyebrow">YOUR SAVE FILE, IRL</div><h2>Player <span>profile.</span></h2><p>Look at you, making this little corner of the internet your own.</p><form class="modal-form" @submit.prevent="saveProfile"><label>Player name<input v-model="profileForm.name" minlength="2" maxlength="24" required /></label><label>About you<textarea v-model="profileForm.bio" maxlength="280" rows="3" placeholder="What do you play when you should be sleeping?" /></label><p class="profile-email">Account email · {{ state.user.email }}</p><p v-if="state.error" class="form-error">{{ state.error }}</p><button class="button button-accent modal-submit" :disabled="submitting">Save profile</button></form><div class="profile-stats"><span><strong>{{ state.stats.guides }}</strong> community guides</span><span><strong>{{ state.stats.reviews }}</strong> player reviews</span><span><strong>{{ state.library.length }}</strong> games in your library</span></div><button class="button button-quiet logout-button" @click="logout">Log out (but your backlog will miss you)</button>
    </template>
  </dialog>
</template>
