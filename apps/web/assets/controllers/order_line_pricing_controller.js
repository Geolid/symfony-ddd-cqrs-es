import { Controller } from '@hotwired/stimulus';
import '../styles/order-lines.css';

export default class extends Controller {
    static targets = ['total'];

    recompute() {
        let totalInCents = 0;

        for (const row of this.element.querySelectorAll('[data-collection-target~="row"]')) {
            const select = row.querySelector('select');
            const priceInCents = Number(select?.selectedOptions[0]?.dataset.priceCents ?? 0);
            const quantity = Number(row.querySelector('input[type="number"]')?.value || 0);

            const priceCell = row.querySelector('.line-price');
            if (priceCell) {
                priceCell.textContent = select?.value ? `${(priceInCents / 100).toFixed(2)} €` : '—';
            }

            totalInCents += priceInCents * quantity;
        }

        this.totalTarget.textContent = (totalInCents / 100).toFixed(2);
    }
}
