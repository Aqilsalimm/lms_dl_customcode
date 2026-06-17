<!DOCTYPE html>
<html>
<head>
    <title>Authenticating...</title>
</head>
<body>
    <script>
        const authData = @json($authData);
        if (window.opener) {
            window.opener.postMessage({
                type: 'google-auth-success',
                ...authData
            }, window.location.origin);
            window.close();
        } else {
            window.location.href = authData.redirect_url || '/';
        }
    </script>
</body>
</html>
