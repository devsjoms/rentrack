
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
            min-width: 650px;
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

        .empty {
            text-align: center;
            padding: 30px;
            color: #64748b;
        }

        .pagination {
            margin-top: 20px;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/rentrack-admin-legacy.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rentrack-legacy-premium.css') }}">
</head>
<body class="rt-admin-legacy rt-legacy-page">
@include('components.premium-legacy-nav')

@if(session('status'))
    <p style="color:green;">
        {{ session('status') }}
    </p>
@endif

@if($errors->any())
    <p style="color:red;">
        {{ $errors->first() }}
    </p>
@endif

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

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Date Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($requests as $item)
                    <tr>
                        <td>{{ $item->registration_id }}</td>

                        <td>{{ $item->email }}</td>

                        <td>
                            <span class="status {{ strtolower($item->status) }}">
                                {{ $item->status }}
                            </span>
                        </td>

                        <td>
                            {{ $item->created_at?->format('M d, Y h:i A') }}
                        </td>

                        <td>
                            @if($item->status === 'Pending')
                                <div style="display:flex;gap:8px;">

                                    <form
                                        method="POST"
                                        action="{{ route('admin.registrations.approve', $item) }}"
                                        data-confirm-title="Approve registration"
                                        data-confirm-message="Approve this registration request and send an invitation?"
                                        data-confirm-button="Approve request"
                                    >
                                        @csrf
                                        <button
                                            type="submit"
                                            style="background:#0f766e;color:white;padding:8px;border:0;border-radius:6px;"
                                        >
                                            Approve
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.registrations.reject', $item) }}"
                                        data-confirm-title="Reject registration"
                                        data-confirm-message="Reject this pending registration request?"
                                        data-confirm-button="Reject request"
                                        data-confirm-danger="true"
                                    >
                                        @csrf
                                        <button
                                            type="submit"
                                            style="background:#dc2626;color:white;padding:8px;border:0;border-radius:6px;"
                                        >
                                            Reject
                                        </button>
                                    </form>

                                </div>
                            @else
                                <span>No available actions</span>
                            @endif
                        </td>

                    </tr>

                @empty
                    <tr>
                        <td colspan="5" class="empty">
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
