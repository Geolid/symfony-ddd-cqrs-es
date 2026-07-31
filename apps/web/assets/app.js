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
