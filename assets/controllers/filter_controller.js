import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['btn', 'item'];

    filter(event) {
        const category = event.currentTarget.dataset.category;
        this.btnTargets.forEach(b => b.classList.toggle('active', b.dataset.category === category));
        this.itemTargets.forEach(item => {
            const itemCat = item.dataset.category || 'other';
            const show = category === 'all' || String(itemCat) === String(category);
            item.style.display = show ? '' : 'none';
        });
    }
}
