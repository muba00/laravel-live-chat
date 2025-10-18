# Using LiveChat React Component in Laravel Apps

## Direct Import from Vendor (Development)

When developing with the package installed via Composer, you can import directly from the vendor directory:

```tsx
// app/resources/js/components/LiveChat.tsx (or your wrapper component)
import { LiveChat } from "../../../vendor/muba00/laravel-live-chat/resources/js/react";

export default LiveChat;
export { LiveChat };
```

**No manual CSS import needed!** The component automatically includes its styles.

## How It Works

The LiveChat component has CSS imports at multiple levels:

1. `react/index.ts` - Imports styles at package entry point
2. `react/components/index.ts` - Imports styles at components level
3. `react/components/LiveChat.tsx` - Imports styles directly in the component

This ensures that **whenever you import the LiveChat component, the CSS comes with it automatically**.

## Vite Configuration

Make sure your Laravel `vite.config.js` is set up to handle CSS imports:

```javascript
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import react from "@vitejs/plugin-react";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.tsx"],
            refresh: true,
        }),
        react(),
    ],
    // This ensures Vite processes CSS from node_modules and vendor
    css: {
        postcss: "./postcss.config.js", // if you have PostCSS
    },
});
```

## Usage Example

```tsx
// In your Laravel Blade view
@vite(['resources/js/app.tsx'])

// In your React app (resources/js/app.tsx)
import React from 'react';
import ReactDOM from 'react-dom/client';
import LiveChat from './components/LiveChat';

ReactDOM.createRoot(document.getElementById('app')!).render(
    <React.StrictMode>
        <LiveChat
            userId={1}
            apiUrl="/api/chat"
            wsHost={window.location.hostname}
            wsPort={6001}
            wsKey="your-reverb-key"
        />
    </React.StrictMode>
);
```

## Troubleshooting

### Styles Not Appearing

If styles aren't appearing:

1. **Check the browser DevTools Console** for CSS loading errors
2. **Check the Network tab** to see if CSS is being loaded
3. **Clear Vite cache**: `rm -rf node_modules/.vite`
4. **Rebuild**: `npm run build` or `npm run dev`

### TypeScript Errors

The package uses TypeScript. Make sure your `tsconfig.json` includes:

```json
{
    "compilerOptions": {
        "jsx": "react-jsx",
        "moduleResolution": "node",
        "resolveJsonModule": true,
        "esModuleInterop": true
    }
}
```

## NPM Package (Future)

When the package is published to NPM, you can install it normally:

```bash
npm install @muba00/laravel-live-chat-react
```

Then import it:

```tsx
import { LiveChat } from "@muba00/laravel-live-chat-react";
// Styles are automatically included!
```

## Notes

-   The component uses CSS custom properties (CSS variables) for theming
-   All styles are scoped with `.live-chat` class prefix to avoid conflicts
-   Dark mode is supported via `theme="dark"` prop
-   The component is fully self-contained - no additional CSS files needed
