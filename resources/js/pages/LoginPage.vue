<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
    <div
      class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
    >
      <h1 class="mb-1 text-2xl font-semibold text-slate-800">Вход</h1>
      <p class="mb-6 text-sm text-slate-500">Авторизация по логину и паролю</p>

      <form class="flex flex-col gap-4" @submit.prevent="submit">
        <label class="flex flex-col gap-2">
          <span class="text-sm font-medium text-slate-700">Email</span>
          <input
            v-model.trim="email"
            type="email"
            autocomplete="username"
            required
            class="h-11 rounded-md border border-slate-300 px-3 text-sm text-slate-800 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
          />
        </label>

        <label class="flex flex-col gap-2">
          <span class="text-sm font-medium text-slate-700">Пароль</span>
          <input
            v-model="password"
            type="password"
            autocomplete="current-password"
            required
            class="h-11 rounded-md border border-slate-300 px-3 text-sm text-slate-800 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
          />
        </label>

        <button
          type="submit"
          :disabled="loading"
          class="inline-flex h-10 items-center justify-center rounded-md bg-sky-500 px-4 text-sm font-semibold text-white transition hover:bg-sky-600 disabled:opacity-60"
        >
          {{ loading ? "Вход..." : "Войти" }}
        </button>

        <p v-if="errorMessage" class="text-sm text-red-600">
          {{ errorMessage }}
        </p>
      </form>

      <p class="mt-5 rounded-md bg-slate-50 p-3 text-xs text-slate-500">
        Демо-доступ: demo@imtera.ru / demo12345
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { axios } from "../bootstrap";

const DEMO_AUTH_KEY = "demo_auth";

const email = ref("demo@imtera.ru");
const password = ref("demo12345");
const loading = ref(false);
const errorMessage = ref("");

const getNextUrl = () => {
  const params = new URLSearchParams(window.location.search);
  const nextRaw = params.get("next");
  if (!nextRaw) return "/demo.html";

  try {
    const decoded = decodeURIComponent(nextRaw);
    return decoded.startsWith("/demo.html") ? decoded : "/demo.html";
  } catch {
    return "/demo.html";
  }
};

const submit = async () => {
  loading.value = true;
  errorMessage.value = "";

  try {
    const { data } = await axios.post("/api/login", {
      email: email.value,
      password: password.value,
    });

    localStorage.setItem(DEMO_AUTH_KEY, JSON.stringify(data));
    window.location.replace(getNextUrl());
  } catch (error) {
    errorMessage.value =
      error?.response?.data?.message || "Не удалось выполнить вход";
  } finally {
    loading.value = false;
  }
};
</script>
