import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['code'];
    static values = { filename: String };

    copy() {
        navigator.clipboard.writeText(this.text());
    }

    download() {
        const blob = new Blob([this.text()], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);

        const link = document.createElement('a');
        link.href = url;
        link.download = this.filenameValue || 'recovery-codes.txt';
        link.click();

        URL.revokeObjectURL(url);
    }

    text() {
        return this.codeTargets.map((code) => code.textContent.trim()).join('\n');
    }
}
