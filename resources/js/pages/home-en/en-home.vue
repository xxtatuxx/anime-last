<script setup lang="ts">
import AppLayout from '@/layouts/EN-HomeLayout.vue';
import { ref, onMounted, onUnmounted, watch, nextTick, computed } from 'vue';
import { usePage, router as inertia, Head, Link } from '@inertiajs/vue3';
import { Episode } from '@/types';

// ----- البيانات من السيرفر -----
const page = usePage<{
  episodes: { data: Episode[]; next_page_url?: string; current_page?: number };
  animes?: any[]; 
  news?: any[];
  filters?: { search?: string };
}>();

// ----- السلايدر العلوي (أخبار) -----
const slides = ref(
  page.props.news?.map((n: any) => ({
    id: n.id,
    image: n.image ? `/storage/${n.image}` : '/placeholder.jpg',
    title: n.title_ar,
    subtitle: n.subtitle_ar,
    description: n.description_ar,
    link: n.link_ar
  })) || []
);

const currentSlide = ref(0);
const sliderTrack = ref<HTMLDivElement>();
let interval: number;
let startX = 0;
let endX = 0;
let isDragging = false;
let dragStartX = 0;
let dragCurrentX = 0;
let dragOffset = 0;
const transitionEnabled = ref(true);

function nextSlide() { transitionEnabled.value = true; if (slides.value.length) currentSlide.value = (currentSlide.value + 1) % slides.value.length; }
function prevSlide() { transitionEnabled.value = true; if (slides.value.length) currentSlide.value = (currentSlide.value - 1 + slides.value.length) % slides.value.length; }
function goToSlide(index: number) { transitionEnabled.value = true; currentSlide.value = index; }

onMounted(() => { interval = window.setInterval(nextSlide, 4000); });
onUnmounted(() => { clearInterval(interval); });

function handleTouchStart(e: TouchEvent) { transitionEnabled.value = false; startX = e.touches[0].clientX; if (sliderTrack.value) { clearInterval(interval); } }
function handleTouchMove(e: TouchEvent) {
  if (!sliderTrack.value) return;
  const currentX = e.touches[0].clientX;
  const diff = startX - currentX;
  const slideWidth = sliderTrack.value.offsetWidth;
  dragOffset = (-currentSlide.value * 100) - (diff / slideWidth * 100);
  sliderTrack.value.style.transform = `translateX(${dragOffset}%)`;
}
function handleTouchEnd(e: TouchEvent) {
  transitionEnabled.value = true;
  endX = e.changedTouches[0].clientX;
  const diff = startX - endX;
  if (Math.abs(diff) > 50) { if (diff > 0) nextSlide(); else prevSlide(); }  
  else { nextTick(() => { if (sliderTrack.value) sliderTrack.value.style.transform = `translateX(-${currentSlide.value * 100}%)`; }); }
  interval = window.setInterval(nextSlide, 4000);
}

function handleMouseDown(e: MouseEvent) {
  e.preventDefault(); isDragging = true; transitionEnabled.value = false; dragStartX = e.clientX;
  document.addEventListener('mousemove', handleMouseMove);
  document.addEventListener('mouseup', handleMouseUp);
  clearInterval(interval);
}
function handleMouseMove(e: MouseEvent) {
  if (!isDragging || !sliderTrack.value) return;
  dragCurrentX = e.clientX;
  const diff = dragStartX - dragCurrentX;
  const slideWidth = sliderTrack.value.offsetWidth;
  dragOffset = (-currentSlide.value * 100) - (diff / slideWidth * 100);
  sliderTrack.value.style.transform = `translateX(${dragOffset}%)`;
}
function handleMouseUp() {
  if (!isDragging) return;
  const diff = dragStartX - dragCurrentX;
  const slideWidth = sliderTrack.value?.offsetWidth || 0;
  const threshold = slideWidth * 0.1;
  transitionEnabled.value = true;
  if (Math.abs(diff) > threshold) { if (diff > 0) nextSlide(); else prevSlide(); }  
  else { nextTick(() => { if (sliderTrack.value) sliderTrack.value.style.transform = `translateX(-${currentSlide.value * 100}%)`; }); }
  isDragging = false;
  document.removeEventListener('mousemove', handleMouseMove);
  document.removeEventListener('mouseup', handleMouseUp);
  interval = window.setInterval(nextSlide, 4000);
}

// ----- حلقات Grid -----
const episodes = ref<Episode[]>(page.props.episodes.data || []);
const nextPageUrl = ref(page.props.episodes.next_page_url || null);
const currentPage = ref(page.props.episodes.current_page || 1);
const loadingMore = ref(false);
const search = ref(page.props.filters?.search || '');

// ----- فلترة الأنميات -----
const movies = computed(() =>
  page.props.animes?.filter((anime: any) => anime.type === 'Movie') || []
);
const tvAnimes = computed(() =>
  page.props.animes?.filter((anime: any) => anime.type === 'tv') || []
);

watch(search, (value) => {
  currentPage.value = 1;
  inertia.get('/ar/home', { search: value, page: currentPage.value }, {
    preserveState: true,
    preserveScroll: true,
    only: ['episodes', 'filters', 'animes'],
    onSuccess: (pageResponse) => {
      episodes.value = pageResponse.props.episodes.data;
      nextPageUrl.value = pageResponse.props.episodes.next_page_url || null;
    },
  });
});

const loadMoreEpisodes = () => {
  if (!nextPageUrl.value || loadingMore.value) return;
  loadingMore.value = true;
  currentPage.value++;
  inertia.get('/ar/home', { page: currentPage.value, search: search.value }, {
    preserveState: true,
    preserveScroll: true,
    only: ['episodes', 'animes'],
    onSuccess: (pageResponse) => {
      episodes.value.push(...pageResponse.props.episodes.data);
      nextPageUrl.value = pageResponse.props.episodes.next_page_url || null;
    },
    onFinish: () => { loadingMore.value = false; },
  });
};

// =============================================
//          ⭐ إضافات الـ Modal ⭐
// =============================================
const showModal = ref(false);
const modalTitle = ref('');
const modalDescription = ref('');

/**
  * ⭐ دالة لفتح الـ Modal (معدلة حسب طلبك)
  * @param item - العنصر (حلقة 'episode' أو أنمي 'anime')
  */
function openInfoModal(item: any) {
  // ❗ نستخدم الحقل 'description_ar' كافتراض، قم بتغييره إذا كان اسم الحقل مختلف
  
  // الحالة 1: كرت حلقة (له 'series' أو 'episode_number')
  if (item.series || item.episode_number !== undefined) { 
    modalTitle.value = item.series?.title || 'تفاصيل الحلقة';
    // المنطق: 1. وصف الحلقة نفسها | 2. وصف المسلسل (الأنمي)
    modalDescription.value = item.description_ar || item.series?.description || 'لا يوجد وصف متوفر.';
  } 
  // الحالة 2: كرت أنمي (فيلم أو مسلسل)
  else { 
    modalTitle.value = item.title || 'لا يوجد عنوان';
    // المنطق: 1. وصف الأنمي
    modalDescription.value = item.description || 'لا يوجد وصف متوفر.';
  }
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}
</script>
<template>
  <Head>
    <title>الرئيسية</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap"/>
  </Head>

  <AppLayout>
<!-- Slider -->

<!-- Episodes Grid -->
<div class="relative flex items-center justify-between px-4 mt-4">
  <Link
    href="/your-route"
    class="font-bold text-purple-600 underline dark:text-white"
  >
    View All
  </Link>
  <h2 class="pr-2 text-2xl font-bold text-right text-gray-800 dark:text-white">
    Latest Added Episodes
  </h2>
</div>

<div class="grid grid-cols-2 gap-4 p-4 pb-8 sm:grid-cols-3 lg:grid-cols-5">
  <div
    v-for="episode in episodes"
    :key="episode.id"
    class="overflow-hidden transition-transform bg-white dark:bg-[#171717] rounded-md shadow-md hover:scale-105"
  >
    <div
      class="relative w-full h-40 cursor-pointer"
      @click="inertia.visit(`/episodes/${episode.id}`)"
    >
      <img
        v-if="episode.thumbnail"
        :src="`/storage/${episode.thumbnail}`"
        class="object-cover w-full h-full rounded-t-md"
        alt="Thumbnail"
      />
      <div
        v-else
        class="flex items-center justify-center w-full h-full text-gray-500 bg-gray-200 dark:text-gray-300 dark:bg-gray-800"
      >
        No Image
      </div>

      <div class="absolute bottom-0 left-0 right-0 py-1 text-lg font-bold text-center text-white bg-black/60">
        Episode {{ episode.episode_number }}
      </div>

      <div
        v-if="episode.is_published"
        class="absolute top-1 left-1 bg-green-600 text-white text-xs font-bold rounded-full px-1.5 py-0.5 shadow"
      >
        Now Airing
      </div>
      <div
        v-if="episode.video_format"
        class="absolute top-1 right-1 bg-blue-600 text-white text-xs font-bold rounded-full px-1.5 py-0.5 shadow"
      >
        {{ episode.video_format }}
      </div>
    </div>

    <div class="relative flex items-center justify-between gap-2 p-1 bg-white dark:bg-[#171717]">
      <span class="text-sm font-medium text-black truncate dark:text-white">
        {{ episode.series?.title }}
      </span>
    </div>
  </div>
</div>

<!-- Movies Grid -->
<div class="relative flex items-center justify-between px-4">
  <Link
    href="/your-route"
    class="font-bold text-purple-600 underline dark:text-white"
  >
    View All
  </Link>

  <h2 class="pr-2 text-2xl font-bold text-right text-gray-800 dark:text-white">
    Latest Added Movies
  </h2>
</div>

<div class="grid grid-cols-2 gap-4 p-4 pb-8 sm:grid-cols-3 lg:grid-cols-5">
  <div
    v-for="anime in movies"
    :key="anime.id"
    class="overflow-hidden transition-transform bg-white dark:bg-[#171717] rounded-md shadow-md hover:scale-105"
  >
    <div
      class="relative w-full cursor-pointer h-72"
      @click="inertia.visit(`/animes/${anime.id}`)"
    >
      <img
        v-if="anime.cover"
        :src="`/storage/${anime.cover}`"
        class="object-cover w-full h-full rounded-t-md"
        alt="Cover"
      />
      <div
        v-else
        class="flex items-center justify-center w-full h-full text-gray-500 bg-gray-200 dark:text-gray-300 dark:bg-gray-800"
      >
        No Image
      </div>

      <div class="absolute bottom-0 left-0 right-0 py-1 text-xl font-bold text-center text-white bg-black/60">
        Movie
      </div>
    </div>

    <div class="relative flex items-center justify-between gap-2 p-1 bg-white dark:bg-[#171717]">
      <span class="text-base font-medium text-black truncate dark:text-white">
        {{ anime.title }}
      </span>
    </div>
  </div>
</div>

<!-- TV Anime Grid -->
<div class="relative flex items-center justify-between px-4">
  <Link
    href="/your-route"
    class="font-bold text-purple-600 underline dark:text-white"
  >
    View All
  </Link>
  <h2 class="pr-2 text-2xl font-bold text-right text-gray-800 dark:text-white">
    Latest Added TV Animes
  </h2>
</div>

<div class="grid grid-cols-2 gap-4 p-4 pb-8 sm:grid-cols-3 lg:grid-cols-5">
  <div
    v-for="anime in tvAnimes"
    :key="anime.id"
    class="overflow-hidden transition-transform bg-white dark:bg-[#171717] rounded-md shadow-md hover:scale-105"
  >
    <div
      class="relative w-full cursor-pointer h-80"
      @click="inertia.visit(`/animes/${anime.id}`)"
    >
      <img
        v-if="anime.cover"
        :src="`/storage/${anime.cover}`"
        class="object-cover w-full h-full rounded-t-md"
        alt="Cover"
      />
      <div
        v-else
        class="flex items-center justify-center w-full h-full text-gray-500 bg-gray-200 dark:text-gray-300 dark:bg-gray-800"
      >
        No Image
      </div>

      <div class="absolute bottom-0 left-0 right-0 py-1 text-xl font-bold text-center text-white bg-black/60">
        TV
      </div>
    </div>

    <div class="relative flex items-center justify-between gap-2 p-1 bg-white dark:bg-[#171717]">
      <span class="text-base font-medium text-black truncate dark:text-white">
        {{ anime.title }}
      </span>
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="bg-neutral-primary-soft">
  <div class="w-full max-w-screen-xl p-4 py-6 mx-auto lg:py-8">
    <div class="md:flex md:justify-between">
      <div class="mb-6 md:mb-0">
        <a href="https://flowbite.com/" class="flex items-center">
          <img src="https://flowbite.com/docs/images/logo.svg" class="h-7 me-3" alt="FlowBite Logo" />
          <span class="self-center text-2xl font-semibold text-heading whitespace-nowrap">ANIME LAST</span>
        </a>
      </div>
      <div class="grid grid-cols-2 gap-8 sm:gap-6 sm:grid-cols-3">
        <div>
          <h2 class="mb-6 text-sm font-semibold uppercase text-heading">Resources</h2>
          <ul class="font-medium text-body">
            <li class="mb-4"><a href="https://flowbite.com/" class="hover:underline">Flowbite</a></li>
            <li><a href="https://tailwindcss.com/" class="hover:underline">Tailwind CSS</a></li>
          </ul>
        </div>
        <div>
          <h2 class="mb-6 text-sm font-semibold uppercase text-heading">Follow Us</h2>
          <ul class="font-medium text-body">
            <li class="mb-4"><a href="https://github.com/themesberg/flowbite" class="hover:underline">Github</a></li>
            <li><a href="https://discord.gg/4eeurUVvTy" class="hover:underline">Discord</a></li>
          </ul>
        </div>
        <div>
          <h2 class="mb-6 text-sm font-semibold uppercase text-heading">Legal</h2>
          <ul class="font-medium text-body">
            <li class="mb-4"><a href="#" class="hover:underline">Privacy Policy</a></li>
            <li><a href="#" class="hover:underline">Terms & Conditions</a></li>
          </ul>
        </div>
      </div>
    </div>
    <hr class="my-6 border-default sm:mx-auto lg:my-8" />
    <div class="sm:flex sm:items-center sm:justify-between">
      <span class="text-sm text-body sm:text-center">
        © 2023 <a href="https://flowbite.com/" class="hover:underline">Flowbite™</a>. All Rights Reserved.
      </span>
      <div class="flex mt-4 space-x-5 sm:justify-center sm:mt-0"></div>
    </div>
  </div>
</footer>


  </AppLayout>
</template>

<style scoped>
.left-1\/2 {
  left: 50%;
}
.-translate-x-1\/2 {
  transform: translateX(-50%);
}
</style>