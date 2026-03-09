import "./assets/main.css";

import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import { authStore } from "@/stores/auth";

const app = createApp(App);

app.use(router);

authStore.initAuth();

app.mount("#app");
