
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RentTrack Dashboard</title>
</head>
<body style="font-family:Arial;padding:40px;">

    <h1>RentTrack</h1>

    <h2>{{ ucfirst($account->role) }} Dashboard</h2>

    <p>Welcome, {{ $account->username }}!</p>

    <p>Your account has been authenticated successfully.</p>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit">Logout</button>
    </form>

</body>
</html>
