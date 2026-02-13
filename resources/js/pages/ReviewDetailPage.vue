<template>
  <AppFrame active="reviews" title="Отзывы">
    <div class="mx-auto w-full max-w-4xl space-y-6">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="text-2xl font-semibold text-slate-800">Детальный отзыв</h1>
          <p class="mt-1 text-sm text-slate-500">Источник: Яндекс Карты</p>
        </div>
        <a
          href="/demo.html"
          class="inline-flex h-10 items-center rounded-md border border-slate-300 px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
          К списку
        </a>
      </div>

      <section
        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
      >
        <div
          class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500"
        >
          <span>{{ formatDate(review.date) }}</span>
          <span>Филиал 1</span>
        </div>

        <div class="mb-3 text-sm font-semibold text-slate-800">
          {{ review.author }}
        </div>

        <div
          class="mb-3 inline-flex items-center gap-1"
          role="radiogroup"
          aria-label="Оценка"
        >
          <button
            v-for="star in 5"
            :key="star"
            class="text-2xl leading-none"
            :class="star <= review.rating ? 'text-amber-400' : 'text-slate-300'"
            type="button"
            @click="setRating(star)"
            :aria-label="`Оценка ${star}`"
          >
            ★
          </button>

          <span class="ml-2 text-sm font-semibold text-slate-700"
            >{{ review.rating }}.0</span
          >
        </div>

        <p class="text-sm leading-6 text-slate-600">{{ review.text }}</p>
      </section>

      <section
        class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
      >
        <div class="mb-2 text-sm font-semibold text-slate-800">
          Ответ компании
        </div>

        <textarea
          v-model="reply"
          class="min-h-[140px] w-full rounded-md border border-slate-300 p-3 text-sm text-slate-800 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
          placeholder="Введите текст ответа..."
        ></textarea>

        <div class="mt-3 flex items-center gap-3">
          <button
            type="button"
            @click="saveReply"
            class="inline-flex h-10 items-center rounded-md bg-sky-500 px-4 text-sm font-semibold text-white transition hover:bg-sky-600"
          >
            Сохранить ответ
          </button>

          <span v-if="saved" class="text-sm text-emerald-700">Сохранено</span>
        </div>
      </section>
    </div>
  </AppFrame>
</template>

<script>
import AppFrame from "../components/AppFrame.vue";
import { axios } from "../bootstrap";

const DEFAULT_REVIEW = {
  id: 1,
  author: "—",
  rating: 0,
  date: new Date().toISOString().slice(0, 10),
  text: "",
  reply: "",
};

export default {
  components: { AppFrame },
  data() {
    return {
      reply: "",
      saved: false,
      review: DEFAULT_REVIEW,
      reviewId: 1,
    };
  },
  async mounted() {
    const params = new URLSearchParams(window.location.search);
    const id = Number(params.get("id") || 1);
    this.reviewId = id;

    try {
      const { data } = await axios.get(`/api/reviews/${id}`);
      this.review = data || DEFAULT_REVIEW;
      this.reply = data?.reply || "";
    } catch {
      this.review = DEFAULT_REVIEW;
      this.reply = "";
    }
  },
  methods: {
    async setRating(value) {
      try {
        const { data } = await axios.patch(`/api/reviews/${this.reviewId}`, {
          rating: value,
        });

        this.review = data?.review || this.review;
        this.saved = true;
        setTimeout(() => {
          this.saved = false;
        }, 900);
      } catch {
        this.saved = false;
      }
    },
    async saveReply() {
      try {
        const { data } = await axios.patch(`/api/reviews/${this.reviewId}`, {
          reply: this.reply,
        });

        this.review = data?.review || this.review;
        this.reply = this.review?.reply || this.reply;
        this.saved = true;
        setTimeout(() => {
          this.saved = false;
        }, 1200);
      } catch {
        this.saved = false;
      }
    },
    formatDate(value) {
      return new Date(value).toLocaleDateString("ru-RU", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      });
    },
  },
};
</script>
