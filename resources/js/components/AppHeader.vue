<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router as inertia } from '@inertiajs/vue3';
import axios from 'axios';

// --- Components Imports ---
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { 
    DropdownMenu, 
    DropdownMenuContent, 
    DropdownMenuTrigger, 
    DropdownMenuItem, 
    DropdownMenuSeparator, 
    DropdownMenuLabel 
} from '@/components/ui/dropdown-menu';

// --- Icons Imports ---
import { 
    User, LogIn, UserPlus, BookOpen, Folder, Menu, Home, Sparkles, ChevronDown,
    Tv, Film, PlayCircle, Search, CalendarClock, Bell, History, Clock, Moon,
    Sun, Loader2, X, MoreVertical, Settings, LogOut, Palette, LayoutDashboard
} from 'lucide-vue-next';

// --- Composables/Types ---
import { getInitials } from '@/composables/useInitials';
import type { BreadcrumbItem, NavItem } from '@/types';

// --- Props & Page Data ---
interface Props {
  breadcrumbs?: BreadcrumbItem[];
}
const props = withDefaults(defineProps<Props>(), { breadcrumbs: () => [] });
const page = usePage();
const auth = computed(() => page.props.auth);

// =============================================================================
// 1. Theme Logic (الوضع الليلي والنهاري)
// =============================================================================
const isDarkMode = ref(false);
const toggleTheme = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    const savedTheme = localStorage.getItem('theme');
    const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (savedTheme === 'dark' || (!savedTheme && systemDark)) {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    }
});

// =============================================================================
// 2. Data Fetching (Mock Data for Notifications/History)
// =============================================================================

// --- Notifications ---
const notifications = ref<any[]>([]);
const loadingNotifications = ref(false);
const handleOpenNotifications = async (isOpen: boolean) => {
    if (isOpen) {
        loadingNotifications.value = true;
        notifications.value = [];
        setTimeout(() => {
            notifications.value = [
                { id: 1, title: 'تم إضافة حلقة جديدة: One Piece 1090', time: 'منذ دقيقتين', image: '/storage/anime/one-piece.jpg', read: false },
                { id: 2, title: 'رد أحد المشرفين على تعليقك', time: 'منذ ساعة', image: null, icon: 'msg', read: true },
                { id: 3, title: 'فيلم Demon Slayer الجديد متاح الآن', time: 'منذ 3 ساعات', image: '/storage/anime/ds-movie.jpg', read: false },
            ];
            loadingNotifications.value = false;
        }, 1500);
    }
};

// --- History ---
const watchHistory = ref<any[]>([]);
const loadingHistory = ref(false);
const handleOpenHistory = async (isOpen: boolean) => {
    if (isOpen) {
        loadingHistory.value = true;
        watchHistory.value = [];
        setTimeout(() => {
            watchHistory.value = [
                { id: 101, title: 'Jujutsu Kaisen Season 2', episode: 'الحلقة 14', progress: 80, image: 'https://cdn.myanimelist.net/images/anime/1792/138022.jpg' },
                { id: 102, title: 'Spy x Family', episode: 'الحلقة 5', progress: 30, image: 'https://cdn.myanimelist.net/images/anime/1441/122795.jpg' },
            ];
            loadingHistory.value = false;
        }, 1200);
    }
};

// --- Watch Later ---
const watchLater = ref<any[]>([]);
const loadingWatchLater = ref(false);
const handleOpenWatchLater = async (isOpen: boolean) => {
    if (isOpen) {
        loadingWatchLater.value = true;
        watchLater.value = [];
        setTimeout(() => {
            watchLater.value = [
                { id: 201, title: 'Attack on Titan: The Final Season', type: 'TV', image: 'https://cdn.myanimelist.net/images/anime/1000/110531.jpg' },
                { id: 202, title: 'Your Name (Kimi no Na wa)', type: 'Movie', image: 'https://cdn.myanimelist.net/images/anime/5/87048.jpg' },
            ];
            loadingWatchLater.value = false;
        }, 1000);
    }
};

// --- Latest Anime Data ---
const latestAnime = ref<{ latest_tv?: any; latest_movie?: any; latest_episode?: any }>({});
onMounted(async () => {
  try {
    const res = await axios.get('/latest-tv-anime');
    latestAnime.value = res.data;
  } catch (e) {
    console.error('Failed to fetch latest anime', e);
    latestAnime.value = {};
  }
});

// =============================================================================
// 3. Navigation Configuration
// =============================================================================
const mainNavItems: NavItem[] = [
  { title: 'الرئيسية', href: '/ar/home', icon: Home },
  { title: 'أنمي القادم', href: '/ar/coming-soon', icon: CalendarClock },
  { title: 'قائمة مسلسلات - TV', href: '/ar/anime', icon: Tv },
  { title: 'قائمة الأفلام - Movies', href: '/ar/movies', icon: Film },
  { title: 'قائمة الحلقات - Episodes', href: '/ar/episodes-list', icon: PlayCircle },
];

// =============================================================================
// 4. Search Logic
// =============================================================================
const searchQuery = ref('');
const dropdownOpen = ref(false);
const searchResults = ref<{ animes: any[]; episodes: any[] }>({ animes: [], episodes: [] });
const searching = ref(false);
const isMobileSearchOpen = ref(false);
const searchWrapper = ref<HTMLElement | null>(null);

// Watch for search input changes
watch(searchQuery, async (val) => {
  if (val.length < 1) {
    dropdownOpen.value = false;
    searchResults.value = { animes: [], episodes: [] };
    return;
  }
  searching.value = true;
  dropdownOpen.value = true;

  try {
    const res = await axios.get(route('search'), { params: { q: val } });
    searchResults.value = res.data.searchResults || { animes: [], episodes: [] };
  } catch (e) {
    searchResults.value = { animes: [], episodes: [] };
  } finally {
    searching.value = false;
  }
});

// Navigation helpers
const goToAnime = (id: number) => {
    isMobileSearchOpen.value = false;
    inertia.visit(route('ar.animes.show', id));
};
const goToEpisode = (id: number) => {
    isMobileSearchOpen.value = false;
    inertia.visit(route('ar.episodes.show', id));
};

// Click outside to close dropdown (Desktop mainly)
const handleClickOutside = (event: MouseEvent) => {
  if (searchWrapper.value && !searchWrapper.value.contains(event.target as Node)) {
     // If needed logic here
  }
};
onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));

</script>

<template>
<div>
  
  <div dir="rtl" class="hidden md:block fixed top-0 left-0 z-50 w-full transition-colors duration-300 border-b bg-white/95 backdrop-blur-md border-sidebar-border/80 dark:bg-black/95 dark:border-neutral-800">
    <div class="relative flex items-center h-20 px-4 mx-auto md:max-w-[1600px]">

      <Link :href="route('ar.home')" class="flex items-center mr-4 transition-transform gap-x-2 hover:scale-105">
        <AppLogo class="w-auto h-8" />
      </Link>

      <div class="items-center hidden h-full gap-2 ml-6 lg:flex lg:flex-1">
        <Link href="/ar/home" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-700 transition-all duration-200 rounded-full dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-neutral-800">
           <Home class="w-4 h-4" /> <span>الرئيسية</span>
        </Link>
        <Link href="/ar/coming-soon" class="flex items-center gap-2 px-5 py-2 text-sm font-bold text-white transition-all duration-300 rounded-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:shadow-lg hover:shadow-indigo-500/30 hover:scale-105">
           <Sparkles class="w-4 h-4 animate-pulse" /> <span>Coming Soon</span>
        </Link>

        <div class="relative flex items-center ml-2 group">
          <button class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-black dark:hover:text-white transition-colors">
            <span>القائمة</span>
            <ChevronDown class="w-4 h-4 transition-transform duration-200 group-hover:rotate-180" />
          </button>
          <div class="absolute right-0 z-50 invisible pt-4 transition-all duration-200 translate-y-2 opacity-0 top-full w-80 group-hover:visible group-hover:opacity-100 group-hover:translate-y-0">
             <div class="bg-white dark:bg-[#111] border border-gray-100 dark:border-neutral-800 rounded-2xl shadow-xl overflow-hidden">
                <div class="p-2 space-y-1">
                  <div v-for="item in mainNavItems" :key="item.title">
                    <Link v-if="!['الرئيسية', 'أنمي القادم'].includes(item.title)" :href="item.href" class="flex items-center justify-between px-4 py-3 text-sm font-medium transition-colors rounded-xl hover:bg-gray-50 dark:hover:bg-neutral-800 dark:text-gray-300 dark:hover:text-white group/item">
                       <div class="flex items-center gap-3">
                        <div class="p-2 transition-colors bg-gray-100 rounded-lg dark:bg-neutral-900 group-hover/item:bg-indigo-50 dark:group-hover/item:bg-indigo-900/20">
                            <component v-if="item.icon" :is="item.icon" class="w-4 h-4 text-gray-500 dark:text-gray-400 group-hover/item:text-indigo-500"/>
                        </div>
                        <span>{{ item.title }}</span>
                      </div>
                      <template v-if="item.title.includes('TV') && latestAnime.latest_tv">
                         <img v-if="latestAnime.latest_tv.image" :src="latestAnime.latest_tv.image.includes('http') ? latestAnime.latest_tv.image : `/storage/${latestAnime.latest_tv.image}`" class="object-cover w-8 h-8 transition-opacity rounded-md opacity-80 group-hover/item:opacity-100"/>
                      </template>
                      <template v-if="item.title.includes('Movies') && latestAnime.latest_movie">
                          <img v-if="latestAnime.latest_movie.image" :src="latestAnime.latest_movie.image.includes('http') ? latestAnime.latest_movie.image : `/storage/${latestAnime.latest_movie.image}`" class="object-cover w-8 h-8 transition-opacity rounded-md opacity-80 group-hover/item:opacity-100"/>
                      </template>
                    </Link>
                  </div>
                </div>
             </div>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2 ml-auto lg:gap-3">
        
        <div ref="searchWrapper" class="relative hidden md:block w-64 lg:w-72 xl:w-80">
          <div class="relative flex items-center w-full">
             <Search class="absolute w-5 h-5 text-gray-400 -translate-y-1/2 right-3 top-1/2" />
             <Input v-model="searchQuery" placeholder="بحث..." class="w-full h-10 pr-10 text-sm transition-all border-gray-200 rounded-full bg-gray-50 dark:bg-neutral-900 dark:border-neutral-800 focus:ring-2 focus:ring-indigo-500" @focus="dropdownOpen = true"/>
          </div>
          <div v-if="dropdownOpen" class="absolute right-0 z-50 w-full mt-2 overflow-hidden bg-white dark:bg-[#111] border border-gray-100 dark:border-neutral-800 rounded-2xl shadow-2xl max-h-[400px] overflow-y-auto">
            <div v-if="searching" class="p-4 text-center text-gray-500">
                <Loader2 class="w-5 h-5 mx-auto animate-spin" />
            </div>
            <div v-else>
                <div v-if="searchResults.animes.length" class="p-2">
                  <h4 class="px-3 py-2 text-xs font-bold text-gray-400 uppercase">أنميات</h4>
                  <div v-for="anime in searchResults.animes" :key="anime.id" class="flex items-center gap-3 p-2 cursor-pointer rounded-xl hover:bg-gray-50 dark:hover:bg-neutral-800" @click="goToAnime(anime.id)">
                    <img v-if="anime.image" :src="anime.image.includes('http') ? anime.image : `/storage/${anime.image}`" class="object-cover w-10 h-10 rounded-lg" />
                    <span class="text-sm font-semibold dark:text-gray-200">{{ anime.title }}</span>
                  </div>
                </div>
                <div v-if="searchResults.episodes.length" class="p-2 border-t border-gray-50 dark:border-neutral-800">
                  <h4 class="px-3 py-2 text-xs font-bold text-gray-400 uppercase">حلقات</h4>
                  <div v-for="ep in searchResults.episodes" :key="ep.id" class="flex items-center gap-3 p-2 cursor-pointer rounded-xl hover:bg-gray-50 dark:hover:bg-neutral-800" @click="goToEpisode(ep.id)">
                     <img v-if="ep.thumbnail" :src="ep.thumbnail.includes('http') ? ep.thumbnail : `/storage/${ep.thumbnail}`" class="object-cover w-10 h-10 rounded-lg" />
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold dark:text-gray-200">{{ ep.title }}</span>
                        <span class="text-xs text-gray-500">حلقة {{ ep.episode_number }}</span>
                    </div>
                  </div>
                </div>
            </div>
          </div>
        </div>

        <TooltipProvider :delay-duration="0">
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button variant="ghost" size="icon" @click="toggleTheme" class="w-10 h-10 rounded-full hover:bg-gray-100 dark:hover:bg-neutral-800">
                        <Sun v-if="!isDarkMode" class="w-5 h-5 text-gray-700 dark:text-gray-200" />
                        <Moon v-else class="w-5 h-5 text-gray-700 dark:text-gray-200" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent><p>الوضع {{ isDarkMode ? 'النهاري' : 'الليلي' }}</p></TooltipContent>
            </Tooltip>
        </TooltipProvider>

        <DropdownMenu v-if="auth?.user" @update:open="handleOpenWatchLater">
             <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon" class="w-10 h-10 rounded-full hover:bg-gray-100 dark:hover:bg-neutral-800">
                    <Clock class="w-5 h-5 text-gray-700 dark:text-gray-200" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-80 md:w-96 p-0 bg-white dark:bg-[#111] border-gray-100 dark:border-neutral-800 shadow-2xl rounded-2xl">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-neutral-800">
                    <h3 class="font-bold text-gray-900 dark:text-white">المشاهدة لاحقاً</h3>
                    <Link href="/watch-later" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">عرض الكل</Link>
                </div>
                <div class="max-h-[350px] overflow-y-auto p-2">
                    <div v-if="loadingWatchLater" class="flex justify-center p-4"><Loader2 class="animate-spin text-indigo-500"/></div>
                    <div v-else-if="!watchLater.length" class="text-center p-4 text-gray-500">القائمة فارغة</div>
                    <div v-else v-for="item in watchLater" :key="item.id" class="flex gap-3 p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-neutral-900">
                         <img :src="item.image" class="w-16 h-10 rounded object-cover"/>
                         <div class="flex flex-col"><span class="text-sm font-semibold dark:text-white">{{ item.title }}</span></div>
                    </div>
                </div>
            </DropdownMenuContent>
        </DropdownMenu>

        <DropdownMenu v-if="auth?.user" @update:open="handleOpenHistory">
            <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon" class="w-10 h-10 rounded-full hover:bg-gray-100 dark:hover:bg-neutral-800">
                    <History class="w-5 h-5 text-gray-700 dark:text-gray-200" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-80 md:w-96 bg-white dark:bg-[#111] border-gray-100 dark:border-neutral-800 shadow-2xl rounded-2xl">
                 <div class="p-4 text-center text-gray-500" v-if="watchHistory.length === 0 && !loadingHistory">لا يوجد سجل</div>
                 <div v-else class="p-2 space-y-2">
                    <div v-for="item in watchHistory" :key="item.id" class="flex gap-2 text-sm dark:text-white">
                        <img :src="item.image" class="w-12 h-12 rounded object-cover" />
                        <div>{{ item.title }} - {{ item.episode }}</div>
                    </div>
                 </div>
            </DropdownMenuContent>
        </DropdownMenu>

        <DropdownMenu v-if="auth?.user" @update:open="handleOpenNotifications">
             <DropdownMenuTrigger as-child>
                <Button variant="ghost" size="icon" class="relative w-10 h-10 rounded-full hover:bg-gray-100 dark:hover:bg-neutral-800">
                    <Bell class="w-5 h-5 text-gray-700 dark:text-gray-200" />
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                </Button>
             </DropdownMenuTrigger>
             <DropdownMenuContent align="end" class="w-80 md:w-[400px] p-0 bg-white dark:bg-[#111] border-gray-100 dark:border-neutral-800 shadow-2xl rounded-2xl">
                <div class="p-3 border-b dark:border-neutral-800 font-bold dark:text-white">الإشعارات</div>
                <div class="max-h-[400px] overflow-y-auto">
                    <div v-if="loadingNotifications" class="p-4 text-center"><Loader2 class="animate-spin inline"/></div>
                    <div v-else class="p-2 space-y-1">
                        <div v-for="notif in notifications" :key="notif.id" class="flex gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-neutral-900" :class="{'bg-indigo-50 dark:bg-indigo-900/20': !notif.read}">
                            <div class="flex-1">
                                <p class="text-sm font-medium dark:text-gray-200">{{ notif.title }}</p>
                                <span class="text-xs text-gray-500">{{ notif.time }}</span>
                            </div>
                        </div>
                    </div>
                </div>
             </DropdownMenuContent>
        </DropdownMenu>

        <DropdownMenu v-if="auth?.user">
            <DropdownMenuTrigger :as-child="true">
            <Button variant="ghost" size="icon" class="w-10 h-10 rounded-full">
                <Avatar class="w-9 h-9">
                <AvatarImage v-if="auth?.user?.avatar" :src="auth.user.avatar" :alt="auth?.user?.name" />
                <AvatarFallback class="bg-indigo-600 text-white">{{ getInitials(auth.user?.name) }}</AvatarFallback>
                </Avatar>
            </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56 mt-2 rounded-xl border-gray-100 dark:border-neutral-800 bg-white dark:bg-[#111]">
            <UserMenuContent :user="auth.user" />
            </DropdownMenuContent>
        </DropdownMenu>

        <div v-else>
             <Link href="/ar/login" class="text-sm font-bold text-gray-700 dark:text-white hover:text-indigo-600">تسجيل الدخول</Link>
        </div>

      </div>
    </div>
  </div>


  <div dir="rtl" class="md:hidden fixed bottom-0 left-0 z-[100] w-full h-16 bg-white border-t border-gray-200 dark:bg-[#0a0a0a] dark:border-neutral-800 flex items-center justify-around px-2 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]">
      
      <Sheet>
          <SheetTrigger :as-child="true">
            <button class="flex flex-col items-center justify-center w-full h-full gap-1 text-neutral-500 dark:text-neutral-400 hover:text-indigo-600 dark:hover:text-indigo-500 active:scale-95 transition-transform">
               <Menu class="w-6 h-6" />
               <span class="text-[10px] font-medium">القائمة</span>
            </button>
          </SheetTrigger>
          <SheetContent side="right" class="w-[300px] p-0 bg-white dark:bg-[#0a0a0a] border-l border-neutral-200 dark:border-neutral-800 overflow-y-auto">
             <SheetHeader class="flex justify-start p-6 text-right border-b border-neutral-100 dark:border-neutral-800">
                <div class="flex items-center gap-2">
                    <AppLogoIcon class="text-indigo-600 fill-current size-8 dark:text-indigo-500" />
                    <span class="text-lg font-bold text-gray-900 dark:text-white">Anime Last</span>
                </div>
             </SheetHeader>
             <div class="p-4 space-y-2">
                <Link v-for="item in mainNavItems" :key="item.title" :href="item.href" class="flex items-center justify-between px-4 py-3 text-sm font-medium rounded-xl transition-all hover:bg-gray-100 dark:hover:bg-neutral-800 dark:text-gray-300">
                    <div class="flex items-center gap-3">
                        <component :is="item.icon" class="w-5 h-5"/>
                        {{ item.title }}
                    </div>
                    <template v-if="item.title.includes('TV') && latestAnime.latest_tv">
                      <img v-if="latestAnime.latest_tv.image" :src="latestAnime.latest_tv.image.includes('http') ? latestAnime.latest_tv.image : `/storage/${latestAnime.latest_tv.image}`" class="object-cover w-8 h-8 rounded-md border border-gray-200 dark:border-gray-700"/>
                  </template>
                  <template v-if="item.title.includes('Movies') && latestAnime.latest_movie">
                      <img v-if="latestAnime.latest_movie.image" :src="latestAnime.latest_movie.image.includes('http') ? latestAnime.latest_movie.image : `/storage/${latestAnime.latest_movie.image}`" class="object-cover w-8 h-8 rounded-md border border-gray-200 dark:border-gray-700"/>
                  </template>
                </Link>
             </div>
          </SheetContent>
      </Sheet>

      <button @click="isMobileSearchOpen = true" class="flex flex-col items-center justify-center w-full h-full gap-1 text-neutral-500 dark:text-neutral-400 hover:text-indigo-600 dark:hover:text-indigo-500 active:scale-95 transition-transform">
          <Search class="w-6 h-6" />
          <span class="text-[10px] font-medium">بحث</span>
      </button>

      <DropdownMenu>
        <DropdownMenuTrigger class="w-full h-full outline-none">
            <div class="flex flex-col items-center justify-center w-full h-full gap-1 text-neutral-500 dark:text-neutral-400 hover:text-indigo-600 dark:hover:text-indigo-500 active:scale-95 transition-transform">
               <div v-if="auth?.user" class="relative">
                   <Avatar class="w-7 h-7 border border-indigo-500/30">
                      <AvatarImage v-if="auth.user.avatar" :src="auth.user.avatar" />
                      <AvatarFallback class="text-[10px] bg-indigo-600 text-white">{{ getInitials(auth.user.name) }}</AvatarFallback>
                   </Avatar>
                   <span class="absolute bottom-0 right-0 w-2 h-2 bg-green-500 border-2 border-white rounded-full dark:border-black"></span>
               </div>
               <User v-else class="w-6 h-6" />
               <span class="text-[10px] font-medium">{{ auth?.user ? 'حسابي' : 'دخول' }}</span>
            </div>
        </DropdownMenuTrigger>
        
        <DropdownMenuContent align="end" :sideOffset="15" class="w-[90vw] mb-4 bg-white dark:bg-[#151515] border-gray-100 dark:border-neutral-800 rounded-2xl shadow-[0_-10px_40px_-15px_rgba(0,0,0,0.3)] p-2">
            
            <div v-if="auth?.user" class="flex items-center gap-3 p-4 mb-2 bg-gray-50 dark:bg-neutral-900 rounded-xl">
                <Avatar class="w-12 h-12 border-2 border-white dark:border-neutral-700 shadow-sm">
                   <AvatarImage :src="auth.user.avatar" />
                   <AvatarFallback>{{ getInitials(auth.user.name) }}</AvatarFallback>
                </Avatar>
                <div class="flex flex-col">
                    <span class="font-bold text-gray-900 dark:text-white text-lg">{{ auth.user.name }}</span>
                    <span class="text-xs text-gray-500 font-medium">{{ auth.user.email }}</span>
                </div>
            </div>

            <div class="space-y-1">
                <div @click="toggleTheme" class="flex items-center justify-between p-3 transition-colors cursor-pointer rounded-xl hover:bg-gray-100 dark:hover:bg-neutral-800 active:bg-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="p-2 text-purple-600 bg-purple-100 rounded-lg dark:bg-purple-900/30 dark:text-purple-400">
                           <Palette class="w-5 h-5" />
                        </div>
                        <span class="text-base font-medium text-gray-700 dark:text-gray-200">المظهر</span>
                    </div>
                    <div class="flex items-center gap-2 bg-gray-100 dark:bg-neutral-900 px-3 py-1 rounded-full">
                       <span class="text-xs text-gray-500 font-bold">{{ isDarkMode ? 'داكن' : 'فاتح' }}</span>
                       <component :is="isDarkMode ? Moon : Sun" class="w-4 h-4 text-gray-500" />
                    </div>
                </div>

                <DropdownMenuSeparator class="bg-gray-100 dark:bg-neutral-800 my-2" />

                <template v-if="auth?.user">
                    <Link href="/history" class="flex items-center gap-3 p-3 transition-colors rounded-xl hover:bg-gray-100 dark:hover:bg-neutral-800">
                        <div class="p-2 text-blue-600 bg-blue-100 rounded-lg dark:bg-blue-900/30 dark:text-blue-400"><History class="w-5 h-5" /></div>
                        <span class="text-base font-medium text-gray-700 dark:text-gray-200">سجل المشاهدة</span>
                    </Link>

                    <Link href="/watch-later" class="flex items-center gap-3 p-3 transition-colors rounded-xl hover:bg-gray-100 dark:hover:bg-neutral-800">
                        <div class="p-2 text-orange-600 bg-orange-100 rounded-lg dark:bg-orange-900/30 dark:text-orange-400"><Clock class="w-5 h-5" /></div>
                        <span class="text-base font-medium text-gray-700 dark:text-gray-200">المشاهدة لاحقاً</span>
                    </Link>

                    <Link href="/settings" class="flex items-center gap-3 p-3 transition-colors rounded-xl hover:bg-gray-100 dark:hover:bg-neutral-800">
                        <div class="p-2 text-gray-600 bg-gray-100 rounded-lg dark:bg-neutral-800 dark:text-gray-400"><Settings class="w-5 h-5" /></div>
                        <span class="text-base font-medium text-gray-700 dark:text-gray-200">الإعدادات</span>
                    </Link>

                    <DropdownMenuSeparator class="bg-gray-100 dark:bg-neutral-800 my-2" />

                    <Link href="/logout" method="post" as="button" class="flex items-center w-full gap-3 p-3 transition-colors rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 group">
                        <div class="p-2 text-red-600 bg-red-100 rounded-lg dark:bg-red-900/30 dark:text-red-400 group-hover:bg-red-200"><LogOut class="w-5 h-5" /></div>
                        <span class="text-base font-medium text-red-600 dark:text-red-400">تسجيل الخروج</span>
                    </Link>
                </template>

                <div v-else class="grid grid-cols-2 gap-3 mt-2">
                    <Link href="/login" class="flex items-center justify-center gap-2 p-3 font-bold text-white bg-indigo-600 rounded-xl shadow-lg shadow-indigo-500/30">
                        <LogIn class="w-4 h-4" /> دخول
                    </Link>
                    <Link href="/register" class="flex items-center justify-center gap-2 p-3 font-bold text-indigo-600 bg-indigo-50 dark:bg-indigo-900/20 dark:text-indigo-400 rounded-xl">
                        <UserPlus class="w-4 h-4" /> جديد
                    </Link>
                </div>
            </div>
        </DropdownMenuContent>
      </DropdownMenu>
  </div>


  <div v-if="isMobileSearchOpen" class="fixed inset-0 z-[150] bg-white/95 backdrop-blur-xl dark:bg-black/95 flex flex-col animate-in fade-in duration-200">
      
      <div class="flex items-center gap-3 p-4 border-b border-gray-100 dark:border-neutral-800">
          <Button variant="ghost" size="icon" class="shrink-0 rounded-full hover:bg-gray-100 dark:hover:bg-neutral-800" @click="isMobileSearchOpen = false">
              <X class="w-6 h-6 text-gray-500" />
          </Button>
          <div class="relative flex-1">
              <Search class="absolute w-5 h-5 text-gray-400 -translate-y-1/2 right-3 top-1/2" />
              <Input 
                  v-model="searchQuery" 
                  placeholder="ابحث عن أنمي، فيلم..." 
                  class="w-full h-12 pl-4 pr-12 text-lg bg-transparent border-none ring-0 focus:ring-0 placeholder:text-gray-400" 
                  autoFocus
              />
          </div>
      </div>

      <div class="flex-1 overflow-y-auto p-4">
          <div v-if="searching" class="flex justify-center pt-10"><Loader2 class="w-8 h-8 text-indigo-600 animate-spin" /></div>
          
          <div v-else-if="searchQuery && !searchResults.animes.length && !searchResults.episodes.length" class="text-center text-gray-500 pt-10">
              لا توجد نتائج
          </div>

          <div v-else class="space-y-6">
              <div v-if="searchResults.animes.length">
                  <h3 class="text-sm font-bold text-gray-400 uppercase mb-3 px-2">الأنميات</h3>
                  <div class="grid grid-cols-1 gap-2">
                      <div v-for="anime in searchResults.animes" :key="anime.id" 
                           @click="goToAnime(anime.id)"
                           class="flex items-center gap-3 p-2 rounded-xl active:bg-gray-100 dark:active:bg-neutral-800 transition-colors">
                          <img v-if="anime.image" :src="anime.image.includes('http') ? anime.image : `/storage/${anime.image}`" class="w-12 h-16 object-cover rounded-lg shadow-sm" />
                          <div class="flex flex-col">
                              <span class="font-bold text-gray-800 dark:text-gray-100">{{ anime.title }}</span>
                              <span class="text-xs text-gray-500">{{ anime.type || 'TV' }}</span>
                          </div>
                      </div>
                  </div>
              </div>

              <div v-if="searchResults.episodes.length">
                  <h3 class="text-sm font-bold text-gray-400 uppercase mb-3 px-2">الحلقات</h3>
                  <div class="grid grid-cols-1 gap-2">
                      <div v-for="ep in searchResults.episodes" :key="ep.id" 
                           @click="goToEpisode(ep.id)"
                           class="flex items-center gap-3 p-2 rounded-xl active:bg-gray-100 dark:active:bg-neutral-800 transition-colors">
                           <div class="relative w-20 h-12 rounded-lg overflow-hidden shrink-0">
                               <img v-if="ep.thumbnail" :src="ep.thumbnail.includes('http') ? ep.thumbnail : `/storage/${ep.thumbnail}`" class="w-full h-full object-cover" />
                               <div class="absolute inset-0 flex items-center justify-center bg-black/20"><PlayCircle class="w-5 h-5 text-white/90"/></div>
                           </div>
                          <div class="flex flex-col">
                              <span class="font-bold text-gray-800 dark:text-gray-100 text-sm line-clamp-1">{{ ep.title }}</span>
                              <span class="text-xs text-indigo-500">حلقة {{ ep.episode_number }}</span>
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>

  <div class="pb-20 md:pb-0 md:pt-20"></div>

  <div v-if="props.breadcrumbs.length > 1" class="hidden md:flex w-full border-b border-gray-100 dark:border-neutral-800 bg-white/50 dark:bg-black/50 backdrop-blur-sm">
    <div class="flex items-center justify-start w-full h-10 px-4 mx-auto text-neutral-500 md:max-w-[1600px]">
      <Breadcrumbs :breadcrumbs="breadcrumbs" />
    </div>
  </div>

</div>
</template>
