<template>
  <AppFrame active="reviews" title="Отзывы">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_280px]">
      <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div
            class="inline-flex items-center rounded-md border border-slate-200 bg-white px-3 py-1 text-xs text-slate-500"
          >
            Яндекс Карты
          </div>

          <div class="inline-flex items-center gap-2">
            <label class="text-xs text-slate-500" for="sort-order"
              >Сортировка</label
            >
            <select
              id="sort-order"
              v-model="sortOrder"
              class="h-9 rounded-md border border-slate-300 bg-white px-2 text-sm text-slate-700"
            >
              <option value="default">По умолчанию</option>
              <option value="newest">По новизне</option>
              <option value="negative">Сначала отрицательные</option>
              <option value="positive">Сначала положительные</option>
            </select>
          </div>
        </div>

        <a
          v-for="review in pagedReviews"
          :key="review.id"
          :href="`/demo.html?page=review&id=${review.id}`"
          class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-sky-300"
        >
          <div
            class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500"
          >
            <span>{{ formatDate(review.date) }}</span>
            <span>Филиал 1</span>
          </div>

          <div class="mb-2 text-sm font-semibold text-slate-800">
            {{ review.author }}
          </div>

          <div class="mb-2 inline-flex items-center gap-1">
            <span
              v-for="star in 5"
              :key="star"
              class="text-sm"
              :class="
                star <= review.rating ? 'text-amber-400' : 'text-slate-300'
              "
              >★</span
            >
            <span class="ml-2 text-xs font-semibold text-slate-600"
              >{{ review.rating }}.0</span
            >
          </div>

          <p class="line-clamp-3 text-sm leading-6 text-slate-600">
            {{ review.text }}
          </p>
        </a>

        <div
          v-if="!loading && pagedReviews.length === 0"
          class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
        >
          Отзывы не найдены. Выполните импорт из Яндекс.Карт на странице
          настроек.
        </div>

        <div
          v-if="loading"
          class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
        >
          Загрузка отзывов...
        </div>

        <div
          class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-3"
        >
          <button
            type="button"
            class="rounded-md border border-slate-300 px-3 py-1 text-sm text-slate-700 disabled:opacity-50"
            :disabled="currentPage === 1"
            @click="prevPage"
          >
            Назад
          </button>

          <div class="text-sm text-slate-500">
            Страница {{ currentPage }} из {{ totalPages }}
          </div>

          <button
            type="button"
            class="rounded-md border border-slate-300 px-3 py-1 text-sm text-slate-700 disabled:opacity-50"
            :disabled="currentPage === totalPages"
            @click="nextPage"
          >
            Вперёд
          </button>
        </div>
      </div>

      <aside
        class="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
      >
        <div class="mb-2 flex items-end gap-2">
          <span class="text-5xl font-semibold leading-none text-slate-800">{{
            avgRating
          }}</span>
          <span class="pb-1 text-sm text-slate-500">из 5</span>
        </div>

        <div class="mb-3 inline-flex items-center gap-1">
          <span
            v-for="star in 5"
            :key="`avg-${star}`"
            class="text-xl"
            :class="
              star <= Math.round(avgRating)
                ? 'text-amber-400'
                : 'text-slate-300'
            "
            >★</span
          >
        </div>

        <div class="text-sm text-slate-500">
          Всего отзывов: {{ totalReviews }}
        </div>
      </aside>
    </div>
  </AppFrame>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue";
import AppFrame from "../components/AppFrame.vue";
import { axios } from "../bootstrap";

const reviews = ref([]);
const sortOrder = ref("default");
const currentPage = ref(1);
const pageSize = 5;
const totalReviews = ref(0);
const averageRating = ref(0);
const loading = ref(false);

const totalPages = computed(() =>
  Math.max(1, Math.ceil(totalReviews.value / pageSize)),
);

const pagedReviews = computed(() => reviews.value);

const avgRating = computed(() => {
  return Number(averageRating.value || 0).toFixed(1);
});

const formatDate = (value) =>
  new Date(value).toLocaleDateString("ru-RU", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  });

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value += 1;
  }
};

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value -= 1;
  }
};

const fetchReviews = async () => {
  loading.value = true;

  try {
    const { data } = await axios.get("/api/reviews", {
      params: {
        page: currentPage.value,
        per_page: pageSize,
        sort: sortOrder.value,
      },
    });

    reviews.value = Array.isArray(data?.data) ? data.data : [];
    totalReviews.value = Number(data?.meta?.total || reviews.value.length || 0);
    averageRating.value = Number(data?.meta?.average_rating || 0);
    const serverPage = Number(data?.meta?.page || currentPage.value);
    const serverLastPage = Number(data?.meta?.last_page || totalPages.value);
    currentPage.value = Math.min(serverPage, Math.max(1, serverLastPage));
  } catch {
    reviews.value = [];
    totalReviews.value = 0;
    averageRating.value = 0;
  } finally {
    loading.value = false;
  }
};

watch(sortOrder, async () => {
  currentPage.value = 1;
  await fetchReviews();
});

watch(currentPage, async () => {
  await fetchReviews();
});

onMounted(() => {
  fetchReviews();
});
</script>
