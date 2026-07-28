@extends('admin.dashboard')

@section('admin')
<style>
    .link-creator-shell { padding: 24px 4px; }
    .link-creator-hero,
    .link-creator-card {
        border: 1px solid rgba(104, 167, 238, .28);
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 18px 45px rgba(24, 55, 93, .10);
        overflow: hidden;
    }
    .link-creator-hero {
        padding: 26px;
        color: #fff;
        background:
            radial-gradient(circle at 90% 10%, rgba(104, 167, 238, .42), transparent 34%),
            linear-gradient(135deg, #18375d, #244f82);
    }
    .admin-future .content-page .link-creator-hero h2 {
        margin: 0 0 7px;
        color: #fff !important;
        font-weight: 800;
        text-shadow: 0 2px 12px rgba(5, 25, 48, .28);
    }
    .link-creator-hero p {
        margin: 0;
        max-width: 820px;
        color: #f1f7ff !important;
        font-weight: 500;
    }
    .link-creator-card { margin-top: 22px; padding: 24px; }
    .link-creator-card h4 { color: #18375d; font-weight: 800; }
    .link-control {
        width: 100%;
        min-height: 44px;
        border: 1px solid #c9dbef;
        border-radius: 13px;
        padding: 9px 13px;
        color: #18375d;
        background: #f8fbff;
    }
    .link-control:focus { outline: 3px solid rgba(104, 167, 238, .24); border-color: #68a7ee; }
    .link-submit,
    .link-action {
        border: 0;
        border-radius: 12px;
        padding: 10px 15px;
        font-weight: 800;
    }
    .link-submit { color: #fff; background: linear-gradient(135deg, #18375d, #68a7ee); }
    .link-action { padding: 7px 10px; font-size: 12px; }
    .link-copy { color: #18375d; background: #eaf4ff; }
    .link-toggle { color: #14734b; background: #e8f7ef; }
    .link-delete { color: #a82c37; background: #fff0f0; }
    .share-url {
        max-width: 390px;
        padding: 9px 11px;
        border-radius: 10px;
        color: #365779;
        background: #f2f7fd;
        font-size: 12px;
        overflow-wrap: anywhere;
    }
    .link-status {
        display: inline-flex;
        border-radius: 999px;
        padding: 5px 9px;
        font-size: 11px;
        font-weight: 800;
    }
    .link-status.active { color: #126741; background: #dff7ea; }
    .link-status.inactive { color: #8f3030; background: #f5e8e8; }
    .event-managed-note {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 10px;
        padding: 8px 11px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, .24);
        color: #fff !important;
        background: rgba(8, 35, 65, .34);
        font-size: 12px;
        font-weight: 700;
    }
    .event-managed-note i { color: #bdddff !important; }
    @media (max-width: 767px) {
        .link-creator-shell { padding: 12px 0; }
        .link-creator-card, .link-creator-hero { padding: 18px; }
        .link-table { min-width: 940px; }
        .link-search-form { width: 100%; }
        .link-search-form .link-control { min-width: 0 !important; }
    }
</style>

<div class="link-creator-shell">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <section class="link-creator-hero">
        <h2>Campus Event Route Links</h2>
        <p>Route links are generated automatically when a campus event is created. Use this page to copy, monitor, disable, or remove those links.</p>
        <div class="event-managed-note">
            <i class="ri-calendar-event-line"></i>
            Create destinations and new links from the Campus Event page.
        </div>
    </section>

    <section class="link-creator-card">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
            <div>
                <h4 class="mb-1">Generated Event Links</h4>
                <small class="text-muted">{{ $destinationLinks->total() }} link(s)</small>
            </div>
            <form method="GET" action="{{ route('admin.destination-links.index') }}" class="d-flex gap-2 link-search-form">
                <input class="link-control" style="min-width:280px" type="search" name="search" value="{{ $search }}" placeholder="Search events, links, or destinations">
                <button class="link-submit" type="submit">Search</button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table align-middle link-table">
                <thead>
                    <tr>
                        <th>Event / Destination</th>
                        <th>Shareable Link</th>
                        <th>Campus Event</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($destinationLinks as $link)
                        @php
                            $shareUrl = route('destination-links.open', $link);
                            $eventHasEnded = $link->campusEvent?->ends_at?->isPast() ?? false;
                            $eventBlocksLink = $link->campusEvent
                                && (! $link->campusEvent->is_active || $eventHasEnded);
                            $linkAvailable = $link->isAvailable();
                            $statusLabel = $eventHasEnded
                                ? 'Event Ended'
                                : ($link->campusEvent && ! $link->campusEvent->is_active
                                    ? 'Event Inactive'
                                    : ($link->is_active ? 'Active' : 'Inactive'));
                        @endphp
                        <tr>
                            <td>
                                <strong style="color:#18375d">{{ $link->title ?: 'Campus Event Route' }}</strong>
                                <div class="small text-muted">{{ ucfirst($link->destination_type) }} · {{ $link->destinationLabel() }}</div>
                            </td>
                            <td><div class="share-url">{{ $shareUrl }}</div></td>
                            <td>{{ $link->campusEvent?->title ?? 'Legacy / Manual Link' }}</td>
                            <td><span class="link-status {{ $linkAvailable ? 'active' : 'inactive' }}">{{ $statusLabel }}</span></td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    @if($linkAvailable)
                                        <button type="button" class="link-action link-copy" data-copy-url="{{ $shareUrl }}">Copy</button>
                                    @endif
                                    @if(!$eventBlocksLink)
                                        <form method="POST" action="{{ route('admin.destination-links.toggle', $link) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="link-action link-toggle" type="submit">{{ $link->is_active ? 'Disable' : 'Enable' }}</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.destination-links.destroy', $link) }}" onsubmit="return confirm('Delete this destination link?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="link-action link-delete" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                No event links yet. Create a campus event to generate its route link automatically.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin.partials.pagination', [
            'paginator' => $destinationLinks,
            'label' => 'event links',
        ])
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-copy-url]').forEach(button => {
            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(button.dataset.copyUrl);
                    const previous = button.textContent;
                    button.textContent = 'Copied!';
                    setTimeout(() => button.textContent = previous, 1400);
                } catch {
                    window.FuturisticDialog.copy(
                        'Copy this destination link and share it with campus visitors.',
                        button.dataset.copyUrl
                    );
                }
            });
        });
    });
</script>
@endsection
