import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { PageProps as AppPageProps } from './';

declare global {
    interface Window {}
}

declare module 'vue' {
    interface ComponentCustomProperties {}
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps {}
}
