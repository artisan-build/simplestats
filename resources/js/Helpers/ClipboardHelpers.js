import {usePage} from "@inertiajs/vue3";

export async function copyToClipboard(text) {
    const page = usePage();

    try {
        await navigator.clipboard.writeText(text);

        // Set flash message if page object is provided
        if (page && page.props && page.props.jetstream) {
            page.props.jetstream.flash = {
                banner: 'Copied to clipboard!',
                bannerStyle: 'success'
            };
        }

        return true;
    } catch (err) {
        // Set error flash message if page object is provided
        if (page && page.props && page.props.jetstream) {
            page.props.jetstream.flash = {
                banner: 'Cannot be copied to clipboard!',
                bannerStyle: 'danger'
            };
        }

        // console.error('Failed to copy: ', err);
        return false;
    }
}
