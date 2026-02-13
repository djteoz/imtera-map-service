import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";

export default defineConfig(async () => {
  const plugins = [vue()];

  try {
    const laravelModule = await import("laravel-vite-plugin");
    const laravel = laravelModule.default || laravelModule;
    plugins.push(
      laravel({
        input: ["resources/js/app.js", "resources/sass/app.scss"],
        refresh: true,
      }),
    );
  } catch (e) {
    // If the laravel plugin cannot be loaded (incompatible/ESM issues),
    // skip it to allow the dev server to run for demo pages.
    // console.warn("laravel-vite-plugin not loaded, continuing without it.");
  }

  return { plugins };
});
