import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
  plugins: [react()],
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: './vitest.setup.ts',
    css: true,
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html', 'lcov'],
      exclude: [
        'node_modules/',
        'dist/',
        '**/*.stories.tsx',
        '**/*.test.ts',
        '**/*.test.tsx',
        'vitest.setup.ts',
        'vitest.config.ts',
        '.storybook/',
      ],
    },
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, './react'),
    },
  },
});
