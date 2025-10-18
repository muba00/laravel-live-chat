import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";
import { resolve } from "path";

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            "@": resolve(__dirname, "./react"),
        },
    },
    css: {
        postcss: "./postcss.config.cjs",
    },
    build: {
        lib: {
            entry: resolve(__dirname, "react/index.ts"),
            name: "LaravelLiveChat",
            formats: ["es"],
            fileName: "index",
        },
        rollupOptions: {
            external: ["react", "react-dom", "laravel-echo", "pusher-js"],
            output: {
                globals: {
                    react: "React",
                    "react-dom": "ReactDOM",
                    "laravel-echo": "Echo",
                    "pusher-js": "Pusher",
                },
                // Ensure CSS is extracted to proper file name
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name && assetInfo.name.endsWith(".css")) {
                        return "live-chat.css";
                    }
                    return assetInfo.name || "assets/[name][extname]";
                },
            },
        },
        // Enable CSS code splitting to generate separate CSS file
        cssCodeSplit: false,
        sourcemap: true,
        outDir: "dist",
        emptyOutDir: true,
    },
    server: {
        port: 53833,
        host: "0.0.0.0",
        strictPort: false,
    },
});
