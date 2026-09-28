import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu', 'toggle'];

    toggle() {
        const isOpen = this.menuTarget.classList.toggle('is-open');
        this.toggleTarget.setAttribute('aria-expanded', String(isOpen));
    }
}
