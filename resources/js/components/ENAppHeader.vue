<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import type { BreadcrumbItem, NavItem } from '@/types';
import { Link, usePage, router as inertia } from '@inertiajs/vue3';
import { BookOpen, Folder, Menu } from 'lucide-vue-next';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Input } from '@/components/ui/input';
import axios from 'axios';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);

const isCurrentRoute = computed(() => (url: string) => page.url === url);

const activeItemStyles = computed(
    () => (url: string) => (isCurrentRoute.value(url) ? 'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100' : '')
);

const mainNavItems: NavItem[] = [
    { title: 'Home', href: '/en/home' },
    { title: 'TV Series List', href: '/en/anime' },
    { title: 'Movies List', href: '/en/movies' },
    { title: 'Episodes List', href: '/en/episodes-list' },
    { title: 'Upcoming Anime', href: '/en/' },
];


const rightNavItems: NavItem[] = [
    { title: 'Repository', href: 'https://github.com/laravel/vue-starter-kit', icon: Folder },
    { title: 'Documentation', href: 'https://laravel.com/docs/starter-kits', icon: BookOpen },
];

// ---------------- Search Logic ----------------
const searchQuery = ref('');
const dropdownOpen = ref(false);
const searchResults = ref<{ animes: any[]; episodes: any[] }>({ animes: [], episodes: [] });
const searching = ref(false);

watch(searchQuery, async (val) => {
    if (val.length < 1) {
        dropdownOpen.value = false;
        searchResults.value = { animes: [], episodes: [] };
        return;
    }
    searching.value = true;
    dropdownOpen.value = true;

    try {
        const res = await axios.get(route('en-search'), { params: { q: val } });
        searchResults.value = res.data.searchResults || { animes: [], episodes: [] };
    } catch (e) {
        searchResults.value = { animes: [], episodes: [] };
    } finally {
        searching.value = false;
    }
});

// Navigate to item
const goToAnime = (id: number) => {
    inertia.visit(route('animes.show', id));
};
const goToEpisode = (id: number) => {
    inertia.visit(route('episodes.show', id));
};

// Close dropdown on outside click
const searchWrapper = ref<HTMLElement | null>(null);
const handleClickOutside = (event: MouseEvent) => {
    if (searchWrapper.value && !searchWrapper.value.contains(event.target as Node)) {
        dropdownOpen.value = false;
    }
};
onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>


<template>
<div>
    <!-- Top Bar Fixed -->
    <div class="fixed top-0 left-0 z-50 w-full bg-white border-b border-sidebar-border/80 dark:bg-black">
        <div class="relative flex items-center h-24 px-4 mx-auto md:max-w-7xl">
            <!-- Mobile Menu -->
            <div class="lg:hidden">
                <Sheet>
                    <SheetTrigger :as-child="true">
                        <Button variant="ghost" size="icon" class="mr-2 h-9 w-9">
                            <Menu class="w-5 h-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-[300px] p-6">
                        <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
                        <SheetHeader class="flex justify-start text-left">
                            <AppLogoIcon class="text-black fill-current size-6 dark:text-white" />
                        </SheetHeader>
                        <div class="flex flex-col justify-between flex-1 h-full py-6 space-y-4">
                            <nav class="-mx-3 space-y-1">
                                <Link
                                    v-for="item in mainNavItems"
                                    :key="item.title"
                                    :href="item.href"
                                    class="flex items-center px-3 py-2 text-sm font-medium rounded-lg gap-x-2 hover:bg-accent"
                                    :class="activeItemStyles(item.href)"
                                >
                                    <component v-if="item.icon" :is="item.icon" class="w-5 h-5" />
                                    {{ item.title }}
                                </Link>
                            </nav>
                            <div class="flex flex-col space-y-4">
                                <a
                                    v-for="item in rightNavItems"
                                    :key="item.title"
                                    :href="item.href"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center space-x-2 text-sm font-medium"
                                >
                                    <component v-if="item.icon" :is="item.icon" class="w-5 h-5" />
                                    <span>{{ item.title }}</span>
                                </a>
                            </div>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>

            <Link :href="route('dashboard')" class="flex items-center gap-x-2">
                <AppLogo />
            </Link>

            <!-- Desktop Menu -->
            <div class="hidden h-full lg:flex lg:flex-1">
                <NavigationMenu class="flex items-stretch h-full ml-10">
                    <NavigationMenuList class="flex items-center justify-center h-full space-x-1">
                        <NavigationMenuItem v-for="(item, index) in mainNavItems" :key="index" class="relative flex items-center h-full">
                            <Link :href="item.href">
                                <NavigationMenuLink
                                    :class="[navigationMenuTriggerStyle(), activeItemStyles(item.href), 'h-10 cursor-pointer px-3 text-sm']"
                                >
                                    <component v-if="item.icon" :is="item.icon" class="w-4 h-4 mr-1" />
                                    {{ item.title }}
                                </NavigationMenuLink>
                            </Link>
                            <div
                                v-if="isCurrentRoute(item.href)"
                                class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                            ></div>
                        </NavigationMenuItem>
                    </NavigationMenuList>
                </NavigationMenu>
            </div>

            <!-- Search Field -->
            <div ref="searchWrapper" class="relative flex flex-col ml-auto w-80">
                <Input
                    v-model="searchQuery"
                    placeholder="Search for an anime or episode..."
                    class="w-full p-2 text-sm border rounded-lg shadow-sm"
                    @focus="dropdownOpen = true"
                />

                <div v-if="dropdownOpen" class="absolute right-0 z-50 w-full mt-1 overflow-auto bg-white border rounded-lg shadow-lg top-full max-h-72">
                    <div v-if="searching" class="p-2 text-sm text-gray-500">Searching...</div>

                    <div v-if="searchResults.animes.length">
                        <h4 class="px-2 pt-2 text-sm font-semibold">Animes</h4>
                        <div v-for="anime in searchResults.animes" :key="anime.id"
                             class="flex items-center gap-2 p-2 cursor-pointer hover:bg-gray-200 dark:hover:bg-neutral-700"
                             @click="goToAnime(anime.id)">
                            <img v-if="anime.image" :src="`/storage/${anime.image}`" class="object-cover w-10 h-10 rounded" />
                            <span class="text-sm font-semibold">{{ anime.title }}</span>
                        </div>
                    </div>

                    <div v-if="searchResults.episodes.length">
                        <h4 class="px-2 pt-2 text-sm font-semibold">Episodes</h4>
                        <div v-for="ep in searchResults.episodes" :key="ep.id"
                             class="flex items-center gap-2 p-2 cursor-pointer hover:bg-gray-200 dark:hover:bg-neutral-700"
                             @click="goToEpisode(ep.id)">
                            <img v-if="ep.thumbnail" :src="`/storage/${ep.thumbnail}`" class="object-cover w-10 h-10 rounded" />
                            <span class="text-sm font-semibold">{{ ep.title }} - Episode {{ ep.episode_number }}</span>
                        </div>
                    </div>

                    <div v-if="searchResults.animes.length === 0 && searchResults.episodes.length === 0 && !searching"
                         class="p-2 text-sm text-gray-500">
                        No results found
                    </div>
                </div>
            </div>

            <!-- Right icons + Avatar -->
            <div class="hidden ml-2 space-x-1 lg:flex">
                <template v-for="item in rightNavItems" :key="item.title">
                    <TooltipProvider :delay-duration="0">
                        <Tooltip>
                            <TooltipTrigger>
                                <Button variant="ghost" size="icon" as-child class="cursor-pointer group h-9 w-9">
                                    <a :href="item.href" target="_blank" rel="noopener noreferrer">
                                        <span class="sr-only">{{ item.title }}</span>
                                        <component :is="item.icon" class="size-5 opacity-80 group-hover:opacity-100" />
                                    </a>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{{ item.title }}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </template>
            </div>

            <DropdownMenu>
                <DropdownMenuTrigger :as-child="true">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="relative w-auto p-1 rounded-full size-10 focus-within:ring-2 focus-within:ring-primary"
                    >
                        <Avatar class="overflow-hidden rounded-full size-8">
                            <AvatarImage
                                v-if="auth?.user?.avatar"
                                :src="auth.user.avatar"
                                :alt="auth?.user?.name"
                            />
                            <AvatarFallback class="font-semibold text-black rounded-lg bg-neutral-200 dark:bg-neutral-700 dark:text-white">
                                {{ getInitials(auth.user?.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <UserMenuContent :user="auth.user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </div>

    <!-- Spacer to prevent content overlap -->
    <div class="pt-24"></div>

    <!-- Breadcrumbs -->
    <div v-if="props.breadcrumbs.length > 1" class="flex w-full border-b border-sidebar-border/70">
        <div class="flex items-center justify-start w-full h-12 px-4 mx-auto text-neutral-500 md:max-w-7xl">
            <Breadcrumbs :breadcrumbs="breadcrumbs" />
        </div>
    </div>
</div>
</template>
