<script setup lang="ts">
import AppLayout from '@/layouts/EN-HomeLayout.vue';
import { Head, Link, usePage, router as inertia } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';
import { Input } from '@/components/ui/input';
import { useAuth } from '@/composables/useAuth';

const breadcrumbs = [
  { title: 'Animes', href: '/animes' },
];

const page = usePage<{
  animes: any,
  categories: any[],
  seasons: any[],
  flash?: { success?: string },
  auth: { user: any }
}>();

const animes = ref([...page.props.animes.data]);
const nextPageUrl = ref(page.props.animes.next_page_url);
const prevPageUrl = ref(page.props.animes.prev_page_url);
const currentPage = ref(page.props.animes.current_page || 1);
const lastPage = ref(page.props.animes.last_page || 1);
const search = ref('');
const loading = ref(false);
const loadingSkeleton = ref(false);

// فلترة
const showFilter = ref(false);

// خيارات متعددة
const selectedStatus = ref('');
const selectedCategory = ref('');
const selectedSeason = ref('');
const categories = ref(page.props.categories || []);
const seasons = ref(page.props.seasons || []);

// الصلاحيات
const { can } = useAuth();
const canAnime = (action: 'create-anime' | 'update-anime' | 'delete-anime') => {
  const user = page.props.auth.user;
  if (can('admin')) return true;
  if (can(action)) return true;
  const userRoles = user.roles || [];
  for (const role of userRoles) {
    if (role.permissions?.includes(action)) return true;
  }
  return false;
};

// حذف الأنمي بدون إعادة تحميل الصفحة
const deleteAnime = (id: number) => {
  if (!confirm('هل أنت متأكد من حذف هذا الأنمي؟')) return;

  inertia.delete(`/animes/${id}`, {
    onSuccess: () => {
      animes.value = animes.value.filter(a => a.id !== id);
      toast.success('تم حذف الأنمي بنجاح');
    },
    onError: () => {
      toast.error('حدث خطأ أثناء الحذف');
    },
  });
};

// دالة إرسال الفلاتر والبحث للسيرفر
const fetchFilteredAnimes = (pageUrl?: string) => {
  loading.value = true;
  inertia.get(pageUrl || route('en.anime'), {
    search: search.value,
    status: selectedStatus.value,
    category: selectedCategory.value,
    season: selectedSeason.value
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['animes'],
    onSuccess: (pageResponse) => {
      if (pageUrl) {
        const newData = pageResponse.props.animes.data;
        const existingIds = new Set(animes.value.map(a => a.id));
        const filteredData = newData.filter(a => !existingIds.has(a.id));
        animes.value.push(...filteredData);
      } else {
        animes.value = pageResponse.props.animes.data;
      }
      nextPageUrl.value = pageResponse.props.animes.next_page_url;
      prevPageUrl.value = pageResponse.props.animes.prev_page_url;
      currentPage.value = pageResponse.props.animes.current_page;
      lastPage.value = pageResponse.props.animes.last_page;
    },
    onFinish: () => {
      loading.value = false;
      loadingSkeleton.value = false;
    }
  });
};

// البحث مباشر عند كتابة أي شيء
watch(search, () => {
  fetchFilteredAnimes();
});

// الفلاتر تعمل مباشرة عند تغييرها
watch([selectedStatus, selectedCategory, selectedSeason], () => {
  fetchFilteredAnimes();
});

// زر تفريغ الفلاتر والبحث
const resetFilters = () => {
  search.value = '';
  selectedStatus.value = '';
  selectedCategory.value = '';
  selectedSeason.value = '';
  fetchFilteredAnimes();
};

// التنقل بين الصفحات عبر pagination
const goToPage = (pageNumber: number) => {
  fetchFilteredAnimes(`${route('ar.anime')}?page=${pageNumber}&search=${search.value}&status=${selectedStatus.value}&category=${selectedCategory.value}&season=${selectedSeason.value}`);
};

// تحميل المزيد عند التمرير
const loadMore = () => {
  if (!nextPageUrl.value || loading.value) return;
  fetchFilteredAnimes(nextPageUrl.value);
};

const onScroll = () => {
  const scrollPosition = window.innerHeight + window.scrollY;
  const bottom = document.documentElement.offsetHeight - 50;
  if (scrollPosition >= bottom) {
    loadMore();
  }
};

// عرض الفلاش مرة واحدة
onMounted(() => {
  if (page.props.flash?.success) {
    toast.success(page.props.flash.success);
    page.props.flash.success = null;
  }
  window.addEventListener('scroll', onScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', onScroll);
});
</script>

<template>
  <Head title="Animes" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col flex-1 h-full gap-4 p-4 rounded-xl">
<span dir="rtl">الأنمي - قسم مسلسلات</span>
      <!-- مربع البحث + زر الفلترة -->
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-center">
        <Input v-model="search" placeholder="Search animes..." class="w-full h-10 md:w-1/3" />
        <button @click="showFilter = !showFilter" class="flex items-center gap-2 px-4 py-2 transition-all duration-300 bg-white border rounded-md hover:bg-gray-100">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
            :class="`w-6 h-6 ${darkMode ? 'stroke-gray-200' : 'stroke-gray-800'}`">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>
          </svg>
          <span class="font-medium text-black">Filter</span>
        </button>
        <button @click="resetFilters" class="flex items-center gap-2 px-4 py-2 text-gray-900 transition-all duration-300 bg-white border rounded-md hover:bg-gray-100">Reset All</button>
      </div>

      <!-- قائمة الفلترة -->
      <transition name="fade-slide">
        <div v-if="showFilter" class="flex flex-wrap items-center justify-center gap-3 p-5  bg-white/80 dark:bg-[#292929cc] backdrop-blur-md rounded-xl shadow-lg transition-all duration-300">
          <select v-model="selectedStatus" class="px-4 py-2 text-gray-800 border rounded-lg dark:bg-gray-900 dark:text-white">
            <option value="">All Status</option>
            <option value="Active">يعرض الأن</option>
            <option value="completed">مكتمل</option>
          </select>

          <select v-model="selectedCategory" class="px-4 py-2 text-gray-800 border rounded-lg dark:bg-gray-900 dark:text-white">
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.name_en">{{ category.name_en }}</option>
          </select>

          <select v-model="selectedSeason" class="px-4 py-2 text-gray-800 bg-white border rounded-lg dark:bg-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500">
            <option value="">All Seasons</option>
            <option v-for="season in seasons" :key="season.id" :value="season.id">{{ season.name_en }}</option>
          </select>
        </div>
      </transition>

      <!-- شبكة الأنميات -->
      <div class="grid grid-cols-2 gap-4 mt-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
        <div v-for="anime in animes" :key="anime.id" class="flex flex-col overflow-hidden transition-transform bg-white rounded-lg shadow-md cursor-pointer dark:bg-[#171717] hover:scale-105">
          <Link :href="route('animes.show', anime.id)">
            <img v-if="anime.image" :src="`/storage/${anime.image}`" class="object-cover w-full h-[300px]"/>
            <div v-else class="flex items-center justify-center w-full h-48 text-gray-500 bg-gray-200 dark:bg-gray-700">No Image</div>
          </Link>
          <div class="flex flex-col gap-1 p-2">
            <h3 class="text-sm font-bold text-gray-800 truncate dark:text-white">{{ anime.title }}</h3>
            <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
              <span :class="anime.is_active ? 'text-green-500' : 'text-red-500'">{{ anime.is_active ? 'يعرض الأن' : 'مكتمل' }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-center gap-2 mt-6">
        <button 
          :disabled="currentPage === 1" 
          @click="goToPage(currentPage - 1)" 
          class="px-3 py-1 border rounded disabled:opacity-50">
          Prev
        </button>

        <span class="px-2">{{ currentPage }} / {{ lastPage }}</span>

        <button 
          :disabled="currentPage === lastPage" 
          @click="goToPage(currentPage + 1)" 
          class="px-3 py-1 border rounded disabled:opacity-50">
          Next
        </button>
      </div>

    </div>
  </AppLayout>
</template>

<style scoped>
.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.3s ease; }
.fade-slide-enter-from, .fade-slide-leave-to { opacity: 0; transform: translateY(-10px); }
.fade-slide-enter-to, .fade-slide-leave-from { opacity: 1; transform: translateY(0); }
</style>
