@extends('layouts.app')

@section('title', 'Booking '.$job->reference)

@section('content')
    <div class="container dash">
        <div class="dash-header">
            <div>
                <a href="{{ route('client.dashboard') }}" class="small">← Back to my bookings</a>
                <h1>{{ $job->title }}</h1>
                <div class="booking-row__badges mt-1">
                    <span class="badge">{{ $job->reference }}</span>
                    <x-job-status :job="$job" />
                    <x-payment-status :job="$job" />
                </div>
            </div>
            @if ($job->canBeCancelledByClient())
                <form action="{{ route('client.bookings.cancel', $job) }}" method="POST" data-confirm="Cancel this booking?{{ $job->isFunded() ? ' Your payment will be refunded.' : '' }}">
                    @csrf
                    <button type="submit" class="btn btn-danger-outline btn-sm">Cancel booking</button>
                </form>
            @endif
        </div>

        <div class="detail-grid">
            <div class="stack">
                {{-- Payment --}}
                @if ($job->needsPayment())
                    <div class="card" id="pay">
                        <div class="card-head">
                            <h2 class="card-title">💳 Pay {{ $site->money($job->budget) }} to confirm your booking</h2>
                        </div>
                        <div class="card-body">
                            @php($rejected = $job->payments->firstWhere('status', 'failed'))
                            @if ($rejected)
                                <div class="alert alert--error">Your last payment ({{ $rejected->transaction_reference }}) could not be verified{{ $rejected->notes ? ': '.$rejected->notes : '' }}. Please try again.</div>
                            @endif

                            <p class="text-muted mb-2">{{ $site->get('payment_instructions') }}</p>

                            <dl class="pay-box mb-3">
                                <dt>MTN Mobile Money</dt>
                                <dd>{{ $site->get('mtn_momo_number') }}</dd>
                                <dt>Airtel Money</dt>
                                <dd>{{ $site->get('airtel_money_number') }}</dd>
                                <dt>Bank transfer</dt>
                                <dd>{{ $site->get('bank_details') }}</dd>
                                <dt>Account name</dt>
                                <dd>{{ $site->get('payment_account_name') }}</dd>
                                <dt>Reference to use</dt>
                                <dd><strong>{{ $job->reference }}</strong></dd>
                            </dl>

                            <form action="{{ route('client.bookings.payments.store', $job) }}" method="POST">
                                @csrf
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="method">How did you pay?</label>
                                        <select id="method" name="method" class="input" data-payment-method required>
                                            @foreach ($paymentMethods as $value => $label)
                                                <option value="{{ $value }}" @selected(old('method') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group" data-payer-phone>
                                        <label for="payer_phone">Phone number you paid from</label>
                                        <input type="tel" id="payer_phone" name="payer_phone" class="input" value="{{ old('payer_phone', auth()->user()->phone_number) }}">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="transaction_reference">Transaction ID</label>
                                    <input type="text" id="transaction_reference" name="transaction_reference" class="input" value="{{ old('transaction_reference') }}" placeholder="From your payment SMS or bank slip" required>
                                </div>
                                <button type="submit" class="btn btn-accent btn-block">I have paid, submit payment</button>
                            </form>
                        </div>
                    </div>
                @elseif ($job->payment_status === 'pending')
                    <div class="alert alert--info">⏳ <span>We received your payment details and are verifying them. Your gig opens to maids as soon as it is confirmed.</span></div>
                @elseif ($job->payment_status === 'refund_due')
                    <div class="alert alert--warning">↩️ <span>Your refund of {{ $site->money($job->budget) }} is being processed.</span></div>
                @endif

                {{-- Approval --}}
                @if ($job->isAwaitingClientApproval())
                    <div class="card" id="approve">
                        <div class="card-head"><h2 class="card-title">⭐ The maid marked this job complete</h2></div>
                        <div class="card-body">
                            <p class="text-muted mb-2">Happy with the work? Approving releases the payment from escrow to {{ $job->assignedMaid?->name }}. If something is wrong, <a href="{{ route('contact') }}">contact support</a> before approving.</p>
                            <form action="{{ route('client.bookings.approve', $job) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <span class="label">Rate the service</span>
                                    <div class="rating-input">
                                        @for ($i = 5; $i >= 1; $i--)
                                            <input type="radio" id="rating-{{ $i }}" name="rating" value="{{ $i }}" @checked((int) old('rating', 5) === $i)>
                                            <label for="rating-{{ $i }}" title="{{ $i }} stars">★</label>
                                        @endfor
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="review">Review (optional)</label>
                                    <textarea id="review" name="review" class="input" rows="3">{{ old('review') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">Approve & release payment</button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Details --}}
                <div class="card">
                    <div class="card-head"><h2 class="card-title">Booking details</h2></div>
                    <div class="card-body">
                        <dl class="dl">
                            <div><dt>Service</dt><dd>{{ $job->service?->name ?? $job->title }}</dd></div>
                            <div><dt>Quantity</dt><dd>{{ $job->quantity }} {{ $job->service?->price_unit }}</dd></div>
                            <div><dt>When</dt><dd>{{ $job->scheduleLabel() ?? 'Not set' }}</dd></div>
                            <div><dt>Where</dt><dd>{{ $job->location }}</dd></div>
                        </dl>
                        @if ($job->description)
                            <p class="mt-2" style="white-space: pre-line;">{{ $job->description }}</p>
                        @endif
                        @if ($job->imageUrls())
                            <div class="thumbs mt-2">
                                @foreach ($job->imageUrls() as $url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt="Photo of the space"></a>
                                @endforeach
                            </div>
                        @endif
                        @if ($job->client_rating)
                            <p class="mt-2"><strong>Your rating:</strong> <span style="color: var(--accent);">{{ str_repeat('★', $job->client_rating) }}</span> {{ $job->client_review }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <aside class="stack">
                <div class="card card-body">
                    <h2 class="card-title mb-2">Progress</h2>
                    @include('partials.booking-tracker', ['job' => $job])
                </div>

                @if ($job->assignedMaid)
                    <div class="card card-body">
                        <h2 class="card-title mb-2">Your maid</h2>
                        <div class="testimonial__who">
                            <span class="avatar avatar--lg">{{ strtoupper(substr($job->assignedMaid->name, 0, 1)) }}</span>
                            <div>
                                <strong>{{ $job->assignedMaid->name }}</strong>
                                <span>@if ($job->assignedMaid->maidProfile?->isVerified()) ✅ ID verified @endif
                                    @if ($job->assignedMaid->maidProfile?->average_rating) · ★ {{ number_format($job->assignedMaid->maidProfile->average_rating, 1) }} @endif</span>
                            </div>
                        </div>
                        <a href="tel:{{ $job->assignedMaid->phone_number }}" class="btn btn-ghost btn-block mt-2">📞 {{ $job->assignedMaid->phone_number }}</a>
                    </div>
                @endif

                <div class="card card-body">
                    <h2 class="card-title mb-2">Payment summary</h2>
                    <div class="money-lines">
                        <div><span>Booking total</span><span>{{ $site->money($job->budget) }}</span></div>
                        <div><span>Status</span><x-payment-status :job="$job" /></div>
                        @foreach ($job->payments as $payment)
                            <div class="small text-muted"><span>{{ $payment->methodLabel() }} · {{ $payment->transaction_reference }}</span><x-record-status :status="$payment->status" /></div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection
