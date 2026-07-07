@extends('layouts.contract-html', ['title' => __('Partnership contract')])

@section('content')
    <div class="doc">
        <h1>{{ __('Partnership contract') }}</h1>
        <p class="muted text-sm">{{ __('Document generated for signing; keep a copy for your records.') }}</p>

        <h2 class="muted" style="font-size:0.875rem;margin-top:1.25rem;">{{ __('Contract summary') }}</h2>
        <div class="contract-header" role="region" aria-label="{{ __('Contract summary') }}">{{ $contractHeader }}</div>
        <h2 class="muted" style="font-size:0.875rem;margin-top:1.25rem;">{{ __('provider_contract_terms_heading') }}</h2>
        @if ($contractTermsIsHtml ?? false)
            <div class="flow-partnership-terms-html" role="region" aria-label="{{ __('provider_contract_terms_heading') }}">{!! $contractTerms !!}</div>
        @else
            <div class="terms" role="region" aria-label="{{ __('provider_contract_terms_heading') }}">{{ $contractTerms }}</div>
        @endif

        @if (($viewer ?? 'provider') === 'company')
            <div class="sig-block">
                <p class="sig-label">{{ __('Provider signature') }}</p>
                @if ($provider->partnership_provider_signature_data)
                    <img src="{{ $provider->partnership_provider_signature_data }}" alt="{{ __('Provider signature') }}" class="sig-img" />
                    @if ($provider->partnership_provider_signed_at)
                        <p class="muted" style="margin-top: 0.5rem;">
                            {{ __('Signed electronically on :datetime', ['datetime' => $provider->partnership_provider_signed_at->timezone(config('app.timezone'))->format('Y-m-d H:i')]) }}
                        </p>
                    @endif
                @else
                    <p class="muted">{{ __('No signature image on file yet.') }}</p>
                @endif
            </div>
            <p class="muted no-print" style="margin-top: 1.5rem;">
                <a href="{{ route('providers.partnership.show', $provider) }}" class="btn-link" style="text-decoration: none; border: 1px solid #cbd5e1; padding: 0.5rem 1rem; border-radius: 0.5rem;">{{ __('Back to partnership signing') }}</a>
            </p>
        @elseif ($canSign)
            <form method="POST" action="{{ route('provider.partnership.sign') }}" id="partnership-sign-form" class="sig-block no-print">
                @csrf
                <p class="sig-label">{{ __('Your signature') }}</p>
                <p class="muted" style="margin-bottom: 0.75rem;">{{ __('Sign inside the box below, then accept the terms and send.') }}</p>
                <canvas id="sig-canvas" width="560" height="160" aria-label="{{ __('Signature pad') }}"></canvas>
                <input type="hidden" name="signature_data" id="signature_data" value="" />
                <div class="btn-row">
                    <button type="button" class="secondary" id="sig-clear">{{ __('Clear signature') }}</button>
                </div>
                @error('signature_data')
                    <p class="err">{{ $message }}</p>
                @enderror

                <label class="chk">
                    <input type="checkbox" name="accept" value="1" required />
                    <span>{{ __('I have read and accept this partnership for :company.', ['company' => $provider->company->name]) }}</span>
                </label>
                <x-input-error :messages="$errors->get('accept')" class="mt-2" />

                <div class="btn-row" style="margin-top: 1rem;">
                    <button type="submit" id="sig-submit">{{ __('Send signed contract') }}</button>
                </div>
            </form>
        @else
            <div class="sig-block">
                <p class="sig-label">{{ __('Your signature') }}</p>
                @if ($provider->partnership_provider_signature_data)
                    <img src="{{ $provider->partnership_provider_signature_data }}" alt="{{ __('Provider signature') }}" class="sig-img" />
                    @if ($provider->partnership_provider_signed_at)
                        <p class="muted" style="margin-top: 0.5rem;">
                            {{ __('Signed electronically on :datetime', ['datetime' => $provider->partnership_provider_signed_at->timezone(config('app.timezone'))->format('Y-m-d H:i')]) }}
                        </p>
                    @endif
                @endif
                @if ($provider->needsCompanyPartnershipSignature())
                    <p class="muted" style="margin-top: 1rem;">{{ __('You have signed. Waiting for a company administrator to finalize the partnership.') }}</p>
                @endif
            </div>
        @endif
    </div>
@endsection

@if (($viewer ?? 'provider') === 'provider' && $canSign)
    @push('scripts')
        <script>
            (function () {
                const canvas = document.getElementById('sig-canvas');
                const input = document.getElementById('signature_data');
                const form = document.getElementById('partnership-sign-form');
                if (!canvas || !input || !form) return;

                const ctx = canvas.getContext('2d');
                let drawing = false;
                let hasInk = false;

                function pos(e) {
                    const r = canvas.getBoundingClientRect();
                    const scaleX = canvas.width / r.width;
                    const scaleY = canvas.height / r.height;
                    if (e.touches && e.touches[0]) {
                        return {
                            x: (e.touches[0].clientX - r.left) * scaleX,
                            y: (e.touches[0].clientY - r.top) * scaleY,
                        };
                    }
                    return {
                        x: (e.clientX - r.left) * scaleX,
                        y: (e.clientY - r.top) * scaleY,
                    };
                }

                function start(e) {
                    e.preventDefault();
                    drawing = true;
                    const p = pos(e);
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                }

                function move(e) {
                    if (!drawing) return;
                    e.preventDefault();
                    const p = pos(e);
                    ctx.strokeStyle = '#0f172a';
                    ctx.lineWidth = 2;
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    ctx.lineTo(p.x, p.y);
                    ctx.stroke();
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                    hasInk = true;
                }

                function end() {
                    drawing = false;
                    ctx.beginPath();
                }

                canvas.addEventListener('mousedown', start);
                canvas.addEventListener('mousemove', move);
                window.addEventListener('mouseup', end);
                canvas.addEventListener('touchstart', start, { passive: false });
                canvas.addEventListener('touchmove', move, { passive: false });
                window.addEventListener('touchend', end);

                document.getElementById('sig-clear').addEventListener('click', function () {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    hasInk = false;
                    input.value = '';
                });

                form.addEventListener('submit', function (e) {
                    if (!hasInk) {
                        e.preventDefault();
                        alert({!! json_encode(__('Please sign inside the box before sending.')) !!});
                        return;
                    }
                    input.value = canvas.toDataURL('image/png');
                });
            })();
        </script>
    @endpush
@endif
