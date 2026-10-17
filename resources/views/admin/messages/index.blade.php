@extends('layouts.admin')

@section('title', 'Messages')

@section('content')
    <div class="dash-header">
        <div>
            <h1>Messages</h1>
            <p>Sent from the Contact page.</p>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table table--stack">
                <thead><tr><th>From</th><th>Subject</th><th>Received</th><th></th></tr></thead>
                <tbody>
                    @forelse ($messages as $message)
                        <tr>
                            <td data-label="From">
                                <a href="{{ route('admin.messages.show', $message) }}" class="{{ $message->read_at ? '' : 'fw-bold' }}">{{ $message->name }}</a>
                                @unless ($message->read_at)<span class="badge badge--amber">New</span>@endunless
                                <div class="small text-muted">{{ $message->email }}</div>
                            </td>
                            <td data-label="Subject">{{ $message->subject ?: Str::limit($message->message, 50) }}</td>
                            <td data-label="Received" class="nowrap small">{{ $message->created_at->diffForHumans() }}</td>
                            <td data-label=""><a href="{{ route('admin.messages.show', $message) }}" class="btn btn-ghost btn-sm">Read</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No messages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $messages->links() }}
@endsection
