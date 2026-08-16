import { createToaster } from '@meforma/vue-toaster';

const toaster = createToaster({});

export const showFlashToast = (flash) => {
    if (!flash?.message) {
        return;
    }

    if (flash.status === true) {
        toaster.success(flash.message);
        return;
    }

    if (flash.status === false) {
        toaster.error(flash.message);
    }
};
