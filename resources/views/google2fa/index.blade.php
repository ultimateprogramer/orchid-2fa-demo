<!DOCTYPE html>
<html lang="en">
<head>
    <title>Bootstrap Theme Simply Me</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

    <style>
        .bg-1 {
            background-color: #1abc9c; /* Green */
            color: #ffffff;
        }
        .bg-2 {
            background-color: #474e5d; /* Dark Blue */
            color: #ffffff;
        }
        .bg-3 {
            background-color: #ffffff; /* White */
            color: #555555;
        }
    </style>
</head>
<body>

<div class="container-fluid bg-1 text-center">
    <h3>Authenticate with Google 2FA</h3>
@if ($auth_kind == "register")
    <div><img src="{{ $qrcode_url }}"></div>
@endif
</div>
<div class="container-fluid bg-2 text-center" style="padding:20px;">
    <form action="/google2fa/authenticate" method="POST" class="form-inline">
        <div class="form-group mb-2">
            <input id="auth_kind" name="auth_kind" type="hidden" value="{{ $auth_kind }}"></input>
            <label for="one_time_password" style="padding:10px;">2FA Authentication Code</label>
            <input id="one_time_password" name="one_time_password" type="text" class="form-control" style="padding:10px;"></input>
        </div>
        <div class="form-group mb-2" style="padding:10px;">
            <button type="submit" class="btn btn-primary">Authenticate</button>
        </div>
    </form>
</div>

</body>
</html>