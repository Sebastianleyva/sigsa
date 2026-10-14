import axios from "axios";

const api = axios.create({
  baseURL:
    import.meta.env.API_URL || "http://localhost/sigsa/backend/modulos",
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});
// Interceptor: Se ejecuta ANTES de que cualquier petición salga al backend
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem("token");
    const csrfToken = localStorage.getItem("csrf_token");

    // Inyectar JWT para autenticación
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    // Inyectar Token CSRF para peticiones de modificación (POST, PATCH, PUT, DELETE)
    if (csrfToken) {
      config.headers["X-CSRF-Token"] = csrfToken;
    }

    return config;
},
  (error) => {
    return Promise.reject(error);
  },
);

export default api;
