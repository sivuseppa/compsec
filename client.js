export default client = {
  API_URL: '/api/',
  async get(action, params) {
    const response = await fetch(this.API_URL, {
      method: 'POST',
      body: JSON.stringify({ ...params, action: action }),
    });

    return response.json();
  },
  async post(action, params) {
    const response = await fetch(this.API_URL, {
      method: 'POST',
      body: JSON.stringify({ ...params, action: action }),
    });

    return response.json();
  },
};
