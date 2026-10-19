import api from '../api/axios';

export const authService = {
  /**
   * Envía la solicitud de registro al backend PHP
   * @param {Object} userData - Datos del formulario
   * @returns {Promise<Object>} Respuesta del servidor
   */
  async register(userData) {
    try {
      // Axios hace el JSON.stringify internamente
      const response = await api.post('/auth/controllers/registrar.php', userData);
      return response.data;
    } catch (error) {
      console.error("Detalle del error en login:", error.response || error);
      if (error.response && error.response.data) {
        throw new Error(
          error.response.data.ERROR || 
          error.response.data.error || 
          error.response.data.CONFLICT ||
          'Error en la solicitud de registro'
        );
      }
      throw new Error('No se pudo conectar con el servidor');
    }

  },
  async login(userData){
      try{
      // Error: el servicio intentaba guardar la respuesta del backend sin validar la 
      // estructura real de la respuesta y, previamente, se estaba leyendo la data equivocada. 
      // Se asegura la lectura de `response.data` antes de guardar el token y el usuario.
      const response = await api.post('/auth/controllers/login.php',userData)
      if (response.data.token) {
        localStorage.setItem('token', response.data.token);
      }
      if (response.data.csrf_token) {
        localStorage.setItem('csrf_token', response.data.csrf_token);
      }
      if (response.data.usuario) {
        localStorage.setItem('usuario', JSON.stringify(response.data.usuario));
      }

      return response.data;
    } catch (error) {
      if (error.response && error.response.data) {
        throw new Error(
          error.response.data.ERROR || 
          error.response.data.error || 
          error.response.data.CONFLICT ||
          'Error al inicio de sesión'
        );
      }
      throw new Error('No se pudo conectar con el servidor');
    }
  },
  async CrearSalida(solicitud) {
    try {
      const response = await api.post('/salidas/routes/crear_solicitud.php', solicitud);
      return response.data;
    } catch (error) {
      if (error.response && error.response.data) {
        throw new Error(
          error.response.data.ERROR ||
          error.response.data.error ||
          error.response.data.CONFLICT ||
          'No fue posible registrar la solicitud de salida.'
        );
      }
      throw new Error('No se pudo conectar con el servidor.');
    }
  },
  async VerHistorialPersonal(){
    try{
      const response = await api.get('salidas/routes/ver_historial_personal.php');
      return response.data;
    } catch (error) {
      if (error.response && error.response.data) {
        throw new Error(
          error.response.data.ERROR ||
          error.response.data.error ||
          error.response.data.CONFLICT ||
          'No fue posible obtener el historial personal.'
        );
      }
      throw new Error('No se pudo conectar con el servidor.');
    }
  },
  logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('csrf_token');
    localStorage.removeItem('usuario');
  }
  
};

  
 