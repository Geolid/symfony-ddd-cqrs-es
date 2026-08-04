import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['rows'];
    static values = { prototype: String, index: Number };

    add() {
        const html = this.prototypeValue.replaceAll('__name__', this.indexValue);
        this.rowsTarget.insertAdjacentHTML('beforeend', html);
        this.indexValue++;
    }

    remove(event) {
        event.target.closest('[data-collection-target~="row"]').remove();
    }
}
