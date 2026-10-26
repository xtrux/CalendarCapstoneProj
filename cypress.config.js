import { defineConfig } from 'cypress';

export default defineConfig({
  e2e: {
    // Change this to match wherever you're running the app:
    //   local dev server: http://127.0.0.1:8000
    //   droplet:          http://<your-droplet-ip>
    baseUrl: 'http://127.0.0.1:8000',
    supportFile: 'cypress/support/e2e.js',
    specPattern: 'cypress/e2e/**/*.cy.js',
    video: false,
  },
});
