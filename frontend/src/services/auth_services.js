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
      
      // La respuesta del PHP viene directamente en response.data
      return response.data;
    } catch (error) {
      // Si el servidor PHP respondió con un HTTP 400, 409, etc.
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
  }
};