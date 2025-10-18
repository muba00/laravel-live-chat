#!/bin/bash

# Test script to verify CSS auto-import works
# Run from: resources/js/

echo "🔍 Checking CSS imports in React components..."
echo ""

# Check if CSS is imported in all required files
echo "1. Checking react/index.ts..."
if grep -q "import.*styles/index.css" react/index.ts; then
    echo "   ✅ CSS imported in main entry point"
else
    echo "   ❌ Missing CSS import in react/index.ts"
    exit 1
fi

echo ""
echo "2. Checking react/components/index.ts..."
if grep -q "import.*styles/index.css" react/components/index.ts; then
    echo "   ✅ CSS imported in components entry"
else
    echo "   ❌ Missing CSS import in react/components/index.ts"
    exit 1
fi

echo ""
echo "3. Checking react/components/LiveChat.tsx..."
if grep -q "import.*styles/index.css" react/components/LiveChat.tsx; then
    echo "   ✅ CSS imported in LiveChat component"
else
    echo "   ❌ Missing CSS import in react/components/LiveChat.tsx"
    exit 1
fi

echo ""
echo "4. Checking CSS files exist..."
if [ -f "react/styles/index.css" ] && [ -f "react/styles/variables.css" ] && [ -f "react/styles/live-chat.css" ]; then
    echo "   ✅ All CSS source files present"
else
    echo "   ❌ Missing CSS source files"
    exit 1
fi

echo ""
echo "5. Checking build output..."
if [ -f "dist/live-chat.css" ] && [ -f "dist/index.js" ]; then
    echo "   ✅ Built files present"
    
    # Check CSS file size (should be > 1KB)
    SIZE=$(wc -c < "dist/live-chat.css")
    if [ $SIZE -gt 1000 ]; then
        echo "   ✅ CSS file has content ($SIZE bytes)"
    else
        echo "   ❌ CSS file too small ($SIZE bytes)"
        exit 1
    fi
else
    echo "   ❌ Missing built files (run 'npm run build' first)"
    exit 1
fi

echo ""
echo "✅ All CSS auto-import checks passed!"
echo ""
echo "Usage in Laravel app:"
echo "  import { LiveChat } from '../../../vendor/muba00/laravel-live-chat/resources/js/react';"
echo ""
echo "No manual CSS import needed - styles are automatically included! 🎉"
