import { reactive } from "vue";
import api from "@/services/api";

type AuthUser = {
  id: number;
  name: string;
  email: string;
};

const state = reactive({
  user: null as AuthUser | null,
  initialized: false,
  authRequiredModalVisible: false,
});

const ensureCsrfCookie = async () => {
  await api.get("/sanctum/csrf-cookie");
};

const fetchCurrentUser = async () => {
  try {
    const response = await api.get("/api/auth/user");
    state.user = response.data;
    return state.user;
  } catch {
    state.user = null;
    return null;
  }
};

const initAuth = async () => {
  if (state.initialized) {
    return;
  }

  await fetchCurrentUser();
  state.initialized = true;
};

const login = async (email: string, password: string) => {
  await ensureCsrfCookie();
  await api.post("/api/login", { email, password });
  await fetchCurrentUser();
};

const register = async (
  name: string,
  email: string,
  password: string,
  passwordConfirmation: string,
) => {
  await ensureCsrfCookie();
  const response = await api.post("/api/register", {
    name,
    email,
    password,
    password_confirmation: passwordConfirmation,
  });
  await fetchCurrentUser();
  return response;
};

const logout = async () => {
  try {
    await ensureCsrfCookie();
    await api.post("/api/auth/logout");
  } finally {
    state.user = null;
  }
};

const isAuthenticated = () => state.user !== null;

const showAuthRequiredModal = () => {
  state.authRequiredModalVisible = true;
};

const hideAuthRequiredModal = () => {
  state.authRequiredModalVisible = false;
};

export const authStore = {
  state,
  initAuth,
  login,
  register,
  logout,
  fetchCurrentUser,
  isAuthenticated,
  showAuthRequiredModal,
  hideAuthRequiredModal,
};
