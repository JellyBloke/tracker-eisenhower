import './toast';

document.documentElement.classList.add('js-ready');

declare global {
    interface Window {
        appBoot?: () => void;
    }
}

if (typeof window.appBoot === 'function') {
    window.appBoot();
}
