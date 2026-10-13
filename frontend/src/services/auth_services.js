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
      if (error.response && error.response.data) {
        throw new Error(
          error.response.data.ERROR || 
          error.response.data.error || 
          error.response.data.CONFLICT ||
          'Error en la solicitud de registro'
        );
      }
      // Si fue un error de red o de conexión con el servidor
      throw new Error('No se pudo conectar con el servidor');
    }

  },
  async login(userData){
      try{
      const response = await api.post('/auth/controllers/login.php',userData)
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
      // Si fue un error de red o de conexión con el servidor
      throw new Error('No se pudo conectar con el servidor');
    }

  },
  
};

  
 