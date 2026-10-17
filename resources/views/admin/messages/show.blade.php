@extends('layouts.admin')

@section('title', 'Message from '.$message->name)

@section('content')
    <div class="dash-header">
        <div>
            <a href="{{ route('admin.messages.index') }}" class="small">← Messages</a>
            <h1>{{ $message->subject ?: 'Message from '.$message->name }}</h1>
            <p>{{ $message->created_at->format('M j, Y g:i A') }}</p>
        </div>
        <form action="{{ route('admin.messages.destroy', $message) }}" method="POST" data-confirm="Delete this message?">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger-outline btn-sm">Delete</button>
        </form>
    </div>

    <div class="card card-body" style="max-width: 760px;">
        <dl class="dl mb-3">
            <div><dt>Name</dt><dd>{{ $message->name }}</dd></div>
            <div><dt>Email</dt><dd><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></dd></div>
            @if ($message->phone)
                <div><dt>Phone</dt><dd><a href="tel:{{ $message->phone }}">{{ $message->phone }}</a></dd></div>
            @endif
        </dl>
        <p style="white-space: pre-line;">{{ $message->message }}</p>
        <div class="form-actions">
            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.($message->subject ?: 'Your message')) }}" class="btn btn-primary btn-sm">Reply by email</a>
        </div>
    </div>
@endsection
