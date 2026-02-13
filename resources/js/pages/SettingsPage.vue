<template>
  <AppFrame active="settings" title="Настройка">
    <div
      class="mx-auto w-full max-w-3xl rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:p-8"
    >
      <h1 class="mb-2 text-2xl font-semibold text-slate-800">
        Подключить Яндекс
      </h1>
      <p class="mb-6 text-sm text-slate-500">
        Укажите ссылку на страницу отзывов организации в Яндекс.Картах.
      </p>

      <div class="flex flex-col gap-6">
        <label class="flex flex-col gap-2">
          <span class="text-sm font-medium text-slate-700"
            >Ссылка на Яндекс</span
          >
          <input
            v-model="yandexUrl"
            type="url"
            aria-label="Ссылка на Яндекс"
            placeholder="https://yandex.ru/maps/org/.../reviews/"
            class="h-11 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-800 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
          />
        </label>

        <div class="flex items-center gap-3">
          <button
            type="button"
            @click="save"
            class="inline-flex h-10 items-center rounded-md bg-sky-500 px-4 text-sm font-semibold text-white transition hover:bg-sky-600"
          >
            Сохранить
          </button>

          <button
            type="button"
            @click="runImport"
            :disabled="importing"
            class="inline-flex h-10 items-center rounded-md border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
          >
            {{ importing ? "Импорт..." : "Импортировать отзывы" }}
          </button>

          <p
            v-if="message"
            class="text-sm"
            :class="isError ? 'text-red-600' : 'text-emerald-700'"
          >
            {{ message }}
          </p>
        </div>
      </div>
    </div>
  </AppFrame>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { axios } from "../bootstrap";
import AppFrame from "../components/AppFrame.vue";

const yandexUrl = ref("");
const message = ref("");
const isError = ref(false);
const importing = ref(false);

onMounted(async () => {
  try {
    const { data } = await axios.get("/api/settings");
    yandexUrl.value = data?.yandex_url || "";
  } catch {
    message.value = "Не удалось загрузить настройки";
    isError.value = true;
  }
});

const save = async () => {
  message.value = "";
  isError.value = false;

  try {
    await axios.post("/api/settings", {
      yandex_url: yandexUrl.value,
    });
    message.value = "Ссылка сохранена";
  } catch (error) {
    isError.value = true;
    message.value = error?.response?.data?.message || "Ошибка сохранения";
  }
};

const runImport = async () => {
  importing.value = true;
  message.value = "";
  isError.value = false;

  try {
    const { data } = await axios.post("/api/import", {
      yandex_url: yandexUrl.value,
    });

    const imported = Number(data?.imported_count || 0);
    message.value = `Импорт завершён. Загружено отзывов: ${imported}`;
  } catch (error) {
    isError.value = true;
    message.value = error?.response?.data?.message || "Ошибка импорта";
  } finally {
    importing.value = false;
  }
};
</script>
