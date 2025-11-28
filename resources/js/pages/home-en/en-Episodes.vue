<script setup lang="ts">
import AppLayout from '@/layouts/EN-HomeLayout.vue';
import { Head, usePage, router as inertia } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { LoaderCircle, Eye } from 'lucide-vue-next';
import { toast } from 'vue-sonner';
import { Episode, type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Episodes', href: '/ar/episodes-list' }
];

const page = usePage<{
    episodes: { data: Episode[]; next_page_url?: string };
    animes: Array<{ id: number; title: string; image?: string }>;
}>();

const selectedAnimeId = ref<number | null>(null);
const animeSearchQuery = ref('');
const episodeNumber = ref<number | null>(null);
const dropdownOpen = ref(false);

const filteredAnimes = computed(() => {
    const q = animeSearchQuery.value.toLowerCase();
    return page.props.animes.filter(a => a.title.toLowerCase().includes(q));
});

const selectAnime = (animeId: number) => {
    selectedAnimeId.value = animeId;
    dropdownOpen.value = false;

    // امسح النص بعد الاختيار
    animeSearchQuery.value = '';
};


const episodes = ref<Episode[]>(page.props.episodes.data);
const loading = ref(false);

// البحث عند تغيير الأنمي أو رقم الحلقة
watch([selectedAnimeId, episodeNumber], async ([animeId, epNumber]) => {
    if (!animeId && !epNumber) {
        episodes.value = [];
        return;
    }
    loading.value = true;

    try {
        await inertia.get(
            route('en.Episodes'),
            {
                anime_name: animeId ? page.props.animes.find(a => a.id === animeId)?.title : '',
                episode_number: epNumber || ''
            },
            {
                preserveState: true,
                only: ['episodes'],
                onSuccess: (res) => {
                    episodes.value = res.props.episodes.data;
                },
                onFinish: () => {
                    loading.value = false;
                }
            }
        );
    } catch (e) {
        toast.error('حدث خطأ أثناء البحث');
        loading.value = false;
    }
});

// ------------------ التوجيه عند الضغط على الحلقة ----------------
const goToEpisode = (episodeId: number) => {
    inertia.visit(route('episodes.show', episodeId));
};
</script>

<template>
<Head>
    <title>البحث في الحلقات</title>
</Head>

<AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-6 font-[Cairo]">

        <!-- ---------------- Search ---------------- -->
        <div class="relative w-full max-w-md mx-auto">
            <Input
                v-model="animeSearchQuery"
                placeholder="ابحث عن أنمي..."
                @focus="dropdownOpen = true"
                class="w-full p-3 text-sm border rounded-lg shadow-sm"
            />
            <div
                v-if="dropdownOpen"
                class="mt-1 overflow-auto bg-white border rounded-lg shadow-lg dark:bg-gray-800 dark:border-gray-700 max-h-60"
            >
                <div
                    v-for="anime in filteredAnimes"
                    :key="anime.id"
                    @click="selectAnime(anime.id)"
                    class="flex items-center gap-2 p-2 cursor-pointer hover:bg-gray-200 dark:hover:bg-neutral-700"
                >
                    <img v-if="anime.image" :src="`/storage/${anime.image}`" alt="thumb" class="object-cover w-10 h-10 rounded" />
                    <span class="text-sm font-semibold dark:text-white">{{ anime.title }}</span>
                </div>
                <div v-if="filteredAnimes.length === 0" class="p-2 text-gray-500 dark:text-gray-400">
                    لا يوجد نتائج
                </div>
            </div>
        </div>

        <!-- ---------------- Episode Number ---------------- -->
        <div v-if="selectedAnimeId" class="w-full max-w-xs mx-auto">
            <Input
                v-model.number="episodeNumber"
                placeholder="أدخل رقم الحلقة..."
                type="number"
                min="1"
                class="w-full p-3 mt-3 text-sm border rounded-lg shadow-sm"
            />
        </div>

        <!-- ---------------- Spinner ---------------- -->
        <div v-if="loading" class="flex items-center gap-2 mx-auto mt-2 text-gray-600 dark:text-gray-300">
            <LoaderCircle class="w-5 h-5 animate-spin" />
            جارٍ البحث...
        </div>

        <!-- ---------------- Episodes Grid ---------------- -->
        <div v-if="episodes.length > 0" class="grid grid-cols-2 gap-4 mt-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
            <div
                v-for="episode in episodes"
                :key="episode.id"
                @click="goToEpisode(episode.id)"
                class="relative transition transform bg-white rounded-lg shadow cursor-pointer dark:bg-gray-800 hover:scale-105"
            >
                <div class="relative">
                    <img
                        v-if="episode.thumbnail"
                        :src="`/storage/${episode.thumbnail}`"
                        class="object-cover w-full h-40 rounded-t-lg"
                    />
                    <div v-else class="flex items-center justify-center w-full h-40 text-gray-500 bg-gray-200 rounded-t-lg dark:text-gray-300 dark:bg-gray-700">
                        لا توجد صورة
                    </div>

                    <div class="absolute bottom-0 left-0 right-0 py-1 text-sm font-bold text-center text-white bg-black bg-opacity-60">
                        episodes {{ episode.episode_number }}
                    </div>
                </div>

                <div class="p-2">
                    <div class="text-sm font-semibold truncate dark:text-white">{{ episode.title_en }}</div>
                    <div class="flex justify-end mt-1">
                        <Eye class="w-4 h-4 text-blue-500" />
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="!loading && selectedAnimeId && episodeNumber" class="mt-4 text-center text-gray-500 dark:text-gray-400">
            لا توجد حلقات مطابقة
        </div>

        <div v-else-if="!selectedAnimeId && !loading" class="mt-4 text-center text-gray-500 dark:text-gray-400">
            اختر أنمي لعرض حلقاته
        </div>
    </div>
</AppLayout>
</template>

<style scoped>
div.relative:hover {
    transition: transform 0.2s ease;
    transform: scale(1.05);
}
</style>
