
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentTrack | Registration Requests</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            padding: 35px;
            color: #1f2937;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 25px;
        }

        h1 {
            color: #0f766e;
        }

        a {
            color: #0f766e;
            text-decoration: none;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 5px 20px #00000010;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #0f766e;
            color: white;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            background: #e5e7eb;
        }

        .pending {
            background: #fef3c7;
            color: #92400e;
        }

        .approved {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .used {
            background: #dcfce7;
            color: #166534;
        }

        .expired {
            background: #fee2e2;
            color: #991b1b;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .actions form {
            margin: 0;
        }

        .btn {
            color: white;
            padding: 9px 13px;
            border: 0;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
        }

        .btn-approve {
            background: #0f766e;
        }

        .btn-approve:hover {
            background: #115e59;
        }

        .btn-reject {
            background: #dc2626;
        }

        .btn-reject:hover {
            background: #b91c1c;
        }

        .btn-resend {
            background: #2563eb;
        }

        .btn-resend:hover {
            background: #1d4ed8;
        }

        .muted {
            color: #64748b;
            font-size: 13px;
        }

        .expired-text {
            color: #dc2626;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #64748b;
        }

        .pagination {
            margin-top: 20px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 768px) {
            body {
                padding: 20px 12px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/rentrack-admin-legacy.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rentrack-legacy-premium.css') }}">
</head>

<body class="rt-admin-legacy rt-legacy-page">
@include('components.premium-legacy-nav')
<div class="container">

    <div class="header">
        <div>
            <h1>RentTrack</h1>
            <h2>Registration Requests</h2>
        </div>

        <a href="{{ route('admin.dashboard') }}">
            Back to Dashboard
        </a>
    </div>

    @if(session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Date Submitted</th>
                    <th>Link Expiration</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($requests as $item)

                    @php
                        $expiry = $item->expires_at
                            ? \Illuminate\Support\Carbon::parse(
                                $item->expires_at
                            )
                            : null;

                        $isExpired = (
                            $item->status === 'Approved'
                            && $expiry !== null
                            && $expiry->lessThanOrEqualTo(now())
                        );

                        $displayStatus = $isExpired
                            ? 'Expired'
                            : $item->status;

                        $canResend = (
                            $displayStatus === 'Expired'
                        );
                    @endphp

                    <tr>
                        <td>
                            {{ $item->registration_id }}
                        </td>

                        <td>
                            {{ $item->email }}
                        </td>

                        <td>
                            <span class="status {{ strtolower($displayStatus) }}">
                                {{ $displayStatus }}
                            </span>
                        </td>

                        <td>
                            {{ $item->created_at
                                ? \Illuminate\Support\Carbon::parse(
                                    $item->created_at
                                  )->format('M d, Y h:i A')
                                : '—' }}
                        </td>

                        <td>
                            @if($expiry)
                                <div>
                                    {{ $expiry->format('M d, Y h:i A') }}
                                </div>

                                @if($displayStatus === 'Expired')
                                    <small class="expired-text">
                                        Link expired
                                    </small>
                                @elseif($displayStatus === 'Approved')
                                    <small class="muted">
                                        Link active
                                    </small>
                                @endif
                            @else
                                <span class="muted">
                                    —
                                </span>
                            @endif
                        </td>

                        <td>
                            @if($item->status === 'Pending')

                                <div class="actions">

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'admin.registrations.approve',
                                            $item
                                        ) }}"
                                        data-confirm-title="Approve registration"
                                        data-confirm-message="Approve this registration request and send its invitation?"
                                        data-confirm-button="Approve request"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="btn btn-approve"
                                        >
                                            Approve
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'admin.registrations.reject',
                                            $item
                                        ) }}"
                                        data-confirm-title="Reject registration"
                                        data-confirm-message="Reject this pending registration request?"
                                        data-confirm-button="Reject request"
                                        data-confirm-danger="true"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="btn btn-reject"
                                        >
                                            Reject
                                        </button>
                                    </form>

                                </div>

                            @elseif($canResend)

                                @if(\Illuminate\Support\Facades\Route::has(
                                    'admin.registrations.resend'
                                ))

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'admin.registrations.resend',
                                            $item
                                        ) }}"
                                        data-confirm-title="Resend invitation"
                                        data-confirm-message="Generate a new one-time invitation link and send it?"
                                        data-confirm-button="Resend invitation"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            class="btn btn-resend"
                                        >
                                            Regenerate &amp; Resend
                                        </button>
                                    </form>

                                @else
                                    <span class="muted">
                                        Resend route not configured
                                    </span>
                                @endif

                            @else

                                <span class="muted">
                                    No available actions
                                </span>

                            @endif
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="6" class="empty">
                            No registration requests found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div> 

    <div class="pagination">
        {{ $requests->links() }}
    </div>

</div>
@include('components.confirm-dialog')
</body>
</html>
