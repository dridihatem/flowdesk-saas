<script>
    window.flowdeskCountryDefaults = {
        vat: @json(config('flowdesk_country_vat', [])),
        currency: @json(config('flowdesk.country_currency', [])),
    };
</script>
