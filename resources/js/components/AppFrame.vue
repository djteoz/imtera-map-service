<template>
  <div class="min-h-screen bg-white text-slate-700">
    <div class="flex min-h-screen">
      <aside
        class="hidden w-72 shrink-0 border-r border-slate-200 bg-slate-50 p-6 lg:block"
      >
        <div class="mb-8">
          <div class="text-4xl font-semibold leading-none text-slate-800">
            Daily Grow
          </div>
          <div class="mt-3 text-base text-slate-500">{{ accountName }}</div>
        </div>

        <nav class="space-y-2">
          <a
            href="/demo.html"
            :class="[
              'block rounded-lg px-4 py-3 text-sm font-medium transition',
              active === 'reviews'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-500 hover:bg-white hover:text-slate-800',
            ]"
          >
            Отзывы
          </a>

          <a
            href="/demo.html?page=settings"
            :class="[
              'block rounded-lg px-4 py-3 text-sm font-medium transition',
              active === 'settings'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-500 hover:bg-white hover:text-slate-800',
            ]"
          >
            Настройка
          </a>
        </nav>
      </aside>

      <main class="flex-1">
        <header
          class="flex h-[76px] items-center justify-between border-b border-slate-200 px-6 lg:px-10"
        >
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="inline-flex h-9 w-9 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 lg:hidden"
              aria-label="Открыть меню"
              @click="isMobileMenuOpen = true"
            >
              ☰
            </button>

            <div class="text-sm text-slate-500">{{ title }}</div>
          </div>

          <a
            href="/demo.html?page=login&logout=1"
            class="inline-flex h-9 items-center justify-center rounded-md border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            aria-label="Выйти"
          >
            Выйти
          </a>
        </header>

        <section class="p-6 lg:p-10">
          <slot />
        </section>
      </main>
    </div>

    <div
      v-if="isMobileMenuOpen"
      class="fixed inset-0 z-50 lg:hidden"
      aria-modal="true"
      role="dialog"
    >
      <button
        type="button"
        class="absolute inset-0 bg-black/40"
        aria-label="Закрыть меню"
        @click="isMobileMenuOpen = false"
      ></button>

      <aside
        ref="mobileMenuRef"
        tabindex="-1"
        class="relative h-full w-72 border-r border-slate-200 bg-slate-50 p-6 shadow-xl"
      >
        <div class="mb-8 flex items-start justify-between gap-2">
          <div>
            <div class="text-4xl font-semibold leading-none text-slate-800">
              Daily Grow
            </div>
            <div class="mt-3 text-base text-slate-500">{{ accountName }}</div>
          </div>

          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-200 hover:text-slate-800"
            aria-label="Закрыть меню"
            @click="isMobileMenuOpen = false"
          >
            ✕
          </button>
        </div>

        <nav class="space-y-2">
          <a
            href="/demo.html"
            :class="[
              'block rounded-lg px-4 py-3 text-sm font-medium transition',
              active === 'reviews'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-500 hover:bg-white hover:text-slate-800',
            ]"
            @click="isMobileMenuOpen = false"
          >
            Отзывы
          </a>

          <a
            href="/demo.html?page=settings"
            :class="[
              'block rounded-lg px-4 py-3 text-sm font-medium transition',
              active === 'settings'
                ? 'bg-white text-slate-900 shadow-sm'
                : 'text-slate-500 hover:bg-white hover:text-slate-800',
            ]"
            @click="isMobileMenuOpen = false"
          >
            Настройка
          </a>

          <a
            href="/demo.html?page=login&logout=1"
            class="mt-2 block rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
            @click="isMobileMenuOpen = false"
          >
            Выйти
          </a>
        </nav>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from "vue";

const isMobileMenuOpen = ref(false);
const previousBodyOverflow = ref("");
const previouslyFocusedElement = ref(null);
const mobileMenuRef = ref(null);
const DEMO_AUTH_KEY = "demo_auth";

const accountName = computed(() => {
  if (typeof localStorage === "undefined") {
    return "Название аккаунта";
  }

  try {
    const raw = localStorage.getItem(DEMO_AUTH_KEY);
    if (!raw) return "Название аккаунта";

    const parsed = JSON.parse(raw);
    return parsed?.user?.name || parsed?.user?.email || "Название аккаунта";
  } catch {
    return "Название аккаунта";
  }
});

const FOCUSABLE_SELECTOR =
  'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

const getFocusableElements = () => {
  const panel = mobileMenuRef.value;
  if (!panel) return [];

  return Array.from(panel.querySelectorAll(FOCUSABLE_SELECTOR)).filter(
    (element) =>
      !element.hasAttribute("disabled") &&
      element.getAttribute("aria-hidden") !== "true",
  );
};

const focusFirstElementInDrawer = () => {
  const panel = mobileMenuRef.value;
  if (!panel) return;

  const focusable = getFocusableElements();
  if (focusable.length > 0) {
    focusable[0].focus();
    return;
  }

  panel.focus();
};

const handleGlobalKeydown = (event) => {
  if (!isMobileMenuOpen.value) return;

  if (event.key === "Escape") {
    isMobileMenuOpen.value = false;
    return;
  }

  if (event.key !== "Tab") return;

  const panel = mobileMenuRef.value;
  if (!panel) return;

  const focusable = getFocusableElements();

  if (!focusable.length) {
    event.preventDefault();
    panel.focus();
    return;
  }

  const first = focusable[0];
  const last = focusable[focusable.length - 1];
  const activeElement = document.activeElement;

  if (event.shiftKey) {
    if (activeElement === first || activeElement === panel) {
      event.preventDefault();
      last.focus();
    }
    return;
  }

  if (activeElement === last) {
    event.preventDefault();
    first.focus();
  }
};

watch(isMobileMenuOpen, (isOpen) => {
  if (typeof document === "undefined") return;

  if (isOpen) {
    previouslyFocusedElement.value = document.activeElement;
    previousBodyOverflow.value = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    document.addEventListener("keydown", handleGlobalKeydown);
    nextTick(() => {
      focusFirstElementInDrawer();
    });
    return;
  }

  document.removeEventListener("keydown", handleGlobalKeydown);
  document.body.style.overflow = previousBodyOverflow.value || "";

  if (
    previouslyFocusedElement.value &&
    typeof previouslyFocusedElement.value.focus === "function"
  ) {
    previouslyFocusedElement.value.focus();
  }
});

onUnmounted(() => {
  if (typeof document === "undefined") return;
  document.removeEventListener("keydown", handleGlobalKeydown);
  document.body.style.overflow = previousBodyOverflow.value || "";
});

defineProps({
  active: {
    type: String,
    default: "reviews",
  },
  title: {
    type: String,
    default: "",
  },
});
</script>
