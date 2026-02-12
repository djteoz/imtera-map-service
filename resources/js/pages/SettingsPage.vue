<template>
  <div class="container">
    <h1 class="text-heading text-2xl mb-4">Настройки интеграции</h1>
    <div
      class="bg-white p-6 rounded shadow-sm border border-gray-200 max-w-2xl"
    >
      <label class="block text-sm font-medium mb-2"
        >Ссылка на Яндекс-карты</label
      >
      <input
        v-model="yandexUrl"
        class="w-full p-2 border rounded mb-3"
        placeholder="https://yandex.ru/maps/org/.../reviews/"
      />
      <div class="flex gap-2">
        <button @click="save" class="btn-primary px-4 py-2 rounded">
          Сохранить
        </button>
        <button @click="importNow" class="px-4 py-2 border rounded">
          Импорт сейчас
        </button>
      </div>
      <div v-if="message" class="mt-3 text-sm text-green-600">
        {{ message }}
      </div>
    </div>
  </div>
</template>

<script>
import { axios } from "../bootstrap";
export default {
  data() {
    return { yandexUrl: "", message: "" };
  },
  mounted() {
    axios.get("/api/public/settings").then((r) => {
      if (r.data?.yandex_url) this.yandexUrl = r.data.yandex_url;
    }).catch(() => {});
  },
  methods: {
    save() {
      axios.post("/api/public/settings", { yandex_url: this.yandexUrl }).then(() => {
        this.message = "Сохранено";
      });
    },
    importNow() {
      axios.post("/api/public/import", { url: this.yandexUrl }).then(() => {
        this.message = "Импорт запущен";
      });
    },
  },
};
</script>

<style scoped>
.btn-primary {
  background: var(--color-accent);
  color: #000;
}
</style>
