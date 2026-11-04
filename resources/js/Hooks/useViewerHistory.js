import { useEffect, useRef } from 'react';

/**
 * Push a dummy history entry while a viewer is open so browser Back closes
 * the viewer instead of leaving the current list page.
 */
export default function useViewerHistory(show, onClose) {
    const onCloseRef = useRef(onClose);
    onCloseRef.current = onClose;
    const pushedRef = useRef(false);

    useEffect(() => {
        if (!show) return undefined;

        window.history.pushState({ gestdocViewer: 1 }, '');
        pushedRef.current = true;

        const onPop = () => {
            pushedRef.current = false;
            onCloseRef.current();
        };
        window.addEventListener('popstate', onPop);
        return () => {
            window.removeEventListener('popstate', onPop);
            if (pushedRef.current) {
                pushedRef.current = false;
                window.history.back();
            }
        };
    }, [show]);
}
