import { Controller } from '@hotwired/stimulus';

const DISPLAY_DURATION_MS = 4000;
const FADE_DURATION_MS = 300;

export default class extends Controller {
    connect() {
        this.timeout = setTimeout(() => this.dismiss(), DISPLAY_DURATION_MS);
    }

    disconnect() {
        clearTimeout(this.timeout);
    }

    dismiss() {
        clearTimeout(this.timeout);
        this.element.style.opacity = '0';
        setTimeout(() => this.element.remove(), FADE_DURATION_MS);
    }
}
