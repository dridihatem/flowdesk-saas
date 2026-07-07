function bindPhoneNationalDigits() {
    document.querySelectorAll('[data-flowdesk-digits-only]').forEach((el) => {
        el.classList.add('flowdesk-ltr-num', 'tabular-nums');
        el.addEventListener('input', () => {
            el.value = el.value.replace(/\D/g, '');
        });
    });
}

function bindCountryToPhoneIso() {
    const country = document.getElementById('country');
    const phoneIso = document.getElementById('phone_country_iso');
    if (!country || !phoneIso) {
        return;
    }
    country.addEventListener('change', () => {
        const v = country.value;
        if (!v) {
            return;
        }
        const hasOption = [...phoneIso.options].some((o) => o.value === v);
        if (hasOption) {
            phoneIso.value = v;
        }
    });
}

function bindCountryDefaults() {
    const country = document.getElementById('country');
    if (!country) {
        return;
    }

    const defaults = window.flowdeskCountryDefaults || {};
    const vatRates = defaults.vat || {};
    const currencies = defaults.currency || {};
    const vatInput = document.getElementById('vat_percent');
    const currencyInput = document.getElementById('default_currency');

    if (vatInput) {
        vatInput.addEventListener('input', () => {
            vatInput.dataset.userEdited = '1';
        });
    }

    const apply = () => {
        const code = country.value;
        if (!code) {
            return;
        }

        if (vatInput && vatInput.dataset.userEdited !== '1') {
            if (Object.prototype.hasOwnProperty.call(vatRates, code)) {
                const rate = Number(vatRates[code]);
                vatInput.value = Number.isInteger(rate) ? String(rate) : String(rate);
            }
        }

        if (currencyInput && currencyInput.value.trim() === '' && Object.prototype.hasOwnProperty.call(currencies, code)) {
            currencyInput.value = currencies[code];
        }
    };

    country.addEventListener('change', apply);
    apply();
}

document.addEventListener('DOMContentLoaded', () => {
    bindPhoneNationalDigits();
    bindCountryToPhoneIso();
    bindCountryDefaults();
});
