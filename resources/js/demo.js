import { createApp } from "vue";
import SettingsPage from "./pages/SettingsPage.vue";
import ReviewsPage from "./pages/ReviewsPage.vue";
import ReviewDetailPage from "./pages/ReviewDetailPage.vue";
import LoginPage from "./pages/LoginPage.vue";
import { axios } from "./bootstrap";

// Import semantic colors and Tailwind for the demo
import "../../scss/_colors.scss";
import "../css/tailwind.css";

const DEMO_KEY = "demo_settings";
const DEMO_AUTH_KEY = "demo_auth";

function getAuthPayload() {
  const raw = localStorage.getItem(DEMO_AUTH_KEY);
  if (!raw) return null;

  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

function syncAuthHeader() {
  const token = getAuthPayload()?.token;

  if (token) {
    axios.defaults.headers.common.Authorization = `Bearer ${token}`;
  } else {
    delete axios.defaults.headers.common.Authorization;
  }
}

function isAuthorized() {
  return Boolean(getAuthPayload()?.token);
}

const params = new URLSearchParams(window.location.search);

if (params.get("logout") === "1") {
  const token = getAuthPayload()?.token;
  if (token) {
    axios.post("/api/logout").catch(() => undefined);
  }
  localStorage.removeItem(DEMO_AUTH_KEY);
  localStorage.removeItem(DEMO_KEY);
}

syncAuthHeader();

const page = params.get("page");

if (page !== "login" && !isAuthorized()) {
  const nextUrl = `${window.location.pathname}${window.location.search}`;
  window.location.replace(
    `/demo.html?page=login&next=${encodeURIComponent(nextUrl)}`,
  );
}

if (page === "login" && isAuthorized()) {
  const nextRaw = params.get("next");
  const nextTarget = nextRaw ? decodeURIComponent(nextRaw) : "/demo.html";
  window.location.replace(
    nextTarget.startsWith("/demo.html") ? nextTarget : "/demo.html",
  );
}

const pageMap = {
  login: LoginPage,
  settings: SettingsPage,
  review: ReviewDetailPage,
};
const component = pageMap[page] || ReviewsPage;

const app = createApp(component);
app.mount("#demo-app");
