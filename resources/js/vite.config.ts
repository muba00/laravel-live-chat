import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': resolve(__dirname, './react'),
    },
  },
  build: {
    lib: {
      entry: resolve(__dirname, 'react/index.ts'),
      name: 'LaravelLiveChat',
      formats: ['es'],
      fileName: 'index',
    },
    rollupOptions: {
      external: ['react', 'react-dom', 'laravel-echo', 'pusher-js'],
      output: {
        globals: {
          react: 'React',
          'react-dom': 'ReactDOM',
          'laravel-echo': 'Echo',
          'pusher-js': 'Pusher',
        },
      },
    },
    sourcemap: true,
    outDir: 'dist',
  },
  server: {
    port: 53833,
    host: '0.0.0.0',
    strictPort: false,
  },
});
