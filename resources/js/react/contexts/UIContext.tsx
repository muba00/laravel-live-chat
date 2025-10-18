import React, {
    createContext,
    useReducer,
    useContext,
    useCallback,
    useMemo,
    useEffect,
} from "react";
import type { UIState, UIAction } from "../types";

const initialState: UIState = {
    sidebarOpen: true,
    newConversationModalOpen: false,
    searchQuery: "",
    theme: "light",
    isMobile: false,
    toast: null,
    confirm: null,
};

function uiReducer(state: UIState, action: UIAction): UIState {
    switch (action.type) {
        case "TOGGLE_SIDEBAR":
            return { ...state, sidebarOpen: !state.sidebarOpen };

        case "SET_SIDEBAR":
            return { ...state, sidebarOpen: action.payload };

        case "TOGGLE_NEW_CONVERSATION_MODAL":
            return {
                ...state,
                newConversationModalOpen: !state.newConversationModalOpen,
            };

        case "SET_NEW_CONVERSATION_MODAL":
            return { ...state, newConversationModalOpen: action.payload };

        case "SET_SEARCH_QUERY":
            return { ...state, searchQuery: action.payload };

        case "SET_THEME":
            return { ...state, theme: action.payload };

        case "SET_IS_MOBILE":
            return { ...state, isMobile: action.payload };

        case "SHOW_TOAST":
            return { ...state, toast: action.payload };

        case "HIDE_TOAST":
            return { ...state, toast: null };

        case "SHOW_CONFIRM":
            return { ...state, confirm: action.payload };

        case "HIDE_CONFIRM":
            return { ...state, confirm: null };

        case "UI_RESET":
            return initialState;

        default:
            return state;
    }
}

interface UIContextValue {
    state: UIState;
    dispatch: React.Dispatch<UIAction>;
    toggleSidebar: () => void;
    setSidebar: (open: boolean) => void;
    toggleNewConversationModal: () => void;
    setNewConversationModal: (open: boolean) => void;
    setSearchQuery: (query: string) => void;
    setTheme: (theme: "light" | "dark") => void;
    setIsMobile: (isMobile: boolean) => void;
    showToast: (
        message: string,
        type?: "success" | "error" | "info" | "warning",
        duration?: number
    ) => void;
    hideToast: () => void;
    showConfirm: (title: string, message: string) => Promise<boolean>;
    hideConfirm: () => void;
}

const UIContext = createContext<UIContextValue | undefined>(undefined);

export function UIProvider({ children }: { children: React.ReactNode }) {
    const [state, dispatch] = useReducer(uiReducer, initialState);

    const toggleSidebar = useCallback(() => {
        dispatch({ type: "TOGGLE_SIDEBAR" });
    }, []);

    const setSidebar = useCallback((open: boolean) => {
        dispatch({ type: "SET_SIDEBAR", payload: open });
    }, []);

    const toggleNewConversationModal = useCallback(() => {
        dispatch({ type: "TOGGLE_NEW_CONVERSATION_MODAL" });
    }, []);

    const setNewConversationModal = useCallback((open: boolean) => {
        dispatch({ type: "SET_NEW_CONVERSATION_MODAL", payload: open });
    }, []);

    const setSearchQuery = useCallback((query: string) => {
        dispatch({ type: "SET_SEARCH_QUERY", payload: query });
    }, []);

    const setTheme = useCallback((theme: "light" | "dark") => {
        dispatch({ type: "SET_THEME", payload: theme });
    }, []);

    const setIsMobile = useCallback((isMobile: boolean) => {
        dispatch({ type: "SET_IS_MOBILE", payload: isMobile });
    }, []);

    const showToast = useCallback(
        (
            message: string,
            type: "success" | "error" | "info" | "warning" = "info",
            duration: number = 3000
        ) => {
            const id = Math.random().toString(36).substr(2, 9);
            dispatch({
                type: "SHOW_TOAST",
                payload: { id, message, type, duration },
            });

            // Auto-hide after duration
            if (duration > 0) {
                setTimeout(() => {
                    dispatch({ type: "HIDE_TOAST" });
                }, duration);
            }
        },
        []
    );

    const hideToast = useCallback(() => {
        dispatch({ type: "HIDE_TOAST" });
    }, []);

    const showConfirm = useCallback(
        (title: string, message: string): Promise<boolean> => {
            return new Promise((resolve) => {
                const id = Math.random().toString(36).substr(2, 9);
                dispatch({
                    type: "SHOW_CONFIRM",
                    payload: { id, title, message, resolve },
                });
            });
        },
        []
    );

    const hideConfirm = useCallback(() => {
        dispatch({ type: "HIDE_CONFIRM" });
    }, []);

    useEffect(() => {
        const checkMobile = () => {
            const isMobileView = window.innerWidth < 768;
            setIsMobile(isMobileView);

            if (!isMobileView && !state.sidebarOpen) {
                setSidebar(true);
            }
        };

        checkMobile();
        window.addEventListener("resize", checkMobile);

        return () => {
            window.removeEventListener("resize", checkMobile);
        };
    }, [state.sidebarOpen, setIsMobile, setSidebar]);

    const value = useMemo(
        () => ({
            state,
            dispatch,
            toggleSidebar,
            setSidebar,
            toggleNewConversationModal,
            setNewConversationModal,
            setSearchQuery,
            setTheme,
            setIsMobile,
            showToast,
            hideToast,
            showConfirm,
            hideConfirm,
        }),
        [
            state,
            toggleSidebar,
            setSidebar,
            toggleNewConversationModal,
            setNewConversationModal,
            setSearchQuery,
            setTheme,
            setIsMobile,
            showToast,
            hideToast,
            showConfirm,
            hideConfirm,
        ]
    );

    return <UIContext.Provider value={value}>{children}</UIContext.Provider>;
}

export function useUI() {
    const context = useContext(UIContext);
    if (!context) {
        throw new Error("useUI must be used within UIProvider");
    }

    // Destructure state for easier access
    const {
        sidebarOpen,
        newConversationModalOpen,
        searchQuery,
        theme,
        isMobile,
        toast,
        confirm,
    } = context.state;

    return {
        ...context,
        sidebarOpen,
        newConversationModalOpen,
        searchQuery,
        theme,
        isMobile,
        toast,
        confirm,
    };
}
