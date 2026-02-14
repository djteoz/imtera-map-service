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

      <div
        class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600"
      >
        Можно выбрать организацию прямо здесь: используется ключ из backend-конфига
        по умолчанию. Поле ниже — дополнительная опция для замены ключа.
      </div>

      <div class="flex flex-col gap-6">
        <label class="flex flex-col gap-2">
          <span class="text-sm font-medium text-slate-700"
            >Доп. Yandex JavaScript API key (опционально)</span
          >
          <div class="flex flex-wrap items-center gap-3">
            <input
              v-model.trim="yandexApiKeyOverride"
              type="text"
              aria-label="Yandex API Key"
              placeholder="Оставьте пустым, чтобы использовать ключ по умолчанию"
              class="h-11 min-w-[260px] flex-1 rounded-md border border-slate-300 px-3 text-sm text-slate-800 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
            />

            <button
              type="button"
              @click="initMap"
              :disabled="mapLoading || !effectiveMapApiKey"
              class="inline-flex h-10 items-center rounded-md border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60"
            >
              {{ mapLoading ? "Подключение..." : "Открыть карту" }}
            </button>
          </div>
          <p v-if="defaultYandexApiKey" class="text-xs text-slate-500">
            Ключ по умолчанию из backend-конфига подключён.
          </p>
          <p
            v-if="mapStatus"
            class="text-xs"
            :class="mapError ? 'text-red-600' : 'text-emerald-700'"
          >
            {{ mapStatus }}
          </p>
        </label>

        <div
          v-show="mapReady"
          class="overflow-hidden rounded-lg border border-slate-200"
        >
          <div ref="mapContainer" class="h-[380px] w-full"></div>
        </div>

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
import { computed, onMounted, onUnmounted, ref } from "vue";
import { axios } from "../bootstrap";
import AppFrame from "../components/AppFrame.vue";

const yandexUrl = ref("");
const yandexApiKeyOverride = ref("");
const defaultYandexApiKey = ref("");
const message = ref("");
const isError = ref(false);
const importing = ref(false);
const mapContainer = ref(null);
const mapStatus = ref("");
const mapError = ref(false);
const mapLoading = ref(false);
const mapReady = ref(false);

let ymapsMap = null;
let ymapsSearchControl = null;

const effectiveMapApiKey = computed(() => {
  return (
    String(yandexApiKeyOverride.value || "").trim() ||
    String(defaultYandexApiKey.value || "").trim()
  );
});

const extractOrgId = (value) => {
  const raw = String(value || "");
  if (!raw) return "";

  const match = raw.match(/(?:oid(?:%3D|=)|(\/org\/[^/]+\/))(\d{6,})/i);
  if (!match) return "";

  return match[1] || match[2] || "";
};

const bindSearchResultHandler = () => {
  if (!ymapsSearchControl) return;

  ymapsSearchControl.events.add("resultselect", async (event) => {
    const index = event.get("index");

    try {
      const result = await ymapsSearchControl.getResult(index);
      if (!result) return;

      const boundedBy = result.properties?.get("boundedBy");
      if (boundedBy && ymapsMap) {
        ymapsMap.setBounds(boundedBy, { checkZoomRange: true, duration: 250 });
      } else {
        const coords = result.geometry?.getCoordinates?.();
        if (coords && ymapsMap) {
          ymapsMap.setCenter(coords, 16, { duration: 250 });
        }
      }

      const allProps = result.properties?.getAll?.() || {};
      const companyMeta =
        allProps?.CompanyMetaData ||
        allProps?.metaDataProperty?.CompanyMetaData;

      const orgId =
        extractOrgId(companyMeta?.id) ||
        extractOrgId(companyMeta?.url) ||
        extractOrgId(JSON.stringify(allProps));

      if (orgId) {
        yandexUrl.value = `https://yandex.ru/maps/org/${orgId}/reviews/`;
        mapStatus.value = `Организация выбрана (OID: ${orgId}). Ссылка подставлена.`;
        mapError.value = false;
      } else {
        mapStatus.value =
          "Организация выбрана, но OID не удалось извлечь автоматически. Скопируйте URL карточки из Яндекс.Карт.";
        mapError.value = true;
      }
    } catch {
      mapStatus.value = "Не удалось обработать выбранный результат поиска.";
      mapError.value = true;
    }
  });
};

const loadYmaps = (apiKey) =>
  new Promise((resolve, reject) => {
    if (window.ymaps) {
      window.ymaps.ready(() => resolve(window.ymaps));
      return;
    }

    const existingScript = document.getElementById("ymaps-api-script");
    if (existingScript) {
      existingScript.addEventListener("load", () => {
        window.ymaps?.ready(() => resolve(window.ymaps));
      });
      existingScript.addEventListener("error", () => {
        reject(new Error("Не удалось загрузить JavaScript API Яндекс.Карт."));
      });
      return;
    }

    const script = document.createElement("script");
    script.id = "ymaps-api-script";
    script.src = `https://api-maps.yandex.ru/2.1/?apikey=${encodeURIComponent(apiKey)}&lang=ru_RU`;
    script.async = true;
    script.defer = true;

    script.onload = () => {
      window.ymaps?.ready(() => resolve(window.ymaps));
    };

    script.onerror = () => {
      reject(new Error("Не удалось загрузить JavaScript API Яндекс.Карт."));
    };

    document.head.appendChild(script);
  });

const initMap = async () => {
  const mapApiKey = effectiveMapApiKey.value;
  if (!mapApiKey) {
    mapStatus.value = "Введите API ключ.";
    mapError.value = true;
    return;
  }

  mapLoading.value = true;
  mapStatus.value = "";
  mapError.value = false;

  try {
    const ymaps = await loadYmaps(mapApiKey);

    if (!mapContainer.value) {
      throw new Error("Контейнер карты недоступен.");
    }

    if (ymapsMap) {
      ymapsMap.destroy();
      ymapsMap = null;
      ymapsSearchControl = null;
    }

    ymapsMap = new ymaps.Map(mapContainer.value, {
      center: [57.6261, 39.8845],
      zoom: 12,
      controls: ["zoomControl", "fullscreenControl"],
    });

    ymapsSearchControl = new ymaps.control.SearchControl({
      options: {
        provider: "yandex#search",
        noPlacemark: false,
        placeholderContent: "Найти организацию (театр, кафе, салон и т.д.)",
      },
    });

    ymapsMap.controls.add(ymapsSearchControl);
    bindSearchResultHandler();

    mapReady.value = true;
    mapStatus.value =
      "Ключ валиден. Карта загружена — выберите организацию в поиске.";
    mapError.value = false;
  } catch (error) {
    mapReady.value = false;
    mapStatus.value = error?.message || "Ошибка инициализации карты.";
    mapError.value = true;
  } finally {
    mapLoading.value = false;
  }
};

onMounted(async () => {
  try {
    const { data } = await axios.get("/api/settings");
    yandexUrl.value = data?.yandex_url || "";
    yandexApiKeyOverride.value = data?.yandex_maps_api_key || "";
    defaultYandexApiKey.value = data?.yandex_maps_api_key_default || "";

    if (effectiveMapApiKey.value) {
      await initMap();
    }
  } catch {
    message.value = "Не удалось загрузить настройки";
    isError.value = true;
  }
});

onUnmounted(() => {
  if (ymapsMap) {
    ymapsMap.destroy();
    ymapsMap = null;
    ymapsSearchControl = null;
  }
});

const save = async () => {
  message.value = "";
  isError.value = false;

  try {
    await axios.post("/api/settings", {
      yandex_url: yandexUrl.value,
      yandex_maps_api_key: yandexApiKeyOverride.value,
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
    const totalAvailable = Number(data?.total_available || imported);
    message.value = `Импорт завершён. Загружено: ${imported}. Найдено на Яндексе: ${totalAvailable}`;
  } catch (error) {
    isError.value = true;
    message.value = error?.response?.data?.message || "Ошибка импорта";
  } finally {
    importing.value = false;
  }
};
</script>
