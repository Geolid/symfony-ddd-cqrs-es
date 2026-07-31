import '@picocss/pico/css/pico.min.css';
import '@picocss/pico/css/pico.colors.min.css';
import './styles/app.css';

import Alpine from 'alpinejs';

Alpine.start();

document.addEventListener('click', (event) => {
    const add = event.target.closest('[data-collection-add]');

    if (!add) {
        return;
    }

    const collection = add.closest('[data-collection]');
    const rows = collection.querySelector('[data-collection-rows]');

    rows.insertAdjacentHTML(
        'beforeend',
        collection.dataset.prototype.replaceAll('__name__', String(rows.children.length)),
    );
});
